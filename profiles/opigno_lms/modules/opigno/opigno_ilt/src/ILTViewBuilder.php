<?php

namespace Drupal\opigno_ilt;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Link;

/**
 * Provides a list controller for opigno_ilt entity.
 */
class ILTViewBuilder extends EntityViewBuilder {

  /**
   * Returns render array for the navigation.
   *
   * @param \Drupal\opigno_ilt\ILTInterface $entity
   *   ILT interface.
   *
   * @return array
   *   Render array.
   */
  protected function buildNavigation(ILTInterface $entity) {
    $gid = $entity->getTrainingId();
    if (empty($gid)) {
      return [];
    }

    $actions = [];
    $actions['form-actions'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['form-actions'],
        'id' => 'edit-actions',
      ],
      '#title' => 'test',
    ];

    $title = 'Back to training homepage';
    $route = 'entity.group.canonical';
    $route_params = [
      'group' => $gid,
    ];
    $options = [
      'attributes' => [
        'class' => [
          'btn',
          'btn-success',
        ],
        'id' => 'edit-submit',
      ],
    ];

    $actions['form-actions'][] = Link::createFromRoute(
      $title,
      $route,
      $route_params,
      $options
    )->toRenderable();

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterBuild(
    array &$build,
    EntityInterface $entity,
    EntityViewDisplayInterface $display,
    $view_mode
  ) {
    /** @var \Drupal\opigno_ilt\ILTInterface $entity */
    $build[] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $entity->getTitle(),
    ];

    $start_date = $entity->getStartDate();
    $end_date = $entity->getEndDate();
    if (isset($start_date) && isset($end_date)) {
      $start_date = DrupalDateTime::createFromFormat(
        DrupalDateTime::FORMAT,
        $start_date
      );
      $end_date = DrupalDateTime::createFromFormat(
        DrupalDateTime::FORMAT,
        $end_date
      );

      $build[] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('Date: @start to @end', [
          '@start' => $start_date->format('jS F Y - g:i A'),
          '@end' => $end_date->format('g:i A'),
        ]),
      ];
    }

    $build[] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Place: @place', [
        '@place' => $entity->getPlace(),
      ]),
    ];

    $build[] = $this->buildNavigation($entity);
  }

}
