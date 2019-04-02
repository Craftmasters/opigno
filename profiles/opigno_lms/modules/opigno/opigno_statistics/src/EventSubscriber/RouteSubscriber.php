<?php

namespace Drupal\opigno_statistics\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Replace default user profile route.
    $user_page = $collection->get('opigno_statistics.user');
    if (isset($user_page)) {
      $default_user_page_key = 'entity.user.canonical';
      $default_user_page = $collection->get($default_user_page_key);
      $user_page->setPath($default_user_page->getPath());
      $collection->remove($default_user_page_key);
      $collection->add($default_user_page_key, $user_page);
    }
  }

}
