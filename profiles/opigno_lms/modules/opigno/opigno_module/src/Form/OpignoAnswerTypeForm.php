<?php

namespace Drupal\opigno_module\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class OpignoAnswerTypeForm.
 *
 * @package Drupal\opigno_module\Form
 */
class OpignoAnswerTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $opigno_answer_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $opigno_answer_type->label(),
      '#description' => $this->t("Label for the Answer type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $opigno_answer_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\opigno_module\Entity\OpignoAnswerType::load',
      ],
      '#disabled' => !$opigno_answer_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $opigno_answer_type = $this->entity;
    $status = $opigno_answer_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Answer type.', [
          '%label' => $opigno_answer_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Answer type.', [
          '%label' => $opigno_answer_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($opigno_answer_type->toUrl('collection'));
  }

}
