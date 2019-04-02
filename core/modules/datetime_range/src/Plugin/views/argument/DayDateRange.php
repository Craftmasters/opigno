<?php

namespace Drupal\datetime_range\Plugin\views\argument;

/**
 * Argument handler for a day.
 *
 * @ViewsArgument("datetime_range_day")
 */
class DayDateRange extends DateRange {

  /**
   * {@inheritdoc}
   */
  protected $argFormat = 'd';

}
