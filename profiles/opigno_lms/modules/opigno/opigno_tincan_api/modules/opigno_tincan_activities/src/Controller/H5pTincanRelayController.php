<?php

namespace Drupal\opigno_tincan_activities\Controller;

use TinCan\Statement;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\opigno_tincan_api\OpignoTinCanApiStatements;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class H5pTincanRelayController.
 */
class H5pTincanRelayController extends ControllerBase {

  /**
   * H5pTincanStatementRelay.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return object
   *   Return JsResponse object.
   */
  public function h5pTincanStatementRelay(Request $request) {

    $data = $request->request->get('statement', '');
    $data = json_decode($data, TRUE);

    // Set object id.
    $aid = $data['object']['definition']['extensions']['http://h5p.org/x-api/h5p-local-content-id'];
    $url = Url::fromRoute('entity.opigno_activity.canonical',
      ['opigno_activity' => $aid],
      ['absolute' => TRUE])
      ->toString();
    $data['object']['id'] = $url;

    // Try to create and send the statement.
    if (class_exists('TinCan\Statement')) {
      try {
        $statement = new Statement($data);
      }
      catch (Exception $e) {
        \Drupal::logger('opigno_tincan')
          ->error('The following statement could not be created: <br /><pre>' . print_r($data, TRUE) . '</pre><br />This exception was raised: ' . $e->getMessage());
        return new JsonResponse(NULL, Response::HTTP_BAD_REQUEST);
      }

      $statement->stamp();
      // Sending statement.
      OpignoTinCanApiStatements::sendStatement($statement);
    }

    return new JsonResponse(NULL, Response::HTTP_OK);
  }

}
