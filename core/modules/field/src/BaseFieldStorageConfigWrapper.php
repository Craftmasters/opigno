<?php

namespace Drupal\field;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Wrapper to allow base fields to be handled as fields provided by the Field
 * module when dealing with code special casing them.
 *
 * @internal
 *   This should be removed as soon all core APIs uniformly handle entity fields
 *   regardless of their underlying implementations.
 */
class BaseFieldStorageConfigWrapper extends FieldStorageConfig {

  /**
   * An associative array of field bundle IDs keyed by entity type ID and field
   * name.
   *
   * @var string[][][]
   */
  protected static $field_bundle_ids;

  /**
   * Constructs a wrapper from a base field definition.
   *
   * @param \Drupal\Core\Field\BaseFieldDefinition $base_field_definition
   *   A base field definition.
   *
   * @return static
   *   A wrapper instance.
   */
  public static function createFromBaseFieldDefinition(BaseFieldDefinition $base_field_definition) {
    $values = $base_field_definition->toArray();
    $values['type'] = $base_field_definition->getType();
    $values['module'] = static::lookupTypeProvider($values['type']);
    return new static($values);
  }

  /**
   * Returns the field type provider.
   *
   * @param string $type
   *   The field type.
   *
   * @return string
   *   The field type provider.
   */
  protected static function lookupTypeProvider($type) {
    /** @var \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager */
    $field_type_manager = \Drupal::service('plugin.manager.field.field_type');
    try {
      $field_type = $field_type_manager->getDefinition($type);
      return $field_type['provider'];
    }
    catch (PluginNotFoundException $e) {
      return NULL;
    }
  }

  /**
   * Returns field bundles for the specified entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return string[][]
   *   An associative array of bundle IDs keyed by field name.
   */
  public static function getFieldBundles($entity_type_id) {
    $field_bundles = &static::$field_bundle_ids[$entity_type_id];
    if (!isset($field_bundles)) {
      $field_bundles = [];
      /** @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info */
      $bundle_info = \Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type_id);
      /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
      $entity_field_manager = \Drupal::service('entity_field.manager');
      foreach ($bundle_info as $bundle_id => $info) {
        /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
        foreach ($entity_field_manager->getFieldDefinitions($entity_type_id, $bundle_id) as $field_name => $field_definition) {
          // Filter out Field module's field definitions.
          if (!($field_definition instanceof FieldConfigInterface) && $field_definition instanceof FieldStorageDefinitionInterface && !$field_definition->isBaseField()) {
            $field_bundles[$field_name][] = $bundle_id;
          }
        }
      }
    }
    return $field_bundles;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundles() {
    $field_bundles = static::getFieldBundles($this->entity_type);
    return isset($field_bundles[$this->field_name]) ? $field_bundles[$this->field_name] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    throw new \BadMethodCallException('BaseFieldStorageConfigWrapper instances cannot be persisted.');
  }

}
