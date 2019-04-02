<?php

namespace Drupal\opigno_tincan_api;

use DateInterval;
use DateTime;
use Drupal\Core\Language\Language;
use Drupal\Core\Url;
use Exception;
use TinCan\Activity;
use TinCan\Agent;
use TinCan\Context;
use TinCan\RemoteLRS;
use TinCan\Result;
use TinCan\Statement;
use TinCan\Verb;

/**
 * Class OpignoTinCanApiStatements.
 */
class OpignoTinCanApiStatements {

  /**
   * Set activity.
   *
   * @param \TinCan\Statement $statement
   *   The statement to add the object.
   * @param string $activity_definition_type
   *   An array containing the activity name and type (url).
   *   Use TinCanActivityDefinitions.
   * @param object $activity_object
   *   An object: Group or Module or Activity.
   *
   * @return bool|\TinCan\Statement
   *   Statement.
   *
   * @throws \Exception
   */
  public static function setObjectActivity(Statement &$statement, $activity_definition_type, $activity_object) {
    // opigno_tincan_api_set_object_activity.
    if (empty($activity_definition_type)) {
      throw new Exception('The definition type given is empty.');
    }

    if (empty($activity_object)) {
      \Drupal::logger('opigno_tincan_api')
        ->notice('The entity given is not loaded or is not a entity: <pre>' . print_r($activity_object, TRUE) . '</pre>', []);
      return FALSE;
    }

    $entity_type_id = $activity_object->getEntityTypeId();
    switch ($entity_type_id) {
      case 'group':
        $url = Url::fromRoute('entity.group.canonical',
          ['group' => $activity_object->id()],
          ['absolute' => TRUE])
          ->toString();
        $title = $activity_object->label();
        break;

      case 'opigno_module':
        $url = Url::fromRoute('entity.opigno_module.canonical',
          ['opigno_module' => $activity_object->id()],
          ['absolute' => TRUE])
          ->toString();
        $title = $activity_object->getName();
        break;

      case 'opigno_activity':
        $url = Url::fromRoute('entity.opigno_activity.canonical',
          ['opigno_activity' => $activity_object->id()],
          ['absolute' => TRUE])
          ->toString();
        $title = $activity_object->getName();
        break;

      case 'opigno_moxtra_meeting':
        $url = Url::fromRoute('entity.opigno_moxtra_meeting.canonical',
          ['opigno_moxtra_meeting' => $activity_object->id()],
          ['absolute' => TRUE])
          ->toString();
        $title = $activity_object->getTitle();
        break;

      case 'opigno_ilt':
        $url = Url::fromRoute('entity.opigno_ilt.canonical',
          ['opigno_ilt' => $activity_object->id()],
          ['absolute' => TRUE])
          ->toString();
        $title = $activity_object->getTitle();
        break;

      case 'opigno_certificate':
        $url = Url::fromRoute('entity.opigno_certificate.canonical',
          ['opigno_certificate' => $activity_object->id()],
          ['absolute' => TRUE])
          ->toString();
        $title = $activity_object->label();
        break;

    }

    $object = new Activity([
      'id' => $url,
      'definition' => [
        'name' => [
          'en-US' => $title,
        ],
        'type' => $activity_definition_type,
      ],
    ]);

    $statement->setObject($object);
    return $statement;
  }

  /**
   * Sets context language.
   *
   * @param \TinCan\Context $context
   *   The context to edit for the statement.
   * @param string $language
   *   The language to add.
   *
   * @return \TinCan\Context
   *   Context.
   */
  public static function contextSetLanguage(Context &$context, $language) {
    // _opigno_tincan_api_context_set_language.
    if (!empty($language) && $language != Language::LANGCODE_NOT_SPECIFIED) {
      $context->setLanguage($language);
    }
    return $context;
  }

