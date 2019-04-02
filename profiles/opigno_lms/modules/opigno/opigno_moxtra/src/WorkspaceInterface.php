<?php

namespace Drupal\opigno_moxtra;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a Workspace entity.
 */
interface WorkspaceInterface extends ContentEntityInterface, EntityOwnerInterface {

  /**
   * Returns the Collaborative Workspace name.
   *
   * @return string|null
   *   The Collaborative Workspace name, or NULL in case name field has not been
   *   set on the entity.
   */
  public function getName();

  /**
   * Sets the Collaborative Workspace name.
   *
   * @param string $name
   *   The Collaborative Workspace name.
   *
   * @return $this
   */
  public function setName($name);

  /**
   * Returns the entity's Moxtra binder ID.
   *
   * @return string|null
   *   The Moxtra binder ID, or NULL in case the binder ID field has not been
   *   set on the entity.
   */
  public function getBinderId();

  /**
   * Sets the entity's Moxtra binder ID.
   *
   * @param string $id
   *   The Moxtra binder ID.
   *
   * @return $this
   */
  public function setBinderId($id);

  /**
   * Returns the entity's Moxtra workspace Auto Register value.
   *
   * @return string|null
   *   The Moxtra workspace Auto Register value.
   */
  public function getAutoRegister();

  /**
   * Sets the entity's Moxtra workspace Auto Register value.
   *
   * @return $this
   */
  public function setAutoRegister($boolean);

  /**
   * Sets the entity's Moxtra workspace Members value.
   *
   * @param array $uids
   *   The Workspace members.
   *
   * @return $this
   */
  public function setMembers($uids);

  /**
   * Adds member to the workspace.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return $this
   */
  public function addMember($uid);

  /**
   * Removes member from the workspace.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return $this
   */
  public function removeMember($uid);

  /**
   * Returns ids of the members of the workspace.
   *
   * @return int[]
   *   Array of users IDs.
   */
  public function getMembersIds();

  /**
   * Returns members of the workspace.
   *
   * @return \Drupal\user\Entity\User[]
   *   Array of users.
   */
  public function getMembers();

}
