<?php

namespace Drupal\opigno_module_group\Plugin\GroupContentEnabler;

use Drupal\group\Plugin\GroupContentEnablerBase;

/**
 * Allows Opigno Module content to be added to groups.
 *
 * @GroupContentEnabler(
 *   id = "opigno_module_group",
 *   label = @Translation("Opigno Module Group"),
 *   description = @Translation("Adds opigno module entity to groups ."),
 *   entity_type_id = "opigno_module",
 *   pretty_path_key = "opigno-module",
 *   reference_label = @Translation("Module"),
 *   reference_description = @Translation("The name of the module you want to add")
 * )
 */
class OpignoModuleGroup extends GroupContentEnablerBase {

}
