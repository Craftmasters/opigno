<?php

namespace Drupal\opigno_video\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\file\Entity\File;

/**
 * Class ImagePopup.
 *
 * @package Drupal\opigno_video\Controller
 */
class ImagePopup extends ControllerBase {

  /**
   * Render.
   *
   * @return string
   *   Return Hello string.
   */
  public function render($fid) {
    $file = File::load($fid);

    return [
      '#theme' => 'image_style',
      '#style_name' => 'large',
      '#uri' => $file->getFileUri(),
    ];
  }

}
