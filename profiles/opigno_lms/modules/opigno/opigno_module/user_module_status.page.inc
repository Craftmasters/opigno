<?php

/**
 * @file
 * Contains user_module_status.page.inc.
 *
 * Page callback for User module status entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for User module status templates.
 *
 * Default template: user_module_status.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_user_module_status(array &$variables) {
  // Fetch UserModuleStatus Entity Object.
  $user_module_status = $variables['elements']['#user_module_status'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
