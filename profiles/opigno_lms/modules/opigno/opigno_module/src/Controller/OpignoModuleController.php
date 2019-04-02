<?php

namespace Drupal\opigno_module\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\group\Entity\Group;
use Drupal\opigno_group_manager\OpignoGroupContext;
use Drupal\opigno_module\Entity\OpignoActivity;
use Drupal\opigno_module\Entity\OpignoActivityInterface;
use Drupal\opigno_module\Entity\OpignoModule;
use Drupal\opigno_module\Entity\OpignoModuleInterface;
use Drupal\opigno_module\Entity\UserModuleStatus;
use Drupal\opigno_module\OpignoModuleBadges;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class OpignoModuleController.
 *
 * @package Drupal\opigno_module
 */
class OpignoModuleController extends ControllerBase {

  /**
   * Get activities related to specific module.
   *
   * @param \Drupal\opigno_module\Entity\OpignoModuleInterface $opigno_module
   *   Opigno module entity object.
   *
   * @return array
   *   Array of module's activities.
   */
  public function moduleActivities(OpignoModuleInterface $opigno_module) {
    /* @todo join table with activity revisions */
    $activities = [];
    /* @var $db_connection \Drupal\Core\Database\Connection */
    $db_connection = \Drupal::service('database');
    $query = $db_connection->select('opigno_activity', 'oa');
    $query->fields('oafd', ['id', 'type', 'name']);
    $query->fields('omr', [
      'activity_status',
      'weight', 'max_score',
      'auto_update_max_score',
      'omr_id',
      'omr_pid',
      'child_id',
      'child_vid',
    ]);
    $query->addJoin('inner', 'opigno_activity_field_data', 'oafd', 'oa.id = oafd.id');
    $query->addJoin('inner', 'opigno_module_relationship', 'omr', 'oa.id = omr.child_vid');
    $query->condition('oafd.status', 1);
    $query->condition('omr.parent_id', $opigno_module->id());
    if ($opigno_module->getRevisionId()) {
      $query->condition('omr.parent_vid', $opigno_module->getRevisionId());
    }
    $query->condition('omr_pid', NULL, 'IS');
    $query->orderBy('omr.weight');
    $result = $query->execute();
    foreach ($result as $activity) {
      $activities[] = $activity;
    }

    return $activities;
  }

  /**
   * Add activities to existing module.
   *
   * @param array $activities
   *   Array of activities that will be added.
   * @param \Drupal\opigno_module\Entity\OpignoModuleInterface $module
   *   Opigno module entity object.
   *
   * @return bool
   *   Activities to module flag.
   *
   * @throws \Exception
   */
  public function activitiesToModule(array $activities, OpignoModuleInterface $module) {
    /* @var $connection \Drupal\Core\Database\Connection */
    $connection = \Drupal::service('database');
    $module_activities_fields = [];
    foreach ($activities as $activity) {
      if ($activity instanceof OpignoActivityInterface) {
        /* @todo Use version ID instead of reuse of ID. */
        $module_activity_fields['parent_id'] = $module->id();
        $module_activity_fields['parent_vid'] = $module->getRevisionId();
        $module_activity_fields['child_id'] = $activity->id();
        $module_activity_fields['child_vid'] = $activity->getRevisionId();
        $module_activity_fields['max_score'] = 10;
        $module_activities_fields[] = $module_activity_fields;
      }
    }
    if (!empty($module_activities_fields)) {
      $insert_query = $connection->insert('opigno_module_relationship')->fields([
        'parent_id',
        'parent_vid',
        'child_id',
        'child_vid',
        'max_score',
      ]);
      foreach ($module_activities_fields as $module_activities_field) {
        $insert_query->values($module_activities_field);
      }
      $insert_query->execute();
    }
    return TRUE;
  }

