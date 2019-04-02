<?php

namespace Drupal\datetime_range\Plugin\views\argument;

/**
 * Argument handler for a month.
 *
 * @ViewsArgument("datetime_range_month")
 */
class MonthDateRange extends DateRange {

  /**
   * {@inheritdoc}
   */
  protected $argFormat = 'm';

}
