<?php

namespace Drupal\opigno_h5p;

/**
 * Class H5PReport.
 */
class H5PReport {

  private static $processorMap = [
    'compound'    => 'Drupal\opigno_h5p\TypeProcessors\CompoundProcessor',
    'fill-in'     => 'Drupal\opigno_h5p\TypeProcessors\FillInProcessor',
    'true-false'  => 'Drupal\opigno_h5p\TypeProcessors\TrueFalseProcessor',
    'matching'    => 'Drupal\opigno_h5p\TypeProcessors\MatchingProcessor',
    'choice'      => 'Drupal\opigno_h5p\TypeProcessors\ChoiceProcessor',
    'long-choice' => 'Drupal\opigno_h5p\TypeProcessors\LongChoiceProcessor',
  ];

  private $processors = [];

  /**
   * Generate the proper report depending on xAPI data.
   *
   * @param object $xapiData
   *   XAPI data.
   * @param string $forcedProcessor
   *   Force a processor type.
   * @param bool $disableScoring
   *   Disables scoring for the report.
   *
   * @return string
   *   A report.
   */
  public function generateReport($xapiData, $forcedProcessor = NULL, $disableScoring = FALSE) {
    $interactionType = isset($forcedProcessor) ? $forcedProcessor :
      $xapiData->interaction_type;

    if (!isset(self::$processorMap[$interactionType])) {
      // No processor found.
      return '';
    }

    if (!isset($this->processors[$interactionType])) {
      // Not used before. Initialize new processor.
      $this->processors[$interactionType] = new self::$processorMap[$interactionType]();
    }

    // Generate and return report from xAPI data.
    return $this->processors[$interactionType]
      ->generateReport($xapiData, $disableScoring);
  }

  /**
   * List of CSS stylesheets used by the processors when rendering the report.
   */
  public function getStylesUsed() {
    $styles = [];
    // Fetch style used by each report processor.
    foreach ($this->processors as $processor) {
      $style = $processor->getStyle();
      if (!empty($style)) {
        $styles[] = $style;
      }
    }

    return $styles;
  }

  /**
   * Caches instance of report generator.
   */
  public static function getInstance() {
    static $instance;

    if (!$instance) {
      $instance = new H5PReport();
    }

    return $instance;
  }

}