  /**
   * Method for Take module tab route.
   */
  public function takeModule(Request $request, Group $group, OpignoModuleInterface $opigno_module) {
    /* @var $opigno_module \Drupal\opigno_module\Entity\OpignoModule */
    /* @var $query_options \Symfony\Component\HttpFoundation\ParameterBag */
    $query_options = $request->query;
    // Check Module availability.
    $availability = $opigno_module->checkModuleAvailability();
    if (!$availability['open']) {
      // Module is not available. Based on availability time.
      drupal_set_message($availability['message'], 'warning');
      return $this->redirect('entity.group.canonical', [
        'group' => $group->id(),
      ]);
    }
    // Check Module attempts.
    $allowed_attempts = $opigno_module->get('takes')->value;
    if ($allowed_attempts > 0) {
      // It means, that module attempts are limited.
      // Need to check User attempts.
      $user_attempts = $opigno_module->getModuleAttempts($this->currentUser());
      if (count($user_attempts) >= $allowed_attempts) {
        // User has more attempts then allowed.
        // Check for not finished attempt.
        $active_attempt = $opigno_module->getModuleActiveAttempt($this->currentUser());
        if ($active_attempt == NULL) {
          // There is no not finished attempt.
          drupal_set_message($this->t('Maximum attempts for this module reached.'), 'warning');
          return $this->redirect('entity.group.canonical', [
            'group' => $group->id(),
          ]);
        }
      }
    }

    // Get activities from the Module.
    $activities = $opigno_module->getModuleActivities();
    if (count($activities) > 0) {
      // Create new attempt or resume existing one.
      $attempt = $opigno_module->getModuleActiveAttempt($this->currentUser());
      if ($attempt == NULL) {
        // No existing attempt, create new one.
        $attempt = UserModuleStatus::create([]);
        $attempt->setModule($opigno_module);
        $attempt->setFinished(0);
        $attempt->save();
      }
      else {
        // Not finished attempt exist. Check if user allowed to resume.
        $allow_resume = $opigno_module->getAllowResume();
        $continue_param = $query_options->get('continue');
        // Continue param will exist only after previous answer form submit.
        if (!$allow_resume && !$continue_param) {
          // If resume is not allowed we need to finish existing attempt.
          $attempt->finishAttempt();
          drupal_set_message($this->t('Module resume is not allowed.'), 'warning');
          // After finish existing attempt we will redirect again
          // to take page to start new attempt.
          return $this->redirect('opigno_module.take_module', [
            'group' => $group->id(),
            'opigno_module' => $opigno_module->id(),
          ]);
        }
      }
      // Get activity that will be answered.
      $next_activity_id = NULL;
      $last_activity_id = $attempt->getLastActivityId();
      $get_next = FALSE;
      // Get additional module settings.
      $backwards_param = $query_options->get('backwards');
      // Take into account randomization options.
      $randomization = $opigno_module->getRandomization();
      if ($randomization > 0) {
        // Get random activity and put it in a sequence.
        $random_activity = $opigno_module->getRandomActivity($attempt);
        if ($random_activity) {
          $next_activity_id = $random_activity->id();
        }
      }
      else {
        foreach ($activities as $activity_id => $activity) {
          // Check for backwards navigation submit.
          if ($opigno_module->getBackwardsNavigation() && isset($prev_activity_id) && $last_activity_id == $activity_id && $backwards_param) {
            $next_activity_id = $prev_activity_id;
            break;
          }
          if (is_null($last_activity_id) || $get_next) {
            // Get the first activity.
            $next_activity_id = $activity_id;
            break;
          }
          if ($last_activity_id == $activity_id) {
            // Get the next activity after this one.
            $get_next = TRUE;
          }
          $prev_activity_id = $activity_id;
        }

        // Check if user navigate to previous module with "back" button.
        $from_first_activity = (key($activities) == $last_activity_id)
          || (key($activities) == $attempt->getCurrentActivityId())
          || ($last_activity_id == NULL);
        if ($opigno_module->getBackwardsNavigation() && $from_first_activity && $backwards_param) {
          return $this->redirect('opigno_module.get_previous_module', [
            'opigno_module' => $opigno_module->id(),
          ]);
        }
      }
      // Get group context.
      $cid = OpignoGroupContext::getCurrentGroupContentId();
      $gid = OpignoGroupContext::getCurrentGroupId();
      $steps = $this->getAllStepsOnlyModules($gid);
      foreach ($steps as $step) {
        if ($step['id'] == $opigno_module->id() && $step['cid'] != $cid) {
          // Update content cid.
          OpignoGroupContext::setCurrentContentId($step['cid']);
        }
      }
      $activities_storage = static::entityTypeManager()->getStorage('opigno_activity');
      if (!is_null($next_activity_id)) {
        // Means that we have some activity to answer.
        $attempt->setCurrentActivity($activities_storage->load($next_activity_id));
        $attempt->save();
        return $this->redirect('opigno_module.group.answer_form', [
          'group' => $group->id(),
          'opigno_activity' => $next_activity_id,
          'opigno_module' => $opigno_module->id(),
        ]);
      }
      else {
        // If a user clicks "Back" button,
        // show the last question instead of summary page.
        $previous = $query_options->get('previous');
        if ($previous) {
          return $this->redirect('opigno_module.group.answer_form', [
            'group' => $group->id(),
            'opigno_activity' => $last_activity_id,
            'opigno_module' => $opigno_module->id(),
          ]);
        }
        else {
          $attempt->finishAttempt();
          return $this->redirect('opigno_module.module_result', [
            'opigno_module' => $opigno_module->id(),
            'user_module_status' => $attempt->id(),
          ]);
        }
      }
    }

    drupal_set_message($this->t('This module does not contain any activity.'), 'warning');
    return $this->redirect('entity.opigno_module.canonical', [
      'opigno_module' => $opigno_module->id(),
    ]);
  }

