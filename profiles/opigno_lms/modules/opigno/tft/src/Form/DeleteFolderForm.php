<?php

namespace Drupal\tft\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;

/**
 * Delete a term form.
 */
class DeleteFolderForm extends FormBase {

  /**
   * Check if the term has no files or child terms.
   */
  protected function check_term_is_deletable($tid) {
    /** @var \Drupal\taxonomy\TermStorage $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $terms = $storage->loadTree('tft_tree', $tid, 1);

    if (!empty($terms)) {
      return FALSE;
    }

    $fids = \Drupal::entityQuery('media')
      ->condition('bundle', 'tft_file')
      ->condition('tft_folder.target_id', $tid)
      ->execute();

    if (!empty($fids)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tft_delete_term_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, TermInterface $taxonomy_term = NULL) {
    $tid = $taxonomy_term->id();
    $name = $taxonomy_term->getName();

    // Check that this term has no child terms or files.
    if (!$this->check_term_is_deletable($tid)) {
      $form[] = [
        '#markup' => $this->t("<em>@name</em> contains files and/or child folders. Move or delete these before deleting this folder.", [
          '@name' => $name,
        ]),
      ];

      $cancel_uri = str_replace('%23', '#', $_GET['destination']);
      $form['actions']['cancel'] = [
        '#type' => 'link',
        '#title' => $this->t("cancel"),
        '#url' => Url::fromUri('internal:' . $cancel_uri),
      ];

      return $form;
    }

    $form['#title'] = $this->t("Are you sure you want to delete the folder @term ?", [
      '@term' => $name,
    ]);

    $form['tid'] = [
      '#type' => 'hidden',
      '#value' => $tid,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete'),
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $term = Term::load($form_state->getValue('tid'));
    $term->delete();
  }

}
