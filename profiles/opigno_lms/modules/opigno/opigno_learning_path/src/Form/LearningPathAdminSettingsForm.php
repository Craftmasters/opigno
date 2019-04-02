<?php

namespace Drupal\opigno_learning_path\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class LearningPathAdminSettingsForm.
 */
class LearningPathAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'opigno_learning_path.learning_path_settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'learning_path_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $tokens_description = [
      '#markup' => t('Group tokens:') . '
        <ul>
        <li>[group] - group name</li>
        <li>[link] - link to group</li>
        <li>[user] - user account name</li>
        <li>[user-role] - user role in group</li>
        <li>[user-status] - user current status</li>
        </ul>',
    ];

    $config = $this->config('opigno_learning_path.learning_path_settings');

    $form['opigno_learning_path_mail'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('E-mail notifications settings'),
    ];
    $form['opigno_learning_path_mail']['opigno_learning_path_notify_admin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Notify administrators'),
      '#description' => $this->t('If checked administrators will be notified on trainings updates.'),
      '#default_value' => $config->get('opigno_learning_path_notify_admin'),
    ];
    $form['opigno_learning_path_mail']['opigno_learning_path_notify_admin_mails'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Administrators email list.'),
      '#default_value' => $config->get('opigno_learning_path_notify_admin_mails'),
    ];
    $form['opigno_learning_path_mail']['opigno_learning_path_notify_admin_user_subscribed'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message to admins when user subscribed/updated/removed'),
      '#default_value' => $config->get('opigno_learning_path_notify_admin_user_subscribed'),
    ];
    $form['opigno_learning_path_mail']['opigno_learning_path_notify_admin_user_approval'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message to admins when user awaiting approval'),
      '#default_value' => $config->get('opigno_learning_path_notify_admin_user_approval'),
    ];
    $form['opigno_learning_path_mail']['opigno_learning_path_notify_admin_user_blocked'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message to admins when user blocked'),
      '#default_value' => $config->get('opigno_learning_path_notify_admin_user_blocked'),
    ];
    $form['opigno_learning_path_mail']['opigno_learning_path_notify_group_tokens_1'] = $tokens_description;
    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $form['opigno_learning_path_mail']['token_tree_1'] = [
        '#theme' => 'token_tree_link',
        '#show_restricted' => TRUE,
      ];
    }

    $form['opigno_learning_path_mail']['opigno_learning_path_notify_users'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Notify users'),
      '#description' => $this->t('If checked users will be notified on training updates.'),
      '#default_value' => $config->get('opigno_learning_path_notify_users'),
    ];
    $form['opigno_learning_path_mail']['opigno_learning_path_notify_user_user_subscribed'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message to user when he subscribed/updated/removed'),
      '#default_value' => $config->get('opigno_learning_path_notify_user_user_subscribed'),
    ];
    $form['opigno_learning_path_mail']['opigno_learning_path_notify_user_user_approval'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message to user when he awaiting approval'),
      '#default_value' => $config->get('opigno_learning_path_notify_user_user_approval'),
    ];
    $form['opigno_learning_path_mail']['opigno_learning_path_notify_user_user_blocked'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message to user when he blocked'),
      '#default_value' => $config->get('opigno_learning_path_notify_user_user_blocked'),
    ];
    $form['opigno_learning_path_mail']['opigno_learning_path_notify_group_tokens_2'] = $tokens_description;
    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $form['opigno_learning_path_mail']['token_tree_2'] = [
        '#theme' => 'token_tree_link',
        '#show_restricted' => TRUE,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('opigno_learning_path.learning_path_settings')
      ->set('opigno_learning_path_notify_admin', $form_state->getValue('opigno_learning_path_notify_admin'))
      ->set('opigno_learning_path_notify_admin_mails', $form_state->getValue('opigno_learning_path_notify_admin_mails'))
      ->set('opigno_learning_path_notify_users', $form_state->getValue('opigno_learning_path_notify_users'))
      ->set('opigno_learning_path_notify_admin_user_subscribed', $form_state->getValue('opigno_learning_path_notify_admin_user_subscribed'))
      ->set('opigno_learning_path_notify_admin_user_approval', $form_state->getValue('opigno_learning_path_notify_admin_user_approval'))
      ->set('opigno_learning_path_notify_admin_user_blocked', $form_state->getValue('opigno_learning_path_notify_admin_user_blocked'))
      ->set('opigno_learning_path_notify_user_user_subscribed', $form_state->getValue('opigno_learning_path_notify_user_user_subscribed'))
      ->set('opigno_learning_path_notify_user_user_approval', $form_state->getValue('opigno_learning_path_notify_user_user_approval'))
      ->set('opigno_learning_path_notify_user_user_blocked', $form_state->getValue('opigno_learning_path_notify_user_user_blocked'))
      ->save();
  }

}
