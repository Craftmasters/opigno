<?php

namespace Drupal\datetime_range\Plugin\views\argument;

/**
 * Argument handler for a year plus month (CCYYMM).
 *
 * @ViewsArgument("datetime_range_year_month")
 */
class YearMonthDateRange extends DateRange {

  /**
   * {@inheritdoc}
   */
  protected $argFormat = 'Ym';

}
