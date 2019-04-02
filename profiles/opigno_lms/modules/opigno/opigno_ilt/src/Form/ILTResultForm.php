<?php

namespace Drupal\opigno_ilt\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for creating/editing a opigno_ilt_result entity.
 */
class ILTResultForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'opigno_ilt_create_result_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    /** @var \Drupal\opigno_ilt\ILTResultInterface $entity */
    $entity = $this->entity;

    $form['opigno_ilt'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'opigno_ilt',
      '#title' => $this->t('Instructor-Led Training'),
      '#default_value' => $entity->getILT(),
      '#required' => TRUE,
    ];

    $form['user_id'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('User'),
      '#target_type' => 'user',
      '#default_value' => $entity->getUser(),
      '#required' => TRUE,
    ];

    $form['status'] = [
      '#type' => 'select',
      '#title' => $this->t('Status'),
      '#options' => [
        0 => $this->t('Absent'),
        1 => $this->t('Attended'),
      ],
      '#default_value' => $entity->getStatus(),
    ];

    $form['score'] = [
      '#type' => 'number',
      '#title' => $this->t('Score'),
      '#min' => 0,
      '#max' => 100,
      '#step' => 1,
      '#default_value' => $entity->getScore(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\opigno_ilt\ILTResultInterface $entity */
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    // Set status message.
    $link = $entity->toLink()->toString();
    if ($status == SAVED_UPDATED) {
      $message = $this->t('The Instructor-Led Training Result %result has been updated.', [
        '%result' => $link,
      ]);
    }
    else {
      $message = $this->t('The Instructor-Led Training Result Workspace %result has been created.', [
        '%result' => $link,
      ]);
    }
    $this->messenger()->addMessage($message);

    // Set redirect.
    $form_state->setRedirect('entity.opigno_ilt_result.canonical', [
      'opigno_ilt_result' => $entity->id(),
    ]);
    return $status;
  }

}
