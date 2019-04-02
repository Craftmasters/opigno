<?php

namespace Drupal\opigno_module\Plugin\ActivityAnswer;

use Drupal\opigno_module\ActivityAnswerPluginBase;
use Drupal\opigno_module\Entity\OpignoActivityInterface;
use Drupal\opigno_module\Entity\OpignoAnswerInterface;

/**
 * Class TrueFalseActivityAnswer.
 *
 * @ActivityAnswer(
 *   id="opigno_true_false",
 * )
 */
class TrueFalseActivityAnswer extends ActivityAnswerPluginBase {

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
    /* @var $db_connection \Drupal\Core\Database\Connection */
    $db_connection = \Drupal::service('database');
    $score = 0;
    /** @var \Drupal\opigno_module\Entity\OpignoActivityInterface $activity */
    /** @var \Drupal\opigno_module\Entity\OpignoAnswer $answer */
    $activity = $answer->getActivity();
    if ($answer->hasField('field_true_false')
      && $activity->hasField('field_true_false')
      && $answer->get('field_true_false')->value == $activity->get('field_true_false')->value) {
      /** @var \Drupal\opigno_module\Entity\OpignoModuleInterface $module */
      $module = $answer->getModule();
      $score_query = $db_connection->select('opigno_module_relationship', 'omr')
        ->fields('omr', ['max_score'])
        ->condition('omr.parent_id', $module->id())
        ->condition('omr.parent_vid', $module->getRevisionId())
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
    return [
      $this->t('Your answer'),
      $this->t('Choice'),
      $this->t('Correct?'),
    ];
  }

  /**
   * Returns answer result data.
   */
  public function getAnswerResultItemData(OpignoAnswerInterface $answer) {
    $data = [];
    /** @var \Drupal\opigno_module\Entity\OpignoAnswer $answer */
    $user_answer = $answer->hasField('field_true_false')
      ? $answer->get('field_true_false')->value
      : NULL;
    /** @var \Drupal\opigno_module\Entity\OpignoActivityInterface $activity */
    $activity = $answer->getActivity();
    $correct_answer = $activity->hasField('field_true_false')
      ? $activity->get('field_true_false')->value
      : NULL;

    $data[] = [
      'answer' => $user_answer !== NULL && $user_answer == 1 ? '->' : '',
      'choice' => $this->t('True'),
      'correct' => $correct_answer !== NULL && $correct_answer == 1 ? '+' : '',
    ];

    $data[] = [
      'answer' => $user_answer !== NULL && $user_answer == 0 ? '->' : '',
      'choice' => t('False'),
      'correct' => $correct_answer !== NULL && $correct_answer == 0 ? '+' : '',
    ];

    return $data;
  }

}
