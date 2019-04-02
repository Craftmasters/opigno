<?php

namespace Drupal\opigno_group_manager;

use Drupal\Core\Plugin\PluginBase;

/**
 * Class ContentTypeBase.
 *
 * @package Drupal\opigno_group_manager
 *
 * This class contains the basics that every plugin implementation
 * of Learning Path Content Type should extend from.
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
   * Returns ID.
   *
   * @return string
   *   ID.
   */
  public function getId() {
    return $this->pluginDefinition['id'];
  }

  /**
   * Returns readable name.
   *
   * @return string
   *   Readable name.
   */
  public function getReadableName() {
    return $this->pluginDefinition['readable_name'];
  }

  /**
   * Returns description.
   *
   * @return string
   *   Description.
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * Returns allowed group types.
   *
   * @return string
   *   Allowed group types.
   */
  public function getAllowedGroupTypes() {
    return $this->pluginDefinition['allowed_group_types'];
  }

  /**
   * Returns plugin id.
   *
   * @return string
   *   Plugin id.
   */
  public function getGroupContentPluginId() {
    return $this->pluginDefinition['group_content_plugin_id'];
  }

  /**
   * Get the URL object for starting the quiz.
   *
   * @param int $content_id
   *   The content ID (ex: node ID).
   * @param int $group_id
   *   The group ID (optional).
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
