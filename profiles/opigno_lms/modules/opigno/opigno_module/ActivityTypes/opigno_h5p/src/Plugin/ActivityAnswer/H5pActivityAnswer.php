<?php

namespace Drupal\opigno_h5p\Plugin\ActivityAnswer;

use Drupal\opigno_module\ActivityAnswerPluginBase;
use Drupal\opigno_module\Entity\OpignoActivityInterface;
use Drupal\opigno_module\Entity\OpignoAnswerInterface;

/**
 * Class H5pActivityAnswer.
 *
 * @ActivityAnswer(
 *   id="opigno_h5p",
 * )
 */
class H5pActivityAnswer extends ActivityAnswerPluginBase {

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
    $activity = $answer->getActivity();
    $score_query = $db_connection->select('opigno_module_relationship', 'omr')
      ->fields('omr', ['max_score'])
      ->condition('omr.parent_id', $answer->getModule()->id())
      ->condition('omr.parent_vid', $answer->getModule()->getRevisionId())
      ->condition('omr.child_id', $activity->id())
      ->condition('omr.child_vid', $activity->getRevisionId());
    $score_result = $score_query->execute()->fetchObject();
    if ($score_result) {
      $max_score = $score_result->max_score;
      $h5p_score = $answer->get('score')->value;
      if ($h5p_score == 0) {
        $score = 0;
      }
      else {
        $percent_score = ($h5p_score / 1.234) - 32.17;
        $score = round($percent_score * $max_score);
      };
    }
    return $score;
  }

  /**
   * {@inheritdoc}
   */
  public function answeringForm(array &$form) {
    $form['score'] = [
      '#type' => 'hidden',
      '#default_value' => 0,
      '#attributes' => [
        'id' => 'activity-h5p-result',
      ],
    ];

    $form['correct-response'] = [
      '#type' => 'hidden',
      '#default_value' => 0,
      '#attributes' => [
        'id' => 'activity-h5p-correct-response',
      ],
    ];

    $form['response'] = [
      '#type' => 'hidden',
      '#default_value' => 0,
      '#attributes' => [
        'id' => 'activity-h5p-response',
      ],
    ];

    $form['xapi_data'] = [
      '#type' => 'hidden',
      '#default_value' => 0,
      '#attributes' => [
        'id' => 'activity-h5p-xapi-data',
      ],
    ];

    $form['#attached']['library'][] = 'opigno_h5p/opigno_h5p.main';
  }

  /**
   * {@inheritdoc}
   */
  public function getAnswerResultItemHeaders() {
    return [
      $this->t('Your answer'),
      $this->t('Correct answer'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getAnswerResultItemData(OpignoAnswerInterface $answer) {
    $data = [];

    $response = $answer->get('field_response')->getValue();
    $correct_response = $answer->get('field_correct_response')->getValue();

    if ($response && $correct_response) {
      $data[] = [
        $response[0]['value'],
        $correct_response[0]['value'],
      ];
    }

    return $data;
  }

}
