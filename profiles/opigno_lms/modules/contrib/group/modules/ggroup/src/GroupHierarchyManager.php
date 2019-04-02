<?php

namespace Drupal\ggroup;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ggroup\Graph\GroupGraphStorageInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\GroupMembership;
use Drupal\group\GroupMembershipLoader;

/**
 * Manages the relationship between groups (as subgroups).
 */
class GroupHierarchyManager implements GroupHierarchyManagerInterface {

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
   * The group membership loader.
   *
   * @var \Drupal\group\GroupMembershipLoader
   */
  protected $membershipLoader;

  /**
   * The group role inheritance manager.
   *
   * @var \Drupal\ggroup\GroupRoleInheritanceInterface
   */
  protected $groupRoleInheritanceManager;

  /**
   * Static cache for all group memberships per user.
   *
   * A nested array with all group memberships keyed by user ID.
   *
   * @var \Drupal\group\GroupMembership[][]
   */
  protected $userMemberships = [];

  /**
   * Static cache for all inherited group roles by user.
   *
   * A nested array with all inherited roles keyed by user ID and group ID.
   *
   * @var array
   */
  protected $userGroupRoles = [];

  /**
   * Constructs a new GroupHierarchyManager.
   *
   * @param \Drupal\ggroup\Graph\GroupGraphStorageInterface $group_graph_storage
   *   The group graph storage service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\group\GroupMembershipLoader $membership_loader
   *   The group membership loader.
   * @param \Drupal\ggroup\GroupRoleInheritanceInterface $group_role_inheritance_manager
   *   The group membership loader.
   */
  public function __construct(GroupGraphStorageInterface $group_graph_storage, EntityTypeManagerInterface $entity_type_manager, GroupMembershipLoader $membership_loader, GroupRoleInheritanceInterface $group_role_inheritance_manager) {
    $this->groupGraphStorage = $group_graph_storage;
    $this->entityTypeManager = $entity_type_manager;
    $this->membershipLoader = $membership_loader;
    $this->groupRoleInheritanceManager = $group_role_inheritance_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function addSubgroup(GroupContentInterface $group_content) {
    $plugin = $group_content->getContentPlugin();

    if ($plugin->getEntityTypeId() !== 'group') {
      throw new \InvalidArgumentException('Given group content entity does not represent a subgroup relationship.');
    }

    $parent_group = $group_content->getGroup();
    /** @var \Drupal\group\Entity\GroupInterface $child_group */
    $child_group = $group_content->getEntity();

    if ($parent_group->id() === NULL) {
      throw new \InvalidArgumentException('Parent group must be saved before it can be related to another group.');
    }

    if ($child_group->id() === NULL) {
      throw new \InvalidArgumentException('Child group must be saved before it can be related to another group.');
    }

    $new_edge_id = $this->groupGraphStorage->addEdge($parent_group->id(), $child_group->id());

    // @todo Invalidate some kind of cache?
  }

  /**
   * {@inheritdoc}
   */
  public function removeSubgroup(GroupContentInterface $group_content) {
    $plugin = $group_content->getContentPlugin();

    if ($plugin->getEntityTypeId() !== 'group') {
      throw new \InvalidArgumentException('Given group content entity does not represent a subgroup relationship.');
    }

    $parent_group = $group_content->getGroup();

    $child_group_id = $group_content->get('entity_id')->getValue();

    if (!empty($child_group_id)) {
      $child_group_id = reset($child_group_id)['target_id'];
      $this->groupGraphStorage->removeEdge($parent_group->id(), $child_group_id);
    }

    // @todo Invalidate some kind of cache?
  }

  /**
   * {@inheritdoc}
   */
  public function groupHasSubgroup(GroupInterface $group, GroupInterface $subgroup) {
    return $this->groupGraphStorage->isDescendant($subgroup->id(), $group->id());
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupSubgroups($group_id) {
    $subgroup_ids = $this->getGroupSubgroupIds($group_id);
    return $this->entityTypeManager->getStorage('group')->loadMultiple($subgroup_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupSubgroupIds($group_id) {
    return $this->groupGraphStorage->getDescendants($group_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupSupergroups($group_id) {
    $subgroup_ids = $this->getGroupSupergroupIds($group_id);
    return $this->entityTypeManager->getStorage('group')->loadMultiple($subgroup_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupSupergroupIds($group_id) {
    return $this->groupGraphStorage->getAncestors($group_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getInheritedGroupRoleIdsByUser(GroupInterface $group, AccountInterface $account) {
    $account_id = $account->id();
    $group_id = $group->id();

    if (isset($this->userGroupRoles[$account_id][$group_id])) {
      return $this->userGroupRoles[$account_id][$group_id];
    }

    // Statically cache the memberships of a user since this method could get
    // called a lot.
    if (empty($this->userMemberships[$account_id])) {
      $this->userMemberships[$account_id] = $this->membershipLoader->loadByUser($account);
    }

    $role_map = $this->groupRoleInheritanceManager->getAllInheritedGroupRoleIds();

    $mapped_role_ids = [[]];
    foreach ($this->userMemberships[$account_id] as $membership) {
      $membership_gid = $membership->getGroupContent()->gid->target_id;

      if (!isset($role_map[$group_id][$membership_gid])) {
        continue;
      }

      $mapped_role_ids[] = array_intersect_key($role_map[$group_id][$membership_gid], array_flip($this->getMembershipRoles($membership)));
    }
    $mapped_role_ids = array_replace_recursive(...$mapped_role_ids);

    $this->userGroupRoles[$account_id][$group_id] = $this->entityTypeManager->getStorage('group_role')->loadMultiple(array_unique($mapped_role_ids));
    return $this->userGroupRoles[$account_id][$group_id];
  }

  /**
   * Get the role IDs for a group membership.
   *
   * @param \Drupal\group\GroupMembership $membership
   *   The user to load the membership for.
   *
   * @return string[]
   *   An array of role IDs.
   */
  protected function getMembershipRoles(GroupMembership $membership) {
    $ids = [];
    foreach ($membership->getGroupContent()->group_roles as $group_role_ref) {
      $ids[] = $group_role_ref->target_id;
    }

    // We add the implied member role. Usually we should get this from the
    // membership $membership->getGroup()->getGrouptype()->getMemberRoleID(),
    // but since this means the whole Group and GroupType entities need to be
    // loaded, this has a big impact on performance.
    // @todo: Fix this hacky solution!
    $ids[] = str_replace('-group_membership', '', $membership->getGroupContent()->bundle()) . '-member';

    return $ids;
  }

}
