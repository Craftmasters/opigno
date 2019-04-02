<?php

namespace Drupal\opigno_tincan_activity;

use Drupal\Core\Database\Connection;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\opigno_module\Entity\OpignoActivityInterface;
use Drupal\opigno_tincan_api\OpignoTincanApiTinCanVerbs;
use TinCan\Activity;
use TinCan\RemoteLRS;
use TinCan\Statement;
use TinCan\Util;
use TinCan\Verb;

/**
 * Class TincanContentAnswerAssistant.
 */
class TincanContentAnswerAssistant {

  protected $connection;

  /**
   * Constructs a new TincanContentAnswerAssistant object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Database connection.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * This method get the registration UUID.
   *
   * It gets it from the database or from the PHPSESSION variable.
   * If it is from the PHPSESSION, the method will save this registration to the
   *   database.
   *
   * @param \Drupal\opigno_module\Entity\OpignoActivityInterface $activity
   *   Activity.
   * @param \Drupal\Core\Session\AccountProxyInterface $user
   *   Account proxy interface.
   *
   * @return bool|string
   *   The registration UUID if success, FALSE if not found.
   */
  public function getRegistration(OpignoActivityInterface $activity, AccountProxyInterface $user) {
    // If we have the RID, try to get the registration from the DB.
    if (!empty($activity) && !empty($user)) {
      $connection = $this->connection;
      $result = $connection->select('opigno_tincan_activity_answers', 't')
        ->fields('t', [])
        ->condition('opigno_activity_id', $activity->id())
        ->condition('uid', $user->id())
        ->execute()
        ->fetchObject();

      // If we have a result, we can return the registration.
      if ($result) {
        return $result->registration;
      }
      else {
        // Create new registration uuid.
        $registration = Util::getUUID();
        $this->saveRegistration($registration, $activity, $user);
        return $registration;
      }
    }

    // If we don't find the registration, return FALSE.
    return FALSE;
  }

