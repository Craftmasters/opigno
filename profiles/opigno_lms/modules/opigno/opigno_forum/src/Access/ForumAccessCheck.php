<?php

namespace Drupal\opigno_forum\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AccountInterface;

/**
 * Checks access for displaying forum page.
 */
class ForumAccessCheck implements AccessInterface {

  /**
   * Returns forum access.
   */
  public function access(RouteMatch $route_match, AccountInterface $account) {
    $forum = $route_match->getParameter('taxonomy_term');
    if ($forum !== NULL) {
      if (!_opigno_forum_access($forum->id(), $account)) {
        return AccessResult::forbidden();
      }
    }

    return AccessResult::allowed();
  }

}
