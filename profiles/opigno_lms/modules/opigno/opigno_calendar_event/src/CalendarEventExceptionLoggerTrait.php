<?php

namespace Drupal\opigno_calendar_event;

/**
 * Logs exceptions thrown in the the Calendar Event codebase.
 *
 * @internal
 */
trait CalendarEventExceptionLoggerTrait {

  /**
   * Logs an exception.
   *
   * @param \Exception $e
   *   The exception object to be logged.
   */
  protected function logException(\Exception $e) {
    watchdog_exception('opigno_calendar_event', $e);
  }

}
