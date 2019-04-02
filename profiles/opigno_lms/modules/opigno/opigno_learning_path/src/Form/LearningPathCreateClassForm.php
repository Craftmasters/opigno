<?php

namespace Drupal\opigno_learning_path\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\Group;
use Drupal\user\Entity\User;

/**
 * Members create form.
 */
class LearningPathCreateClassForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'learning_path_create_class_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#prefix'] = '<div id="learning_path_create_class_form">';
    $form['#suffix'] = '</div>';

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Give a name to the class'),
      '#placeholder' => $this->t('Enter the class name'),
    ];

    $form['search_label'] = [
      '#type' => 'label',
      '#title' => $this->t('Add learners to the class'),
    ];

    $group = $this->getRequest()->get('group');

    $form['search'] = [
      '#type' => 'textfield',
      '#autocomplete_route_name' => 'opigno_learning_path.membership.add_user_to_class_autocomplete',
      '#autocomplete_route_parameters' => [
        'group' => $group !== NULL ? $group->id() : 0,
      ],
      '#placeholder' => $this->t('Search name or email'),
      '#attributes' => [
        'id' => 'class_users_autocomplete',
      ],
    ];

    $form['select_all'] = [
      '#type' => 'button',
      '#value' => $this->t('Select all'),
      '#submit' => [],
      '#attributes' => [
        'id' => 'select_all',
      ],
    ];

    $form['users'] = [
      '#type' => 'select',
      '#options' => [],
      '#multiple' => TRUE,
      '#attributes' => [
        'id' => 'class_users',
        'multiple' => TRUE,
      ],
      // Allow modifying option with AJAX.
      '#validated' => TRUE,
      '#prefix' => '<div id="learning_path_create_class_form_messages" class="alert-danger"></div>',
    ];

    $form['create'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create new class'),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'btn_create',
        ],
      ],
      '#ajax' => [
        'callback' => [$this, 'submitFormAjax'],
        'event' => 'click',
      ],
    ];

    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $form['#attached']['library'][] = 'opigno_learning_path/create_member';
    return $form;
  }

  /**
   * Handles AJAX form submit.
   */
  public function submitFormAjax(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $form = \Drupal::formBuilder()->getForm('Drupal\opigno_learning_path\Form\LearningPathCreateMemberForm');

    if ($form_state->hasValidateError) {
      $response->addCommand(new HtmlCommand('#learning_path_create_class_form_messages', $this->t('Please select at least one user')));
    }
    else {
      $response->addCommand(new ReplaceCommand('#learning_path_create_class_form', $form));
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('users'))) {
      $form_state->hasValidateError = TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $name = $form_state->getValue('name');
    $users = $form_state->getValue('users');

    // Parse uids.
    $uids = array_map(function ($user) {
      list($type, $id) = explode('_', $user);
      return $id;
    }, $users);

    // Load users.
    $users = User::loadMultiple($uids);

    // Create new class.
    /** @var \Drupal\group\Entity\Group $class */
    $class = Group::create([
      'type' => 'opigno_class',
      'label' => $name,
    ]);
    $class->save();

    // Assign the class to the learning path.
    $group = $this->getRequest()->get('group');
    $group->addContent($class, 'subgroup:opigno_class');

    // Assign users to the class.
    foreach ($users as $user) {
      if (!isset($user)) {
        continue;
      }

      $class->addMember($user);
    }

    // Assign users to the learning path.
    foreach ($users as $user) {
      if (!isset($user)) {
        continue;
      }

      $group->addMember($user);
    }

    $this->messenger()->addMessage(t('New class created'));
  }

}
