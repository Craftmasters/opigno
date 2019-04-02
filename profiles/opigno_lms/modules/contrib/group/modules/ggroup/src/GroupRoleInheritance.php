<?php

namespace Drupal\ggroup;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ggroup\Graph\GroupGraphStorageInterface;

/**
 * Provides all direct and indirect group relations and the inherited roles.
 */
class GroupRoleInheritance implements GroupRoleInheritanceInterface {

  /**
   * The group graph storage.
   *
   * @var \Drupal\ggroup\Graph\GroupGraphStorageInterface
   */
  protected $groupGraphStorage;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Static cache for the total role map.
   *
   * @var array[]
   */
  protected $roleMap = [];

  /**
   * Static cache for config of all installed subgroups.
   *
   * @var array[]
   */
  protected $subgroupConfig = [];

  /**
   * Static cache of all group content types for subgroup group content.
   *
   * This nested array is keyed by subgroup ID and group ID.
   *
   * @var string[][]
   */
  protected $subgroupRelations = [];

  /**
   * Constructs a new GroupHierarchyManager.
   *
   * @param \Drupal\ggroup\Graph\GroupGraphStorageInterface $group_graph_storage
   *   The group graph storage service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   */
  public function __construct(GroupGraphStorageInterface $group_graph_storage, EntityTypeManagerInterface $entity_type_manager, CacheBackendInterface $cache) {
    $this->groupGraphStorage = $group_graph_storage;
    $this->entityTypeManager = $entity_type_manager;
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllInheritedGroupRoleIds() {
    if (!empty($this->roleMap)) {
      return $this->roleMap;
    }

    $cache = $this->cache->get(GroupRoleInheritanceInterface::ROLE_MAP_CID);
    if ($cache && $cache->valid) {
      $this->roleMap = $cache->data;
      return $this->roleMap;
    }

    $this->roleMap = $this->build();
    $this->cache->set(GroupRoleInheritanceInterface::ROLE_MAP_CID, $this->roleMap);

    return $this->build();
  }

  /**
   * {@inheritdoc}
   */
  public function rebuild() {
    $this->cache->delete(GroupRoleInheritanceInterface::ROLE_MAP_CID);
    $this->roleMap = $this->build();
    $this->cache->set(GroupRoleInheritanceInterface::ROLE_MAP_CID, $this->roleMap);
  }

  /**
   * Build a nested array with all inherited roles for all group relations.
   *
   * @return array
   *   A nested array with all inherited roles for all direct/indirect group
   *   relations. The array is in the form of:
   *   $map[$group_a_id][$group_b_id][$group_b_role_id] = $group_a_role_id;
   */
  protected function build() {
    $role_map = [];
    $group_relations = array_reverse($this->groupGraphStorage->getGraph());
    foreach ($group_relations as $group_relation) {
      $group_id = $group_relation->start_vertex;
      $subgroup_id = $group_relation->end_vertex;
      $paths = $this->groupGraphStorage->getPath($group_id, $subgroup_id);

      foreach ($paths as $path) {
        $path_role_map = [];

        // Get all direct role mappings.
        foreach ($path as $key => $path_subgroup_id) {
          // We reached the end of the path, store mapped role IDs.
          if ($path_subgroup_id === $group_id) {
            break;
          }

          // Get the supergroup ID from the next element.
          $path_supergroup_id = isset($path[$key + 1]) ? $path[$key + 1] : NULL;

          if (!$path_supergroup_id) {
            continue;
          }

          // Get mapped roles for relation type. Filter array to remove
          // unmapped roles.
          $relation_config = $this->getSubgroupRelationConfig($path_supergroup_id, $path_subgroup_id);

          $path_role_map[$path_supergroup_id][$path_subgroup_id] = array_filter($relation_config['child_role_mapping']);
          $path_role_map[$path_subgroup_id][$path_supergroup_id] = array_filter($relation_config['parent_role_mapping']);
        }
        $role_map[] = $path_role_map;

        // Add all indirectly inherited subgroup roles (bottom up).
        $role_map[] = $this->mapIndirectPathRoles($path, $path_role_map);

        // Add all indirectly inherited group roles between groups.
        $role_map[] = $this->mapIndirectPathRoles(array_reverse($path), $path_role_map);
      }
    }

    return array_replace_recursive(...$role_map);
  }

  /**
   * Map all the indirectly inherited roles in a path between group A and B.
   *
   * Within a graph, getting the role inheritance for every direct relation is
   * relatively easy and cheap. There are also a lot of indirectly inherited
   * roles in a path between 2 groups though. When there is a relation between
   * groups like '1 => 20 => 300 => 4000', this method calculates the role
   * inheritance for every indirect relationship in the path:
   * 1 => 300
   * 1 => 4000
   * 20 => 4000
   *
   * @param array $path
   *   An array containing all group IDs in a path between group A and B.
   * @param array $path_role_map
   *   A nested array containing all directly inherited roles for the path
   *   between group A and B.
   *
   * @return array
   *   A nested array with all indirectly inherited roles for a path between 2
   *   groups. The array is in the form of:
   *   $map[$group_a_id][$group_b_id][$group_b_role_id] = $group_a_role_id;
   */
  protected function mapIndirectPathRoles($path, $path_role_map) {
    $indirect_role_map = [];
    foreach ($path as $from_group_key => $path_from_group_id) {
      $inherited_roles_map = [];
      foreach ($path as $to_group_key => $path_to_group_id) {
        if ($to_group_key <= $from_group_key) {
          continue;
        }

        // Get the previous group ID from the previous element.
        $path_direct_to_group_id = isset($path[$to_group_key - 1]) ? $path[$to_group_key - 1] : NULL;

        if (!$path_direct_to_group_id) {
          continue;
        }

        $direct_role_map = $path_role_map[$path_to_group_id][$path_direct_to_group_id];

        if (empty($inherited_roles_map)) {
          $inherited_roles_map = $direct_role_map;
        }

        foreach ($inherited_roles_map as $from_group_role_id => $to_group_role_id) {
          if (isset($direct_role_map[$to_group_role_id])) {
            $indirect_role_map[$path_to_group_id][$path_from_group_id][$from_group_role_id] = $direct_role_map[$to_group_role_id];
            $inherited_roles_map[$from_group_role_id] = $direct_role_map[$to_group_role_id];
          }
        }
      }
    }
    return $indirect_role_map;
  }

  /**
   * Get the config for all installed subgroup relations.
   *
   * @return array[]
   *   A nested array with configuration values keyed by subgroup relation ID.
   */
  protected function getSubgroupRelationsConfig() {
    // We create a static cache with the configuration for all subgroup
    // relations since having separate queries for every relation has a big
    // impact on performance.
    if (!$this->subgroupConfig) {
      foreach ($this->entityTypeManager->getStorage('group_type')->loadMultiple() as $group_type) {
        $plugin_id = 'subgroup:' . $group_type->id();
        /** @var \Drupal\group\Entity\Storage\GroupContentTypeStorageInterface $storage */
        $storage = $this->entityTypeManager->getStorage('group_content_type');
        $subgroup_content_types = $storage->loadByContentPluginId($plugin_id);
        foreach ($subgroup_content_types as $subgroup_content_type) {
          /** @var \Drupal\group\Entity\GroupContentTypeInterface $subgroup_content_type */
          $this->subgroupConfig[$subgroup_content_type->id()] = $subgroup_content_type->getContentPlugin()->getConfiguration();
        }
      }
    }
    return $this->subgroupConfig;
  }

  /**
   * Get the config for a relation between a group and a subgroup.
   *
   * @param int $group_id
   *   The group for which to get the configuration.
   * @param int $subgroup_id
   *   The subgroup for which to get the configuration.
   *
   * @return array[]
   *   A nested array with configuration values.
   */
  protected function getSubgroupRelationConfig($group_id, $subgroup_id) {
    $subgroup_relations_config = $this->getSubgroupRelationsConfig();

    // We need the type of each relation to fetch the configuration. We create
    // a static cache for the types of all subgroup relations since fetching
    // each relation independently has a big impact on performance.
    if (!$this->subgroupRelations) {
      // Get all  type between the supergroup and subgroup.
      $group_contents = $this->entityTypeManager->getStorage('group_content')
        ->loadByProperties([
          'type' => array_keys($subgroup_relations_config),
        ]);
      foreach ($group_contents as $group_content) {
        $this->subgroupRelations[$group_content->gid->target_id][$group_content->entity_id->target_id] = $group_content->bundle();
      }
    }

    $type = $this->subgroupRelations[$group_id][$subgroup_id];
    return $subgroup_relations_config[$type];
  }

}
