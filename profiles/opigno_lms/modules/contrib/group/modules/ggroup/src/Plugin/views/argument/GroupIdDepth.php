<?php

namespace Drupal\ggroup\Plugin\views\argument;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views\Plugin\views\argument\ArgumentPluginBase;
use Drupal\views\Views;

/**
 * Argument handler for group content with depth.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("group_id_depth")
 */
class GroupIdDepth extends ArgumentPluginBase implements ContainerFactoryPluginInterface {

  /**
   * @inheritdoc
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['depth'] = ['default' => -1];

    return $options;
  }

  /**
   * @inheritdoc
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['depth'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Depth'),
      '#default_value' => $this->options['depth'],
      '#options' => [
        '-1' => $this->t('Content from target group'),
        '0' => $this->t('Subgroup 1 level'),
        '1' => $this->t('Subgroup 2 level'),
        '2' => $this->t('Subgroup 3 level'),
      ],
      '#description' => $this->t('The depth will match group content with hierarchy. So if you have country group "Germany" with project group "Germany project" as subgroup, and selected "Content from parent group" + "Subgroup 1 level" that will result to filter all group content from "Germany" and "Germany project" groups'),
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);

    $depth_value = $form_state->getValue(['options', 'depth']);
    $form_state->setValue(['options', 'depth'], array_filter($depth_value, function ($value) { return $value !== 0; }));
  }

  /**
   * @inheritdoc
   */
  protected function defaultActions($which = NULL) {
    if ($which) {
      if (in_array($which, ['ignore', 'not found', 'empty', 'default'])) {
        return parent::defaultActions($which);
      }
      return;
    }
    $actions = parent::defaultActions();
    unset($actions['summary asc']);
    unset($actions['summary desc']);
    unset($actions['summary asc by count']);
    unset($actions['summary desc by count']);
    return $actions;
  }

  /**
   * @inheritdoc
   */
  public function query($group_by = FALSE) {
    $table = $this->ensureMyTable();

    $definition = [
      'table' => 'group_graph',
      'field' => 'end_vertex',
      'left_table' => $table,
      'left_field' => 'gid',
    ];

    $join = Views::pluginManager('join')->createInstance('standard', $definition);
    $this->query->addRelationship('group_graph', $join, 'group_graph');

    $group = $this->query->setWhereGroup('OR', 'group_id_depth');

    foreach ($this->options['depth'] as $depth) {
      if ($depth === '-1') {
        $this->query->addWhereExpression($group, "$table.gid = :gid", [':gid' => $this->argument]);
      }
      else {
        $this->query->addWhereExpression(
          $group,
          "group_graph.start_vertex = :gid AND group_graph.hops = :hops_$depth",
          [
            ':gid' => $this->argument,
            ":hops_$depth" => $depth
          ]
        );
      }

    }
  }

}
