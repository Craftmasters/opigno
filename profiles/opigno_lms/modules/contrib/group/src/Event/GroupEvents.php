<?php

namespace Drupal\group\Event;

/**
 * Defines events for the group module.
 *
 * @see \Drupal\group\Event\GroupPermissionEvent
 */
final class GroupEvents {

  /**
   * Name of the event fired when a group permission is checked for a user.
   *
   * This event allows modules to add a permission for a user to a group. The
   * event listener method receives a \Drupal\group\Event\GroupPermissionEvent
   * instance.
   *
   * @Event
   *
   * @see \Drupal\group\Event\GroupPermissionEvent
   *
   * @var string
   */
  const PERMISSION = 'group.permission';

}
