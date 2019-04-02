<?php

namespace Drupal\opigno_group_manager\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Class OpignoGroupManagerContentType.
 *
 * @Annotation
 */
class OpignoGroupManagerContentType extends Plugin {

  /**
   * The content type ID.
   *
   * @var string
   */
  public $id;

  /**
   * The content type name.
   *
   * @var string
   */
  public $readable_name;

  /**
   * The content type description.
   *
   * @var string
   */
  public $description;

  /**
   * The content's entity type.
   *
   * @var string
   */
  public $entity_type;

  /**
   * Allowed group types.
   *
   * @var array
   */
  public $allowed_group_types;

  /**
   * Group content plugin id that will be used to add entity as a content.
   *
   * @var string
   */
  public $group_content_plugin_id;

}