  /**
   * This method will save the given registration UUID to the database.
   *
   * @param string $registration
   *   The UUID to save.
   * @param \Drupal\opigno_module\Entity\OpignoActivityInterface $activity
   *   Activity object.
   * @param \Drupal\Core\Session\AccountProxyInterface $user
   *   User object.
   *
   * @return null|mixed
   *   Exception array.
   */
  public function saveRegistration($registration, OpignoActivityInterface $activity, AccountProxyInterface $user) {
    $connection = $this->connection;

    try {
      $connection->insert('opigno_tincan_activity_answers')
        ->fields([
          'uid' => $user->id(),
          'opigno_activity_id' => $activity->id(),
          'registration' => $registration,
        ])
        ->execute();
    }
    catch (\Exception $e) {
      return $e;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function score($activity_id, $registration, $max_score) {
    return $this->getScoreFromLrs($activity_id, $registration, $max_score);
  }

  /***************
   *
   * PROTECTED METHODS
   *
   **************************/

  /**
   * This method will return the score from the LRS system for this response.
   *
   * @param string $activity_id
   *   Tincan activity id.
   * @param string $registration
   *   UUID registration.
   * @param int $max_score
   *   Max score for activity.
   *
   * @return int
   *   The score not weighted.
   */
  protected function getScoreFromLrs($activity_id, $registration, $max_score) {
    $messenger = \Drupal::messenger();
    // First, try to get the connection.
    $lrs = $this->getLrsConnection();
    if (!$lrs) {
      $messenger->addWarning($this->t('Connection to the LRS failed'));
      return 0;
    }

    // If we have the connection, get the statement.
    if (empty($registration)) {
      $messenger->addWarning($this->t('There was an error while answering the question, please go back and try again.'));
      return 0;
    }

    if (empty($activity_id)) {
      $messenger->addWarning($this->t('Error while obtaining the activity ID. Maybe a malformed TinCan package.'));
      return 0;
    }

    $score_statement = $this->getStatementFinalScore($lrs, $registration, $activity_id);
    if (!$score_statement) {
      $messenger->addWarning($this->t('Can not get score from LRS. Check your LRS settings.'));
      return 0;
    }

    // If we have the statement, extract the score and returns it.
    return $this->getScoreFromStatement($score_statement, $max_score);
  }

  /**
   * This method returns the connection to the LRS.
   *
   * If there is a problem, this method will show an error message and
   *   return FALSE.
   *
   * @return bool|\TinCan\RemoteLRS
   *   FALSE in case of error. The LRS connection if connection was success.
   */
  protected function getLrsConnection() {
    // Check first if the TinCanPHP library is installed
    // If not, return FALSE.
    $messenger = \Drupal::messenger();
    if (!class_exists('TinCan\\Version')) {
      $messenger->addError($this->t(
        'Please install the @tincanphp_library using Composer, with the command: <em>composer require rusticisoftware/tincan:@stable</em>.',
        ['@tincanphp_library' => Link::fromTextAndUrl($this->t('TinCanPHP library'), Url::fromUri('https://github.com/RusticiSoftware/TinCanPHP'))]
      ));
      return FALSE;
    }

    $config = \Drupal::config('opigno_tincan_api.settings');
    $endpoint = $config->get('opigno_tincan_api_endpoint');
    $username = $config->get('opigno_tincan_api_username');
    $password = $config->get('opigno_tincan_api_password');

    if (empty($endpoint) || empty($username) || empty($password)) {
      $messenger->addWarning($this->t('Please configure first the Opigno TinCan API module. Go to @url',
        [
          '@url' => Link::createFromRoute($this->t('the setting page'), 'opigno_tincan_api.settings_form')
            ->toString(),
        ]
      ));
      return FALSE;
    }

    return new RemoteLRS(
      $endpoint,
      '1.0.1',
      $username,
      $password
    );
  }

  /**
   * Get the statement containing the final score.
   *
   * @param \TinCan\RemoteLRS $lrs
   *   The LRS connection.
   * @param string $registration_uuid
   *   The registration UUID.
   * @param string $activity_id
   *   The activity ID of the statement.
   *
   * @return bool|\TinCan\Statement
   *   The statement. If not found, returns FALSE.
   */
  protected function getStatementFinalScore(RemoteLRS $lrs, $registration_uuid, $activity_id) {
    $activity = new Activity();
    $activity->setId($activity_id);

    $verb_passed = new Verb();
    $verb_passed->setId(OpignoTincanApiTinCanVerbs::$passed['id']);

    // Test with verb "passed".
    $result = $lrs->queryStatements([
      'activity' => $activity,
      'registration' => $registration_uuid,
      'verb' => $verb_passed,
      'limit' => 1,
    ]);
    $statements = $result->content->getStatements();

    // If nothing with "passed", test with "failed" verb.
    if (count($statements) === 0) {
      $verb_failed = new Verb();
      $verb_failed->setId(OpignoTincanApiTinCanVerbs::$failed['id']);

      $result = $lrs->queryStatements([
        'activity' => $activity,
        'registration' => $registration_uuid,
        'verb' => $verb_failed,
        'limit' => 1,
      ]);

      $statements = $result->content->getStatements();
    }

    if (count($statements) > 0) {
      return $statements[0];
    }
    else {
      return FALSE;
    }
  }

  /**
   * This method calculate the score using a statement.
   *
   * @param \TinCan\Statement $statement
   *   The statement that contains the score.
   * @param int $max_score
   *   Max score.
   *
   * @return float|int
   *   The final score ready to be returned for the Quiz module. Can returns 0
   *   if the score is not found in the statement.
   */
  protected function getScoreFromStatement(Statement $statement, $max_score) {
    $result = $statement->getResult();
    if (!isset($result)) {
      return 0;
    }

    $score = $result->getScore();
    if (isset($score)) {
      $scaled = $score->getScaled();
      if (isset($scaled) && $scaled >= 0) {
        return $scaled * $max_score;
      }

      $raw = $score->getRaw();
      $max = $score->getMax();
      $min = $score->getMin();
      if (!isset($min)) {
        $min = 0;
      }

      if (isset($raw) && isset($max)) {
        return ((float) ($raw - $min) / ($max - $min)) * $max_score;
      }
    }

    $success = $result->getSuccess();
    if (isset($success)) {
      return $max_score;
    }

    return 0;
  }

}
