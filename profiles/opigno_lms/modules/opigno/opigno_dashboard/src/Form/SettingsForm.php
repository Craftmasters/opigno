<?php

namespace Drupal\opigno_dashboard\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'opigno_dashboard_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['opigno_dashboard.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('opigno_dashboard.settings')->get('blocks');
    $blocks = \Drupal::service('opigno_dashboard.block')->getAllBlocks();

    $form['blocks'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Name'),
        $this->t('Available'),
        $this->t('Mandatory'),
      ],
    ];

    foreach ($blocks as $id => $block) {
      $form['blocks'][$id]['name'] = [
        '#markup' => $block['admin_label'],
      ];

      $form['blocks'][$id]['available'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Available'),
        '#title_display' => 'invisible',
        '#default_value' => isset($config[$id]['available']) ? $config[$id]['available'] : NULL,
      ];

      $form['blocks'][$id]['mandatory'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Mandatory'),
        '#title_display' => 'invisible',
        '#default_value' => isset($config[$id]['mandatory']) ? $config[$id]['mandatory'] : NULL,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save settings.
    $this->config('opigno_dashboard.settings')
      ->set('blocks', $form_state->getValue('blocks'))
      ->save();

    \Drupal::service('opigno_dashboard.block')->createBlocksInstances($form_state->getValue('blocks'));

    parent::submitForm($form, $form_state);
  }

}