  /**
   * Sets context parents.
   *
   * @param \TinCan\Context $context
   *   The context to add the parents.
   * @param array $group_ids
   *   The nodes IDs to add as parents.
   * @param null|string $definition_type
   *   Definition type.
   *
   * @return \TinCan\Context
   *   Context.
   */
  public static function contextSetParents(Context &$context, array $group_ids, $definition_type = NULL) {

    $parents = [];
    foreach ($group_ids as $group_id) {
      $parent = [];
      $options = ['absolute' => TRUE];
      $url = Url::fromRoute('entity.group.canonical', [
        'group' => $group_id,
      ], $options)->toString();
      $parent['id'] = $url;

      if (!empty($definition_type)) {
        $parent['definition'] = ['type' => $definition_type];
      }

      $parents[] = $parent;
    }

    if (!empty($parents)) {
      $context->getContextActivities()->setParent($parents);
    }
    return $context;
  }

  /**
   * Sets context grouping.
   */
  public static function contextSetGrouping(Context &$context, $group_ids, $definition_type = NULL) {
    // _opigno_tincan_api_context_set_grouping.
    $grouping = [];
    foreach ($group_ids as $group_id) {
      $statement_group = [];
      $url = Url::fromRoute('entity.group.canonical',
        ['group' => $group_id],
        ['absolute' => TRUE])
        ->toString();
      $statement_group['id'] = $url;

      if (!empty($definition_type)) {
        $statement_group['definition'] = ['type' => $definition_type];
      }
      $grouping[] = $statement_group;
    }
    if (!empty($statement_group)) {
      $context->getContextActivities()->setGrouping($statement_group);
    }
    return $context;
  }

  /**
   * Sets result.
   */
  public static function setResult(Statement &$statement,
  $user_score = NULL,
  $score_max = NULL,
  $score_min = NULL,
  $is_success = NULL,
  $response = NULL,
  $duration_s = NULL) {
    // _opigno_tincan_api_set_result.
    $result = new Result();

    if ($user_score !== NULL) {
      self::setScore($result, $user_score, $score_max, $score_min);
    }

    if ($is_success !== NULL) {
      $result->setSuccess($is_success);
    }

    $result->setCompletion(TRUE);

    if ($response !== NULL) {
      $result->setResponse($response);
    }

    if ($duration_s !== NULL) {
      $time_now = new DateTime();
      $time_more = new DateTime();

      $time_more->add(new DateInterval('PT' . (int) ($duration_s) . 'S'));
      $time = $time_now->diff($time_more);

      // Remove all the 0 in the formatted duration.
      $duration_string = $time->format('P%yY%mM%dDT%hH%iM%sS');

      $duration_string = preg_replace('/(\D)0{1}\D/i', '$1', $duration_string);
      if (strpos($duration_string, 'P0M') !== FALSE) {
        // Need twice same replacement for deleting 0M before T.
        $duration_string = preg_replace('/(\D)0{1}\D/i', '$1', $duration_string);
      };

      ($duration_string == 'PT' ? $duration_string = 'PT0S' : NULL);

      $result->setDuration($duration_string);
    }

    $statement->setResult($result);
    return $statement;
  }

  /**
   * Creates and sends statement.
   *
   * @param \TinCan\Statement $statement
   *   The statement to send.
   *
   * @return bool
   *   Response success flag.
   */
  public static function sendStatement(Statement $statement) {
    // _opigno_tincan_api_send_statement.
    // The variables 'opigno_tincan_api_*'
    // will be used to send the statement to the LRS.
    $config = \Drupal::config('opigno_tincan_api.settings');
    $endpoint = $config->get('opigno_tincan_api_endpoint');
    $username = $config->get('opigno_tincan_api_username');
    $password = $config->get('opigno_tincan_api_password');

    if (empty($endpoint) || empty($username) || empty($password)) {
      \Drupal::logger('opigno_tincan_api')
        ->notice('Tincan statements can not be send. LRS settings are not configured.');
      return FALSE;
    }

    $lrs = new RemoteLRS(
      $endpoint,
      '1.0.1',
      $username,
      $password
    );
    $response = $lrs->saveStatement($statement);

    if ($response->success === FALSE) {
      \Drupal::logger('Opigno Tincan API')
        ->error('The following statement could not be sent :<br /><pre>' . print_r($statement->asVersion('1.0.1'), TRUE) . '</pre><br/>', []);

      return FALSE;
    }

    return TRUE;
  }

