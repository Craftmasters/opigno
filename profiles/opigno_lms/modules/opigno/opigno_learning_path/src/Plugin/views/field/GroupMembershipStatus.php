<?php

namespace Drupal\opigno_learning_path\Plugin\views\field;

use Drupal\opigno_learning_path\LearningPathAccess;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler for group user membership status.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("group_membership_status")
 */
class GroupMembershipStatus extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {}

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return LearningPathAccess::getMembershipStatus($values->_entity->id(), TRUE);
  }

}
