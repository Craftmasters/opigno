<?php

namespace Drupal\ggroup\Plugin\GroupContentEnabler;

use Drupal\group\Entity\GroupType;
use Drupal\Component\Plugin\Derivative\DeriverBase;

class SubgroupDeriver extends DeriverBase {

  /**
   * {@inheritdoc}.
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach (GroupType::loadMultiple() as $name => $group_type) {
      $label = $group_type->label();

      $this->derivatives[$name] = [
        'entity_bundle' => $name,
        'label' => t('Subgroup (@type)', ['@type' => $label]),
        'description' => t('Adds %type groups to groups both publicly and privately.', ['%type' => $label]),
      ] + $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
