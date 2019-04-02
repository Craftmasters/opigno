<?php

namespace Drupal\opigno_h5p;

use Drupal\opigno_module\Entity\OpignoActivityInterface;
use Drupal\opigno_module\Entity\OpignoAnswerInterface;

/**
 * Class H5PReportXAPIData.
 */
class H5PReportXAPIData {

  private $statement;
  private $onlyScore;
  private $children;
  private $parentID;
  private $question;
  private $answer;

  /**
   * H5PReportXAPIData constructor.
   */
  public function __construct(OpignoActivityInterface $activity, OpignoAnswerInterface $answer, $data, $parentID = NULL) {
    // Keep track of statement and children.
    if (isset($data->statement)) {
      $this->statement = $data->statement;
    }
    elseif (isset($data->onlyScore)) {
      $this->onlyScore = $data->onlyScore;
    }

    $this->parentID = $parentID;

    if (!empty($data->children)) {
      $this->children = $data->children;
    }

    $this->question = $activity;
    $this->answer = $answer;
  }

  /**
   * Check if the interaction has sub interactions with scoring.
   */
  public function isCompound() {
    return ($this->getInteractionType() === 'compound');
  }

  /**
   * Get list of children with given parentID.
   */
  public function getChildren($parentID = NULL) {
    $children = [];

    // Parse children data.
    if (!empty($this->children)) {
      foreach ($this->children as $child) {
        $children[] = new H5PReportXAPIData($this->question, $this->answer, $child, $parentID);
      }
    }

    return $children;
  }

  /**
   * Get the ID of the parent statement.
   *
   * Only works for statements part of a compound interaction.
   */
  public function getParentID() {
    return $this->parentID;
  }

  /**
   * Get score of given type from statement result.
   */
  private function getScore($type) {
    return (isset($this->statement->result->score->{$type}) ? (float) $this->statement->result->score->{$type} : NULL);
  }

  /**
   * Get the optional scaled score.
   *
   * Must be between -1 and 1.
   *
   * @return float
   *   Scaled score.
   */
  public function getScoreScaled() {
    if (isset($this->onlyScore)) {
      // Special case if we only have the scaled score.
      $score = 0.;
      if ($this->onlyScore !== 1 && is_numeric($this->onlyScore)) {
        // Let's "decrypt" it….
        $score = $this->onlyScore / 1.234 - 32.17;
      }
      if ($score < 0 || $score > 1) {
        // Invalid score.
        $score = 0.;
      }
      return $score;
    }

    $score = $this->getScore('scaled');

    if ($score !== NULL) {
      if ($score < -1) {
        $score = -1.;
      }
      elseif ($score > 1) {
        $score = 1.;
      }
    }

    return $score;
  }

  /**
   * Get the required raw score for the interaction.
   *
   * Can be anything between min and max.
   */
  public function getScoreRaw() {
    return $this->getScore('raw');
  }

  /**
   * Get the optional min. score.
   */
  public function getScoreMin() {
    return $this->getScore('min');
  }

  /**
   * Get the optional max. score.
   */
  public function getScoreMax() {
    return $this->getScore('max');
  }

  /**
   * Get object definition property or default value if not set.
   *
   * @param string $property
   *   Property name.
   * @param mixed $default
   *   If not set. Default default is blank string.
   *
   * @return mixed
   *   Property value.
   */
  private function getObjectDefinition($property, $default = '') {
    return (isset($this->statement->object->definition->{$property}) ? $this->statement->object->definition->{$property} : $default);
  }

  /**
   * Get the type of interaction.
   */
  public function getInteractionType() {
    // Can be any string.
    return $this->getObjectDefinition('interactionType');
  }

  /**
   * Get the description of the interaction.
   */
  public function getDescription() {
    $description = $this->getObjectDefinition('description');
    if ($description !== '') {
      $description = (isset($description->{'en-US'}) ? $description->{'en-US'} : '');
    }

    return $description;
  }

  /**
   * Get the correct response patterns.
   */
  public function getCorrectResponsesPattern() {
    $correctResponsesPattern = $this->getObjectDefinition('correctResponsesPattern');
    if (is_array($correctResponsesPattern)) {
      return json_encode($correctResponsesPattern);
    }

    return '';
  }

  /**
   * Get the user response.
   */
  public function getResponse() {
    return (isset($this->statement->result->response) ? $this->statement->result->response : '');
  }

  /**
   * Get additional data for some interaction types.
   */
  public function getAdditionals() {
    $additionals = [];

    switch ($this->getInteractionType()) {
      case 'choice':
        $additionals['choices'] = $this->getObjectDefinition('choices', []);
        $additionals['extensions'] = $this->getObjectDefinition('extensions', (object) []);
        break;

      case 'long-choice':
        $additionals['choices'] = $this->getObjectDefinition('choices', []);
        $additionals['extensions'] = $this->getObjectDefinition('extensions', (object) []);
        break;

      case 'matching':
        $additionals['source'] = $this->getObjectDefinition('source', []);
        $additionals['target'] = $this->getObjectDefinition('target', []);
        break;
    }

    return (empty($additionals) ? '' : json_encode($additionals));
  }

  /**
   * Checks if data is valid.
   *
   * @return bool
   *   True if valid data.
   */
  public function validateData() {

    if ($this->getInteractionType() === '') {
      return FALSE;
    }

    // Validate children.
    $children = $this->getChildren();
    foreach ($children as $child) {
      if (!$child->validateData()) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Recursive save of xAPI data.
   */
  public function saveXAPIData() {
    /* @var $db_connection \Drupal\Core\Database\Connection */
    $db_connection = \Drupal::service('database');
    // Save statement data.
    $dataID = $db_connection->insert('opigno_h5p_user_answer_results')
      ->fields([
        'parent_id' => $this->getParentID(),
        'question_id' => $this->question->id(),
        'question_vid' => $this->question->getLoadedRevisionId(),
        'answer_id' => $this->answer->id(),
        'answer_vid' => $this->answer->getLoadedRevisionId(),
        'score_scaled' => $this->getScoreScaled(),
        'score_raw' => $this->getScoreRaw(),
        'score_min' => $this->getScoreMin(),
        'score_max' => $this->getScoreMax(),
        'interaction_type' => $this->getInteractionType(),
        'description' => $this->getDescription(),
        'correct_responses_pattern' => $this->getCorrectResponsesPattern(),
        'response' => $this->getResponse(),
        'additionals' => $this->getAdditionals(),
      ])
      ->execute();

    // Save sub content statements data.
    if ($this->isCompound()) {
      foreach ($this->getChildren($dataID) as $subData) {
        $subData->saveXAPIData();
      }
    }
  }

}
