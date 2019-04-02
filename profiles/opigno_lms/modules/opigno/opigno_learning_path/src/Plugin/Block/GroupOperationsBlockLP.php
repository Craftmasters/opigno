<?php

namespace Drupal\opigno_learning_path\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'GroupOperationsBlockLP' block.
 *
 * @Block(
 *  id = "group_operations_lp",
 *  admin_label = @Translation("Opigno Group Operations Block"),
 * )
 */
class GroupOperationsBlockLP extends BlockBase {

  /**
   * Non cacheable group join/leave link block.
   */
  public function build() {
    $build['content'] = [
      '#create_placeholder' => TRUE,
      '#lazy_builder' => [
        'opigno_learning_path.group_operations:getLink', [],
      ],
    ];

    return $build;
  }

}
