<?php

namespace Drupal\opigno_tincan_activity;

use Drupal\file\Entity\File;

/**
 * Interface TincanServiceInterface.
 */
interface TincanServiceInterface {

  /**
   * Save tincan package.
   *
   * @param \Drupal\file\Entity\File $file
   *   File object.
   *
   * @return int
   *   Downloaded file $fid.
   */
  public function saveTincanPackageInfo(File $file);

  /**
   * Get the maximum possible score for this question.
   */
  public function getMaximumScore();

  /**
   * Implementation of deleteTincanPackage().
   */
  public function deleteTincanPackage();

  /**
   * Get Tincan data by it's file id.
   *
   * @param object \Drupal\file\Entity\File $file
   *   File entity.
   */
  public function tincanLoadByFileEntity(File $file);

}
