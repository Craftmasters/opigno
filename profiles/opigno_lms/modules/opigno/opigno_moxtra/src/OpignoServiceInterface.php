<?php

namespace Drupal\opigno_moxtra;

/**
 * Implements Opigno API.
 */
interface OpignoServiceInterface {

  /**
   * Creates organization.
   *
   * @return array
   *   Response data.
   */
  public function createOrganization();

  /**
   * Returns max count of users.
   *
   * @return int
   *   Max total users.
   */
  public function getMaxTotalUsers();

  /**
   * Returns current organization info.
   *
   * @return array
   *   Response data.
   */
  public function getOrganizationInfo();

  /**
   * Creates users.
   *
   * @param \Drupal\user\UserInterface[] $users
   *   An array of the user entities.
   *
   * @return array
   *   Response data.
   */
  public function createUsers($users);

  /**
   * Updates user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   *
   * @return array
   *   Response data.
   */
  public function updateUser($user);

  /**
   * Enables user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   *
   * @return array
   *   Response data.
   */
  public function enableUser($user);

  /**
   * Disables user.
   *
   * @param int $user_id
   *   User ID.
   *
   * @return array
   *   Response data.
   */
  public function disableUser($user_id);

  /**
   * Gets the moxtra API access token for the user.
   *
   * @param int $user_id
   *   User ID.
   * @param bool $use_cache
   *   Store the access token to the drupal cache.
   *
   * @return string|bool
   *   Access token. FALSE on error.
   */
  public function getToken($user_id, $use_cache = TRUE);

}
