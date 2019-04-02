<?php

namespace Drupal\opigno_ilt;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\UserInterface;

/**
 * Provides an interface defining a ILT Result entity.
 */
interface ILTResultInterface extends ContentEntityInterface {

  /**
   * Returns the ILT entity.
   *
   * @return \Drupal\opigno_ilt\ILTInterface
   *   The ILT entity.
   */
  public function getILT();

  /**
   * Sets the ILT entity.
   *
   * @param \Drupal\opigno_ilt\ILTInterface $opigno_ilt
   *   The ILT entity.
   *
   * @return $this
   */
  public function setILT(ILTInterface $opigno_ilt);

  /**
   * Returns the ILT ID.
   *
   * @return int|null
   *   The ILT ID, or NULL in case the ILT ID field has not been set on
   *   the entity.
   */
  public function getILTId();

  /**
   * Sets the ILT ID.
   *
   * @param int $id
   *   The ILT id.
   *
   * @return $this
   */
  public function setILTId($id);

  /**
   * Returns the user entity.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity.
   */
  public function getUser();

  /**
   * Sets the user entity.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user entity.
   *
   * @return $this
   */
  public function setUser(UserInterface $account);

  /**
   * Returns the user ID.
   *
   * @return int|null
   *   The user ID, or NULL in case the user ID field has not been set on
   *   the entity.
   */
  public function getUserId();

  /**
   * Sets the user ID.
   *
   * @param int $uid
   *   The user id.
   *
   * @return $this
   */
  public function setUserId($uid);

  /**
   * Returns the user status.
   *
   * @return int|null
   *   The user status, or NULL in case the user status field
   *   has not been set on the entity.
   */
  public function getStatus();

  /**
   * Returns the user status as string.
   *
   * @return string|null
   *   The user status, or NULL in case the user status field
   *   has not been set on the entity.
   */
  public function getStatusString();

  /**
   * Sets the user status.
   *
   * @param int $value
   *   The user status.
   *
   * @return $this
   */
  public function setStatus($value);

  /**
   * Returns the user score.
   *
   * @return int|null
   *   The user score, or NULL in case the user score field
   *   has not been set on the entity.
   */
  public function getScore();

  /**
   * Sets the user score.
   *
   * @param int $value
   *   The user score.
   *
   * @return $this
   */
  public function setScore($value);

}
