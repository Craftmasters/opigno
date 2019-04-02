<?php

namespace Drupal\opigno_scorm_activity\Plugin\ActivityAnswer;

use Drupal\opigno_module\ActivityAnswerPluginBase;
use Drupal\opigno_module\Entity\OpignoActivityInterface;
use Drupal\opigno_module\Entity\OpignoAnswerInterface;

/**
 * Class ScormActivityAnswer.
 *
 * @ActivityAnswer(
 *   id="opigno_scorm",
 * )
 */
class ScormActivityAnswer extends ActivityAnswerPluginBase {

  /**
   * {@inheritdoc}
   */
  public function evaluatedOnSave(OpignoActivityInterface $activity) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getScore(OpignoAnswerInterface $answer) {
    $db_connection = \Drupal::service('database');
    $score = 0;
    $user = \Drupal::currentUser();
    $activity = $answer->getActivity();
    $scorm_controller = \Drupal::service('opigno_scorm.scorm');
    $scorm_file = $activity->get('opigno_scorm_package')->entity;
    $scorm = $scorm_controller->scormLoadByFileEntity($scorm_file);

    // Get SCORM API version.
    $metadata = unserialize($scorm->metadata);
    if (strpos($metadata['schemaversion'], '1.2') !== FALSE) {
      $scorm_version = '1.2';
      $completion_key = 'cmi.core.lesson_status';
      $raw_key = 'cmi.core.score.raw';
    }
    else {
      $scorm_version = '2004';
      $completion_key = 'cmi.completion_status';
      $raw_key = 'cmi.score.raw';
    }

    // We get the latest result.
    // The way the SCORM API works always overwrites attempts
    // for the global CMI storage.
    // The result stored is always the latest.
    // Get it, and presist it again in the user results table
    // so we can track results through time.
    $scaled = opigno_scorm_scorm_cmi_get($user->id(), $scorm->id, 'cmi.score.scaled', '');

    $completion = opigno_scorm_scorm_cmi_get($user->id(), $scorm->id, $completion_key, '');
    $raw = opigno_scorm_scorm_cmi_get($user->id(), $scorm->id, $raw_key, '');
    if ($scorm_version == '1.2' && !empty($raw)) {
      $scaled = $raw / 100;
    }

    if (empty($completion)) {
      $scaled = 0;
    }
    if (($completion == "completed") && (empty($raw)) && (!is_numeric($raw))) {
      $scaled = 1;
    }
    if (($completion == "incomplete") && (empty($raw)) && (!is_numeric($raw))) {
      $scaled = 0;
    }

    // Something went wrong. Set a score of -1.
    if (!isset($scaled) || !is_numeric($scaled)) {
      $scaled = -1;
    }
    $score_query = $db_connection->select('opigno_module_relationship', 'omr')
      ->fields('omr', ['max_score'])
      ->condition('omr.parent_id', $answer->getModule()->id())
      ->condition('omr.parent_vid', $answer->getModule()->getRevisionId())
      ->condition('omr.child_id', $activity->id())
      ->condition('omr.child_vid', $activity->getRevisionId());
    $score_result = $score_query->execute()->fetchObject();
    if ($score_result) {
      $score = $score_result->max_score * $scaled;
    }
    return $score;
  }

  /**
   * {@inheritdoc}
   */
  public function getAnswerResultItemHeaders() {
    return [
      $this->t('Your answer'),
      $this->t('Choice'),
      $this->t('Correct?') . '  ',
      $this->t('Score'),
      $this->t('Correct answer'),
    ];
  }

  /**
   * Returns answer result data.
   */
  public function getAnswerResultItemData(OpignoAnswerInterface $answer) {
    $db_connection = \Drupal::service('database');
    $interactions = $db_connection->select('opigno_scorm_user_answer_results', 'osur')
      ->fields('osur')
      ->condition('answer_id', $answer->id())
      ->condition('answer_vid', $answer->getLoadedRevisionId())
      ->orderBy('timestamp', 'ASC')
      ->execute()->fetchAll();
    if ($interactions) {
      return $interactions;
    }
    return FALSE;
  }

}
