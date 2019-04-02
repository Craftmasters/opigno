<?php

namespace Drupal\opigno_moxtra\Plugin\GroupContentEnabler;

use Drupal\group\Plugin\GroupContentEnablerBase;

/**
 * Allows Opigno Module content to be added to groups.
 *
 * @GroupContentEnabler(
 *   id = "opigno_moxtra_meeting_group",
 *   label = @Translation("Live Meeting Group"),
 *   description = @Translation("Adds Live Meeting entity to groups."),
 *   entity_type_id = "opigno_moxtra_meeting",
 *   pretty_path_key = "meeting",
 *   reference_label = @Translation("Live Meeting"),
 *   reference_description = @Translation("The name of the Live Meeting you want to add")
 * )
 */
class MeetingGroup extends GroupContentEnablerBase {
}
