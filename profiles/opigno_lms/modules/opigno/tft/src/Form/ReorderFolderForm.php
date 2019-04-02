<?php

namespace Drupal\tft\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;

/**
 * Term reordering form.
 */
class ReorderFolderForm extends FormBase {

  /**
   * Form helper. Flattens the terms tree and creates the form elements.
   *
   * @param array $tree
   *   The current tree level to be rendered.
   * @param mixed $form
   *   A reference to the form array.
   * @param int $root_depth
   *   The depth of the root term.
   */
  protected function manage_folders_form(array $tree, &$form, $root_depth = 0) {
    foreach ($tree as $data) {
      $data['depth'] = isset($data['tid'])
        ? _tft_get_depth($data['tid']) - $root_depth
        : _tft_get_depth($data['parent']) - $root_depth + 1;

      $key = 'term-' . $data['tid'];
      $form['table'][$key] = [];
      $form['table'][$key]['name'] = [
        '#type' => 'textfield',
        '#default_value' => $data['name'],
        '#maxlength' => 255,
        '#required' => TRUE,
        '#size' => '',
      ];

      $form['table'][$key]['parent'] = [
        '#type' => 'textfield',
        '#default_value' => $data['parent'],
        '#size' => 6,
        '#attributes' => [
          'class' => ['taxonomy_term_hierarchy-parent'],
        ],
      ];

      $form['table'][$key]['id'] = [
        '#type' => 'hidden',
        '#default_value' => $data['tid'],
        '#attributes' => [
          'class' => ['taxonomy_term_hierarchy-tid'],
        ],
      ];

      $form['table'][$key]['type'] = [
        '#type' => 'hidden',
        '#value' => isset($data['type']) ? $data['type'] : 'term',
      ];

      $form['table'][$key]['depth'] = [
        '#type' => 'value',
        '#value' => $data['depth'],
      ];

      $form['table'][$key]['weight'] = [
        '#type' => 'weight',
        '#delta' => 50,
        '#default_value' => $data['weight'],
        '#attributes' => [
          'class' => ['taxonomy_term_hierarchy-weight'],
        ],
      ];

      if (isset($data['children'])) {
        $this->manage_folders_form($data['children'], $form, $root_depth);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tft_reorder_terms_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, TermInterface $taxonomy_term = NULL) {
    $tid = $taxonomy_term->id();
    $tree = _tft_folder_tree($tid);
    $root_depth = _tft_get_depth($tid) + 1;

    $form['#attributes'] = [
      'id' => 'tft-manage-folders-form',
    ];
    $form['#tid'] = $tid;
    $form['#use_hierarchy'] = TRUE;
    $form['#use_weight'] = TRUE;

    $this->manage_folders_form($tree, $form, $root_depth);

    $data = array_filter($form['table'], function ($item, $key) {
      return is_array($item) && preg_match('/term-[0-9]+/', $key);
    }, ARRAY_FILTER_USE_BOTH);

    $form['table'] = [
      '#type' => 'table',
      '#attributes' => [
        'id' => 'tft-outline',
      ],
      '#header' => [
        $this->t('Name'),
        $this->t('Parent'),
        $this->t('Weight'),
      ],
      '#tabledrag' => [
        [
          'action' => 'match',
          'relationship' => 'parent',
          'group' => 'taxonomy_term_hierarchy-parent',
          'subgroup' => 'taxonomy_term_hierarchy-parent',
          'source' => 'taxonomy_term_hierarchy-tid',
        ],
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'taxonomy_term_hierarchy-weight',
        ],
      ],
      '#tree' => TRUE,
      '#weight' => 1,
    ];

    foreach ($data as $key => $item) {
      $form['table'][$key]['#attributes']['class'][] = 'draggable';
      $form['table'][$key]['#weight'] = $item['weight']['#default_value'];

      $form['table'][$key]['name'] = [
        [
          '#theme' => 'indentation',
          '#size' => isset($item['depth']['#value']) ? $item['depth']['#value'] : 0,
        ],
        [
          '#theme' => 'image',
          '#uri' => drupal_get_path('module', 'tft') . '/img/folder.png',
          '#attributes' => [
            'class' => 'tft-admin-folder-content-item',
          ],
        ],
        $item['name'],
      ];

      $form['table'][$key]['parent'] = [
        $item['parent'],
        $item['id'],
        $item['type'],
      ];

      $form['table'][$key]['weight'] = [
        $item['weight'],
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save changes'),
    ];

    $form[] = [
      // Add CSS and Javascript files.
      '#attached' => [
        'library' => [
          'tft/tft',
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('table') as $key => $item) {
      $name = $item['name'][2];
      $pid = $item['parent'][0];
      $tid = $item['parent'][1];
      $weight = $item['weight'][0];

      $term = Term::load($tid);
      $term->set('parent', ['target_id' => $pid]);
      $term->setName($name);
      $term->setWeight($weight);
      $term->save();
    }
  }

}
