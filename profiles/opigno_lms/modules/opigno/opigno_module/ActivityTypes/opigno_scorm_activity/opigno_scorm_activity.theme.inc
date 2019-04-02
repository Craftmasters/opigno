<?php

/**
 * @file
 * Contains opigno_scorm_activity.theme.inc.
 */

use Drupal\Core\Render\Element;
use Drupal\opigno_scorm_activity\ScormReport;

/**
 * @file
 * Contains opigno_scorm_activity.theme.inc.
 *
 * Page callback for SCORM Answer entities.
 */

/**
 * Implements hook_preprocess_HOOK().
 */
function template_preprocess_opigno_user_result_item__opigno_scorm(&$variables) {
  $route = \Drupal::routeMatch();
  /** @var \Drupal\opigno_module\Entity\OpignoModule $opigno_module */
  $opigno_module = $route->getParameter('opigno_module');
  // Check if module allow display answer content for users.
  $hide_results = $opigno_module->getHideResults();
  /** @var \Drupal\opigno_module\Entity\OpignoAnswer $answer */
  $answer = $variables['opigno_answer'];
  /** @var Drupal\opigno_module\Entity\OpignoActivity $answer_activity */
  $answer_activity = $variables['opigno_answer_activity'];
  /* @var $answer_service \Drupal\opigno_module\ActivityAnswerManager */
  $answer_service = \Drupal::service('plugin.manager.activity_answer');
  $answer_activity_type = $answer_activity->getType();
  if (!$hide_results) {
    // Get the data about an answer.
    if ($answer_service->hasDefinition($answer_activity_type)) {
      $answer_instance = $answer_service->createInstance($answer_activity_type);
      $answer_data = $answer_instance->getAnswerResultItemData($answer);

      $content = [];
      if ($answer_data) {
        $scormReport = ScormReport::getInstance();
        $reportHtml = $scormReport->generateHtml($answer_data);

        $content[] = [
          '#markup' => $reportHtml,
        ];
      }

      $variables['content'] = $content;
    }
  }
  // Output question number info.
  if ($variables['question_number']) {
    $activity_title = $answer_activity->getName();
    $variables['label'] = $variables['question_number'] . '. ' . ucfirst($activity_title);
  }

  if (isset($variables['answer_max_score'])
    && $variables['answer_max_score'] > 0) {
    // Get the user score.
    $variables['score'] = t('Score: %score of %max_score', [
      '%score' => $answer->isEvaluated() ? $answer->getScore() : '?',
      '%max_score' => $variables['answer_max_score'],
    ]);
  }
  else {
    $variables['score'] = t('No score retrieved');
  }
  $variables['#attached']['library'][] = 'opigno_module/module_results_page';
}

/**
 * Prepares variables for Answer templates.
 *
 * Default template: opigno-answer--opigno-scorm--full.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_opigno_answer__opigno_scorm__full(array &$variables) {
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }

  $answer = $variables['elements']['#opigno_answer'];
  $answer_service = \Drupal::service('plugin.manager.activity_answer');
  $answer_instance = $answer_service->createInstance('opigno_scorm');
  $answer_data = $answer_instance->getAnswerResultItemData($answer);

  $table = [];
  if ($answer_data) {
    $scormReport = ScormReport::getInstance();
    $table = $scormReport->generateHtml($answer_data, TRUE);

    $variables['#attached']['library'][] = 'opigno_module/module_results_page';
    $variables['#attached']['library'][] = 'opigno_h5p/opigno_h5p.choice';
    $variables['#attached']['library'][] = 'opigno_h5p/opigno_h5p.true-false';
  }

  $variables['content']['table'] = [
    '#markup' => $table,
  ];
}
