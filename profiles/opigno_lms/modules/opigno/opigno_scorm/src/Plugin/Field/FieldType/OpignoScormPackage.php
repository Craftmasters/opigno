<?php

namespace Drupal\opigno_scorm\Plugin\Field\FieldType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldType\FileItem;

/**
 * Plugin implementation of the 'opigno_scorm_package' field type.
 *
 * @FieldType(
 *   id = "opigno_scorm_package",
 *   label = @Translation("Scorm package"),
 *   description = @Translation("This field stores the ID of a Scorm package file."),
 *   category = @Translation("Reference"),
 *   default_widget = "file_generic",
 *   default_formatter = "opigno_scorm_field_formatter",
 *   list_class = "\Drupal\opigno_scorm\Plugin\Field\FieldType\OpignoScormPackageItemList",
 *   constraints = {"ReferenceAccess" = {}, "FileValidation" = {}}
 * )
 */
class OpignoScormPackage extends FileItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    $settings = parent::defaultFieldSettings();
    $settings['file_extensions'] = 'zip';
    $settings['file_directory'] = 'opigno_scorm';
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
