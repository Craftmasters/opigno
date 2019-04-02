<?php

namespace Drupal\opigno_h5p\TypeProcessors;

use Drupal\opigno_h5p\H5PReport;

/**
 * Class FillInProcessor.
 *
 * Processes and generates HTML report for 'fill-in' interaction type.
 */
class ChoiceProcessor extends TypeProcessor {

  /**
   * Determines options for interaction, generates a human readable HTML report.
   *
   * @inheritdoc
   */
  public function generateHTML($description, $crp, $response, $extras = NULL, $scoreSettings = NULL) {
    if ($this->isLongChoice($extras)) {
      return H5PReport::getInstance()->generateReport(
        $this->xapiData,
        'long-choice',
        $this->disableScoring
      );
    }

    // We need some style for our report.
    $this->setStyle('opigno_h5p/opigno_h5p.choice');

    $correctAnswers = explode('[,]', $crp[0]);
    $responses = explode('[,]', $response);

    $headerHtml = $this->generateHeader($description, $scoreSettings);
    $tableHTML = $this->generateTable($extras, $correctAnswers, $responses);

    return
      '<div class="h5p-reporting-container h5p-choices-container">' .
        $headerHtml . $tableHTML .
      '</div>';
  }

  /**
   * Generate header element.
   */
  private function generateHeader($description, $scoreSettings) {
    $descriptionHtml = $this->generateDescription($description);
    $scoreHtml = $this->generateScoreHtml($scoreSettings);

    return
      "<div class='h5p-choices-header'>" .
        $descriptionHtml . $scoreHtml .
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
  private function generateTable($extras, $correctAnswers, $responses) {

    $choices = $extras->choices;
    $tableHeader =
      '<tr class="h5p-choices-table-heading">' .
        '<td class="h5p-choices-choice">Answers</td>' .
        '<td class="h5p-choices-user-answer">Your Answer</td>' .
        '<td class="h5p-choices-crp-answer">Correct</td></tr>';

    $rows = '';
    foreach ($choices as $choice) {
      $choiceID = $choice->id;
      $isCRP = in_array($choiceID, $correctAnswers);
      $isAnswered = in_array($choiceID, $responses);

      $userClasses = 'h5p-choices-user';
      $crpClasses = 'h5p-choices-crp';
      if ($isAnswered) {
        $userClasses .= ' h5p-choices-answered';
      }
      if ($isCRP) {
        $userClasses .= ' h5p-choices-user-correct';
        $crpClasses .= ' h5p-choices-crp-correct';
      }

      $row =
        '<td class="h5p-choices-alternative">' .
            $choice->description->{'en-US'} .
        '</td><td class="h5p-choices-icon-cell"><span class="' . $userClasses . '"></span></td>' .
        '<td class="h5p-choices-icon-cell"><span class="' . $crpClasses . '"></span></td>';

      $rows .= '<tr>' . $row . '</tr>';
    }

    $tableContent = '<tbody>' . $tableHeader . $rows . '</tbody>';
    return '<table class="h5p-choices-table">' . $tableContent . '</table>';
  }

  /**
   * Determine if choice is a long choice interaction type.
   */
  private function isLongChoice($extras) {
    $extensions = isset($extras->extensions) ? $extras->extensions : (object) [];

    // Determine if line-breaks extension exists.
    return isset($extensions->{'https://h5p.org/x-api/line-breaks'});
  }

}
