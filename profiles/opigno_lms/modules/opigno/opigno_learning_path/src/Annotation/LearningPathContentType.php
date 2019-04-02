<?php

namespace Drupal\opigno_learning_path\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Class LearningPathContentType.
 *
 * @Annotation
 */
class LearningPathContentType extends Plugin {

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

}
