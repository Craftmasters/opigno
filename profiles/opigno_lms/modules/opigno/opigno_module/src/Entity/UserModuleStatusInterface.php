<?php

namespace Drupal\opigno_module\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining User module status entities.
 *
 * @ingroup opigno_module
 */
interface UserModuleStatusInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the User module status name.
   *
   * @return string
   *   Name of the User module status.
   */
  public function getName();

  /**
   * Sets the User module status name.
   *
   * @param string $name
   *   The User module status name.
   *
   * @return \Drupal\opigno_module\Entity\UserModuleStatusInterface
   *   The called User module status entity.
   */
  public function setName($name);

  /**
   * Gets the User module status creation timestamp.
   *
   * @return int
   *   Creation timestamp of the User module status.
   */
  public function getCreatedTime();

  /**
   * Sets the User module status creation timestamp.
   *
   * @param int $timestamp
   *   The User module status creation timestamp.
   *
   * @return \Drupal\opigno_module\Entity\UserModuleStatusInterface
   *   The called User module status entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the User module status published status indicator.
   *
   * Unpublished User module status are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the User module status is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a User module status.
   *
   * @param bool $published
   *   TRUE to set this User module status to published,
   *   FALSE to set it to unpublished.
   *
   * @return \Drupal\opigno_module\Entity\UserModuleStatusInterface
   *   The called User module status entity.
   */
  public function setPublished($published);

  /**
   * Calculates module best score.
   *
   * @return int
   *   Score in percent.
   */
  public function calculateBestScore();

}
