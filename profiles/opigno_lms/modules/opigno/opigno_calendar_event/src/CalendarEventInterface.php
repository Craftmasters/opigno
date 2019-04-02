<?php

namespace Drupal\opigno_calendar_event;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\RevisionLogInterface;

/**
 * Common interface for Calendar events.
 */
interface CalendarEventInterface extends ContentEntityInterface, EntityChangedInterface, RevisionLogInterface, EntityPublishedInterface {

  /**
   * The calendar event entity type ID.
   */
  const ENTITY_TYPE_ID = 'opigno_calendar_event';

  /**
   * Returns the calendar event date items.
   *
   * @return \Drupal\datetime\Plugin\Field\FieldType\DateTimeFieldItemList
   *   Сalendar event date items.
   */
  public function getDateItems();

  /**
   * Checks whether this calendar event should appear on calendars.
   *
   * @return bool
   *   TRUE if the calendar event should be displayed, FALSE otherwise.
   */
  public function isDisplayed();

  /**
   * Determines whether this calendar event should appear on calendars.
   *
   * @param bool $displayed
   *   TRUE if the calendar event should be displayed, FALSE otherwise.
   */
  public function setDisplayed($displayed);

}
