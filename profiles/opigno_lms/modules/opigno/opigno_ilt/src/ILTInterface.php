<?php

namespace Drupal\opigno_ilt;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\opigno_calendar_event\Entity\CalendarEvent;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a ILT entity.
 */
interface ILTInterface extends ContentEntityInterface, EntityOwnerInterface {

  /**
   * Returns the ILT title.
   *
   * @return string|null
   *   The ILT title, or NULL in case title field has not been
   *   set on the entity.
   */
  public function getTitle();

  /**
   * Sets the ILT title.
   *
   * @param string $title
   *   The ILT title.
   *
   * @return $this
   */
  public function setTitle($title);

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
  public function setDate(array $date);

  /**
   * Returns the ILT place.
   *
   * @return string|null
   *   The ILT place, or NULL in case place field has not been
   *   set on the entity.
   */
  public function getPlace();

  /**
   * Sets the ILT place.
   *
   * @param string $place
   *   The ILT place.
   *
   * @return $this
   */
  public function setPlace($place);

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
  public function setCalendarEvent(CalendarEvent $event);

  /**
   * Adds member to the ILT.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return $this
   */
  public function addMember($uid);

  /**
   * Removes member from the ILT.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return $this
   */
  public function removeMember($uid);

  /**
   * Returns ids of the members of the ILT.
   *
   * @return int[]
   *   Array of users IDs.
   */
  public function getMembersIds();

  /**
   * Set members to the ILT.
   *
   * @param int[] $ids
   *   The users IDs.
   *
   * @return $this
   */
  public function setMembersIds(array $ids);

  /**
   * Returns members of the ILT.
   *
   * @return \Drupal\user\Entity\User[]
   *   Array of users.
   */
  public function getMembers();

  /**
   * Adds recipient for email notification to the ILT.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return $this
   */
  public function addNotifiedMember($uid);

  /**
   * Removes recipient from email notification to the ILT.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return $this
   */
  public function removeNotifiedMember($uid);

  /**
   * Returns ids of the recipient for email notification to the ILT.
   *
   * @return int[]
   *   Array of users IDs.
   */
  public function getNotifiedMembersIds();

  /**
   * Sets ids of the recipient for email notification to the ILT.
   *
   * @param int[] $ids
   *   The users IDs.
   *
   * @return $this
   */
  public function setNotifiedMembersIds(array $ids);

  /**
   * Returns the recipients for email notification to the ILT.
   *
   * @return \Drupal\user\Entity\User[]
   *   Array of users.
   */
  public function getNotifiedMembers();

  /**
   * Checks if the user is a member of the ILT or related training.
   *
   * @param int $user_id
   *   User ID.
   *
   * @return bool
   *   TRUE if the user is a member, FALSE otherwise.
   */
  public function isMember($user_id);

}
