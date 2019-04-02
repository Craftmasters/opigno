<?php

namespace Drupal\opigno_scorm\Plugin\Field\FieldType;

use Drupal\file\Plugin\Field\FieldType\FileFieldItemList;

/**
 * Represents a configurable entity file field.
 */
class OpignoScormPackageItemList extends FileFieldItemList {

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    parent::postSave($update);
    $scorm_controller = \Drupal::service('opigno_scorm.scorm');
    if (!$update) {
      // Extract scorm per each archive.
      foreach ($this->referencedEntities() as $file) {
        $scorm_controller->scormExtract($file);
      }
    }
    else {
      foreach ($this->referencedEntities() as $file) {
        $scorm = $scorm_controller->scormLoadByFileEntity($file);
        if (empty($scorm->id)) {
          $scorm_controller->scormExtract($file);
        }
      }
    }
  }

}
