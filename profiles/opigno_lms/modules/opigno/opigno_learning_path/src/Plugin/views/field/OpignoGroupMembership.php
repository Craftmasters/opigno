<?php

namespace Drupal\opigno_learning_path\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to output boolean indication of current user membership.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("opigno_group_membership")
 */
class OpignoGroupMembership extends FieldPluginBase {

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
    $membership_service = \Drupal::service('group.membership_loader');
    $membership = $membership_service->load($values->_entity, $account);
    return $membership ? (bool) $membership : $membership;
  }

}
