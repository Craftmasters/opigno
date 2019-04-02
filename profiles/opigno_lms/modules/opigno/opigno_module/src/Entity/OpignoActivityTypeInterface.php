<?php

namespace Drupal\opigno_module\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Activity type entities.
 */
interface OpignoActivityTypeInterface extends ConfigEntityInterface {

  /**
   * Gets the Activity type description.
   *
   * @return string
   *   Activity type description.
   */
  public function getDescription();

  /**
   * Sets the Activity type description.
   *
   * @param string $description
   *   The Activity type description.
   *
   * @return \Drupal\opigno_module\Entity\OpignoActivityType
   *   The called Activity type entity.
   */
  public function setDescription($description);

  /**
   * Gets the Activity type summary.
   *
   * @return string
   *   Activity type summary.
   */
  public function getSummary();

  /**
   * Sets the Activity type summary.
   *
   * @param string $summary
   *   The Activity type summary.
   *
   * @return \Drupal\opigno_module\Entity\OpignoActivityType
   *   The called Activity type entity.
   */
  public function setSummary($summary);

  /**
   * Gets the Activity type image id.
   *
   * @return string
   *   Activity type image id.
   */
  public function getImageId();

  /**
   * Gets the Activity type image entity.
   *
   * @return \Drupal\file\Entity\File
   *   Activity type image object.
   */
  public function getImage();

}
