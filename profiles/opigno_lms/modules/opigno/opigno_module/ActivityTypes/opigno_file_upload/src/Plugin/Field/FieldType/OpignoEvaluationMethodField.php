<?php

namespace Drupal\opigno_file_upload\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'opigno_evaluation_method' field type.
 *
 * @FieldType(
 *   id = "opigno_evaluation_method",
 *   label = @Translation("Evaluation method"),
 *   description = @Translation("A field that prompts a choice of the evaluation method."),
 *   default_widget = "opigno_evaluation_method_widget",
 *   default_formatter = "opigno_evaluation_method_formatter"
 * )
 */
class OpignoEvaluationMethodField extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    return parent::applyDefaultValue($notify);
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['value'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Evaluation method'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'value' => [
          'type' => 'int',
          'description' => 'The value of the evaluation method.',
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values['value'] = 0;
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

}
