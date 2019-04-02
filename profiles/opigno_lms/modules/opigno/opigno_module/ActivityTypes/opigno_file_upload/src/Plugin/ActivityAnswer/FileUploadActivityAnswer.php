<?php

namespace Drupal\opigno_file_upload\Plugin\ActivityAnswer;

use Drupal\opigno_module\ActivityAnswerPluginBase;
use Drupal\opigno_module\Entity\OpignoActivityInterface;
use Drupal\opigno_module\Entity\OpignoAnswerInterface;

/**
 * Class FileUploadActivityAnswer.
 *
 * @ActivityAnswer(
 *   id="opigno_file_upload",
 * )
 */
class FileUploadActivityAnswer extends ActivityAnswerPluginBase {

  /**
   * {@inheritdoc}
   */
  public function evaluatedOnSave(OpignoActivityInterface $activity) {
    // Check evaluation method field.
    $method = $activity->get('opigno_evaluation_method')->value;
    if ($method == 0) {
      // Automatic evaluation.
      return TRUE;
    }
    else {
      // Manual evaluation.
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getScore(OpignoAnswerInterface $answer) {
    /* @var $db_connection \Drupal\Core\Database\Connection */
    $db_connection = \Drupal::service('database');
    $score = 0;
    $activity = $answer->getActivity();
    $method = $activity->get('opigno_evaluation_method')->value;
    if ($method == 0) {
      // Automatic evaluation and score.
      $score_query = $db_connection->select('opigno_module_relationship', 'omr')
        ->fields('omr', ['max_score'])
        ->condition('omr.parent_id', $answer->getModule()->id())
        ->condition('omr.parent_vid', $answer->getModule()->getRevisionId())
        ->condition('omr.child_id', $activity->id())
        ->condition('omr.child_vid', $activity->getRevisionId());
      $score_result = $score_query->execute()->fetchObject();
      if ($score_result) {
        $score = $score_result->max_score;
      }
    }
    return $score;
  }

  /**
   * {@inheritdoc}
   */
  public function getAnswerResultItemHeaders(OpignoAnswerInterface $answer) {
    $headings = [$this->t('Your answer')];
    if (!$answer->isEvaluated()) {
      $headings[] = $this->t('Result');
    }
    return $headings;
  }

  /**
   * Returns answer result data.
   */
  public function getAnswerResultItemData(OpignoAnswerInterface $answer) {
    $data = [];
    /** @var \Drupal\file\Entity\File $uploaded_file */
    $uploaded_file = $answer->get('opigno_file')->entity;

    if ($uploaded_file !== NULL) {
      $file_link = [
        '#theme' => 'file_link',
        '#file' => $uploaded_file,
      ];
      $data['item'][] = \Drupal::service('renderer')->render($file_link);
    }

    if (!$answer->isEvaluated()) {
      $data['item'][] = $this->t('This answer has not yet been scored.');
    }

    return $data;
  }

}
