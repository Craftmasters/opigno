<?php

namespace Drupal\opigno_module\Entity;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Answer entities.
 *
 * @ingroup opigno_module
 */
interface OpignoAnswerInterface extends EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Answer type.
   *
   * @return string
   *   The Answer type.
   */
  public function getType();

  /**
   * Gets the Answer creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Answer.
   */
  public function getCreatedTime();

  /**
   * Sets the Answer creation timestamp.
   *
   * @param int $timestamp
   *   The Answer creation timestamp.
   *
   * @return \Drupal\opigno_module\Entity\OpignoAnswerInterface
   *   The called Answer entity.
   */
  public function setCreatedTime($timestamp);

}
