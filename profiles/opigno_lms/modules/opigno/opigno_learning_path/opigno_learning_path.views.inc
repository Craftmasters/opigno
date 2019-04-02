<?php

/**
 * @file
 * Learning path views custom implementation.
 */

/**
 * Implements hook_views_data_alter().
 */
function opigno_learning_path_views_data_alter(array &$data) {
  $data['groups_field_data']['opigno_group_membership'] = [
    'title' => t('Current user group membership'),
    'field' => [
      'title' => t('Current user group membership'),
      'help' => t('Current user group membership status'),
      'id' => 'opigno_group_membership',
    ],
    'filter' => [
      'title' => t('Current user group membership filter'),
      'help' => t('Current user group membership status filter'),
      'id' => 'opigno_group_membership_boolean',
    ],
    'relationship' => [
      'base' => 'group_content_field_data',
      'base field' => 'gid',
      'field' => 'id',
      'id' => 'standard',
      'label' => t('Membership relation'),
    ],
  ];
  $data['groups_field_data']['opigno_lp_take_link'] = [
    'title' => t('Learning path take'),
    'field' => [
      'title' => t('Learning path take link'),
      'help' => t('Learning path take link'),
      'id' => 'opigno_learning_path_take_link',
    ],
  ];
  $data['groups_field_data']['opigno_lp_progress'] = [
    'title' => t('Learning path progress'),
    'field' => [
      'title' => t('Learning path progress'),
      'help' => t('Learning path progress'),
      'id' => 'opigno_learning_path_progress',
    ],
  ];
  $data['group_content']['group_membership_status'] = [
    'title' => t('Status'),
    'field' => [
      'title' => t('Status'),
      'help' => t('Returns group user membership status.'),
      'id' => 'group_membership_status',
    ],
    'filter' => [
      'title' => t('Group Membership Status filter'),
      'help' => t('Group Membership Status filter'),
      'id' => 'opigno_group_membership_status',
    ],
  ];
}
