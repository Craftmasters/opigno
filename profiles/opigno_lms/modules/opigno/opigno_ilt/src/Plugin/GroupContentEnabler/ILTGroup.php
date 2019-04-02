<?php

namespace Drupal\opigno_ilt\Plugin\GroupContentEnabler;

use Drupal\group\Plugin\GroupContentEnablerBase;

/**
 * Allows Opigno ILT content to be added to groups.
 *
 * @GroupContentEnabler(
 *   id = "opigno_ilt_group",
 *   label = @Translation("Instructor-Led Training Group"),
 *   description = @Translation("Adds Instructor-Led Training entity to groups."),
 *   entity_type_id = "opigno_ilt",
 *   pretty_path_key = "ilt",
 *   reference_label = @Translation("Instructor-Led Training"),
 *   reference_description = @Translation("The name of the Instructor-Led Training you want to add")
 * )
 */
class ILTGroup extends GroupContentEnablerBase {
}
