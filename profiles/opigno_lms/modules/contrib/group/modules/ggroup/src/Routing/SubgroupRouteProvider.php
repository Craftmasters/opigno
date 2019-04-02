<?php

namespace Drupal\ggroup\Routing;

use Drupal\group\Entity\GroupType;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for subgroup content.
 */
class SubgroupRouteProvider {

  /**
   * Provides the shared collection route for subgroup plugins.
   */
  public function getRoutes() {
    $routes = $plugin_ids = $permissions_add = $permissions_create = [];

    foreach (GroupType::loadMultiple() as $name => $group_type) {
      $plugin_id = "subgroup:$name";

      $plugin_ids[] = $plugin_id;
      $permissions_add[] = "create $plugin_id content";
      $permissions_create[] = "create $plugin_id entity";
    }

    // If there are no group types yet, we cannot have any plugin IDs and should
    // therefore exit early because we cannot have any routes for them either.
    if (empty($plugin_ids)) {
      return $routes;
    }

    // @todo Conditionally (see above) alter GroupContent info to use this path.
    $routes['entity.group_content.subgroup_relate_page'] = new Route('group/{group}/subgroup/add');
    $routes['entity.group_content.subgroup_relate_page']
      ->setDefaults([
        '_title' => 'Relate subgroup',
        '_controller' => '\Drupal\ggroup\Controller\SubgroupController::addPage',
      ])
      ->setRequirement('_group_permission', implode('+', $permissions_add))
      ->setRequirement('_group_installed_content', implode('+', $plugin_ids))
      ->setOption('_group_operation_route', TRUE);

    // @todo Conditionally (see above) alter GroupContent info to use this path.
    $routes['entity.group_content.subgroup_add_page'] = new Route('group/{group}/subgroup/create');
    $routes['entity.group_content.subgroup_add_page']
      ->setDefaults([
        '_title' => 'Create subgroup',
        '_controller' => '\Drupal\ggroup\Controller\SubgroupWizardController::addPage',
        'create_mode' => TRUE,
      ])
      ->setRequirement('_group_permission', implode('+', $permissions_create))
      ->setRequirement('_group_installed_content', implode('+', $plugin_ids))
      ->setOption('_group_operation_route', TRUE);

    return $routes;
  }

}
