<?php

namespace Drupal\opigno_module\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class OpignoModuleSettingsForm.
 *
 * @package Drupal\opigno_module\Form
 *
 * @ingroup opigno_module
 */
class OpignoModuleSettingsForm extends ConfigFormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'OpignoModule_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'opigno_module.settings',
    ];
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('opigno_module.settings')
      ->set('description', $form_state->getValue('description'))
      ->set('availability_closed_message', $form_state->getValue('availability_closed_message'))
      ->set('availability_unavailable_message', $form_state->getValue('availability_unavailable_message'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Defines the settings form for Module entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['OpignoModule_settings']['#markup'] = 'Settings form for Module entities. Manage field settings here.';

    $config = $this->config('opigno_module.settings');

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $config->get('description'),
    ];

    $form['availability_options'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Availability options'),
    );

    $form['availability_options']['availability_closed_message'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Module closed message'),
      '#default_value' => $config->get('availability_closed_message'),
    );

    $form['availability_options']['availability_unavailable_message'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Module not yet opened message'),
      '#default_value' => $config->get('availability_unavailable_message'),
    );

    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $form['availability_options']['token_tree'] = [
        '#theme' => 'token_tree_link',
        '#show_restricted' => TRUE,
        '#token_types' => array('opigno_module'),
      ];
    }

    $form['availability_options'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Availability options'),
    );

    $form['availability_options']['availability_closed_message'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Module closed message'),
      '#default_value' => $config->get('availability_closed_message'),
    );

    $form['availability_options']['availability_unavailable_message'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Module not yet opened message'),
      '#default_value' => $config->get('availability_unavailable_message'),
    );

    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $form['availability_options']['token_tree'] = [
        '#theme' => 'token_tree_link',
        '#show_restricted' => TRUE,
        '#token_types' => array('opigno_module'),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

}
