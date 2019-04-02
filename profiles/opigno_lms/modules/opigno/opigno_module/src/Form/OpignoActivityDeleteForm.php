<?php

namespace Drupal\opigno_module\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for deleting Activity entities.
 *
 * @ingroup opigno_module
 */
class OpignoActivityDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();
    if (isset($storage['activity_answers'])) {
      // Activity has answers.
      $form_state->setRedirectUrl($this->getRedirectUrl());
      $this->messenger()->addMessage($storage['activity_message'], 'warning');
    }
    else {
      // Normal activity deleting.
      parent::submitForm($form, $form_state);
    }
  }

}
