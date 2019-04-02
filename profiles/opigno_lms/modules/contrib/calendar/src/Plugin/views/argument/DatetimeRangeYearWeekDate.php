<?php

namespace Drupal\calendar\Plugin\views\argument;

use Drupal\datetime_range\Plugin\views\argument\DateRange;

/**
 * Argument handler for a day.
 *
 * @ViewsArgument("datetime_range_year_week")
 */
class DatetimeRangeYearWeekDate extends DateRange {

  /**
   * {@inheritdoc}
   */
  protected $argFormat = 'YW';

}
