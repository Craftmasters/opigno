<?php

namespace Drupal\opigno_migration\H5PMigrationClasses;

/**
 * This class is used for saving H5P files.
 */
class H5PStorageMigrate extends \H5PStorage {

  /**
   * Helps savePackage.
   */
  public function saveLibraries() {
    // Keep track of the number of libraries that have been saved.
    $newOnes = 0;
    $oldOnes = 0;

    // Go through libraries that came with this package.
    foreach ($this->h5pC->librariesJsonData as $libString => &$library) {
      // Find local library identifier.
      $libraryId = $this->h5pC->getLibraryId($library, $libString);

      // Assume new library.
      $new = TRUE;
      if ($libraryId) {
        // Found old library.
        $library['libraryId'] = $libraryId;

        if ($this->h5pF->isPatchedLibrary($library)) {
          // This is a newer version than ours. Upgrade!
          $new = FALSE;
        }
        else {
          $library['saveDependencies'] = FALSE;
          // This is an older version, no need to save.
          continue;
        }
      }

      // Indicate that the dependencies of this library should be saved.
      $library['saveDependencies'] = TRUE;

      // Save library meta data.
      $this->h5pF->saveLibraryData($library, $new);

      // Save library folder.
      $this->h5pC->fs->saveLibrary($library);

      // Remove cached assets that uses this library.
      if ($this->h5pC->aggregateAssets && isset($library['libraryId'])) {
        $removedKeys = $this->h5pF->deleteCachedAssets($library['libraryId']);
        $this->h5pC->fs->deleteCachedAssets($removedKeys);
      }

      // Remove tmp folder.
      self::deleteFileTree($library['uploadDirectory']);

      if ($new) {
        $message = $this->h5pF->t('Added library @lib', ['@lib' => $libString]);
        \Drupal::logger('opigno_groups_migration')->notice($message);
        $newOnes++;
      }
      else {
        $oldOnes++;
      }
    }

    // Go through the libraries again to save dependencies.
    foreach ($this->h5pC->librariesJsonData as &$library) {
      if (!$library['saveDependencies']) {
        continue;
      }

      // Remove any old dependencies.
      $this->h5pF->deleteLibraryDependencies($library['libraryId']);

      // Insert the different new ones.
      if (isset($library['preloadedDependencies'])) {
        $this->h5pF->saveLibraryDependencies($library['libraryId'], $library['preloadedDependencies'], 'preloaded');
      }
      if (isset($library['dynamicDependencies'])) {
        $this->h5pF->saveLibraryDependencies($library['libraryId'], $library['dynamicDependencies'], 'dynamic');
      }
      if (isset($library['editorDependencies'])) {
        $this->h5pF->saveLibraryDependencies($library['libraryId'], $library['editorDependencies'], 'editor');
      }

      // Make sure libraries dependencies, parameter filtering and
      // export files gets regenerated for all content who uses this library.
      $this->h5pF->clearFilteredParameters($library['libraryId']);
    }

    // Tell the user what we've done.
    if ($newOnes && $oldOnes) {
      if ($newOnes === 1) {
        if ($oldOnes === 1) {
          // Singular Singular.
          $message = $this->h5pF->t('Added %new new H5P library and updated %old old one.', [
            '%new' => $newOnes,
            '%old' => $oldOnes,
          ]);
        }
        else {
          // Singular Plural.
          $message = $this->h5pF->t('Added %new new H5P library and updated %old old ones.', [
            '%new' => $newOnes,
            '%old' => $oldOnes,
          ]);
        }
      }
      else {
        // Plural.
        if ($oldOnes === 1) {
          // Plural Singular.
          $message = $this->h5pF->t('Added %new new H5P libraries and updated %old old one.', [
            '%new' => $newOnes,
            '%old' => $oldOnes,
          ]);
        }
        else {
          // Plural Plural.
          $message = $this->h5pF->t('Added %new new H5P libraries and updated %old old ones.', [
            '%new' => $newOnes,
            '%old' => $oldOnes,
          ]);
        }
      }
    }
    elseif ($newOnes) {
      if ($newOnes === 1) {
        // Singular.
        $message = $this->h5pF->t('Added %new new H5P library.', ['%new' => $newOnes]);
      }
      else {
        // Plural.
        $message = $this->h5pF->t('Added %new new H5P libraries.', ['%new' => $newOnes]);
      }
    }
    elseif ($oldOnes) {
      if ($oldOnes === 1) {
        // Singular.
        $message = $this->h5pF->t('Updated %old H5P library.', ['%old' => $oldOnes]);
      }
      else {
        // Plural.
        $message = $this->h5pF->t('Updated %old H5P libraries.', ['%old' => $oldOnes]);
      }
    }

    if (isset($message)) {
      \Drupal::logger('opigno_groups_migration')->notice($message);
    }
  }

  /**
   * Recursive function for removing directories.
   *
   * @param string $dir
   *   Path to the directory we'll be deleting.
   *
   * @return bool
   *   Indicates if the directory existed.
   */
  public static function deleteFileTree($dir) {
    if (!is_dir($dir)) {
      return FALSE;
    }
    if (is_link($dir)) {
      // Do not traverse and delete linked content, simply unlink.
      unlink($dir);
      return TRUE;
    }
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
      $filepath = "$dir/$file";
      // Note that links may resolve as directories.
      if (!is_dir($filepath) || is_link($filepath)) {
        // Unlink files and links.
        unlink($filepath);
      }
      else {
        // Traverse subdir and delete files.
        self::deleteFileTree($filepath);
      }
    }
    return rmdir($dir);
  }

}
