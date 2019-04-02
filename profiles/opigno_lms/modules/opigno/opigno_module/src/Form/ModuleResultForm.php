<?php

namespace Drupal\opigno_module\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\opigno_h5p\H5PReport;
use Drupal\opigno_module\Entity\OpignoAnswerInterface;
use Drupal\opigno_module\Entity\OpignoModule;
use Drupal\opigno_module\Entity\UserModuleStatus;

/**
 * Class ModuleResultForm.
 *
 * @package Drupal\opigno_module\Form
 */
class ModuleResultForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'opigno_module_result_form';
  }

  /**
   * Title callback for the form.
   */
  public function formTitle(OpignoModule $opigno_module = NULL) {
    return $this->t('Edit module result for %module_name', ['%module_name' => $opigno_module->getName()]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, OpignoModule $opigno_module = NULL, UserModuleStatus $user_module_status = NULL) {
    // Get attempt answers.
    $form['answers'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
    ];
    $answers = $user_module_status->getAnswers();
    foreach ($answers as $answer_id => $answer) {
      $answer_activity = $answer->getActivity();
      $form['answers'][$answer_id] = [
        '#type' => 'fieldset',
        '#title' => Link::createFromRoute($this->t('Activity: %activity', ['%activity' => $answer_activity->getName()]), 'entity.opigno_activity.canonical', ['opigno_activity' => $answer_activity->id()])->toString(),
      ];

      $answer_type = $answer->getType();
      $question_markup = '';
      $answer_markup = '';
      switch ($answer_type) {
        case 'opigno_file_upload':
          $answer_markup = $answer->opigno_file->view('full');
          $question_markup = $answer_activity->opigno_body->view('full');
          break;

        case 'opigno_long_answer':
          $answer_markup = $answer->opigno_body->view('full');
          $question_markup = $answer_activity->opigno_body->view('full');
          break;

        case 'opigno_slide':
          $question_markup = \Drupal::entityTypeManager()->getViewBuilder('opigno_answer')->view($answer);
          break;

        case 'opigno_h5p':
          // Get xApiData.
          /* @var $db_connection \Drupal\Core\Database\Connection */
          $db_connection = \Drupal::service('database');
          $query = $db_connection->select('opigno_h5p_user_answer_results', 'ohr')
            ->fields('ohr')
            ->condition('ohr.answer_id', $answer->id());
          $result = $query->execute()->fetchAll();
          $question_markup = [];
          if ($result) {
            foreach ($result as $xapi_data) {
              $H5PReport = H5PReport::getInstance();
              $reportHtml = $H5PReport->generateReport($xapi_data);
              $question_markup[] = [
                '#markup' => $reportHtml,
              ];
            }
          }
          break;

        case 'opigno_scorm':
          break;

        case 'opigno_tincan':
          break;

      }

      if (!empty($question_markup)) {
        $form['answers'][$answer_id]['question_markup'] = $question_markup;
        $form['answers'][$answer_id]['question_markup']['#weight'] = -10;
      };
      if (!empty($answer_markup)) {
        $form['answers'][$answer_id]['answer_markup'] = $answer_markup;
        $form['answers'][$answer_id]['answer_markup']['#weight'] = -9;
      };

      $max_score = $this->getAnswerMaxScore($answer);
      $form['answers'][$answer_id]['score'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Score'),
        '#default_value' => $answer->getScore(),
        '#required' => TRUE,
        '#field_suffix' => "<span>/{$max_score}</span>",
        '#attributes' => [
          'class' => ['max-score'],
        ],
      ];

    }

    $form['#attached']['library'][] = 'opigno_module/module_results_form';

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save score'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $answer_storage = \Drupal::entityTypeManager()->getStorage('opigno_answer');
    $form_values = $form_state->getValues();
    foreach ($form_values['answers'] as $answer_id => $value) {
      // Check if score is lower than maxScore.
      if (isset($value['score'])) {
        $answer = $answer_storage->load($answer_id);
        $max_score = $this->getAnswerMaxScore($answer);
        if (intval($value['score'] > intval($max_score))) {
          $form_state->setErrorByName('score', $this->t("Score can't be greater than maxScore."));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $build_info = $form_state->getBuildInfo();
    $user_status = $build_info['args'][1];
    $answer_storage = \Drupal::entityTypeManager()->getStorage('opigno_answer');
    $form_values = $form_state->getValues();
    foreach ($form_values['answers'] as $answer_id => $value) {
      if (isset($value['score'])) {
        $answer = $answer_storage->load($answer_id);
        $answer->setScore($value['score']);
        $answer->setEvaluated(1);
        $answer->save();
      }
    }
    $score = $user_status->calculateScore();
    $max_score = $user_status->calculateMaxScore();
    if ($max_score > 0) {
      $percents = round(($score / $max_score) * 100);
    }
    else {
      $percents = 100;
    }
    $user_status->setScore((int) $percents);
    $user_status->setMaxScore($max_score);
    $user_status->setEvaluated(1);
    $user_status->save();

    $form_state->setRedirect('view.opigno_score_modules.opigno_not_evaluated');
  }

  /**
   * Returns answer max score.
   */
  protected function getAnswerMaxScore(OpignoAnswerInterface $answer) {
    /* @var $db_connection \Drupal\Core\Database\Connection */
    $db_connection = \Drupal::service('database');
    $max_score = 0;
    $activity = $answer->getActivity();
    $score_query = $db_connection->select('opigno_module_relationship', 'omr')
      ->fields('omr', ['max_score'])
      ->condition('omr.parent_id', $answer->getModule()->id())
      ->condition('omr.parent_vid', $answer->getModule()->getRevisionId())
      ->condition('omr.child_id', $activity->id())
      ->condition('omr.child_vid', $activity->getRevisionId());
    $score_result = $score_query->execute()->fetchObject();
    if ($score_result) {
      $max_score = $score_result->max_score;
    }
    return $max_score;
  }

}