  /**
   * Returns module question answer form title.
   */
  public function moduleQuestionAnswerFormTitle(OpignoModuleInterface $opigno_module, OpignoActivityInterface $opigno_activity) {
    return $opigno_module->getName();
  }

  /**
   * Returns module question answer form.
   */
  public function moduleQuestionAnswerForm(OpignoModuleInterface $opigno_module, OpignoActivityInterface $opigno_activity) {
    $build = [];
    $user = $this->currentUser();
    $uid = $user->id();
    $attempt = $opigno_module->getModuleActiveAttempt($user);

    // Check if user have access on this step of LP.
    $gid = OpignoGroupContext::getCurrentGroupId();
    $current_cid = OpignoGroupContext::getCurrentGroupContentId();
    $group_steps = opigno_learning_path_get_steps($gid, $uid);

    // Load group courses substeps.
    array_walk($group_steps, function ($step) use ($uid, &$steps) {
      if ($step['typology'] === 'Course') {
        $course_steps = opigno_learning_path_get_steps($step['id'], $uid);
        $last_course_step = end($course_steps);
        $course_steps = array_map(function ($course_step) use ($step, $last_course_step) {
          $course_step['parent'] = $step;
          $course_step['is last child'] = $course_step['cid'] === $last_course_step['cid'];
          return $course_step;
        }, $course_steps);

        if (!isset($steps)) {
          $steps = [];
        }
        $steps = array_merge($steps, $course_steps);
      }
      else {
        $steps[] = $step;
      }
    });

    // Check if user try to load activity from another module.
    $module_activities = opigno_learning_path_get_module_activities($opigno_module->id(), $uid);
    $activities_ids = array_keys($module_activities);

    if (!in_array($opigno_activity->id(), $activities_ids)) {
      $query = \Drupal::database()
        ->select('user_module_status', 'u_m_s');
      $query->fields('u_m_s', ['current_activity']);
      $query->condition('u_m_s.user_id', $uid);
      $query->condition('u_m_s.module', $opigno_module->id());
      $query->condition('u_m_s.learning_path', $gid);
      $query->orderBy('u_m_s.id', 'DESC');
      $current_activity = $query
        ->execute()
        ->fetchField();

      // Set first activity as current if we can't get it from module status.
      if (is_null($current_activity)) $current_activity = $module_activities[$activities_ids[0]];

      return $this->redirect('opigno_module.group.answer_form', [
        'group' => $gid,
        'opigno_module' => $opigno_module->id(),
        'opigno_activity' => $current_activity,
      ]);
    }

    if (!empty($steps)) {
      foreach ($steps as $key => $step) {
        // Check if user manually entered the url.
        if ($current_cid == $step['cid']) {
          if ($opigno_module->id() != $step['id']) {
            return $this->redirect('opigno_learning_path.steps.next', [
              'group' => $gid,
              'parent_content' => $steps[$key]['cid'],
            ]);
          }
          break;
        }

        // Check if user is trying to skip mandatory activity.
        if ($step['mandatory'] == 1 && $step['required score'] > $step['current attempt score']) {
          return $this->redirect('opigno_learning_path.steps.next', [
            'group' => $gid,
            'parent_content' => $steps[$key]['cid'],
          ]);
        }
      }
    }

    if (!is_null($attempt)) {
      $existing_answer = $opigno_activity->getUserAnswer($opigno_module, $attempt, $user);
      if (!is_null($existing_answer)) {
        $answer = $existing_answer;
      }
    }
    if (!isset($answer)) {
      $answer = static::entityTypeManager()->getStorage('opigno_answer')->create([
        'type' => $opigno_activity->getType(),
        'activity' => $opigno_activity->id(),
        'module' => $opigno_module->id(),
      ]);
    }
    // Output rendered activity of the specified type.
    $build[] = \Drupal::entityTypeManager()->getViewBuilder('opigno_activity')->view($opigno_activity, 'activity');
    // Output answer form of the same activity type.
    $build[] = $this->entityFormBuilder()->getForm($answer);

    return $build;
  }

