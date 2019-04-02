<?php

namespace Drupal\ggroup\Plugin\GroupContentEnabler;

use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\GroupContentEnablerBase;
use Drupal\group\Entity\GroupType;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides a content enabler for subgroups.
 *
 * @GroupContentEnabler(
 *   id = "subgroup",
 *   label = @Translation("Subgroup"),
 *   description = @Translation("Adds groups to groups."),
 *   entity_type_id = "group",
 *   entity_access = TRUE,
 *   pretty_path_key = "group",
 *   deriver = "Drupal\ggroup\Plugin\GroupContentEnabler\SubgroupDeriver"
 * )
 */
class Subgroup extends GroupContentEnablerBase {

  /**
   * Retrieves the group type this plugin supports.
   *
   * @return \Drupal\group\Entity\GroupTypeInterface
   *   The group type this plugin supports.
   */
  protected function getSubgroupType() {
    return GroupType::load($this->getEntityBundle());
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupOperations(GroupInterface $group) {
    $account = \Drupal::currentUser();
    $plugin_id = $this->getPluginId();
    $type = $this->getEntityBundle();
    $operations = [];

    if ($group->hasPermission("create $plugin_id entity", $account)) {
      $route_params = ['group' => $group->id(), 'group_type' => $this->getEntityBundle()];
      $operations["ggroup_create-$type"] = [
        'title' => $this->t('Create @type', ['@type' => $this->getSubgroupType()->label()]),
        'url' => new Url('entity.group_content.subgroup_add_form', $route_params),
        'weight' => 35,
      ];
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissions() {
    $permissions = parent::getPermissions();

    // Override default permission titles and descriptions.
    $plugin_id = $this->getPluginId();
    $type_arg = ['%group_type' => $this->getSubgroupType()->label()];
    $defaults = [
      'title_args' => $type_arg,
      'description' => 'Only applies to %group_type subgroups that belong to this group.',
      'description_args' => $type_arg,
    ];

    $permissions["view $plugin_id entity"] = [
      'title' => '%group_type: View subgroups',
    ] + $defaults;

    $permissions["create $plugin_id entity"] = [
      'title' => '%group_type: Create new subgroups',
      'description' => 'Allows you to create %group_type subgroups that immediately belong to this group.',
      'description_args' => $type_arg,
    ] + $defaults;

    $permissions["update own $plugin_id entity"] = [
      'title' => '%group_type: Edit own subgroups',
    ] + $defaults;

    $permissions["update any $plugin_id entity"] = [
      'title' => '%group_type: Edit any subgroup',
    ] + $defaults;

    $permissions["delete own $plugin_id entity"] = [
      'title' => '%group_type: Delete own subgroups',
    ] + $defaults;

    $permissions["delete any $plugin_id entity"] = [
      'title' => '%group_type: Delete any subgroup',
    ] + $defaults;

    // Use the same title prefix to keep permissions sorted properly.
    $prefix = '%group_type Relationship:';
    $defaults = [
      'title_args' => $type_arg,
      'description' => 'Only applies to %group_type subgroup relations that belong to this group.',
      'description_args' => $type_arg,
    ];

    $permissions["view $plugin_id content"] = [
      'title' => "$prefix View subgroup relations",
    ] + $defaults;

    $permissions["create $plugin_id content"] = [
      'title' => "$prefix Add subgroup relation",
      'description' => 'Allows you to relate an existing %entity_type entity to the group as a subgroup.',
    ] + $defaults;

    $permissions["update own $plugin_id content"] = [
      'title' => "$prefix Edit own subgroup relations",
    ] + $defaults;

    $permissions["update any $plugin_id content"] = [
      'title' => "$prefix Edit any subgroup relation",
    ] + $defaults;

    $permissions["delete own $plugin_id content"] = [
      'title' => "$prefix Delete own subgroup relations",
    ] + $defaults;

    $permissions["delete any $plugin_id content"] = [
      'title' => "$prefix Delete any subgroup relation",
    ] + $defaults;

    return $permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();

    $config['entity_cardinality'] = 1;

    // Default parent_role_mapping.
    if ($this->getGroupType()) {
      $parent_roles = $this->getGroupType()->getRoles();
      foreach ($parent_roles as $role_id => $role) {
        $config['parent_role_mapping'][$role_id] = NULL;
      }
    }

    // Default child_role_mapping.
    if ($this->getSubgroupType()) {
      $child_roles = $this->getSubgroupType()->getRoles();
      foreach ($child_roles as $role_id => $role) {
        $config['child_role_mapping'][$role_id] = NULL;
      }
    }

    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Disable the entity cardinality field as the functionality of this module
    // relies on a cardinality of 1. We don't just hide it, though, to keep a UI
    // that's consistent with other content enabler plugins.
    $info = $this->t("This field has been disabled by the plugin to guarantee the functionality that's expected of it.");
    $form['entity_cardinality']['#disabled'] = TRUE;
    $form['entity_cardinality']['#description'] .= '<br /><em>' . $info . '</em>';

    // We create form field to map parent roles to child roles, and map child
    // roles to parent roles. This allow for permissions/membership to
    // propogate up/down.
    $parent_roles = $this->getGroupType()->getRoles();
    $parent_options = [];
    foreach ($parent_roles as $role_id => $role) {
      $parent_options[$role_id] = $role->label();
    }

    $child_roles = $this->getSubgroupType()->getRoles();
    $child_options = [];
    foreach ($child_roles as $role_id => $role) {
      $child_options[$role_id] = $role->label();
    }

    $form['parent_role_mapping'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Map group roles to subgroup roles to allow group membership and permissions to be inherited by the subgroup.'),
      '#tree' => TRUE,
    ];
    foreach ($parent_options as $roleid => $rolename) {
      $form['parent_role_mapping'][$roleid] = [
        '#type' => 'select',
        '#title' => $rolename,
        '#options' => $child_options,
        "#empty_option" => $this->t('- None -'),
        '#default_value' => $this->configuration['parent_role_mapping'][$roleid],
      ];
    }
    $form['child_role_mapping'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Map subgroup roles to group roles to allow subgroup membership and permissions to be propogated to the group.'),
      '#tree' => TRUE,
    ];
    foreach ($child_options as $roleid => $rolename) {
      $form['child_role_mapping'][$roleid] = [
        '#type' => 'select',
        '#title' => $rolename,
        '#options' => $parent_options,
        "#empty_option" => $this->t('- None -'),
        '#default_value' => $this->configuration['child_role_mapping'][$roleid],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return ['config' => ['group.type.' . $this->getEntityBundle()]];
  }

}
