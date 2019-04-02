<?php

namespace Drupal\group\Event;

use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Allow event listeners to alter the authmap data that will get stored.
 */
class GroupPermissionEvent extends Event {

  /**
   * The permission to check.
   *
   * @var string
   */
  protected $permission;

  /**
   * The group for which to check the permission.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * The account for which to check the permission.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Whether the account should have the permission in the group.
   *
   * @var bool
   */
  protected $hasPermission = FALSE;

  /**
   * Constructs a group permission event object.
   *
   * @param string $permission
   *   The permission to check.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group for which to check the permission.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account for which to check the permission.
   */
  public function __construct($permission, GroupInterface $group, AccountInterface $account) {
    $this->permission = $permission;
    $this->group = $group;
    $this->account = $account;
  }

  /**
   * Gets the permission to check.
   *
   * @return string
   *   The name of the permission to check.
   */
  public function getPermission() {
    return $this->permission;
  }

  /**
   * Get the group for which to check the permission.
   *
   * @return \Drupal\group\Entity\GroupInterface
   *   The group for which to check the permission.
   */
  public function getGroup() {
    return $this->group;
  }

  /**
   * Get the account for which to check the permission.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The account for which to check the permission.
   */
  public function getAccount() {
    return $this->account;
  }

  /**
   * Returns whether the user has the permission on the group.
   *
   * @return bool
   *   Whether the user has the permission on the group.
   */
  public function hasPermission() {
    return $this->hasPermission;
  }

  /**
   * Sets that the user should have the permission.
   *
   * @param bool $has_permission
   *   Whether the user should have the permission.
   */
  public function setPermission($has_permission) {
    $this->hasPermission = $has_permission;
  }

}
