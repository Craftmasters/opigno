<?php

namespace Drupal\opigno_forum\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if (($route = $collection->get('forum.page')) !== NULL) {
      $route->setRequirement('_forum_access_check', '_forum_access_check');
    }
  }

}
