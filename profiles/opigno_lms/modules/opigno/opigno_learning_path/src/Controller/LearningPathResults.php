<?php

namespace Drupal\opigno_learning_path\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\group\Entity\Group;
use Drupal\opigno_learning_path\Entity\LPResult;

/**
 * Class LearningPathResults.
 */
class LearningPathResults extends ControllerBase {

  /**
   * Results page for a learning path.
   *
   * It shows all the results of the users of this learning path.
   */
  public function results(Group $group) {
    // Get the results from the database.
    try {
      $results = LPResult::loadByLearningPath($group);
    }
    catch (InvalidPluginDefinitionException $e) {
      return [
        '#markup' => '<p>A problem occured while gathering the results</p>',
      ];
    }

    if (!$results) {
      return [
        '#markup' => '<p>No results for this learning path</p>',
      ];
    }

    // Format the results to be able to show them in a table.
    $rows = [];
    foreach ($results as $result) {
      if (!$result->access('view')) {
        continue;
      }

      $rows[] = [
        $result->getUser()->getUsername(),
        $result->hasPassed() ? 'Passed' : 'Not passed',
        \Drupal::service('date.formatter')->format($result->getCreatedTime(), 'short'),
        Link::createFromRoute('Delete', 'opigno_learning_path.results.delete', [
          'group' => $group->id(),
          'result' => $result->id(),
        ]),
      ];
    }

    // Now create the table.
    $form = [];
    $form['results_table'] = [
      '#type' => 'table',
      '#header' => ['Username', 'Result', 'Date', 'Actions'],
      '#rows' => $rows,
    ];
    return $form;
  }

  /**
   * Delete a result and redirect the user to the results page.
   */
  public function delete(Group $group, LPResult $result) {
    $result->delete();
    $roles = \Drupal::currentUser()->getRoles();
    if (array_search('administrator', $roles) === FALSE) {
      drupal_flush_all_caches();
    }
    return $this->redirect('opigno_learning_path.results', ['group' => $group->id()]);
  }

}
