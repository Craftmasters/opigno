<?php

namespace Drupal\opigno_module\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class OpignoActivityTypeForm.
 *
 * @package Drupal\opigno_module\Form
 */
class OpignoActivityTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $default_entity_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $default_entity_type->label(),
      '#description' => $this->t("Label for the Activity type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $default_entity_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\opigno_module\Entity\OpignoActivityType.php::load',
      ],
      '#disabled' => !$default_entity_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */
    $form['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#default_value' => $default_entity_type->get('description'),
      '#description' => $this->t('Description of the Activity type.'),
    ];

    $form['summary'] = [
      '#title' => $this->t('Summary'),
      '#type' => 'textarea',
      '#default_value' => $default_entity_type->get('summary'),
      '#description' => $this->t('Summary for the Activity type.'),
    ];

    $form['image'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Image'),
      "#default_value" => $default_entity_type->get('image'),
      '#upload_validators' => [
        'file_validate_extensions' => ['gif png jpg jpeg'],
        'file_validate_size' => [25600000],
      ],
      '#upload_location' => 'public://opigno-activity-type',
      '#preview_image_style' => 'medium',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $default_entity_type = $this->entity;
    $status = $default_entity_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Activity type.', [
          '%label' => $default_entity_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Activity type.', [
          '%label' => $default_entity_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($default_entity_type->toUrl('collection'));
  }

}
