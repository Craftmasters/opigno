<?php

namespace Drupal\datetime\Plugin\views\argument;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\views\FieldAPIHandlerTrait;
use Drupal\views\Plugin\views\argument\Date as NumericDate;

/**
 * Abstract argument handler for dates.
 *
 * Adds an option to set a default argument based on the current date.
 *
 * Definitions terms:
 * - many to one: If true, the "many to one" helper will be used.
 * - invalid input: A string to give to the user for obviously invalid input.
 *                  This is deprecated in favor of argument validators.
 *
 * @see \Drupal\views\ManyTonOneHelper
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("datetime")
 */
class Date extends NumericDate {

  use FieldAPIHandlerTrait;

  /**
   * Determines if the timezone offset is calculated.
   *
   * @var bool
   */
  protected $calculateOffset = TRUE;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $route_match);

    $definition = $this->getFieldStorageDefinition();
    if ($definition->getSetting('datetime_type') === DateTimeItem::DATETIME_TYPE_DATE) {
      // Timezone offset calculation is not applicable to dates that are stored
      // as date-only.
      $this->calculateOffset = FALSE;
    }
  }


  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['operator'] = ['default' => '='];
    $options['formula'] = [
      'contains' => [
        'operator' => ['default' => '='],
      ],
    ];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['formula'] = [
      '#type' => 'details',
      '#title' => $this->t('Formula'),
    ];
    $form['formula']['operator'] = [
      '#type' => 'select',
      '#title' => $this->t('Operator'),
      '#options' => [
        '<' => $this->t('Is less than'),
        '<=' => $this->t('Is less than or equal to'),
        '=' => $this->t('Is equal to'),
        '>=' => $this->t('Is greater than or equal to'),
        '>' => $this->t('Is greater than'),
      ],
      '#default_value' => $this->options['formula']['operator'],
      '#required' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   *
   * This identical to the original query method except for the configurable
   * operator.
   */
  public function query($group_by = FALSE) {
    $this->ensureMyTable();
    // Now that our table is secure, get our formula.
    $placeholder = $this->placeholder();
    $formula = $this->getFormula() . " {$this->options['formula']['operator']} " . $placeholder;
    $placeholders = [
      $placeholder => $this->argument,
    ];
    $this->query->addWhere(0, $formula, $placeholders, 'formula');
  }

  /**
   * {@inheritdoc}
   */
  public function getDateField() {
    // Use string date storage/formatting since datetime fields are stored as
    // strings rather than UNIX timestamps.
    return $this->query->getDateField("$this->tableAlias.$this->realField", TRUE, $this->calculateOffset);
  }

  /**
   * {@inheritdoc}
   */
  public function getDateFormat($format) {
    // Pass in the string-field option.
    return $this->query->getDateFormat($this->getDateField(), $format, TRUE);
  }

}
