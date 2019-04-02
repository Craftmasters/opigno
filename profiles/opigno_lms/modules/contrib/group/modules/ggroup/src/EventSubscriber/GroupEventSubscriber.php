<?php

namespace Drupal\ggroup\EventSubscriber;

use Drupal\ggroup\GroupHierarchyManager;
use Drupal\group\Event\GroupEvents;
use Drupal\group\Event\GroupPermissionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Inherit a permission from a subgroup or supergroup.
 */
class GroupEventSubscriber implements EventSubscriberInterface {

  /**
   * The group hierarchy manager.
   *
   * @var \Drupal\ggroup\GroupHierarchyManager
   */
  protected $hierarchyManager;

  /**
   * Static cache of group permissions for a user.
   *
   * This nested array is keyed by user ID, group ID and permission.
   *
   * @var bool[][][]
   */
  protected $groupPermissions;

  /**
   * Constructs a GroupEventSubscriber object.
   *
   * @param \Drupal\ggroup\GroupHierarchyManager $hierarchy_manager
   *   The entity type manager.
   */
  public function __construct(GroupHierarchyManager $hierarchy_manager) {
    $this->hierarchyManager = $hierarchy_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[GroupEvents::PERMISSION][] = ['inheritGroupPermission'];
    return $events;
  }

  /**
   * Inherit a permission from a subgroup or supergroup.
   *
   * @param \Drupal\group\Event\GroupPermissionEvent $event
   *   The subscribed event.
   */
  public function inheritGroupPermission(GroupPermissionEvent $event) {
    $group = $event->getGroup();
    $account = $event->getAccount();
    $permission = $event->getPermission();

    if (isset($this->groupPermissions[$group->id()][$account->id()][$permission])) {
      $event->setPermission($this->groupPermissions[$group->id()][$account->id()][$permission]);
      return;
    }

    $group_roles = $this->hierarchyManager->getInheritedGroupRoleIdsByUser($group, $account);

    // Check each inherited role for the requested permission.
    $this->groupPermissions[$group->id()][$account->id()][$permission] = FALSE;
    foreach ($group_roles as $group_role) {
      if ($group_role->hasPermission($event->getPermission())) {
        $this->groupPermissions[$group->id()][$account->id()][$permission] = TRUE;
        $event->setPermission(TRUE);
        return;
      }
    }
  }

}
