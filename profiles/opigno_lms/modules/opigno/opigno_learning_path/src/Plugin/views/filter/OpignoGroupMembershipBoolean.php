<?php

namespace Drupal\opigno_learning_path\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\BooleanOperator;

/**
 * Filter handler to show trainings filter.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsFilter("opigno_group_membership_boolean")
 */
class OpignoGroupMembershipBoolean extends BooleanOperator {

  public $no_operator = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function operators() {
    // We don't need any operators.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    // Specify available options.
    $this->valueOptions = [
      0 => $this->t('All trainings'),
      1 => $this->t('My trainings'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    if (empty($this->valueOptions)) {
      // Initialize the array of possible values for this filter.
      $this->getValueOptions();
    }
    $exposed = $form_state->get('exposed');
    $form['value'] = [
      '#type' => 'radios',
      '#title' => $this->value_value,
      '#options' => $this->valueOptions,
      '#default_value' => $this->value,
    ];
    if (!empty($this->options['exposed'])) {
      $identifier = $this->options['expose']['identifier'];
      $user_input = $form_state->getUserInput();
      if ($exposed && !isset($user_input[$identifier])) {
        $user_input[$identifier] = $this->value;
        $form_state->setUserInput($user_input);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    // Get current user and take filter value.
    $user = \Drupal::currentUser();
    $value = $this->value;
    if ($value) {
      // Add condition to select only items with membership.
      $this->query->addWhere($this->options['group'], 'group_content_field_data_groups_field_data.entity_id', $user->id(), '=');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function exposedTranslate(&$form, $type) {
    parent::exposedTranslate($form, $type);
    if ($form['#type'] == 'select') {
      $form['#type'] = 'radios';
    }
  }

}
