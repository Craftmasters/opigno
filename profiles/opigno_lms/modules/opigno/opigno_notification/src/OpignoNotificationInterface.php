<?php

namespace Drupal\opigno_notification;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a opigno_notification entity.
 *
 * @ingroup opigno_notification
 */
interface OpignoNotificationInterface extends ContentEntityInterface {

  /**
   * Gets the notification created timestamp.
   *
   * @return int
   *   The created timestamp for the notification.
   */
  public function getCreatedTime();

  /**
   * Gets the notification receiver.
   *
   * @return int
   *   The user id for the notification receiver.
   */
  public function getUser();

  /**
   * Sets the notification receiver.
   *
   * @param int $value
   *   The notification receiver.
   *
   * @return \Drupal\opigno_notification\OpignoNotificationInterface
   *   The called notification entity.
   */
  public function setUser($value);

  /**
   * Gets the notification message.
   *
   * @return string
   *   The message of the notification.
   */
  public function getMessage();

  /**
   * Sets the notification message.
   *
   * @param string $value
   *   The notification message.
   *
   * @return \Drupal\opigno_notification\OpignoNotificationInterface
   *   The called notification entity.
   */
  public function setMessage($value);

  /**
   * Gets the notification status.
   *
   * @return bool
   *   The status of the notification.
   */
  public function getHasRead();

  /**
   * Sets the notification status.
   *
   * @param bool $value
   *   The notification status.
   *
   * @return \Drupal\opigno_notification\OpignoNotificationInterface
   *   The called notification entity.
   */
  public function setHasRead($value);

}
