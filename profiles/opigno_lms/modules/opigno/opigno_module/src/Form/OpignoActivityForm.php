<?php

namespace Drupal\opigno_module\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Activity edit forms.
 *
 * @ingroup opigno_module
 */
class OpignoActivityForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\opigno_module\Entity\OpignoActivity */
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $activity = &$this->entity;
    // Get URL parameters.
    $params = \Drupal::request()->query->all();

    // Save Activity entity.
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        if (isset($params['module_id']) && !empty($params['module_id'] && $params['module_vid'])) {
          $opigno_module = \Drupal::entityTypeManager()->getStorage('opigno_module')->load($params['module_id']);
          $opigno_module_obj = \Drupal::service('opigno_module.opigno_module');
          $save_acitivities = $opigno_module_obj->activitiesToModule([$activity], $opigno_module);
        }
        drupal_set_message($this->t('Created the %label Activity.', [
          '%label' => $activity->label(),
        ]));

        break;

      default:
        drupal_set_message($this->t('Saved the %label Activity.', [
          '%label' => $activity->label(),
        ]));
    }
    $form_state->setRedirect('entity.opigno_activity.canonical', ['opigno_activity' => $activity->id()]);
  }

}