  /**
   * Returns user results.
   */
  public function userResults(OpignoModuleInterface $opigno_module) {
    $content = [];
    $results_feedback = $opigno_module->getResultsOptions();
    $user_attempts = $opigno_module->getModuleAttempts($this->currentUser());
    foreach ($user_attempts as $user_attempt) {
      /* @var $user_attempt UserModuleStatus */
      $score_percents = $user_attempt->getScore();
      $max_score = $user_attempt->getMaxScore();
      $score = round(($max_score * $score_percents) / 100);
      foreach ($results_feedback as $result_feedback) {
        // Check if result is between low and high percents.
        // Break on first meet.
        if ($score_percents <= $result_feedback->option_end && $score_percents >= $result_feedback->option_start) {
          $feedback = check_markup($result_feedback->option_summary, $result_feedback->option_summary_format);
          break;
        }
      }
      $content[] = [
        '#theme' => 'item_list',
        '#items' => [
          $this->t('You got %score of %max_score possible points.', [
            '%max_score' => $max_score,
            '%score' => $score,
          ]),
          $this->t('Score: %score%', ['%score' => $user_attempt->getScore()]),
          isset($feedback) ? $feedback : '',
        ],
      ];
    }
    return $content;
  }

  /**
   * Returns user result.
   */
  public function userResult(OpignoModuleInterface $opigno_module, UserModuleStatus $user_module_status = NULL) {
    $content = [];
    $user_answers = $user_module_status->getAnswers();
    $question_number = 0;
    $module_activities = $opigno_module->getModuleActivities();
    $score = $user_module_status->getScore();

    foreach ($user_answers as $answer) {
      $question_number++;
      $answer_activity = $answer->getActivity();
      if (!$answer_activity) {
        continue;
      }
      $content[] = [
        '#theme' => 'opigno_user_result_item',
        '#opigno_answer' => $answer,
        '#opigno_answer_activity' => $answer_activity ,
        '#question_number' => $question_number,
        '#answer_max_score' => isset($module_activities[$answer_activity->id()]) ? $module_activities[$answer_activity->id()]->max_score : 0,
      ];
    }

    $user = $this->currentUser();
    $uid = $user->id();
    $gid = OpignoGroupContext::getCurrentGroupId();
    $cid = OpignoGroupContext::getCurrentGroupContentId();

    if (!empty($gid)) {
      $group = Group::load($gid);
      $group_steps = opigno_learning_path_get_steps($gid, $uid);
      $steps = [];

      // Load courses substeps.
      array_walk($group_steps, function ($step, $key) use ($uid, &$steps) {
        $step['position'] = $key;
        if ($step['typology'] === 'Course') {
          $course_steps = opigno_learning_path_get_steps($step['id'], $uid);
          // Save parent course and position.
          $course_steps = array_map(function ($course_step, $key) use ($step) {
            $course_step['parent'] = $step;
            $course_step['position'] = $key;
            return $course_step;
          }, $course_steps, array_keys($course_steps));
          $steps = array_merge($steps, $course_steps);
        }
        else {
          $steps[] = $step;
        }
      });

      // Find current step.
      $count = count($steps);
      $current_step = NULL;

      for ($i = 0; $i < $count; ++$i) {
        $step = $steps[$i];

        if ($step['cid'] === $cid) {
          $current_step = $step;
          break;
        }
      }

      // Remove the live meeting and instructor-led training steps.
      $steps = array_filter($steps, function ($step) {
        return !in_array($step['typology'], ['Meeting', 'ILT']);
      });

      if ($current_step !== NULL) {
        $last_step = end($steps);
        $is_last = $last_step['cid'] === $current_step['cid'];

        $current_step['current attempt score'] = $score;

        $start_time = (int) $user_module_status->get('started')->getValue()[0]['value'];
        $end_time = (int) $user_module_status->get('finished')->getValue()[0]['value'];
        $time = $end_time > $start_time ? $end_time - $start_time : 0;
        $current_step['current attempt time'] = $time;
        if (isset($current_step['parent'])) {
          $current_step['parent']['current attempt time'] = $time;
        }

        // Send notification about manual evaluation.
        $is_evaluated = $user_module_status->get('evaluated')->getValue()[0]['value'];
        if (!$is_evaluated) {
          // Get user managers.
          $members = $group->getMembers('learning_path-user_manager');
          $members_entities = array_map(function ($member) {
            return $member->getUser();
          }, $members);
          // Get admins.
          $admins = opigno_module_get_users_by_role('administrator');
          // Get array of all users who must receive notification.
          $users = array_merge($members_entities, $admins);
          foreach ($users as $user) {
            opigno_set_message($user->id(), $this->t("Module @module need manual evaluating.", [
              '@module' => $current_step['name'],
            ]));
          }
        };

        if ($score >= $current_step['required score']) {
          opigno_set_message($uid, $this->t('Successfully completed module @name', [
            '@name' => $current_step['name'],
          ]));

          if ($is_last) {
            opigno_set_message($uid, $this->t('Congratulations! You successfully finished the training @name', [
              '@name' => $group->label(),
            ]));
          }
        }

        if (function_exists('opigno_learning_path_save_step_achievements')) {
          // Save current step parent achievements.
          $parent_id = isset($current_step['parent'])
            ? opigno_learning_path_save_step_achievements($gid, $uid, $current_step['parent'])
            : 0;
          // Save current step achievements.
          opigno_learning_path_save_step_achievements($gid, $uid, $current_step, $parent_id);
        }

        if (function_exists('opigno_learning_path_save_achievements')) {
          // Save training achievements.
          opigno_learning_path_save_achievements($gid, $uid);
        }

        // Modules notifications and badges.
        if ($current_step['typology'] == 'Module' && $opigno_module->badge_active->value) {
          $badge_notification = '';
          $save_badge = FALSE;

          // Badge notification for finished state.
          if ($opigno_module->badge_criteria->value == 'finished') {
            $badge_notification = $opigno_module->badge_name->value;
            $save_badge = TRUE;
          }

          // Badge notification for successful finished state.
          if ($opigno_module->badge_criteria->value == 'success' && $score >= $current_step['required score']) {
            $badge_notification = $opigno_module->badge_name->value;
            $save_badge = TRUE;
          }

          if (!empty($save_badge)) {
            // Save badge.
            try {
              OpignoModuleBadges::opignoModuleSaveBadge($uid, $gid, $current_step['typology'], $current_step['id']);
            }
            catch (\Exception $e) {
              $this->messenger()->addMessage($e->getMessage(), 'error');
            }
          }

          if ($badge_notification) {
            opigno_set_message($uid, $this->t('You earned a badge "@name"', [
              '@name' => $badge_notification,
            ]));
          }
        }

        // Courses notifications and badges.
        if ($current_step['typology'] == 'Module' && !empty($current_step['parent']) && $current_step['parent']['typology'] == 'Course') {
          $course = Group::load($current_step['parent']['id']);
          if ($course->badge_active->value) {
            $badge_notification = '';
            $save_badge = FALSE;

            $course_steps = opigno_learning_path_get_steps($current_step['parent']['id'], $uid);
            $course_last_step = end($course_steps);
            $course_is_last = $course_last_step['cid'] === $current_step['cid'];

            if ($course_is_last) {
              // Badge notification for finished state.
              if ($course->badge_criteria->value == 'finished') {
                $badge_notification = $course->badge_name->value;
                $save_badge = TRUE;
              }

              // Badge notification for successful finished state.
              if ($course->badge_criteria->value == 'success' && $score >= $current_step['required score']) {
                $badge_notification = $course->badge_name->value;
                $save_badge = TRUE;
              }

              if (!empty($save_badge)) {
                // Save badge.
                try {
                  OpignoModuleBadges::opignoModuleSaveBadge($uid, $gid, 'Course', $current_step['parent']['id']);
                }
                catch (\Exception $e) {
                  $this->messenger()->addMessage($e->getMessage(), 'error');
                }
              }

              if ($badge_notification) {
                opigno_set_message($uid, $this->t('You earned a badge "@name"', [
                  '@name' => $badge_notification,
                ]));
              }
            }
          }
        }

        // Get module feedback.
        $feedback_options = $opigno_module->getResultsOptions();
        if ($feedback_options) {
          $feedback_option = array_filter($feedback_options, function ($option) use ($score) {
            $min = (int) $option->option_start;
            $max = (int) $option->option_end;
            if (in_array($score, range($min, $max))) {
              return TRUE;
            }
          });
          if (isset($feedback_option)) {
            // Get only first feedback.
            $feedback_option = reset($feedback_option);
            $feedback['feedback'] = [
              '#type' => 'container',
              '#attributes' => [
                'id' => 'module-feedback',
              ],
              [
                '#type' => 'html_tag',
                '#tag' => 'h4',
                '#attributes' => [
                  'class' => ['feedback-summary'],
                ],
                '#value' => $feedback_option->option_summary,
              ],
            ];
            // Put feedback in the beginning of array.
            array_unshift($content, $feedback);
          }
        }

        // Check if all activities has 0 score.
        // If has - immediately redirect to next step.
        $has_min_score = FALSE;
        foreach ($module_activities as $activity) {
          $score = $activity->max_score;
          if ($score > 0) {
            $has_min_score = TRUE;
            break;
          }
        }
        // Redirect if module has all activities with 0 min score
        // and HideResults option enabled.
        if (!$has_min_score && $opigno_module->getHideResults()) {
          if (!$is_last && !in_array($current_step['typology'], ['Meeting', 'ITL'])) {
            // Redirect to next step.
            return $this->redirect('opigno_learning_path.steps.next', ['group' => $gid, 'parent_content' => $cid]);
          }
          else {
            // Redirect to homepage.
            return $this->redirect('entity.group.canonical', ['group' => $gid]);
          }
        }

        $options = [
          'attributes' => [
            'class' => [
              'btn',
              'btn-success',
            ],
            'id' => 'edit-submit',
          ],
        ];

        if (!$is_last && !in_array($current_step['typology'], ['Meeting', 'ITL'])) {
          $title = 'Next';
          $route = 'opigno_learning_path.steps.next';
          $route_params = [
            'group' => $gid,
            'parent_content' => $cid,
          ];
        }
        else {
          $title = 'Back to training homepage';
          $route = 'entity.group.canonical';
          $route_params = [
            'group' => $gid,
          ];
        }

        $link['form-actions'] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['form-actions'],
            'id' => 'edit-actions',
          ],
          '#title' => 'test',
        ];

        $link['form-actions'][] = Link::createFromRoute(
          $title,
          $route,
          $route_params,
          $options
        )->toRenderable();

        $content[] = $link;
      }
    }

    return $content;
  }

  /**
   * This method get parent module for current module if exist.
   */
  public function moduleGetPrevious(OpignoModuleInterface $opigno_module) {
    $uid = $this->currentUser()->id();

    $cid = OpignoGroupContext::getCurrentGroupContentId();
    $gid = OpignoGroupContext::getCurrentGroupId();

    // Load group steps.
    $group_steps = opigno_learning_path_get_steps($gid, $uid);
    $steps = [];

    // Load group courses substeps.
    array_walk($group_steps, function ($step) use ($uid, &$steps) {
      if ($step['typology'] === 'Course') {
        $course_steps = opigno_learning_path_get_steps($step['id'], $uid);
        $last_course_step = end($course_steps);
        $course_steps = array_map(function ($course_step) use ($step, $last_course_step) {
          $course_step['parent'] = $step;
          $course_step['is last child'] = $course_step['cid'] === $last_course_step['cid'];
          return $course_step;
        }, $course_steps);
        $steps = array_merge($steps, $course_steps);
      }
      else {
        $steps[] = $step;
      }
    });

    // Find current & next step.
    $count = count($steps);
    $previous_step = NULL;
    for ($i = 0; $i < $count; ++$i) {
      if ($steps[$i]['cid'] == $cid) {
        $previous_step = $steps[$i - 1];
        break;
      }
    }

    // Get module.
    $previous_module = OpignoModule::load($previous_step['id']);
    // Get all user module attempts.
    $user_attempts = $previous_module->getModuleAttempts($this->currentUser());
    // Get last active attempt.
    $active_attempt = $previous_module->getModuleActiveAttempt($this->currentUser());
    // Check Module attempts.
    $allowed_attempts = $previous_module->get('takes')->value;
    if ($allowed_attempts > 0) {
      // It means, that module attempts are limited.
      // Need to check User attempts.
      if (count($user_attempts) >= $allowed_attempts) {
        // User has more attempts then allowed.
        // Check for not finished attempt.
        if ($active_attempt == NULL) {
          // There is no not finished attempt.
          drupal_set_message($this->t('Maximum attempts for this module reached.'), 'warning');
          return $this->redirect('opigno_module.take_module', [
            'group' => $gid,
            'opigno_module' => $opigno_module->id(),
          ]);
        }
      }
    }
    // Take into account randomization options.
    $randomization = $previous_module->getRandomization();
    if ($randomization > 0) {
      // @todo: notify user that he will lost his previous module results. Try do this with ajax.
      drupal_set_message($this->t("You can't navigate back to the module with random activities order."), 'warning');
      return $this->redirect('opigno_module.take_module', [
        'group' => $gid,
        'opigno_module' => $opigno_module->id(),
      ]);
    }
    // Check if user allowed to resume.
    $allow_resume = $previous_module->getAllowResume();
    // Continue param will exist only after previous answer form submit.
    if (!$allow_resume) {
      // Module can't be resumed.
      drupal_set_message($this->t('Module resume is not allowed.'), 'warning');
      // After finish existing attempt we will redirect again
      // to take page to start new attempt.
      return $this->redirect('opigno_module.take_module', [
        'group' => $gid,
        'opigno_module' => $opigno_module->id(),
      ]);
    }
    // Get activities from the Module.
    $activities = $previous_module->getModuleActivities();
    if (count($activities) > 0) {
      if ($user_attempts == NULL) {
        // User has not previous module attempts.
        drupal_set_message($this->t("You can't navigate back to the module that you don't attempt."), 'warning');
        return $this->redirect('opigno_module.take_module', [
          'group' => $gid,
          'opigno_module' => $opigno_module->id(),
        ]);
      }
      if ($active_attempt == NULL) {
        // Previous module is finished.
        // Get last finished attempt and make unfinished.
        /* @var \Drupal\opigno_module\Entity\UserModuleStatus $last_attempt */
        $last_attempt = end($user_attempts);
        // Set current activity.
        $current_activity = $last_attempt->getLastActivity();
        $last_attempt->setCurrentActivity($current_activity);
        // Set last activity.
        $last_activity_info = array_slice($activities, -2, 1, TRUE);
        $last_activity = OpignoActivity::load(key($last_activity_info));
        $last_attempt->setLastActivity($last_activity);
        $last_attempt->setFinished(0);
        $last_attempt->save();
      }
      // Update module status for current module.
      $current_module_attempt = $opigno_module->getModuleActiveAttempt($this->currentUser());
      $current_module_attempt->last_activity->target_id = NULL;
      $current_module_attempt->save();
      // Update content id.
      OpignoGroupContext::setCurrentContentId($previous_step['cid']);
      // Redirect to the previous module.
      return $this->redirect('opigno_module.take_module', [
        'group' => $gid,
        'opigno_module' => $previous_module->id(),
      ], ['query' => ['previous' => TRUE]]);
    }

    // Module can't be navigated.
    drupal_set_message($this->t('Can not navigate to previous module.'), 'warning');
    return $this->redirect('opigno_module.take_module', [
      'group' => $gid,
      'opigno_module' => $opigno_module->id(),
    ]);
  }

  /**
   * Function for getting group steps without courses, only modules.
   */
  protected function getAllStepsOnlyModules($group_id) {
    $uid = \Drupal::currentUser()->id();
    // Load group steps.
    $group_steps = opigno_learning_path_get_steps($group_id, $uid);
    $steps = [];

    // Load group courses substeps.
    array_walk($group_steps, function ($step, $key) use ($uid, &$steps) {
      $step['position'] = $key;
      if ($step['typology'] === 'Course') {
        $course_steps = opigno_learning_path_get_steps($step['id'], $uid);
        // Save parent course and position.
        $course_steps = array_map(function ($course_step, $key) use ($step) {
          $course_step['parent'] = $step;
          $course_step['position'] = $key;
          return $course_step;
        }, $course_steps, array_keys($course_steps));
        $steps = array_merge($steps, $course_steps);
      }
      else {
        $steps[] = $step;
      }
    });

    return $steps;
  }

}
