<?php

namespace Drupal\opigno_scorm_activity;

/**
 * Class H5PReport.
 */
class ScormReport {

  /**
   * Determines options for interaction, generates a human readable HTML report.
   *
   * @inheritdoc
   */
  public function generateHtml($answer_data, $full_table = FALSE) {
    $headerHtml = $this->generateHeader(t('Questions'));
    $tableHTML = $this->generateTable($answer_data, $full_table);

    return
      '<div class="h5p-reporting-container h5p-choices-container result-item">' .
      $headerHtml . $tableHTML .
      '</div>';
  }

  /**
   * Generate header element.
   */
  private function generateHeader($description) {
    $descriptionHtml = $this->generateDescription($description);

    return
      "<div class='h5p-choices-header'>" .
        $descriptionHtml .
      "</div>";
  }

  /**
   * Generate description element.
   */
  private function generateDescription($description) {
    return '<p class="h5p-reporting-description h5p-choices-task-description">'
      . $description .
      '</p>';
  }

  /**
   * Generate HTML table of choices.
   */
  private function generateTable($answer_data, $full_table) {
    if ($full_table) {
      $tableHeader =
        '<tr class="h5p-choices-table-heading">' .
        '<td class="h5p-choices-number">#</td><td class="h5p-choices-choice">' . t('Question') . '</td>' .
        '<td class="h5p-choices-user-answer">' . t('Result') . '</td>' .
        '<td class="h5p-choices-crp-answer">' . t('Type') . '</td>' .
        '<td class="h5p-choices-crp-answer">' . t('Date') . '</td></tr>';
    }
    else {
      $tableHeader =
        '<tr class="h5p-choices-table-heading">' .
        '<td class="h5p-choices-number">#</td><td class="h5p-choices-choice">' . t('Question') . '</td>' .
        '<td class="h5p-choices-user-answer">' . t('Result') . '</td>' .
        '<td class="h5p-choices-crp-answer">' . t('Type') . '</td></tr>';
    }

    $rows = '';
    foreach ($answer_data as $key => $choice) {
      $isAnswered = $choice->result == 'correct' ? TRUE : FALSE;
      $userClasses = $isAnswered ? 'h5p-true-false-user-response-correct' : 'h5p-true-false-user-response-wrong';

      if ($full_table) {
        $row =
          '<td class="h5p-choices-icon-cell">' . ($key + 1) . '</td>' .
          '<td class="h5p-choices-alternative">' . $choice->description . '</td>' .
          '<td class="h5p-choices-icon-cell"><span class="' . $userClasses . '">' . ucfirst($choice->result) . '</span></td>' .
          '<td class="h5p-choices-icon-cell">' . ucfirst($choice->interaction_type) . '</td>' .
          '<td class="h5p-choices-icon-cell">' . date('d/m/Y H:i', $choice->timestamp) . '</td>';
      }
      else {
        $row =
          '<td class="h5p-choices-icon-cell">' . ($key + 1) . '</td>' .
          '<td class="h5p-choices-alternative">' . $choice->description . '</td>' .
          '<td class="h5p-choices-icon-cell"><span class="' . $userClasses . '">' . ucfirst($choice->result) . '</span></td>' .
          '<td class="h5p-choices-icon-cell">' . ucfirst($choice->interaction_type) . '</td>';
      }

      $rows .= '<tr>' . $row . '</tr>';
    }

    $tableContent = '<tbody>' . $tableHeader . $rows . '</tbody>';
    return '<table class="h5p-choices-table">' . $tableContent . '</table>';
  }

  /**
   * Caches instance of report generator.
   */
  public static function getInstance() {
    static $instance;

    if (!$instance) {
      $instance = new ScormReport();
    }

    return $instance;
  }

}
