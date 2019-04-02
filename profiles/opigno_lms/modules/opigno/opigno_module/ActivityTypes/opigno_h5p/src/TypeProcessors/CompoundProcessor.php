<?php

namespace Drupal\opigno_h5p\TypeProcessors;

use Drupal\opigno_h5p\H5PReport;

/**
 * Class FillInProcessor.
 *
 * Processes and generates HTML report for 'fill-in' interaction type.
 */
class CompoundProcessor extends TypeProcessor {

  /**
   * Determines options for interaction, generates a human readable HTML report.
   *
   * @inheritdoc
   */
  public function generateHTML($description, $crp, $response, $extras, $scoreSettings = NULL) {
    // We need some style for our report.
    $this->setStyle('opigno_h5p/opigno_h5p.compound');

    $H5PReport = H5PReport::getInstance();
    $reports = '';

    if (isset($extras->children)) {
      foreach ($extras->children as $childData) {
        $reports .=
          '<div class="h5p-result">' .
            $H5PReport->generateReport($childData, NULL, $this->disableScoring) .
          '</div>';
      }
    }

    // Do not display description when children is empty.
    if (!empty($reports) && !empty($description)) {
      $reports =
          '<p class="h5p-reporting-description h5p-compound-task-description">' .
            $description .
          '</p>' .
          $reports;
    }

    if (!empty($reports)) {
      return '<div class="h5p-reporting-container h5p-compound-container">' .
        $reports .
        '</div>';
    }
    else {
      return NULL;
    }
  }

}
