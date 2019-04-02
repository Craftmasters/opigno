<?php

namespace Drupal\opigno_long_answer\Plugin\ActivityAnswer;

use Drupal\opigno_module\ActivityAnswerPluginBase;
use Drupal\opigno_module\Entity\OpignoActivityInterface;
use Drupal\opigno_module\Entity\OpignoAnswerInterface;

/**
 * Class LongAnswerActivityAnswer.
 *
 * @ActivityAnswer(
 *   id="opigno_long_answer",
 * )
 */
class LongAnswerActivityAnswer extends ActivityAnswerPluginBase {

  /**
   * {@inheritdoc}
   */
  public function evaluatedOnSave(OpignoActivityInterface $activity) {
    // Answer must be evaluated manually.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getScore(OpignoAnswerInterface $answer) {
    return 0;
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
    $data['item'][] = strip_tags($answer->get('opigno_body')->getValue()[0]['value']);

    if (!$answer->isEvaluated()) {
      $data['item'][] = $this->t('This answer has not yet been scored.');
    }

    return $data;
  }

}
