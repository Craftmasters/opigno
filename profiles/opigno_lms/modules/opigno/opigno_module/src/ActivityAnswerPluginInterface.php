<?php

namespace Drupal\opigno_module;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\opigno_module\Entity\OpignoActivityInterface;
use Drupal\opigno_module\Entity\OpignoAnswerInterface;

/**
 * Interface ActivityAnswerPluginInterface.
 */
interface ActivityAnswerPluginInterface extends PluginInspectionInterface {

  /**
   * Get plugin id.
   */
  public function getId();

  /**
   * Get plugin activity type.
   */
  public function getActivityType();

  /**
   * Indicates if answer should me evaluated on save or not.
   */
  public function evaluatedOnSave(OpignoActivityInterface $activity);

  /**
   * Score logic for specified activity.
   */
  public function getScore(OpignoAnswerInterface $answer);

  /**
   * Modify answering form.
   */
  public function answeringForm(array &$form);

}
