<?php

namespace Drupal\opigno_tour;

/**
 * Class OpignoTourFunctions.
 *
 * @package Drupal\opigno_tour
 */
class OpignoTourFunctions {

  /**
   * Checks if current route has a Guided tour.
   */
  public static function checkRouteTour($route_name) {
    $routes = [
      'view.frontpage.page_1',
      'view.opigno_training_catalog.training_catalogue',
      'opigno_learning_path.achievements',
      'entity.group.canonical',
      'entity.group.edit_form',
    ];

    if (in_array($route_name, $routes)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Checks if user has already viewed current page.
   */
  public static function isPageUserViewed($route_name, $uid) {
    $viewed = \Drupal::database()->select('opigno_tour_user_routes', 'ur')
      ->fields('ur', ['timestamp'])
      ->condition('uid', $uid)
      ->condition('route', $route_name)
      ->execute()->fetchAll();

    if ($viewed) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Saves user and current route.
   */
  public static function savePageUserViewed($route_name, $uid) {
    try {
      \Drupal::database()->insert('opigno_tour_user_routes')
        ->fields([
          'uid' => $uid,
          'route' => $route_name,
          'timestamp' => time(),
        ])
        ->execute();
    }
    catch (\Exception $e) {
      \Drupal::logger('opigno_tour')->error($e->getMessage());
    }
  }

}