  /**
   * Adds the current user as the actor.
   *
   * The verb selected in parameter, the object based on the
   * node given in parameter and the timestamp based on the current time.
   *
   * @param array $verb
   *   Verb.
   * @param string $activity_definition_type
   *   The activity definition name and ID. Use TinCanActivityDefinitionTypes.
   * @param object $activity_object
   *   Activity.
   * @param null|mixed $user
   *   User.
   *
   * @return \TinCan\Statement
   *   Statement.
   *
   * @throws \Exception
   */
  public static function statementBaseCreation(array $verb, $activity_definition_type, $activity_object, $user = NULL) {
    // _opigno_tincan_api_statement_base_creation.
    $statement = new Statement();

    self::setActor($statement, $user);
    self::setVerb($statement, $verb);
    self::setObjectActivity($statement, $activity_definition_type, $activity_object);

    $statement->stamp();

    return $statement;
  }

  /**
   * Add the current user as the actor of this statement.
   *
   * @param \TinCan\Statement $statement
   *   The statement to add the actor.
   * @param null|mixed $user
   *   User.
   *
   * @throws \Exception
   */
  public static function setActor(Statement &$statement, $user = NULL) {
    // _opigno_tincan_api_set_actor.
    if ($user === NULL) {
      $user = \Drupal::currentUser();
    }
    else {
      if (empty($user) || empty($user->getAccountName()) || empty($user->getEmail())) {
        throw new Exception('The user given was not loaded');
      }
    }

    $actor = new Agent([
      'name' => $user->getAccountName(),
      'mbox_sha1sum' => sha1('mailto:' . $user->getEmail()),
    ]);

    $statement->setActor($actor);
  }

  /**
   * Sets statement verb.
   *
   * @param \TinCan\Statement $statement
   *   The statement to add the verb.
   * @param array $verb
   *   An array containing the verb name and id (url). Use TinCanVerbs.
   *
   * @throws Exception
   *   Throw an Exception if $verb is not conform.
   */
  public static function setVerb(Statement &$statement, array $verb) {
    // opigno_tincan_api_set_verb.
    if (empty($verb) || empty($verb['name']) || empty($verb['id'])) {
      throw new Exception('The verb given does not exist');
    }

    $verb = new Verb([
      'id' => $verb['id'],
      'display' => [
        'en-US' => $verb['name'],
      ],
    ]);

    $statement->setVerb($verb);
  }

  /**
   * Sets score.
   */
  public static function setScore(Result &$result, $raw_score, $max_score = NULL, $min_score = NULL) {
    // _opigno_tincan_api_set_score.
    $score = [];

    if ($min_score === NULL) {
      $min_score = 0;
    }

    if ($max_score === NULL || ($max_score === 0 && $min_score === 0)) {
      $score['raw'] = $raw_score;
      $result->setScore($score);
      return FALSE;
    }

    if ($max_score <= $min_score) {
      // Max must be greater than min.
      $max_score = $min_score + 1;
    }

    $scaled_max = $max_score - $min_score;
    $scaled_raw = $raw_score - $min_score;

    $scaled = ($scaled_max > 0 ? round($scaled_raw / $scaled_max, 2) : 0);

    $result->setScore([
      'min' => $min_score,
      'max' => $max_score,
      'raw' => $raw_score,
      'scaled' => ($scaled < -1 ? -1 : ($scaled > 1 ? 1 : $scaled)),
      // Compris entre -1 et 1.
    ]);

    return TRUE;
  }

}
