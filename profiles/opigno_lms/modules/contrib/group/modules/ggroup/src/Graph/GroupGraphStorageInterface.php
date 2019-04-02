<?php

namespace Drupal\ggroup\Graph;

/**
 * An interface for defining the storage of group relationships as a graph.
 */
interface GroupGraphStorageInterface {

  /**
   * Get all relations between groups ordered by the number of hops.
   *
   * @return array
   *   An array containing all relations between groups with the parent group as
   *   key and the child group as value.
   */
  public function getGraph();

  /**
   * Relates the parent group and the child group.
   *
   * Inferred relationships based on existing relationships to the parent group
   * and the child group will also be created.
   *
   * @param int $parent_group_id
   *   The ID of the parent group.
   * @param int $child_group_id
   *   The ID of the child group.
   *
   * @return int|false
   *   The ID of the graph edge relating the parent group to the child group or
   *   FALSE if the relationship could not be created.
   */
  public function addEdge($parent_group_id, $child_group_id);

  /**
   * Removes the relationship between the parent group and the child group.
   *
   * The child group will no longer be a child of the parent group. Inferred
   * relationships based on existing relationships to the parent group will
   * also be removed.
   *
   * @param int $parent_group_id
   *   The ID of the parent group.
   * @param int $child_group_id
   *   The ID of the child group.
   */
  public function removeEdge($parent_group_id, $child_group_id);

  /**
   * Gets all descendant child groups of the given parent group.
   *
   * @param int $group_id
   *   The parent group ID.
   *
   * @return int[]
   *   An array of descendant child group IDs.
   */
  public function getDescendants($group_id);

  /**
   * Gets all ancestor parent groups of the given child group.
   *
   * @param int $group_id
   *   The child group ID.
   *
   * @return int[]
   *   An array of ancestor parent group IDs.
   */
  public function getAncestors($group_id);

  /**
   * Checks if a group (group A) is the ancestor of another group (group B).
   *
   * @param int $a
   *   The group whose ancestry status will be checked.
   * @param int $b
   *   The group for which ancestry status will be checked against.
   *
   * @return bool
   *   TRUE if group A is an ancestor of group B.
   */
  public function isAncestor($a, $b);

  /**
   * Checks if a group (group A) is the descendant of another group (group B).
   *
   * @param int $a
   *   The group whose descent status will be checked.
   * @param int $b
   *   The group for which descent status will be checked against.
   *
   * @return bool
   *   TRUE if group A is an descendant of group B.
   */
  public function isDescendant($a, $b);

  /**
   * Use the Breadth-first search algoritm to find the path between groups.
   *
   * @param int $parent_group_id
   *   The ID of the parent group.
   * @param int $child_group_id
   *   The ID of the child group.
   *
   * @return array[]
   *   An nested array containing a path between the groups.
   *
   * @see https://en.wikipedia.org/wiki/Breadth-first_search
   * @see https://www.sitepoint.com/data-structures-4/
   */
  public function getPath($parent_group_id, $child_group_id);

}
