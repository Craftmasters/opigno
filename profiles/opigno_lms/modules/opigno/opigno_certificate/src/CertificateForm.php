<?php

namespace Drupal\opigno_certificate;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for the opigno_certificate form.
 */
class CertificateForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $opigno_certificate = $this->entity;
    $insert = $opigno_certificate->isNew();
    $opigno_certificate->save();
    /*$opigno_certificate_link = $opigno_certificate->link($this->t('View'));*/
    $t_args = ['%label' => $opigno_certificate->link($opigno_certificate->label())];

    if ($insert) {
      drupal_set_message($this->t('Certificate %label has been created.', $t_args));
    }
    else {
      drupal_set_message($this->t('Certificate %label has been updated.', $t_args));
    }

    if ($opigno_certificate->id()) {
      if ($opigno_certificate->access('view')) {
        $form_state->setRedirect(
          'entity.opigno_certificate.canonical',
          ['opigno_certificate' => $opigno_certificate->id()]
        );
      }
      else {
        $form_state->setRedirect('<front>');
      }

    }
  }

}
