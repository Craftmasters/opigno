<?php

namespace Drupal\opigno_moxtra;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\UserInterface;

/**
 * Provides an interface defining a Meeting Result entity.
 */
interface MeetingResultInterface extends ContentEntityInterface {

  /**
   * Returns the meeting entity.
   *
   * @return \Drupal\opigno_moxtra\MeetingInterface
   *   The meeting entity.
   */
  public function getMeeting();

  /**
   * Sets the meeting entity.
   *
   * @param \Drupal\opigno_moxtra\MeetingInterface $meeting
   *   The meeting entity.
   *
   * @return $this
   */
  public function setMeeting(MeetingInterface $meeting);

  /**
   * Returns the meeting ID.
   *
   * @return int|null
   *   The meeting ID, or NULL in case the meeting ID field has not been set on
   *   the entity.
   */
  public function getMeetingId();

  /**
   * Sets the meeting ID.
   *
   * @param int $id
   *   The meeting id.
   *
   * @return $this
   */
  public function setMeetingId($id);

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
