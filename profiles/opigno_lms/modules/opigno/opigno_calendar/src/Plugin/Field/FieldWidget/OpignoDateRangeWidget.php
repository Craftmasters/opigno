<?php

namespace Drupal\opigno_calendar\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;

/**
 * Plugin implementation of the 'opigno_daterange' widget.
 *
 * @FieldWidget(
 *   id = "opigno_daterange",
 *   label = @Translation("Opigno date and time range"),
 *   field_types = {
 *     "daterange"
 *   }
 * )
 */
class OpignoDateRangeWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(
    FieldItemListInterface $items,
    $delta,
    array $element,
    array &$form,
    FormStateInterface $form_state
  ) {
    $raw_value = $items->getValue();
    if (!empty($raw_value)
      && isset($raw_value[0]['value'])
      && isset($raw_value[0]['end_value'])) {
      $value_str = $raw_value[0]['value'];
      $end_value_str = $raw_value[0]['end_value'];

      $storage_format = $this->getSetting('value_format');
      if (!isset($storage_format)) {
        $storage_format = 'Y-m-d\TH:i:s';
      }

      $storage_timezone_str = $this->getSetting('value_timezone');
      if (!isset($storage_timezone_str)) {
        $storage_timezone_str = 'UTC';
      }
      $storage_timezone = new \DateTimeZone($storage_timezone_str);

      $value = DrupalDateTime::createFromFormat($storage_format, $value_str, $storage_timezone);
      $end_value = DrupalDateTime::createFromFormat($storage_format, $end_value_str, $storage_timezone);

      $local_timezone = new \DateTimeZone(drupal_get_user_timezone());
      $value->setTimezone($local_timezone);
      $end_value->setTimezone($local_timezone);

      $value_date = $value->format('m/d/Y');
      $value_hours = (int) $value->format('H');
      $value_minutes = (int) $value->format('i');

      $end_value_date = $end_value->format('m/d/Y');
      $end_value_hours = (int) $end_value->format('H');
      $end_value_minutes = (int) $end_value->format('i');
    }
    else {
      $value_date = '';
      $value_hours = 0;
      $value_minutes = 0;

      $end_value_date = '';
      $end_value_hours = 0;
      $end_value_minutes = 0;
    }

    $element['value_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['daterange-value-wrapper'],
      ],
    ];

    $element['value_wrapper']['date'] = [
      '#type' => 'textfield',
      '#wrapper_attributes' => [
        'class' => ['daterange-date'],
      ],
      '#size' => 10,
      '#title' => 'Start date',
      '#default_value' => $value_date,
      '#required' => $element['#required'],
      '#element_validate' => [
        [static::class, 'validateDate'],
      ],
    ];

    $hours_options = [];
    for ($i = 0; $i < 24; ++$i) {
      $hours_options[$i] = str_pad($i, 2, '0', STR_PAD_LEFT);
    }

    $element['value_wrapper']['hours'] = [
      '#type' => 'select',
      '#wrapper_attributes' => [
        'class' => ['daterange-hours'],
      ],
      '#options' => $hours_options,
      '#default_value' => $value_hours,
      '#required' => $element['#required'],
      '#suffix' => '<div class = "daterange-separator">:</div>',
    ];

    $minutes_options = [];
    for ($i = 0; $i < 60; ++$i) {
      $minutes_options[$i] = str_pad($i, 2, '0', STR_PAD_LEFT);
    }

    $element['value_wrapper']['minutes'] = [
      '#type' => 'select',
      '#wrapper_attributes' => [
        'class' => ['daterange-minutes'],
      ],
      '#options' => $minutes_options,
      '#default_value' => $value_minutes,
      '#required' => $element['#required'],
      '#suffix' => '<div class = "daterange-separator">hh:mm</div>',
    ];

    $element['end_value_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['daterange-end_value-wrapper'],
      ],
    ];

    $element['end_value_wrapper']['date'] = $element['value_wrapper']['date'];
    $element['end_value_wrapper']['date']['#title'] = 'End date';
    $element['end_value_wrapper']['date']['#default_value'] = $end_value_date;

    $element['end_value_wrapper']['hours'] = $element['value_wrapper']['hours'];
    $element['end_value_wrapper']['hours']['#default_value'] = $end_value_hours;

    $element['end_value_wrapper']['minutes'] = $element['value_wrapper']['minutes'];
    $element['end_value_wrapper']['minutes']['#default_value'] = $end_value_minutes;

    if (isset($this->settings['value_placeholder'])) {
      $element['value_wrapper']['date']['#attributes']['placeholder'] = $this->settings['value_placeholder'];
      $element['end_value_wrapper']['date']['#attributes']['placeholder'] = $this->settings['value_placeholder'];
    }

    $element['#attached']['library'] = [
      'opigno_calendar/datetime',
    ];

    return $element;
  }

  /**
   * Creates datetime from components.
   *
   * @param array $wrapper
   *   Datetime field wrapper.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   Datetime object.
   *
   * @throws \Exception
   */
  public static function createDateTimeFromWrapper(array $wrapper) {
    $display_format = 'm/d/Y H:i:s';

    $raw_date = $wrapper['date'];
    $raw_hours = $wrapper['hours'];
    $raw_minutes = $wrapper['minutes'];

    $date_str = "$raw_date 00:00:00";
    $time_str = "PT${raw_hours}H${raw_minutes}M";

    $date = DrupalDateTime::createFromFormat($display_format, $date_str);
    $date->add(new \DateInterval($time_str));

    return $date;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    if (!empty($form_state->getErrors())) {
      return $values;
    }

    $storage_timezone = new \DateTimeZone('UTC');
    $storage_format = 'Y-m-d\TH:i:s';

    foreach ($values as &$item) {
      if (!empty($item['value_wrapper'])) {
        $date = static::createDateTimeFromWrapper($item['value_wrapper']);
        $item['value'] = $date
          ->setTimezone($storage_timezone)
          ->format($storage_format);
        unset($item['value_wrapper']);
      }

      if (!empty($item['end_value_wrapper'])) {
        $end_date = static::createDateTimeFromWrapper($item['end_value_wrapper']);
        $item['end_value'] = $end_date
          ->setTimezone($storage_timezone)
          ->format($storage_format);
        unset($item['end_value_wrapper']);

        if (isset($date) && $end_date < $date) {
          $form_state->setError($form, $this->t('The end date cannot be before the start date'));
        }
      }
    }
    return $values;
  }

  /**
   * Creates a date object for use as a default value.
   *
   * This will take a default value, apply the proper timezone for display in
   * a widget, and set the default time for date-only fields.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   The UTC default date.
   * @param string $timezone
   *   The timezone to apply.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   A date object for use as a default value in a field widget.
   */
  protected function createDefaultValue(DrupalDateTime $date, $timezone) {
    // The date was created and verified during field_load(), so it is safe to
    // use without further inspection.
    if ($this->getFieldSetting('datetime_type') === DateTimeItem::DATETIME_TYPE_DATE) {
      $date->setDefaultDateTime();
    }
    $date->setTimezone(new \DateTimeZone($timezone));
    return $date;
  }

  /**
   * Validate the color text field.
   */
  public static function validateDate($element, FormStateInterface $form_state) {
    $value = $element['#value'];
    if (!preg_match('/^\d{2}\/\d{2}\/\d{4}$/i', $value)) {
      $form_state->setError($element, t('The date should be in the mm/dd/yyyy format.'));
    }
  }

}
