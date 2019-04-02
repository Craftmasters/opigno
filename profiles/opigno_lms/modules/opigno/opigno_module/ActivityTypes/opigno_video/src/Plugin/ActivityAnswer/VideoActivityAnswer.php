<?php

namespace Drupal\opigno_video\Plugin\ActivityAnswer;

use Drupal\opigno_module\ActivityAnswerPluginBase;
use Drupal\opigno_module\Entity\OpignoActivityInterface;
use Drupal\opigno_module\Entity\OpignoAnswerInterface;

/**
 * Class VideoActivityAnswer.
 *
 * @ActivityAnswer(
 *   id="opigno_video",
 * )
 */
class VideoActivityAnswer extends ActivityAnswerPluginBase {

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
    return 10;
  }

  /**
   * {@inheritdoc}
   */
  public function getAnswerResultItemHeaders(OpignoAnswerInterface $answer) {
    return;
  }

  /**
   * {@inheritdoc}
   */
  public function getAnswerResultItemData(OpignoAnswerInterface $answer) {
    return;
  }

}
