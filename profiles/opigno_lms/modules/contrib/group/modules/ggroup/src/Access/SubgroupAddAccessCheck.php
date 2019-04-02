<?php

namespace Drupal\ggroup\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access to for subgroup add forms.
 */
class SubgroupAddAccessCheck implements AccessInterface {

  /**
   * Checks access to the subgroup creation wizard.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to create the subgroup in.
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The type of subgroup to create in the group.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $account, GroupInterface $group, GroupTypeInterface $group_type) {
    $needs_access = $route->getRequirement('_subgroup_add_access') === 'TRUE';

    // We can only get the group content type ID if the plugin is installed.
    $plugin_id = 'subgroup:' . $group_type->id();
    if (!$group->getGroupType()->hasContentPlugin($plugin_id)) {
      return AccessResult::neutral();
    }

    // Determine whether the user can create groups of the provided type.
    $access = $group->hasPermission('create subgroup:' . $group_type->id() . ' content', $account);

    // Only allow access if the user can create subgroups of the provided type
    // or if he doesn't need access to do so.
    return AccessResult::allowedIf($access xor !$needs_access);
  }

}
