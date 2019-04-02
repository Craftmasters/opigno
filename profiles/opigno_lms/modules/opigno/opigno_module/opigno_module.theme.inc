<?php

/**
 * @file
 * Contains opigno_module.theme.inc.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Activity templates.
 *
 * Default template: opigno_activity.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_opigno_activity(array &$variables) {
  // Fetch OpignoActivity Entity Object.
  $opigno_activity = $variables['elements']['#opigno_activity'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}

/**
 * Prepares variables for Module templates.
 *
 * Default template: opigno_module.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_opigno_module(array &$variables) {
  // Fetch OpignoModule Entity Object.
  $opigno_module = $variables['elements']['#opigno_module'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
