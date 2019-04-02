<?php

namespace Drupal\datetime_range\Plugin\views\argument;

/**
 * Argument handler for a year.
 *
 * @ViewsArgument("datetime_range_year")
 */
class YearDateRange extends DateRange {

  /**
   * {@inheritdoc}
   */
  protected $argFormat = 'Y';

}
