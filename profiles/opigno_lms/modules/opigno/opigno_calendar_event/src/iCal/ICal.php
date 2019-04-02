<?php

namespace Drupal\opigno_calendar_event\iCal;

/**
 * Class ICal.
 */
class ICal {

  /**
   * Build iCal file for events.
   *
   * @param array $params
   *   Params.
   *
   * @return bool|string
   *   Calendar event.
   */
  public function buildICalEvent(array $params) {
    $event = new ICalendarEvent($params);
    $params['events'] = [$event];
    $calendar = new ICalendar($params);
    return $calendar->generateString();
  }

}
