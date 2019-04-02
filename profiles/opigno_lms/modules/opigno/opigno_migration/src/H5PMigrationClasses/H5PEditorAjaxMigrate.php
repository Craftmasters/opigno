<?php

namespace Drupal\opigno_migration\H5PMigrationClasses;

/**
 * Class H5PEditorAjaxMigrate.
 */
class H5PEditorAjaxMigrate extends \H5PEditorAjax {

  /**
   * Validates the package. Sets error messages if validation fails.
   *
   * @param bool $skipContent
   *   Will not validate cotent if set to TRUE.
   *
   * @return bool
   *   Valid package flag.
   */
  public function isValidPackage($skipContent = FALSE) {
    $validator = new H5PValidatorMigration($this->core->h5pF, $this->core);
    if (!$validator->isValidPackage($skipContent, FALSE)) {
      $this->storage->removeTemporarilySavedFiles($this->core->h5pF->getUploadedH5pPath());
      \Drupal::logger('opigno_groups_migration')->error('Validating h5p package failed.');

      return FALSE;
    }

    return TRUE;
  }

}
