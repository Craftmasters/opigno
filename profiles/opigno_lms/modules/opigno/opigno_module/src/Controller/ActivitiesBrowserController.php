<?php

namespace Drupal\opigno_module\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\opigno_module\Entity\OpignoModuleInterface;

/**
 * Class ActivitiesBrowserController.
 *
 * @package Drupal\opigno_module\Controller
 */
class ActivitiesBrowserController extends ControllerBase {

  protected $opigno_module;

  /**
   * Page title callback.
   *
   * @param \Drupal\opigno_module\Entity\OpignoModuleInterface $opigno_module
   *   Opigno module entity object.
   *
   * @return string
   *   Opigno module entity label.
   */
  public function formTitleCallback(OpignoModuleInterface $opigno_module) {
    // Return entity label.
    return $opigno_module->label();
  }

  /**
   * {@inheritdoc}
   */
  public function content(OpignoModuleInterface $opigno_module = NULL) {
    $this->opigno_module = $opigno_module;
    $activity_types = \Drupal::entityTypeManager()->getStorage('opigno_activity_type')->loadMultiple();
    $links = [];
    if (!empty($activity_types)) {
      foreach ($activity_types as $type_id => $type_info) {
        /* @todo Add Module version ID instead of the same Module ID. */
        $links[]['value'] = Link::createFromRoute(
          $type_info->label(),
          'entity.opigno_activity.add_form',
          ['opigno_activity_type' => $type_info->id()],
          [
            'query' => [
              'module_id' => $opigno_module->id(),
              'module_vid' => $opigno_module->id(),
              'destination' => Url::createFromRequest(\Drupal::request())->toString(),
            ],
          ])->toRenderable();
      }
    }
    else {
      $links[]['value'] = $this->t('There is no Activity types enabled yet.');
    }
    // Create activities links list.
    $activity_types_list = [
      '#theme' => 'item_list',
      '#items' => $links,
    ];
    // Manage Activities tab.
    $manage_activities = [
      '#type' => 'details',
      '#title' => $this->t('Manage activities'),
    ];
    $manage_activities['create_new_activity'] = [
      '#type' => 'details',
      '#title' => $this->t('Create new activity'),
    ];
    $manage_activities['create_new_activity']['activities_links'] = [
      '#markup' => \Drupal::service('renderer')->render($activity_types_list),
    ];
    $manage_activities['module_activities'] = [
      '#type' => 'details',
      '#title' => $this->t('Activities in this module'),
      '#open' => TRUE,
    ];
    $form = \Drupal::formBuilder()->getForm('Drupal\opigno_module\Form\ModuleActivitiesForm', $opigno_module);
    $manage_activities['module_activities']['activities_list'] = $form;
    // Output activities bank view.
    $activities_bank = [
      '#type' => 'details',
      '#title' => $this->t('Activities bank'),
    ];
    $activities_bank['activities_bank'] = views_embed_view('opigno_activities_bank');
    $build = [
      '#theme_wrappers' => ['vertical_tabs'],
      '#attached' => [
        'library' => [
          'core/drupal.vertical-tabs',
        ],
      ],
    ];
    $build[] = $manage_activities;
    $build[] = $activities_bank;

    return $build;
  }

}
