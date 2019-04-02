<?php

namespace Drupal\opigno_catalog;

/**
 * Class StyleService.
 */
class StyleService {

  /**
   * Constructs a new StyleService object.
   */
  public function __construct() {
  }

  /**
   * Returns style.
   */
  public function getStyle() {
    $tempstore = \Drupal::service('user.private_tempstore')->get('opigno_catalog');

    return $tempstore->get('style');
  }

}
