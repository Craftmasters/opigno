<?php

namespace Drupal\opigno_learning_path;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a LatestActivity entity.
 *
 * @ingroup opigno_learning_path
 */
interface LatestActivityInterface extends ContentEntityInterface {

  /**
   * Gets the training ID.
   *
   * @return int
   *   The training ID.
   */
  public function getTrainingId();

  /**
   * Sets the training ID.
   *
   * @param int $id
   *   The training ID.
   *
   * @return \Drupal\opigno_learning_path\LatestActivityInterface
   *   The called entity.
   */
  public function setTrainingId($id);

  /**
   * Gets the training entity.
   *
   * @return \Drupal\group\Entity\GroupInterface
   *   The training entity.
   */
  public function getTraining();

  /**
   * Sets the training entity.
   *
   * @param \Drupal\group\Entity\GroupInterface $training
   *   The training entity.
   *
   * @return \Drupal\opigno_learning_path\LatestActivityInterface
   *   The called entity.
   */
  public function setTraining($training);

  /**
   * Gets the module ID.
   *
   * @return int
   *   The module ID.
   */
  public function getModuleId();

  /**
   * Sets the module ID.
   *
   * @param int $id
   *   The module ID.
   *
   * @return \Drupal\opigno_learning_path\LatestActivityInterface
   *   The called entity.
   */
  public function setModuleId($id);

  /**
   * Gets the module entity.
   *
   * @return \Drupal\opigno_module\Entity\OpignoModuleInterface
   *   The module entity.
   */
  public function getModule();

  /**
   * Sets the module entity.
   *
   * @param \Drupal\opigno_module\Entity\OpignoModuleInterface $module
   *   The module entity.
   *
   * @return \Drupal\opigno_learning_path\LatestActivityInterface
   *   The called entity.
   */
  public function setModule($module);

  /**
   * Gets the timestamp.
   *
   * @return int
   *   The timestamp.
   */
  public function getTimestamp();

  /**
   * Sets the timestamp.
   *
   * @param int $value
   *   The timestamp.
   *
   * @return \Drupal\opigno_learning_path\LatestActivityInterface
   *   The called entity.
   */
  public function setTimestamp($value);

}
