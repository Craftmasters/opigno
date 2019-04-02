<?php

namespace Drupal\ggroup\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for the group subgroup reference.
 *
 * @Constraint(
 *   id = "GroupSubgroup",
 *   label = @Translation("Group subgroup", context = "Validation"),
 *   type = {"entity"}
 * )
 */
class GroupSubgroupConstraint extends Constraint {

  public $message = '%parent_group_label is already a subgroup of the group %child_group_label. Adding %child_group_label as a subgroup will cause a circular reference.';

}
