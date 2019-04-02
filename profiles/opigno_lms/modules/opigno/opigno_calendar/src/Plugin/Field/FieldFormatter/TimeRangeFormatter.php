<?php

namespace Drupal\opigno_calendar\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime_range\Plugin\Field\FieldFormatter\DateRangeCustomFormatter;

/**
 * Time range with optional date formatter.
 *
 * If the range spans multiple days the full datetime format, including the date
 * part, is used otherwise only times are rendered.
 *
 * @FieldFormatter(
 *   id = "opigno_calendar_time_range",
 *   label = @Translation("Time range with optional date"),
 *   field_types = {
 *     "daterange"
 *   }
 * )
 */
class TimeRangeFormatter extends DateRangeCustomFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'time_separator' => '\T',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $separator = $this->getSetting('separator');
    $split_format = $this->getSplitFormat();

    foreach ($items as $delta => $item) {
      if (!empty($item->start_date) && !empty($item->end_date)) {
        /** @var \Drupal\Core\Datetime\DrupalDateTime $start_date */
        $start_date = $item->start_date;
        /** @var \Drupal\Core\Datetime\DrupalDateTime $end_date */
        $end_date = $item->end_date;
        $formatted_start_date = $this->dateFormatter->format($start_date->getTimestamp(), 'custom', $split_format['date']);
        $formatted_end_date = $this->dateFormatter->format($end_date->getTimestamp(), 'custom', $split_format['date']);

        if ($formatted_start_date !== $formatted_end_date) {
          $elements[$delta] = [
            'start_date' => $this->buildDate($start_date),
            'separator' => ['#plain_text' => ' ' . $separator . ' '],
            'end_date' => $this->buildDate($end_date),
          ];
        }
        else {
          $date_format = $this->getSetting('date_format');
          $this->setSetting('date_format', $split_format['time']);
          $elements[$delta] = [
            'start_date' => $this->buildDate($start_date),
            'separator' => ['#plain_text' => ' ' . $separator . ' '],
            'end_date' => $this->buildDate($end_date),
          ];
          $this->setSetting('date_format', $date_format);
        }
      }
    }

    return $elements;
  }

  /**
   * Splits the datetime format into the date and time components.
   *
   * @return array
   *   An associative array of date formats keyed by "date" or "time" keys.
   */
  protected function getSplitFormat() {
    $parts = explode($this->getSetting('time_separator'), $this->getSetting('date_format'), 2);
    return [
      'date' => $parts[0],
      'time' => $parts[1],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['time_separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Time separator'),
      '#description' => $this->t('The string separating the date and time parts of the provided format'),
      '#default_value' => $this->getSetting('time_separator'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $separator = $this->getSetting('time_separator');
    if ($separator) {
      $summary[] = $this->t('Time separator: %separator', ['%separator' => $separator]);
    }

    return $summary;
  }

}
