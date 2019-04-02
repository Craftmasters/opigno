<?php

namespace Drupal\opigno_learning_path;

use Drupal\Core\Plugin\PluginBase;

/**
 * Class ContentTypeBase.
 *
 * @package Drupal\opigno_learning_path
 *
 * This class contains the basics that every plugin implementation of
 * Learning Path Content Type should extend from.
 */
abstract class ContentTypeBase extends PluginBase implements ContentTypeInterface {

  /**
   * Returns entity type.
   *
   * @return string
   *   Entity type.
   */
  public function getEntityType() {
    return $this->pluginDefinition['entity_type'];
  }

  /**
   * Returns entity ID.
   *
   * @return string
   *   Entity ID.
   */
  public function getId() {
    return $this->pluginDefinition['id'];
  }

  /**
   * Returns entity name.
   *
   * @return string
   *   Entity name.
   */
  public function getReadableName() {
    return $this->pluginDefinition['readable_name'];
  }

  /**
   * Returns entity description.
   *
   * @return string
   *   Entity description.
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * Get the URL object for starting the quiz.
   *
   * @param int $content_id
   *   The content ID (ex: node ID).
   * @param int $group_id
   *   Group ID.
   *
   * @return \Drupal\Core\Url
   *   The URL to use to start the "test" for a student.
   */
  public function getStartContentUrl($content_id, $group_id = NULL) {
    return $this->getViewContentUrl($content_id);
  }

  /**
   * Answer if the current page should show the "finish" button.
   *
   * By default, it returns the value from shouldShowNext().
   *
   * @return bool
   *   TRUE if the page should show the "finish" button. FALSE otherwise.
   */
  public function shouldShowFinish() {
    return $this->shouldShowNext();
  }

}
