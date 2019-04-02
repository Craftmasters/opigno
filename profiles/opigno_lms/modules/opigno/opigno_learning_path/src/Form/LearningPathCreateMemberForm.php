<?php

namespace Drupal\opigno_learning_path\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;

/**
 * Members create form.
 */
class LearningPathCreateMemberForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'learning_path_create_member_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#prefix'] = '<div id="learning_path_create_member_form">';
    $form['#suffix'] = '</div>';

    $form['status_messages'] = [
      '#type' => 'status_messages',
    ];

    $group = $this->getRequest()->get('group');
    $args = [
      'group' => $group !== NULL ? $group->id() : 0,
    ];

    $form['create_user'] = Link::createFromRoute(
      $this->t('Create new users'),
      'opigno_learning_path.membership.create_user',
      $args
    )->toRenderable();
    $form['create_user']['#attributes']['class'][] = 'btn_create';
    $form['create_user']['#attributes']['class'][] = 'use-ajax';
    $form['create_user']['#attributes']['data-dialog-type'] = 'modal';

    $form['create_class'] = Link::createFromRoute(
      $this->t('Create a new class'),
      'opigno_learning_path.membership.create_class',
      $args
    )->toRenderable();
    $form['create_class']['#attributes']['class'][] = 'btn_create';
    $form['create_class']['#attributes']['class'][] = 'use-ajax';
    $form['create_class']['#attributes']['data-dialog-type'] = 'modal';

    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $form['#attached']['library'][] = 'opigno_learning_path/create_member';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
