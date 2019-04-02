<?php

namespace Drupal\opigno_dashboard\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for all the actions of the Learning Path manager app.
 */
class DashboardController extends ControllerBase {

  /**
   * Returns positioning.
   */
  public function getPositioning() {
    if (!$current_user = \Drupal::currentUser()) {
      return new JsonResponse(NULL, Response::HTTP_BAD_REQUEST);
    }

    $availables = \Drupal::service('opigno_dashboard.block')->getAvailableBlocks();
    $positions = [[], [], []];
    $connection = \Drupal::database();

    $query = $connection->select('opigno_dashboard_positioning', 'p')
      ->fields('p', ['columns', 'positions'])
      ->condition('p.uid', $current_user->id());

    $result = $query->execute()->fetchObject();
    if (!$positions = json_decode($result->positions, TRUE)) {
      $positions = json_decode(OPIGNO_DASHBOARD_DEFAULT_CONFIG, TRUE);
    }

    // Remove blocks not availables.
    $availables_keys = [];
    foreach ($availables as $available) {
      $availables_keys[$available['id']] = $available['id'];
    }
    foreach ($positions as $key1 => $column) {
      foreach ($column as $key2 => $row) {
        if (!in_array($row['id'], $availables_keys)) {
          unset($positions[$key1][$key2]);
        }
      }
    }

    // Remove block already used.
    foreach ($availables as $key => $value) {
      foreach ($positions as $column) {
        foreach ($column as $row) {
          if ($row['id'] == $value['id']) {
            unset($availables[$key]);
          }
        }
      }
    }

    $entities = array_merge([array_values($availables)], $positions);

    return new JsonResponse([
      'positions' => ($entities) ? $entities : array_merge([array_values($availables)], [
        [],
        [],
        [],
      ]),
      'columns' => ($result->columns) ? $result->columns : 3,
    ], Response::HTTP_OK);
  }

  /**
   * Sets positioning.
   */
  public function setPositioning(Request $request) {
    $datas = json_decode($request->getContent());
    $connection = \Drupal::database();

    // Remove first column.
    unset($datas->positions[0]);

    $connection->merge('opigno_dashboard_positioning')
      ->key(['uid' => \Drupal::currentUser()->id()])
      ->fields(['columns' => (int) $datas->columns])
      ->fields(['positions' => json_encode($datas->positions)])
      ->execute();

    return new JsonResponse(NULL, Response::HTTP_OK);
  }

  /**
   * Returns blocks contents.
   */
  public function getBlocksContents() {
    $blocks = \Drupal::service('opigno_dashboard.block')->getDashboardBlocksContents();

    return new JsonResponse([
      'blocks' => $blocks,
    ], Response::HTTP_OK);
  }

}
