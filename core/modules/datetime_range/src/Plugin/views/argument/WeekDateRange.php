<?php

namespace Drupal\datetime_range\Plugin\views\argument;

/**
 * Argument handler for a week.
 *
 * @ViewsArgument("datetime_range_week")
 */
class WeekDateRange extends DateRange {

  /**
   * {@inheritdoc}
   */
  protected $argFormat = 'W';

}
