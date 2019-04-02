<?php

namespace Drupal\opigno_module\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\opigno_module\Entity\OpignoModuleInterface;

/**
 * Class ModuleActivitiesForm.
 *
 * @package Drupal\opigno_module\Form
 */
class ModuleActivitiesForm extends FormBase {

  protected $opigno_module;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'opigno_module_activities_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, OpignoModuleInterface $opigno_module = NULL) {
    $this->opigno_module = $opigno_module;
    $activity_types = \Drupal::entityTypeManager()->getStorage('opigno_activity_type')->loadMultiple();
    // Current module activities list.
    $form['activities_list'] = [
      '#type' => 'table',
      '#id' => 'activities-list-table',
      '#sticky' => TRUE,
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'activities-list-order-weight',
        ],
      ],
      '#weight' => 5,
    ];
    $activities = $opigno_module->getModuleActivities();
    if (empty($activities)) {
      $form['activities_list'] = [
        '#type' => 'markup',
        '#markup' => $this->t('There are currently no activities in this Module. Assign existing activities by using the activities bank below. You can also use the links above to create new activities.'),
      ];
    }
    else {
      // Build random activities additional fields.
      if ($opigno_module->getRandomization() == 2) {
        $form['random_activities'] = [
          '#type' => 'details',
          '#title' => $this->t('Settings for random activities'),
          '#weight' => 1,
          '#open' => TRUE,
        ];
        $form['random_activities']['random_count'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Number of random activities'),
          '#default_value' => $opigno_module->getRandomActivitiesCount() ? $opigno_module->getRandomActivitiesCount() : 0,
          '#description' => $this->t('The number of activities to be randomly selected each time someone takes this module'),
        ];
        $form['random_activities']['max_score'] = [
          '#type' => 'textfield',
          '#default_value' => $opigno_module->getRandomActivityScore() ? $opigno_module->getRandomActivityScore() : 1,
          '#title' => $this->t('Max score for each random activity'),
        ];
      }
      $this->activitiesToForm($form, $activities, $opigno_module, $activity_types);
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
        '#submit' => [[$this, 'activitiesListSubmit']],
        '#weight' => 100,
      ];
    }

    return $form;
  }

  /**
   * Build Module activities list table.
   */
  private function activitiesToForm(array &$form, array $activities, OpignoModuleInterface $opigno_module, array $activity_types) {
    $current_user = \Drupal::currentUser();
    // Build activities table headers.
    $headers = [
      $this->t('Question'),
      $this->t('Type'),
      $this->t('Actions'),
      $this->t('Max score'),
      [
        'data' => $this->t('Delete'),
        'class' => ['checkbox'],
      ],
      $this->t('Weight'),
    ];
    $form['activities_list']['#header'] = $headers;
    $activities_list = &$form['activities_list'];
    foreach ($activities as $activity) {
      $activity_id = $activity->id . '-' . $activity->vid;
      $activities_list[$activity_id]['#attributes']['class'][] = 'draggable';
      $activities_list[$activity_id]['#weight'] = $activity->weight;
      /* @todo Check for view activity outside module permission */
      // Table title field.
      $activities_list[$activity_id]['title'] = Link::createFromRoute(
        $activity->name,
        'entity.opigno_activity.canonical',
        ['opigno_activity' => $activity->id]
      )->toRenderable();
      // Output Activity type.
      $activities_list[$activity_id]['type'] = [
        '#plain_text' => !empty($activity_types[$activity->type]) ? $activity_types[$activity->type]->label() : '',
      ];
      // Check for permission and output activity edit link.
      if ($current_user->hasPermission('edit activity entities')) {
        $activities_list[$activity_id]['actions'] = Link::createFromRoute(
          $this->t('Edit'),
          'entity.opigno_activity.edit_form',
          ['opigno_activity' => $activity->id],
          [
            'query' => [
              'destination' => Url::createFromRequest(\Drupal::request())->toString(),
            ],
          ]
        )->toRenderable();
      }
      else {
        $activities_list[$activity_id]['actions'] = [
          '#plain_text' => '',
        ];
      }
      $activities_list[$activity_id]['max_score'] = [
        '#type' => 'textfield',
        '#default_value' => is_numeric($activity->max_score) ? $activity->max_score : 0,
        '#element_validate' => [[$this, 'maxScoreValidate']],
      ];
      $activities_list[$activity_id]['delete'] = [
        '#type' => 'checkbox',
        '#default_value' => 0,
        '#attributes' => ['class' => ['checkbox']],
        '#wrapper_attributes' => [
          'class' => ['checkbox'],
        ],
      ];
      $activities_list[$activity_id]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight'),
        '#title_display' => 'invisible',
        '#default_value' => $activity->weight,
        // Classify the weight element for #tabledrag.
        '#attributes' => ['class' => ['activities-list-order-weight']],
      ];
    }
  }

  /**
   * Validates max score.
   */
  public function maxScoreValidate($element, FormStateInterface $form_state, $form) {
    if ($element['#value'] > 100) {
      $form_state
        ->setError($element, $this->t('Max score must be equal or less then 100'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function activitiesListSubmit(array &$form, FormStateInterface $form_state) {
    /* @var $db_connection \Drupal\Core\Database\Connection */
    $db_connection = \Drupal::service('database');
    /* @var $opigno_module \Drupal\opigno_module\Entity\OpignoModule */
    $opigno_module = $this->opigno_module;
    $randomization = $opigno_module->getRandomization();
    // Display result.
    $values = $form_state->getValues();
    $deleted_activities = [];
    $exist_activities = [];
    if (!empty($values['activities_list'])) {
      foreach ($values['activities_list'] as $key => $activity) {
        list($activity_id, $activity_vid) = explode('-', $key, 2);
        $activity_id = (int) $activity_id;
        $activity_vid = (int) $activity_vid;
        $max_score = $activity['max_score'];
        $item = [
          'parent_id' => $opigno_module->id(),
          'parent_vid' => $opigno_module->getRevisionId(),
          'child_id' => $activity_id,
          'child_vid' => $activity_vid,
          'max_score' => $max_score,
          'weight' => $activity['weight'],
        ];
        if ($activity['delete'] == 1) {
          $deleted_activities[$key] = $item;
        }
        else {
          $exist_activities[$key] = $item;
        }
      }
      // Delete activities that were selected to delete.
      /* @todo Replace it with methods from OpignoModule class */
      if (!empty($deleted_activities)) {
        /* @todo Output some information regarding deleted items */
        foreach ($deleted_activities as $deleted_activity) {
          $delete_query = $db_connection->delete('opigno_module_relationship');
          $delete_query->condition('parent_id', $deleted_activity['parent_id']);
          $delete_query->condition('parent_vid', $deleted_activity['parent_vid']);
          $delete_query->condition('child_id', $deleted_activity['child_id']);
          $delete_query->condition('child_vid', $deleted_activity['child_vid']);
          $delete_query->execute();
        }
      }
      // Update activities fields.
      if (!empty($exist_activities)) {
        foreach ($exist_activities as $exist_activity) {
          $db_connection->merge('opigno_module_relationship')
            ->keys([
              'parent_id' => $exist_activity['parent_id'],
              'parent_vid' => $exist_activity['parent_vid'],
              'child_id' => $exist_activity['child_id'],
              'child_vid' => $exist_activity['child_vid'],
            ])
            ->fields([
              'max_score' => $exist_activity['max_score'],
              'weight' => $exist_activity['weight'],
            ])
            ->execute();
        }
        // Update Module randomization options.
        if ($randomization == 2) {
          // Update random activities count and score per random activity.
          if (isset($values['max_score']) && isset($values['random_count'])) {
            $opigno_module->setRandomActivityScore($values['max_score']);
            $random_number = $values['random_count'];
            $opigno_module->setRandomActivitiesCount($random_number);
            $opigno_module->save();
          }
        }
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
    foreach ($form_state->getValues() as $key => $value) {
      drupal_set_message($key . ': ' . $value);
    }

  }

}
