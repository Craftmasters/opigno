<?php

namespace Drupal\opigno_calendar_event\iCal;

/**
 * Class ICalendar.
 */
class ICalendar {
  protected $events;
  protected $title;
  protected $author;

  /**
   * ICalendar constructor.
   */
  public function __construct($parameters) {
    $parameters += [
      'events' => [],
      'title' => 'Calendar',
      'author' => 'Calender Generator',
    ];
    $this->events = $parameters['events'];
    $this->title = $parameters['title'];
    $this->author = $parameters['author'];
  }

  /**
   * Call this function to download the invite.
   */
  public function generateDownload() {
    $generated = $this->generateString();
    // Date in the past.
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    // Tell it we just updated.
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    // Force revaidation.
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Cache-Control: post-check=0, pre-check=0', FALSE);
    header('Pragma: no-cache');
    header('Content-type: text/calendar; charset=utf-8');
    header('Content-Disposition: inline; filename="calendar.ics"');
    header("Content-Description: File Transfer");
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: " . strlen($generated));
    print $generated;
  }

  /**
   * The function generates the actual content of the ICS file and returns it.
   */
  public function generateString() {
    $content = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//" . $this->author . "//NONSGML//EN\r\n"
             . "X-WR-CALNAME:" . $this->title . "\r\nCALSCALE:GREGORIAN\r\n";
    foreach ($this->events as $event) {
      $content .= $event->generateString();
    }
    $content .= "END:VCALENDAR";
    return $content;
  }

}
