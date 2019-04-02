<?php

namespace Drupal\opigno_ilt;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Provides a list controller for opigno_ilt_result entity.
 */
class ILTResultViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function alterBuild(
    array &$build,
    EntityInterface $entity,
    EntityViewDisplayInterface $display,
    $view_mode
  ) {
    /** @var \Drupal\opigno_ilt\ILTResultInterface $entity */
    $build[] = [
      '#markup' => $this->t('Instructor-Led Training: @ilt', [
        '@ilt' => $entity->getILT()->toLink(),
      ]),
    ];

    $build[] = [
      '#markup' => $this->t('User: @user', [
        '@user' => $entity->getUser()->toLink(),
      ]),
    ];

    $build[] = [
      '#markup' => $this->t('Status: @status', [
        '@status' => $entity->getStatusString(),
      ]),
    ];

    $build[] = [
      '#markup' => $this->t('Score: @score', [
        '@score' => $entity->getScore(),
      ]),
    ];
  }

}
