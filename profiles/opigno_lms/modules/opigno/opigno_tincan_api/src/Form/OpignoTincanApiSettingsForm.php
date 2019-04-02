<?php

namespace Drupal\opigno_tincan_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class OpignoTincanApiSettingsForm.
 */
class OpignoTincanApiSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'opigno_tincan_api_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['endpoint'] = [
      '#type' => 'textfield',
      '#title' => 'Endpoint',
      '#default_value' => \Drupal::config('opigno_tincan_api.settings')
        ->get('opigno_tincan_api_endpoint'),
    ];

    $form['username'] = [
      '#type' => 'textfield',
      '#title' => 'User',
      '#default_value' => \Drupal::config('opigno_tincan_api.settings')
        ->get('opigno_tincan_api_username'),
    ];

    $form['password'] = [
      '#type' => 'textfield',
      '#title' => 'Password',
      '#default_value' => \Drupal::config('opigno_tincan_api.settings')
        ->get('opigno_tincan_api_password'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Submit',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $messenger = \Drupal::messenger();

    $config = \Drupal::configFactory()
      ->getEditable('opigno_tincan_api.settings');
    $config->set('opigno_tincan_api_endpoint', $form_state->getValue('endpoint'));
    $config->set('opigno_tincan_api_username', $form_state->getValue('username'));
    $config->set('opigno_tincan_api_password', $form_state->getValue('password'));
    $config->save();
    // TODO(tincan): In D7 here opigno_tincan_api_stats is enabling.
    $messenger->addMessage($this->t('LRS settings saved successfully'));
  }

}
