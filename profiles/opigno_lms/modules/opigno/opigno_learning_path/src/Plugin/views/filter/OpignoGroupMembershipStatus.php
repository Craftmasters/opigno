<?php

namespace Drupal\opigno_learning_path\Plugin\views\filter;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Filters by given list of group membership status options.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("opigno_group_membership_status")
 */
class OpignoGroupMembershipStatus extends InOperator {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->valueTitle = t('Group Membership Status');
    $this->tableAlias = 'opigno_learning_path_group_user_status';
    $this->realField = 'status';
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (isset($this->valueOptions)) {
      return $this->valueOptions;
    }

    // Array keys are used to compare with the table field values.
    return $this->valueOptions = [
      1 => $this->t('Active'),
      2 => $this->t('Pending'),
      3 => $this->t('Blocked'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    parent::query();

    $join = Views::pluginManager('join')
      ->createInstance('standard', [
        'table' => 'opigno_learning_path_group_user_status',
        'field' => 'mid',
        'left_table' => 'group_content_field_data',
        'left_field' => 'id',
        'operator' => '=',
      ]);

    $this->query->addRelationship(
      'opigno_learning_path_group_user_status',
      $join,
      'group_content'
    );
  }

}
