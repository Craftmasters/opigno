<?php

namespace Drupal\opigno_tincan_activity\Plugin\Field\FieldType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldType\FileItem;

/**
 * Plugin implementation of the 'opigno_tincan_package' field type.
 *
 * @FieldType(
 *   id = "opigno_tincan_package",
 *   label = @Translation("Tincan Package"),
 *   description = @Translation("This field stores the ID of a Tincan package file."),
 *   category = @Translation("Reference"),
 *   default_widget = "file_generic",
 *   default_formatter = "opigno_tincan_field_formatter",
 *   list_class = "\Drupal\opigno_tincan_activity\Plugin\Field\FieldType\OpignoTincanPackageItemList",
 *   constraints = {"ReferenceAccess" = {}, "FileValidation" = {}}
 * )
 */
class OpignoTincanPackage extends FileItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    $settings = parent::defaultFieldSettings();
    $settings['file_extensions'] = 'zip';
    $settings['file_directory'] = 'opigno_tincan';
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = [];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    return $element;
  }

}
