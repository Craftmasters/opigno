<?php

namespace Drupal\opigno_group_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class InfoCardController.
 */
class InfoCardController extends ControllerBase {

  /**
   * Hides info card.
   */
  public function hideInfoCard() {
    $tempstore = \Drupal::service('user.private_tempstore')->get('opigno_group_manager');
    $tempstore->set('hide_info_card', TRUE);
    return new JsonResponse(NULL, Response::HTTP_OK);
  }

}
