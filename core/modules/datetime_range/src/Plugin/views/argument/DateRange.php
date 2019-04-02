<?php

namespace Drupal\datetime_range\Plugin\views\argument;

use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\views\argument\Date;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;

/**
 * A date range argument handler.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("datetime_range")
 */
class DateRange extends Date {

  /**
   * The start date view field name.
   *
   * @var string
   */
  protected $startDateField;

  /**
   * The end date view field name.
   *
   * @var string
   */
  protected $endDateField;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    // This plugin is used for both the start date and the end date view field,
    // so we need to make sure both know the two view field names.
    $this->startDateField = str_replace('_end_value', '_value', $this->realField);
    $this->endDateField = str_replace('_value', '_end_value', $this->startDateField);
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['formula']['operator']['#options'] += [
      'range_intersects' => $this->t('Intersects range'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    if (strpos($this->options['formula']['operator'], 'range_') !== 0) {
      parent::query($group_by);
      return;
    }

    $this->ensureMyTable();

    $placeholder = $this->placeholder();
    $start_date_placeholder = $this->getStartDateQueryElement($placeholder);
    $end_date_placeholder = $this->getEndDateQueryElement($start_date_placeholder);

    $formula = $this->getFormula();
    $start_date_formula = $this->getStartDateQueryElement($formula);
    $end_date_formula = $this->getEndDateQueryElement($start_date_formula);

    $condition = '';
    switch ($this->options['formula']['operator']) {
      case 'range_intersects':
        $condition = "($end_date_formula >= $start_date_placeholder AND $start_date_formula <= $end_date_placeholder)";
        break;
    }

    $placeholders = [
      $start_date_placeholder => $this->argument,
      $end_date_placeholder => $this->argument,
    ];

    /** @var \Drupal\views\Plugin\views\query\Sql $query */
    $query = $this->query;
    $query->addWhere(0, $condition, $placeholders, 'formula');
  }

  /**
   * Converts a query element into the corresponding start date element.
   *
   * @param string $query_element
   *   A portion of a SQL query prepared statement.
   *
   * @return string
   *   The corresponding start date element.
   */
  protected function getStartDateQueryElement($query_element) {
    return str_replace($this->endDateField, $this->startDateField, $query_element);
  }

  /**
   * Converts a query element into the corresponding end date element.
   *
   * @param string $query_element
   *   A portion of a SQL query prepared statement.
   *
   * @return string
   *   The corresponding end date element.
   */
  protected function getEndDateQueryElement($query_element) {
    return str_replace($this->startDateField, $this->endDateField, $query_element);
  }

}
