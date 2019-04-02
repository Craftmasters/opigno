<?php

namespace Drupal\opigno_calendar_event\iCal;

/**
 * Class ICalendarEvent.
 */
class ICalendarEvent {

  /**
   * The event ID.
   *
   * @var int
   */
  private $uid;
  /**
   * The event start date.
   *
   * @var DateTime
   */
  private $start;
  /**
   * The event end date.
   *
   * @var DateTime
   */
  private $end;
  /**
   * The event title.
   *
   * @var string
   */
  private $summary;
  /**
   * The event description.
   *
   * @var string
   */
  private $description;
  /**
   * The event location.
   *
   * @var string
   */
  private $location;

  /**
   * ICalendarEvent constructor.
   */
  public function __construct($parameters) {
    $parameters += [
      'summary' => 'Untitled Event',
      'description' => '',
      'url' => '',
      'org' => '',
      'location' => '',
    ];
    if (isset($parameters['uid'])) {
      $this->uid = $parameters['uid'];
    }
    else {
      $this->uid = uniqid(rand(0, getmypid()));
    }
    $this->start = $parameters['start'];
    $this->end = $parameters['end'];
    $this->summary = $parameters['summary'];
    $this->description = $parameters['description'];
    $this->url = $parameters['url'];
    $this->org = $parameters['org'];
    $this->location = $parameters['location'];
    return $this;
  }

  /**
   * Get the start time set for the even.
   */
  private function formatDate($date) {
    return $date->format("Ymd\THis\Z");
  }

  /**
   * Escape commas, semi-colons, backslashes.
   *
   * @see http://stackoverflow.com/questions/1590368/should-a-colon-character-be-escaped-in-text-values-in-icalendar-rfc2445
   */
  private function formatValue($str) {
    return addcslashes($str, ",\\;");
  }

  /**
   * Calendar event string.
   */
  public function generateString() {
    $created = new \DateTime();
    $content = '';
    $content = "BEGIN:VEVENT\r\nUID:{$this->uid}\r\n"
             . "DTSTART:{$this->formatDate($this->start)}\r\n"
             . "DTEND:{$this->formatDate($this->end)}\r\n"
             . "DTSTAMP:{$this->formatDate($this->start)}\r\n"
             . "CREATED:{$this->formatDate($created)}\r\n"
             . "DESCRIPTION:{$this->formatValue($this->description)}\r\n"
             . "LAST-MODIFIED:{$this->formatDate($this->start)}\r\n"
             . "URL:{$this->url}\r\nORGANIZER:{$this->org}\r\n"
             . "LOCATION:{$this->location}\r\n"
             . "SUMMARY:{$this->formatValue($this->summary)}\r\n"
             . "SEQUENCE:0\r\nSTATUS:CONFIRMED\r\nTRANSP:OPAQUE\r\nEND:VEVENT\r\n";
    return $content;
  }

}
