<?php

namespace Drupal\opigno_learning_path\Plugin\views\field;

use Drupal\opigno_learning_path\Entity\LatestActivity;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to output user progress for current LP.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("opigno_learning_path_progress")
 */
class OpignoLearningPathProgress extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $account = \Drupal::currentUser();
    // Get an entity object.
    $entity = $values->_entity;
    $group = $entity instanceof LatestActivity ? $entity->getTraining() : $entity;
    if (!is_null($group)) {
      $group_progress = opigno_learning_path_progress($group->id(), $account->id());
      return round(100 * $group_progress) . '%';
    };

    return '';
  }

}
