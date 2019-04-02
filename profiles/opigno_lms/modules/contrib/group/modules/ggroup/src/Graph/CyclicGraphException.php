<?php

namespace Drupal\ggroup\Graph;

/**
 * An exception thrown when a potential cycle is detected in an acyclic graph.
 */
class CyclicGraphException extends \Exception {

  /**
   * Constructs an CyclicGraphException.
   *
   * @param int|string $parent
   *   The parent group ID or name.
   * @param int|string $child
   *   The child group ID or name.
   */
  public function __construct($parent, $child) {
    parent::__construct("Cannot add group '$child' as a subgroup of group '$parent' because group '$parent' is already a descendant subgroup of group '$child'. Cyclic relationships cannot be handled.");
  }

}
