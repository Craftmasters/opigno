<?php

namespace Drupal\opigno_module\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Answer type entity.
 *
 * @ConfigEntityType(
 *   id = "opigno_answer_type",
 *   label = @Translation("Answer type"),
 *   handlers = {
 *     "list_builder" = "Drupal\opigno_module\OpignoAnswerTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\opigno_module\Form\OpignoAnswerTypeForm",
 *       "edit" = "Drupal\opigno_module\Form\OpignoAnswerTypeForm",
 *       "delete" = "Drupal\opigno_module\Form\OpignoAnswerTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\opigno_module\OpignoAnswerTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "opigno_answer_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "opigno_answer",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/opigno_answer_type/{opigno_answer_type}",
 *     "add-form" = "/admin/structure/opigno_answer_type/add",
 *     "edit-form" = "/admin/structure/opigno_answer_type/{opigno_answer_type}/edit",
 *     "delete-form" = "/admin/structure/opigno_answer_type/{opigno_answer_type}/delete",
 *     "collection" = "/admin/structure/opigno_answer_type"
 *   }
 * )
 */
class OpignoAnswerType extends ConfigEntityBundleBase implements OpignoAnswerTypeInterface {

  /**
   * The Answer type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Answer type label.
   *
   * @var string
   */
  protected $label;

}
