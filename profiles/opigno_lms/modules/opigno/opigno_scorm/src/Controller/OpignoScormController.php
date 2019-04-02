<?php

namespace Drupal\opigno_scorm\Controller;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class OpignoScormController.
 */
class OpignoScormController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function scormIntegrateSco($opigno_scorm_sco) {
    $scorm_service = \Drupal::service('opigno_scorm.scorm');
    $sco = $scorm_service->scormLoadSco($opigno_scorm_sco);
    // Does the SCO have a launch property ?
    if (!empty($sco->launch)) {
      $query = [];

      // Load the SCO data.
      $scorm = $scorm_service->scormLoadById($sco->scorm_id);

      // Remove the URL parameters from the launch URL.
      if (!empty($sco->attributes['parameters'])) {
        $sco->launch .= $sco->attributes['parameters'];
      }
      $parts = explode('?', $sco->launch);
      $launch = array_shift($parts);

      if (!empty($parts)) {
        // Failsafe - in case a launch URL has 2 or more '?'.
        $parameters = implode('&', $parts);
      }

      // Get the SCO location on the filesystem.
      $sco_location = "{$scorm->extracted_dir}/$launch";
      $sco_path = file_create_url($sco_location);

      // Where there any parameters ? If so, prepare them for Drupal.
      if (!empty($parameters)) {
        foreach (explode('&', $parameters) as $param) {
          list($key, $value) = explode('=', $param);
          $query[$key] = !empty($value) ? $value : '';
        }

        if ($query) {
          $query = UrlHelper::buildQuery($query);
          $sco_path = $sco_path . '?' . $query;
        }
      }

      return new TrustedRedirectResponse($sco_path);
    }
    else {
      throw new NotFoundHttpException();
    }
  }

  /**
   * Scorm data commit method.
   */
  public function scormCommit($opigno_scorm_id, $opigno_scorm_sco_id) {
    if (!empty($_POST['data'])) {
      $data = json_decode($_POST['data']);
      if (!empty($data->cmi->interactions)) {
        $_SESSION['scorm_answer_results'] = [
          'opigno_scorm_id' => $opigno_scorm_id,
          'opigno_scorm_sco_id' => $opigno_scorm_sco_id,
          'data' => $data,
        ];
      }
      $scorm_service = \Drupal::service('opigno_scorm.scorm');
      $scorm = $scorm_service->scormLoadById($opigno_scorm_id);
      \Drupal::moduleHandler()->invokeAll('opigno_scorm_commit', [
        $scorm,
        $opigno_scorm_sco_id,
        json_decode($_POST['data']),
      ]);
      return new JsonResponse(['success' => 1]);
    }
    else {
      return new JsonResponse(['error' => 1, 'message' => 'no data received']);
    }
  }

}
