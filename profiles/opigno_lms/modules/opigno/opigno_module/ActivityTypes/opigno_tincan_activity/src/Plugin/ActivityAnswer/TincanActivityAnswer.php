<?php

namespace Drupal\opigno_tincan_activity\Plugin\ActivityAnswer;

use Drupal\opigno_module\ActivityAnswerPluginBase;
use Drupal\opigno_module\Entity\OpignoActivityInterface;
use Drupal\opigno_module\Entity\OpignoAnswerInterface;

/**
 * Class TincanActivityAnswer.
 *
 * @ActivityAnswer(
 *   id="opigno_tincan",
 * )
 */
class TincanActivityAnswer extends ActivityAnswerPluginBase {

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
    $tincan_content_service = \Drupal::service('opigno_tincan_activity.tincan');
    $tincan_assistant = \Drupal::service('opigno_tincan_activity.answer_assistant');
    $activity = $answer->getActivity();
    $tincan_file = $activity->get('opigno_tincan_package')->entity;
    $file_properties = $tincan_content_service->tincanLoadByFileEntity($tincan_file);
    $tincan_activity_id = $file_properties->activity_id;
    $registration = $tincan_assistant->getRegistration($activity, $user);

    $score_query = $db_connection->select('opigno_module_relationship', 'omr')
      ->fields('omr', ['max_score'])
      ->condition('omr.parent_id', $answer->getModule()->id())
      ->condition('omr.parent_vid', $answer->getModule()->getRevisionId())
      ->condition('omr.child_id', $activity->id())
      ->condition('omr.child_vid', $activity->getRevisionId());
    $score_result = $score_query->execute()->fetchObject();

    if ($score_result) {
      $max_score = $score_result->max_score;
      $score = $tincan_assistant->score($tincan_activity_id, $registration, $max_score);
    }

    return $score;
  }

  /**
   * {@inheritdoc}
   */
  public function getAnswerResultItemHeaders() {
    return [
      $this->t('Your answer'),
      $this->t('Score'),
    ];
  }

  /**
   * Returns answer result item data.
   */
  public function getAnswerResultItemData(OpignoAnswerInterface $answer) {

  }

}
