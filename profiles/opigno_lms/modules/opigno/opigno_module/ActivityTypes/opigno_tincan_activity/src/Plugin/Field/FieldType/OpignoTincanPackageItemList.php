<?php

namespace Drupal\opigno_tincan_activity\Plugin\Field\FieldType;

use Drupal\file\Plugin\Field\FieldType\FileFieldItemList;

/**
 * Represents a configurable entity file field.
 */
class OpignoTincanPackageItemList extends FileFieldItemList {

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    parent::postSave($update);
    if (!$update) {
      // Extract tincan per each archive.
      foreach ($this->referencedEntities() as $file) {
        $tincan_content_service = \Drupal::service('opigno_tincan_activity.tincan');
        $tincan_content_service->saveTincanPackageInfo($file);
      }
    }
  }

}
