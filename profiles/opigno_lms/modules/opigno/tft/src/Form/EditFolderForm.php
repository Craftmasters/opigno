<?php

namespace Drupal\tft\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;

/**
 * Edit a term form.
 */
class EditFolderForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tft_edit_term_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, TermInterface $taxonomy_term = NULL) {
    $name = $taxonomy_term->getName();
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => t("Name"),
      '#required' => TRUE,
      '#default_value' => $name,
      '#weight' => -10,
    ];

    $form['tid'] = [
      '#type' => 'hidden',
      '#value' => $taxonomy_term->id(),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    $cancel_uri = str_replace('%23', '#', $_GET['destination']);
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t("cancel"),
      '#url' => Url::fromUri('internal:' . $cancel_uri),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check for forbidden characters.
    if (strpos($form_state->getValue('name'), ',') !== FALSE
      || strpos($form_state->getValue('name'), '+') !== FALSE) {
      $form_state->setErrorByName('name', $this->t("The following characters are not allowed: ',' (comma) and +"));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Update the term name.
    $term = Term::load($form_state->getValue('tid'));
    $term->setName($form_state->getValue('name'));
    $term->save();

    $this->messenger()->addMessage(t("The folder '@name' was updated.", [
      '@name' => $form_state->getValue('name'),
    ]));
  }

}
