<?php

namespace Drupal\opigno_moxtra;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a Workspace entity.
 */
interface MeetingInterface extends ContentEntityInterface, EntityOwnerInterface {

  /**
   * Returns the Live Meeting title.
   *
   * @return string|null
   *   The Live Meeting title, or NULL in case title field has not been
   *   set on the entity.
   */
  public function getTitle();

  /**
   * Sets the Live Meeting title.
   *
   * @param string $title
   *   The Live Meeting title.
   *
   * @return $this
   */
  public function setTitle($title);

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
   * Returns the entity's Moxtra session key.
   *
   * @return string|null
   *   The Moxtra session key, or NULL in case
   *   the session key field has not been set on the entity.
   */
  public function getSessionKey();

  /**
   * Sets the entity's Moxtra session key.
   *
   * @param string $key
   *   The Moxtra session key.
   *
   * @return $this
   */
  public function setSessionKey($key);

  /**
   * Returns the entity's start date.
   *
   * @return string
   *   The start date string in the 'Y-m-d H:i:s' format, or NULL in case
   *   the date range field has not been set on the entity.
   */
  public function getStartDate();

  /**
   * Returns the entity's end date.
   *
   * @return string
   *   The end date string in the 'Y-m-d H:i:s' format, or NULL in case
   *   the date range field has not been set on the entity.
   */
  public function getEndDate();

  /**
   * Returns the entity's date range.
   *
   * @return array
   *   The date range. Array keys:
   *   - value - Start date string in the 'Y-m-d H:i:s' format or NULL.
   *   - end_value - End date string in the 'Y-m-d H:i:s' format or NULL.
   */
  public function getDate();

  /**
   * Sets the entity's date range.
   *
   * @param array $date
   *   The date range. Array keys:
   *   - value - Start date string in the 'Y-m-d H:i:s' format.
   *   - end_value - End date string in the 'Y-m-d H:i:s' format.
   *
   * @return $this
   */
  public function setDate($date);

  /**
   * Returns the ID of the related training.
   *
   * @return int|null
   *   The ID of the related training, or NULL in case training ID field
   *   has not been set on the entity.
   */
  public function getTrainingId();

  /**
   * Sets the ID of the related training.
   *
   * @param int|null $id
   *   The ID of the related training.
   *
   * @return $this
   */
  public function setTrainingId($id);

  /**
   * Returns the related training.
   *
   * @return \Drupal\group\Entity\GroupInterface|null
   *   The related training entity, or NULL in case training ID field
   *   has not been set on the entity.
   */
  public function getTraining();

  /**
   * Sets the related training.
   *
   * @param \Drupal\group\Entity\GroupInterface|null $training
   *   The related training entity.
   *
   * @return $this
   */
  public function setTraining($training);

  /**
   * Returns the ID of the related calendar event.
   *
   * @return int|null
   *   The ID of the related calendar event,
   *   or NULL in case calendar event field
   *   has not been set on the entity.
   */
  public function getCalendarEventId();

  /**
   * Sets the ID of the related calendar event.
   *
   * @param int|null $id
   *   The ID of the related calendar event.
   *
   * @return $this
   */
  public function setCalendarEventId($id);

  /**
   * Returns the entity of the related calendar event.
   *
   * @return \Drupal\opigno_calendar_event\Entity\CalendarEvent|null
   *   The entity of the related calendar event,
   *   or NULL in case calendar event field
   *   has not been set on the entity.
   */
  public function getCalendarEvent();

  /**
   * Sets the entity of the related calendar event.
   *
   * @param \Drupal\opigno_calendar_event\Entity\CalendarEvent $event
   *   The entity of the related calendar event.
   *
   * @return $this
   */
  public function setCalendarEvent($event);

  /**
   * Adds member to the meeting.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return $this
   */
  public function addMember($uid);

  /**
   * Removes member from the meeting.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return $this
   */
  public function removeMember($uid);

  /**
   * Returns ids of the members of the meeting.
   *
   * @return int[]
   *   Array of users IDs.
   */
  public function getMembersIds();

  /**
   * Set members to the meeting.
   *
   * @param int[] $ids
   *   The users IDs.
   *
   * @return $this
   */
  public function setMembersIds($ids);

  /**
   * Returns members of the meeting.
   *
   * @return \Drupal\user\Entity\User[]
   *   Array of users.
   */
  public function getMembers();

  /**
   * Adds member that received the email notification to the meeting.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return $this
   */
  public function addNotifiedMember($uid);

  /**
   * Removes member that received the email notification from the meeting.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return $this
   */
  public function removeNotifiedMember($uid);

  /**
   * Returns meeting members ids that received the email notification.
   *
   * @return int[]
   *   Array of users IDs.
   */
  public function getNotifiedMembersIds();

  /**
   * Set members that received the email notification to the meeting.
   *
   * @param int[] $ids
   *   The users IDs.
   *
   * @return $this
   */
  public function setNotifiedMembersIds($ids);

  /**
   * Returns members of the meeting that received the email notification.
   *
   * @return \Drupal\user\Entity\User[]
   *   Array of users.
   */
  public function getNotifiedMembers();

  /**
   * Checks if the user is a member of the live meeting or related training.
   *
   * @param int $user_id
   *   User ID.
   *
   * @return bool
   *   TRUE if the user is a member, FALSE otherwise.
   */
  public function isMember($user_id);

}
