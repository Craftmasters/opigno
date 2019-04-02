<?php

namespace Drupal\datetime_range\Plugin\views\argument;

/**
 * Argument handler for a full date (CCYYMMDD).
 *
 * @ViewsArgument("datetime_range_full_date")
 */
class FullDateRange extends DateRange {

  /**
   * {@inheritdoc}
   */
  protected $argFormat = 'Ymd';

}
