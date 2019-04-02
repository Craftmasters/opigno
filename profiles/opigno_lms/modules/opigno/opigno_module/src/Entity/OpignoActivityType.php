<?php

namespace Drupal\opigno_module\Entity;

use Drupal\file\Entity\File;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Activity type entity.
 *
 * @ConfigEntityType(
 *   id = "opigno_activity_type",
 *   label = @Translation("Activity type"),
 *   handlers = {
 *     "list_builder" = "Drupal\opigno_module\OpignoActivityTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\opigno_module\Form\OpignoActivityTypeForm",
 *       "edit" = "Drupal\opigno_module\Form\OpignoActivityTypeForm",
 *       "delete" = "Drupal\opigno_module\Form\OpignoActivityTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\opigno_module\OpignoActivityTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "opigno_activity_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "opigno_activity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/opigno_activity_type/{opigno_activity_type}",
 *     "add-form" = "/admin/structure/opigno_activity_type/add",
 *     "edit-form" = "/admin/structure/opigno_activity_type/{opigno_activity_type}/edit",
 *     "delete-form" = "/admin/structure/opigno_activity_type/{opigno_activity_type}/delete",
 *     "collection" = "/admin/structure/opigno_activity_type"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "summary",
 *     "image",
 *   }
 * )
 */
class OpignoActivityType extends ConfigEntityBundleBase implements OpignoActivityTypeInterface {

  /**
   * The Activity type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Activity type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Activity type description.
   *
   * @var string
   */
  protected $description;

  /**
   * The Activity type summary.
   *
   * @var string
   */
  protected $summary;

  /**
   * The Activity type image.
   *
   * @var string
   */
  protected $image;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return $this->summary;
  }

  /**
   * {@inheritdoc}
   */
  public function setSummary($summary) {
    $this->summary = $summary;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getImageId() {
    if (isset($this->image[0])) {
      return $this->image[0];
    };
  }

  /**
   * {@inheritdoc}
   */
  public function getImage() {
    if (isset($this->image[0])) {
      $image = File::load($this->image[0]);
      return $image;
    }
    else {
      return NULL;
    }
  }

}
