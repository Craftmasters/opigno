<?php

namespace Drupal\opigno_learning_path\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;
use Drupal\opigno_module\Entity\OpignoActivity;
use Drupal\opigno_module\Entity\OpignoModule;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\opigno_module\OpignoModuleBadges;

/**
 * Class LearningPathAchievementController.
 */
class LearningPathAchievementController extends ControllerBase {

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('database'));
  }

  /**
   * Returns max score that user can have in this module & activity.
   *
   * @param \Drupal\opigno_module\Entity\OpignoModule $module
   *   Module object.
   * @param \Drupal\opigno_module\Entity\OpignoActivity $activity
   *   Activity object.
   *
   * @return int
   *   Max score.
   */
  protected function get_activity_max_score($module, $activity) {
    $query = $this->database->select('opigno_module_relationship', 'omr')
      ->fields('omr', ['max_score'])
      ->condition('omr.parent_id', $module->id())
      ->condition('omr.parent_vid', $module->getRevisionId())
      ->condition('omr.child_id', $activity->id())
      ->condition('omr.child_vid', $activity->getRevisionId())
      ->condition('omr.activity_status', 1);
    $results = $query->execute()->fetchAll();

    if (empty($results)) {
      return 0;
    }

    $result = reset($results);
    return $result->max_score;
  }

  /**
   * Returns step renderable array.
   *
   * @param array $step
   *   Step.
   *
   * @return array
   *   Step renderable array.
   */
  protected function build_step_name(array $step) {
    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['lp_step_name'],
      ],
      [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => [
          'class' => ['lp_step_name_title'],
        ],
        '#value' => $step['name'],
      ],
      [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => [
          'class' => ['lp_step_name_activities'],
        ],
        '#value' => ' &dash; ' . $step['activities'] . ' Activities',
      ],
    ];
  }

  /**
   * Returns step score renderable array.
   *
   * @param array $step
   *   Step.
   *
   * @return array
   *   Step score renderable array.
   */
  protected function build_step_score(array $step) {
    $uid = $this->currentUser()->id();

    if (opigno_learning_path_is_attempted($step, $uid)) {
      $score = $step['best score'];

      return [
        '#type' => 'container',
        [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => $score . '%',
        ],
        [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['lp_step_result_bar'],
          ],
          [
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#attributes' => [
              'class' => ['lp_step_result_bar_value'],
              'style' => "width: $score%",
            ],
            '#value' => '',
          ],
        ],
      ];
    }

    return ['#markup' => '&nbsp;'];
  }

  /**
   * Returns step state renderable array.
   *
   * @param array $step
   *   Step.
   *
   * @return array
   *   Step state renderable array.
   */
  protected function build_step_state(array $step) {
    $uid = $this->currentUser()->id();
    $status = opigno_learning_path_get_step_status($step, $uid);
    $markups = [
      'pending' => '<span class="lp_step_state_pending"></span>'
      . t('Pending'),
      'failed' => '<span class="lp_step_state_failed"></span>'
      . t('Failed'),
      'passed' => '<span class="lp_step_state_passed"></span>'
      . t('Passed'),
    ];
    $markup = isset($markups[$status]) ? $markups[$status] : '&dash;';
    return ['#markup' => $markup];
  }

  /**
   * Returns module panel renderable array.
   *
   * @param \Drupal\group\Entity\GroupInterface $training
   *   Group.
   * @param null|\Drupal\group\Entity\GroupInterface $course
   *   Group.
   * @param \Drupal\opigno_module\Entity\OpignoModule $module
   *   Module.
   *
   * @return array
   *   Module panel renderable array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function build_module_panel(GroupInterface $training, GroupInterface $course = NULL, OpignoModule $module) {
    /** @var \Drupal\Core\Datetime\DateFormatterInterface $date_formatter */
    $date_formatter = \Drupal::service('date.formatter');
    $user = $this->currentUser();

    $parent = isset($course) ? $course : $training;
    $step = opigno_learning_path_get_module_step($parent->id(), $user->id(), $module);
    $completed_on = $step['completed on'];
    $completed_on = $completed_on > 0
      ? $date_formatter->format($completed_on, 'custom', 'F d, Y')
      : '';

    /** @var \Drupal\opigno_module\Entity\OpignoModule $module */
    $module = OpignoModule::load($step['id']);
    /** @var \Drupal\opigno_module\Entity\UserModuleStatus[] $attempts */
    $attempts = $module->getModuleAttempts($user);

    $activities = $module->getModuleActivities();
    /** @var \Drupal\opigno_module\Entity\OpignoActivity[] $activities */
    $activities = array_map(function ($activity) {
      /** @var \Drupal\opigno_module\Entity\OpignoActivity $activity */
      return OpignoActivity::load($activity->id);
    }, $activities);

    if (!empty($attempts)) {
      // If "newest" score - get the last attempt,
      // else - get the best attempt.
      $attempt = $this->getTargetAttempt($attempts, $module);
      $max_score = $attempt->calculateMaxScore();
      $score_percent = opigno_learning_path_get_attempt_score($attempt);
      $score = round($score_percent * $max_score / 100);
    }
    else {
      $attempt = NULL;
      $max_score = !empty($activities)
        ? array_sum(array_map(function ($activity) use ($module) {
          return (int) $this->get_activity_max_score($module, $activity);
        }, $activities))
        : 0;
      $score_percent = 0;
      $score = 0;
    }

    $activities = array_map(function ($activity) use ($user, $module, $attempt) {
      /** @var \Drupal\opigno_module\Entity\OpignoActivity $activity */
      /** @var \Drupal\opigno_module\Entity\OpignoAnswer $answer */
      $answer = isset($attempt)
        ? $activity->getUserAnswer($module, $attempt, $user)
        : NULL;
      $score = isset($answer) ? $answer->getScore() : 0;
      $max_score = (int) $this->get_activity_max_score($module, $activity);

      return [
        ['data' => $activity->getName()],
        [
          'data' => [
            '#markup' => $score . '/' . $max_score,
          ],
        ],
        [
          'data' => [
            '#type' => 'html_tag',
            '#tag' => 'span',
            '#attributes' => [
              'class' => [isset($answer)
                ? 'lp_step_state_passed'
                : 'lp_step_state_failed',
              ],
            ],
            '#value' => '',
          ],
        ],
      ];
    }, $activities);

    $activities = [
      '#type' => 'table',
      '#attributes' => [
        'class' => ['lp_module_panel_activities_overview'],
      ],
      '#rows' => $activities,
    ];

    $training_id = $training->id();
    $module_id = $module->id();
    if (isset($course)) {
      $course_id = $course->id();
      $id = "module_panel_${training_id}_${course_id}_${module_id}";
    }
    else {
      $id = "module_panel_${training_id}_${module_id}";
    }

    return [
      '#type' => 'container',
      '#attributes' => [
        'id' => $id,
        'class' => ['lp_module_panel'],
      ],
      [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['lp_module_panel_header'],
        ],
        [
          '#markup' => '<a href="#" class="lp_module_panel_close">&times;</a>',
        ],
        [
          '#type' => 'html_tag',
          '#tag' => 'h3',
          '#attributes' => [
            'class' => ['lp_module_panel_title'],
          ],
          '#value' => $step['name'] . ' '
          . (!empty($completed_on)
              ? t('completed')
              : ''),
        ],
      ],
      [
        '#type' => 'html_tag',
        '#tag' => 'hr',
        '#value' => '',
      ],
      [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['lp_module_panel_content'],
        ],
        (!empty($completed_on)
          ? [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => t('@name completed on @date', [
              '@name' => $step['name'],
              '@date' => $completed_on,
            ]),
          ]
          : []),
        [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => t('User got @score of @max_score possible points.', [
            '@score' => $score,
            '@max_score' => $max_score,
          ]),
        ],
        [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => t('Total score @percent%', [
            '@percent' => $score_percent,
          ]),
        ],
        [
          '#type' => 'html_tag',
          '#tag' => 'h3',
          '#attributes' => [
            'class' => ['lp_module_panel_overview_title'],
          ],
          '#value' => t('Activities Overview'),
        ],
        $activities,
        (isset($attempt)
          ? [
            Link::createFromRoute('Details', 'opigno_module.module_result', [
              'opigno_module' => $module->id(),
              'user_module_status' => $attempt->id(),
            ])->toRenderable(),
          ]
          : []),
      ],
    ];
  }

  /**
   * Returns module approved activities.
   *
   * @param int $parent
   *   Group ID.
   * @param int $module
   *   Module ID.
   *
   * @return int
   *   Approved activities.
   */
  protected function module_approved_activities($parent, $module) {
    $approved = 0;
    $user = $this->currentUser();
    $parent = Group::load($parent);
    $module = OpignoModule::load($module);

    $step = opigno_learning_path_get_module_step($parent->id(), $user->id(), $module);

    /** @var \Drupal\opigno_module\Entity\OpignoModule $module */
    $module = OpignoModule::load($step['id']);
    /** @var \Drupal\opigno_module\Entity\UserModuleStatus[] $attempts */
    $attempts = $module->getModuleAttempts($user);

    $activities = $module->getModuleActivities();
    /** @var \Drupal\opigno_module\Entity\OpignoActivity[] $activities */
    $activities = array_map(function ($activity) {
      /** @var \Drupal\opigno_module\Entity\OpignoActivity $activity */
      return OpignoActivity::load($activity->id);
    }, $activities);

    if (!empty($attempts)) {
      // If "newest" score - get the last attempt,
      // else - get the best attempt.
      $attempt = $this->getTargetAttempt($attempts, $module);
      $max_score = $attempt->calculateMaxScore();
      $score_percent = opigno_learning_path_get_attempt_score($attempt);
      $score = round($score_percent * $max_score / 100);
    }
    else {
      $attempt = NULL;
      $max_score = !empty($activities)
        ? array_sum(array_map(function ($activity) use ($module) {
          return (int) $this->get_activity_max_score($module, $activity);
        }, $activities))
        : 0;
      $score_percent = 0;
      $score = 0;
    }

    $activities = array_map(function ($activity) use ($user, $module, $attempt) {
      /** @var \Drupal\opigno_module\Entity\OpignoActivity $activity */
      /** @var \Drupal\opigno_module\Entity\OpignoAnswer $answer */
      $answer = isset($attempt)
        ? $activity->getUserAnswer($module, $attempt, $user)
        : NULL;
      $score = isset($answer) ? $answer->getScore() : 0;
      $max_score = (int) $this->get_activity_max_score($module, $activity);

      return [
        isset($answer) ? 'lp_step_state_passed' : 'lp_step_state_failed',
      ];
    }, $activities);

    foreach ($activities as $activity) {

      if ($activity[0] == 'lp_step_state_passed') {
        $approved++;
      }
    }

    return $approved;
  }

  /**
   * Returns course steps renderable array.
   *
   * @param \Drupal\group\Entity\GroupInterface $training
   *   Parent training group entity.
   * @param \Drupal\group\Entity\GroupInterface $course
   *   Course group entity.
   *
   * @return array
   *   Course steps renderable array.
   */
  protected function build_course_steps(GroupInterface $training, GroupInterface $course) {
    $user = $this->currentUser();
    $steps = opigno_learning_path_get_steps($course->id(), $user->id());
    $rows = array_map(function ($step) use ($training, $course, $user) {
      // var_dump($step);
      return [
        'data-training' => $training->id(),
        'data-course' => $course->id(),
        'data-module' => $step['id'],
        'data' => [
          ['data' => $this->build_step_name($step)],
          ['data' => $this->build_step_score($step)],
          ['data' => $this->build_step_state($step)],
          [
            'data' => Link::createFromRoute(t('details'), 'opigno_module.module_result', [
              'opigno_module' => $step['id'],
              'user_module_status' => $step['best_attempt'],
            ])->toRenderable(),
          ],
        ],
      ];
    }, $steps);

    $module_steps = array_filter($steps, function ($step) {
      return $step['typology'] === 'Module';
    });
    $module_panels = array_map(function ($step) use ($training, $course) {
      $training_id = $training->id();
      $course_id = $course->id();
      $module_id = $step['id'];
      return [
        '#type' => 'container',
        '#attributes' => [
          'id' => "module_panel_${training_id}_${course_id}_${module_id}",
          'class' => ['lp_module_panel'],
        ],
      ];
    }, $module_steps);

    return [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'course_steps_' . $course->id(),
        'class' => [
          'lp_course_steps_wrapper',
          'ml-md-7',
          'mr-md-5',
          'mb-5',
        ],
      ],
      [
        '#type' => 'html_tag',
        '#tag' => 'h4',
        '#attributes' => [
          'class' => ['lp_course_steps_title'],
        ],
        '#value' => t('Course Content'),
      ],
      [
        '#type' => 'table',
        '#attributes' => [
          'class' => ['lp_course_steps', 'mb-0'],
        ],
        '#header' => [
          t('Module'),
          t('Results'),
          t('State'),
          t('Details'),
        ],
        '#rows' => $rows,
      ],
      $module_panels,
    ];
  }

  /**
   * Returns course passed steps.
   *
   * @param \Drupal\group\Entity\GroupInterface $training
   *   Parent training group entity.
   * @param \Drupal\group\Entity\GroupInterface $course
   *   Course group entity.
   *
   * @return array
   *   Course passed steps.
   */
  protected function course_steps_passed(GroupInterface $training, GroupInterface $course) {
    $user = $this->currentUser();
    $steps = opigno_learning_path_get_steps($course->id(), $user->id());

    $passed = 0;
    foreach ($steps as $step) {
      $status = opigno_learning_path_get_step_status($step, $user->id());
      if ($status == 'passed') {
        $passed++;
      }
    }

    return [
      'passed' => $passed,
      'total' => count($steps),
    ];
  }

  /**
   * Returns LP steps.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group.
   *
   * @return array
   *   LP steps.
   */
  protected function build_lp_steps(GroupInterface $group) {
    $user = $this->currentUser();
    $uid = $user->id();
    $result = (int) $this->database
      ->select('opigno_learning_path_achievements', 'a')
      ->fields('a')
      ->condition('uid', $user->id())
      ->condition('gid', $group->id())
      ->condition('status', 'completed')
      ->countQuery()
      ->execute()
      ->fetchField();
    if ($result === 0) {
      $steps = opigno_learning_path_get_steps($group->id(), $user->id());
      $steps = array_filter($steps, function ($step) use ($user) {
        if ($step['typology'] === 'Meeting') {
          // If the user have not the collaborative features role.
          if (!$user->hasPermission('view meeting entities')) {
            return FALSE;
          }

          // If the user is not a member of the meeting.
          /** @var \Drupal\opigno_moxtra\MeetingInterface $meeting */
          $meeting = \Drupal::entityTypeManager()
            ->getStorage('opigno_moxtra_meeting')
            ->load($step['id']);
          if (!$meeting->isMember($user->id())) {
            return FALSE;
          }
        }
        elseif ($step['typology'] === 'ILT') {
          // If the user is not a member of the ILT.
          /** @var \Drupal\opigno_ilt\ILTInterface $ilt */
          $ilt = \Drupal::entityTypeManager()
            ->getStorage('opigno_ilt')
            ->load($step['id']);
          if (!$ilt->isMember($user->id())) {
            return FALSE;
          }
        }

        return TRUE;
      });
      $steps = array_map(function ($step) use ($uid) {
        $step['status'] = opigno_learning_path_get_step_status($step, $uid);
        $step['attempted'] = opigno_learning_path_is_attempted($step, $uid);
        $step['progress'] = opigno_learning_path_get_step_progress($step, $uid);
        return $step;
      }, $steps);
    }
    else {
      // Load steps from cache table.
      $results = $this->database
        ->select('opigno_learning_path_step_achievements', 'a')
        ->fields('a', [
          'entity_id',
          'name',
          'typology',
          'score',
          'time',
          'completed',
          'mandatory',
          'status',
        ])
        ->condition('uid', $user->id())
        ->condition('gid', $group->id())
        ->condition('parent_id', 0)
        ->orderBy('position')
        ->execute()
        ->fetchAll();

      $steps = array_map(function ($result) use ($uid) {
        // Convert datetime string to timestamp.
        if (isset($result->completed)) {
          $completed = DrupalDateTime::createFromFormat(DrupalDateTime::FORMAT, $result->completed);
          $completed_timestamp = $completed->getTimestamp();
        }
        else {
          $completed_timestamp = 0;
        }

        $step = [
          'id' => $result->entity_id,
          'name' => $result->name,
          'typology' => $result->typology,
          'best score' => $result->score,
          'time spent' => (int) $result->time,
          'completed on' => $completed_timestamp,
          'mandatory' => (int) $result->mandatory,
          'status' => $result->status,
          'attempted' => $result->status !== 'pending',
        ];

        if ($step['typology'] === 'Module') {
          $step['activities'] = count(opigno_learning_path_get_module_activities($step['id'], $uid));
        }

        $step['progress'] = opigno_learning_path_get_step_progress($step, $uid);

        return $step;
      }, $results);
    }

    /** @var \Drupal\Core\Datetime\DateFormatterInterface $date_formatter */
    $date_formatter = \Drupal::service('date.formatter');

    $status = [
      'pending' => [
        'class' => 'lp_summary_step_state_in_progress',
        'title' => t('Pending'),
      ],
      'failed' => [
        'class' => 'lp_summary_step_state_failed',
        'title' => t('Failed'),
      ],
      'passed' => [
        'class' => 'lp_summary_step_state_passed',
        'title' => t('Passed'),
      ],
    ];

    return [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'training_steps_' . $group->id(),
        'class' => ['lp_details'],
      ],
      array_map(function ($step) use ($group, $uid, $date_formatter, $status) {
        $is_module = $step['typology'] === 'Module';
        $is_course = $step['typology'] === 'Course';

        $time_spent = ($step['attempted'] && $step['time spent'] > 0) ? $date_formatter->formatInterval($step['time spent']) : '&dash;';
        $completed = ($step['attempted'] && $step['completed on'] > 0) ? $date_formatter->format($step['completed on'], 'custom', 'F d Y') : '&dash;';

        if ($is_module) {
          $approved_activities = $this->module_approved_activities($group->id(), $step['id']);
          $approved = $approved_activities . '/' . $step['activities'];
          $approved_percent = $approved_activities / $step['activities'] * 100;
        }

        if ($is_course) {
          $course_steps = $this->course_steps_passed($group, Group::load($step['id']));
          $passed = $course_steps['passed'] . '/' . $course_steps['total'];
          $passed_percent = ($course_steps['passed'] / $course_steps['total']) * 100;
          $score = $step['best score'];
          $score_percent = $score;
        }

        // Get existing badge count.
        $badges = 0;
        if (\Drupal::moduleHandler()->moduleExists('opigno_module') && ($is_course || $is_module)) {
          $result = OpignoModuleBadges::opignoModuleGetBadges($uid, $group->id(), $step['typology'], $step['id']);
          if ($result) {
            $badges = $result;
          }
        }

        $content = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['lp_step'],
          ],
          [
            '#type' => 'container',
            '#attributes' => [
              'class' => ['lp_step_title_wrapper'],
            ],
            ($step['mandatory']
              ? [
                '#type' => 'html_tag',
                '#tag' => 'span',
                '#attributes' => [
                  'class' => ['lp_step_required'],
                ],
                '#value' => '',
              ]
              : []),
            [
              '#type' => 'html_tag',
              '#tag' => 'h3',
              '#attributes' => [
                'class' => ['lp_step_title'],
              ],
              '#value' => $step['name'],
            ],
            [
              '#type' => 'html_tag',
              '#tag' => 'span',
              '#attributes' => [
                'class' => ['view_trigger'],
                'data-open' => t('Show details'),
                'data-close' => t('Hide details'),
              ],
            ],
          ],
          [
            '#type' => 'container',
            '#attributes' => [
              'class' => array_merge(['lp_step_content'], $is_module
                ? ['lp_step_content_module']
                : []),
            ],
            ($is_module ? [
              '#type' => 'container',
              '#attributes' => [
                'class' => ['lp_step_summary_wrapper'],
              ],
              [
                '#type' => 'container',
                '#attributes' => [
                  'class' => ['lp_step_summary', 'px-3'],
                ],
                [
                  '#type' => 'container',
                  '#attributes' => [
                    'class' => ['row'],
                  ], [
                    '#type' => 'container',
                    '#attributes' => [
                      'class' => [
                        'col-lg-4',
                        'col-md-2',
                        'd-sm-flex',
                        'd-md-block',
                        'd-lg-flex',
                        'mb-4',
                        'mb-md-0',
                      ],
                    ], [
                      '#type' => 'html_tag',
                      '#tag' => 'div',
                      '#attributes' => [
                        'class' => [
                          'lp_step_summary_title',
                          'h4',
                          'mb-0',
                          'mb-md-3',
                          'mb-lg-0',
                          'text-uppercase',
                        ],
                      ],
                      '#value' => isset($status[$step['status']])
                      ? $status[$step['status']]['title'] : $status['pending']['title'],
                    ], [
                      '#type' => 'html_tag',
                      '#tag' => 'div',
                      '#attributes' => [
                        'class' => [
                          'lp_step_summary_icon',
                          isset($status[$step['status']]) ? $status[$step['status']]['class'] : $status['pending']['class'],
                          'ml-3',
                          'ml-md-0',
                          'ml-lg-3',
                        ],
                      ],
                    ],
                  ], [
                    '#type' => 'container',
                    '#attributes' => [
                      'class' => ['col-lg-4', 'col-md-5', 'mb-4', 'mb-md-0'],
                    ], [
                      '#type' => 'html_tag',
                      '#tag' => 'div',
                      '#attributes' => [
                        'class' => ['ml-0', 'ml-md-5', 'mr-3', 'pull-left'],
                      ],
                      '#value' => '<div class="h4 color-blue mb-0">' . round(100 * $step['progress']) . '%</div><div>' . t('Completion') . '</div>',
                    ], [
                      '#type' => 'container',
                      '#attributes' => [
                        'class' => [
                          'lp_step_summary_completion_chart',
                          'donut-wrapper',
                          'ml-3',
                        ],
                      ], [
                        '#type' => 'html_tag',
                        '#tag' => 'canvas',
                        '#attributes' => [
                          'class' => ['donut'],
                          'data-value' => round(100 * $step['progress']),
                          'data-width' => 7,
                          'data-color' => '#5bb4d8',
                          'data-track-color' => '#fff',
                          'width' => 67,
                          'height' => 67,
                        ],
                        '#value' => '',
                      ],
                    ],
                  ], [
                    '#type' => 'container',
                    '#attributes' => [
                      'class' => ['col-lg-4', 'col-md-5'],
                    ], [
                      '#type' => 'html_tag',
                      '#tag' => 'div',
                      '#attributes' => [
                        'class' => [
                          'ml-0',
                          'ml-md-5',
                          'lp_step_summary_approved',
                          'mr-3',
                          'pull-left',
                        ],
                      ],
                      '#value' => '<div class="h4 color-blue mb-0">' . $approved . '</div><div>' . t('Activities') . '<br />' . t('done') . '</div>',
                    ], [
                      '#type' => 'container',
                      '#attributes' => [
                        'class' => [
                          'lp_step_summary_approved_chart',
                          'donut-wrapper',
                          'ml-3',
                          'mr-auto',
                        ],
                      ], [
                        '#type' => 'html_tag',
                        '#tag' => 'canvas',
                        '#attributes' => [
                          'class' => ['donut'],
                          'data-value' => $approved_percent,
                          'data-width' => 7,
                          'data-color' => '#5bb4d8',
                          'data-track-color' => '#fff',
                          'width' => 67,
                          'height' => 67,
                        ],
                        '#value' => '',
                      ],
                    ],
                  ],
                ],
                [
                  '#type' => 'container',
                  '#attributes' => [
                    'class' => [
                      'w-100',
                      'mt-4',
                      'd-flex',
                      'justify-content-center',
                    ],
                  ],
                  [
                    '#type' => 'html_tag',
                    '#tag' => 'div',
                    '#attributes' => [
                      'class' => [''],
                    ],
                    '#value' => '<div class="text-italic">' . t('Time spent') . '</div><div class="color-blue h5">' . $time_spent . '</div>',
                  ],
                  [
                    '#type' => 'html_tag',
                    '#tag' => 'div',
                    '#attributes' => [
                      'class' => ['ml-3', 'ml-md-5'],
                    ],
                    '#value' => '<div class="text-italic">' . t('Completed on') . '</div><div class="color-blue h5">' . $completed . '</div>',
                  ],
                  [
                    '#type' => 'html_tag',
                    '#tag' => 'div',
                    '#attributes' => [
                      'class' => ['ml-3', 'ml-md-5'],
                    ],
                    '#value' => '<div class="text-italic">' . t('Badges earned') . '</div><div class="color-blue h5">' . $badges . '</div>',
                  ],
                ],
              ],
              [
                '#type' => 'html_tag',
                '#tag' => 'div',
                '#attributes' => [
                  'class' => ['lp_step_summary_clickable'],
                  'data-training' => $group->id(),
                  'data-module' => $step['id'],
                ],
                '#value' => t('more details'),
              ],
              [
                '#type' => 'container',
                '#attributes' => [
                  'id' => 'module_panel_' . $group->id() . '_' . $step['id'],
                  'class' => ['lp_module_panel'],
                ],
              ],
            ] : []),
            ($is_course ? [
              '#type' => 'container',
              '#attributes' => [
                'class' => ['lp_step_summary_wrapper'],
              ],
              [
                '#type' => 'container',
                '#attributes' => [
                  'class' => [
                    'px-3',
                    'd-md-flex',
                    'justify-content-center',
                    'flex-wrap',
                  ],
                ],
                [
                  '#type' => 'html_tag',
                  '#tag' => 'div',
                  '#attributes' => [
                    'class' => ['float-left', 'mr-3', 'mr-md-0'],
                  ],
                  '#value' => '<div class="h4 ' . (($passed_percent === 100) ? 'color-green' : 'color-red') . ' mb-0">' . $passed . '</div><div>' . t('Module') . '<br />' . t('passed') . '</div>',
                ],
                [
                  '#type' => 'container',
                  '#attributes' => [
                    'class' => [
                      'lp_step_summary_course_step_passed_chart',
                      'donut-wrapper',
                      'ml-3',
                      'mb-3',
                      'mb-md-0',
                      ($passed_percent === 100) ? 'passed' : 'not_passed',
                    ],
                  ],
                  [
                    '#type' => 'html_tag',
                    '#tag' => 'canvas',
                    '#attributes' => [
                      'class' => ['donut'],
                      'data-value' => $passed_percent,
                      'data-width' => 7,
                      'data-color' => ($passed_percent === 100) ? '#c2e76b' : '#ff5440' ,
                      'data-track-color' => '#eeeeee',
                      'width' => 67,
                      'height' => 67,
                    ],
                    '#value' => '',
                  ],
                ],
                [
                  '#type' => 'html_tag',
                  '#tag' => 'div',
                  '#attributes' => [
                    'class' => ['ml-md-5', 'float-left', 'mr-3', 'mr-md-0'],
                  ],
                  '#value' => '<div class="h4 color-blue mb-0">' . round(100 * $step['progress']) . '%</div><div>' . t('Completion') . '</div>',
                ],
                [
                  '#type' => 'container',
                  '#attributes' => [
                    'class' => [
                      'lp_step_summary_completion_chart',
                      'donut-wrapper',
                      'ml-3',
                      'mb-3',
                      'mb-md-0',
                    ],
                  ],
                  [
                    '#type' => 'html_tag',
                    '#tag' => 'canvas',
                    '#attributes' => [
                      'class' => ['donut'],
                      'data-value' => round(100 * $step['progress']),
                      'data-width' => 7,
                      'data-color' => '#5bb4d8',
                      'data-track-color' => '#eeeeee',
                      'width' => 67,
                      'height' => 67,
                    ],
                    '#value' => '',
                  ],
                ],
                [
                  '#type' => 'html_tag',
                  '#tag' => 'div',
                  '#attributes' => [
                    'class' => ['ml-md-5', 'float-left', 'mr-3', 'mr-md-0'],
                  ],
                  '#value' => '<div class="h4 color-blue mb-0">' . $score . '</div><div>' . t('Score') . '</div>',
                ],
                [
                  '#type' => 'container',
                  '#attributes' => [
                    'class' => [
                      'lp_step_summary_approved_chart',
                      'donut-wrapper',
                      'ml-3',
                      'mb-3',
                      'mb-md-0',
                    ],
                  ],
                  [
                    '#type' => 'html_tag',
                    '#tag' => 'canvas',
                    '#attributes' => [
                      'class' => ['donut'],
                      'data-value' => $score_percent,
                      'data-width' => 7,
                      'data-color' => '#5bb4d8',
                      'data-track-color' => '#eeeeee',
                      'width' => 67,
                      'height' => 67,
                    ],
                    '#value' => '',
                  ],
                ],
                [
                  '#type' => 'container',
                  '#attributes' => [
                    'class' => ['ml-md-5'],
                  ],
                  [
                    '#type' => 'html_tag',
                    '#tag' => 'div',
                    '#attributes' => [
                      'class' => ['text-italic'],
                    ],
                    '#value' => t('Time spent'),
                  ],
                  [
                    '#type' => 'html_tag',
                    '#tag' => 'div',
                    '#attributes' => [
                      'class' => ['h4', 'color-blue'],
                    ],
                    '#value' => $time_spent,
                  ],
                  [
                    '#type' => 'html_tag',
                    '#tag' => 'div',
                    '#attributes' => [
                      'class' => ['text-italic'],
                    ],
                    '#value' => t('Completed on'),
                  ],
                  [
                    '#type' => 'html_tag',
                    '#tag' => 'div',
                    '#attributes' => [
                      'class' => ['h4', 'color-blue'],
                    ],
                    '#value' => $completed,
                  ],
                  [
                    '#type' => 'html_tag',
                    '#tag' => 'div',
                    '#attributes' => [
                      'class' => ['text-italic'],
                    ],
                    '#value' => t('Badges earned'),
                  ],
                  [
                    '#type' => 'html_tag',
                    '#tag' => 'div',
                    '#attributes' => [
                      'class' => ['h4', 'color-blue'],
                    ],
                    '#value' => $badges,
                  ],
                ],
              ],
              [
                '#type' => 'html_tag',
                '#tag' => 'hr',
                '#attributes' => [
                  'class' => [
                    'lp_course_steps_wrapper',
                    'ml-3',
                    'ml-md-7',
                    'mr-3',
                    'mr-md-5',
                    'my-4',
                  ],
                ],
              ],
              $this->build_course_steps($group, Group::load($step['id'])),
            ] : []),
          ],
        ];

        return $content;
      }, $steps),
    ];
  }

  /**
   * Returns training timeline.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group.
   *
   * @return array
   *   Training timeline.
   */
  protected function build_training_timeline(GroupInterface $group) {
    /** @var \Drupal\Core\Datetime\DateFormatterInterface $date_formatter */
    $date_formatter = \Drupal::service('date.formatter');
    $user = $this->currentUser();

    $result = (int) $this->database
      ->select('opigno_learning_path_achievements', 'a')
      ->fields('a')
      ->condition('uid', $user->id())
      ->condition('gid', $group->id())
      ->condition('status', 'completed')
      ->countQuery()
      ->execute()
      ->fetchField();
    if ($result === 0) {
      // If training is not completed, generate steps.
      $steps = opigno_learning_path_get_steps($group->id(), $user->id());
      $steps = array_filter($steps, function ($step) {
        return $step['mandatory'];
      });
      $steps = array_filter($steps, function ($step) use ($user) {
        if ($step['typology'] === 'Meeting') {
          // If the user have not the collaborative features role.
          if (!$user->hasPermission('view meeting entities')) {
            return FALSE;
          }

          // If the user is not a member of the meeting.
          /** @var \Drupal\opigno_moxtra\MeetingInterface $meeting */
          $meeting = \Drupal::entityTypeManager()
            ->getStorage('opigno_moxtra_meeting')
            ->load($step['id']);
          if (!$meeting->isMember($user->id())) {
            return FALSE;
          }
        }
        elseif ($step['typology'] === 'ILT') {
          // If the user is not a member of the ILT.
          /** @var \Drupal\opigno_ilt\ILTInterface $ilt */
          $ilt = \Drupal::entityTypeManager()
            ->getStorage('opigno_ilt')
            ->load($step['id']);
          if (!$ilt->isMember($user->id())) {
            return FALSE;
          }
        }

        return TRUE;
      });
      $steps = array_map(function ($step) use ($user) {
        $step['passed'] = opigno_learning_path_is_passed($step, $user->id());
        return $step;
      }, $steps);
    }
    else {
      // Load steps from cache table.
      $results = $this->database
        ->select('opigno_learning_path_step_achievements', 'a')
        ->fields('a', [
          'name', 'status', 'completed', 'typology', 'entity_id',
        ])
        ->condition('uid', $user->id())
        ->condition('gid', $group->id())
        ->condition('mandatory', 1)
        ->execute()
        ->fetchAll();

      $steps = array_map(function ($result) {
        // Convert datetime string to timestamp.
        if (isset($result->completed)) {
          $completed = DrupalDateTime::createFromFormat(DrupalDateTime::FORMAT, $result->completed);
          $completed_timestamp = $completed->getTimestamp();
        }
        else {
          $completed_timestamp = 0;
        }

        return [
          'name' => $result->name,
          'passed' => $result->status === 'passed',
          'completed on' => $completed_timestamp,
          'typology' => $result->typology,
          'id' => 	$result->entity_id,
        ];
      }, $results);
    }

    $timeline = [];
    $timeline[] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#attributes' => [
        'class' => ['lp_timeline_begin'],
      ],
      '#value' => '',
    ];

    foreach ($steps as $step) {
      $completed_on = $step['completed on'] > 0
        ? $date_formatter->format($step['completed on'], 'custom', 'F d, Y')
        : '';

      $status = opigno_learning_path_get_step_status($step, $user->id());
      $timeline[] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['lp_timeline_step', $status],
        ],
        [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['lp_timeline_step_label'],
          ],
          [
            '#type' => 'html_tag',
            '#tag' => 'span',
            '#attributes' => [
              'class' => ['lp_timeline_step_label_title'],
            ],
            '#value' => $step['name'],
          ],
          [
            '#type' => 'html_tag',
            '#tag' => 'span',
            '#attributes' => [
              'class' => ['lp_timeline_step_label_completed_on'],
            ],
            '#value' => $completed_on,
          ],
        ],
      ];
    }

    $timeline[] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#attributes' => [
        'class' => ['lp_timeline_end'],
      ],
      '#value' => '',
    ];

    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['lp_timeline_wrapper', 'px-3', 'px-md-5', 'pb-5'],
      ],
      [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'lp_timeline',
            'lp_timeline_not_empty',
          ],
        ],
        $timeline,
      ],
    ];
  }

  /**
   * Returns training summary.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group.
   *
   * @return array
   *   Training summary.
   */
  protected function build_training_summary(GroupInterface $group) {
    $gid = $group->id();
    $user = $this->currentUser();
    $uid = $user->id();
    $score = round(opigno_learning_path_get_score($gid, $uid));
    $progress = round(100 * opigno_learning_path_progress($gid, $uid));

    /** @var \Drupal\Core\Datetime\DateFormatterInterface $date_formatter */
    $date_formatter = \Drupal::service('date.formatter');

    /** @var \Drupal\group\Entity\GroupContent $member */
    $member = $group->getMember($user)->getGroupContent();
    $registration = $member->getCreatedTime();
    $registration = $date_formatter->format($registration, 'custom', 'F d, Y');

    $validation = opigno_learning_path_completed_on($gid, $uid);
    $validation = $validation > 0
      ? $date_formatter->format($validation, 'custom', 'F d, Y')
      : '';

    $time_spent = opigno_learning_path_get_time_spent($gid, $uid);
    $time_spent = $date_formatter->formatInterval($time_spent);

    $result = $this->database
      ->select('opigno_learning_path_achievements', 'a')
      ->fields('a', ['status'])
      ->condition('uid', $user->id())
      ->condition('gid', $group->id())
      ->execute()
      ->fetchField();

    if ($result !== FALSE) {
      // Use cached result.
      $is_attempted = TRUE;
      $is_passed = $result === 'completed';
    }
    else {
      // Check the actual data.
      $is_attempted = opigno_learning_path_is_attempted($group, $uid);
      $is_passed = opigno_learning_path_is_passed($group, $uid);
    }

    if ($is_passed || $is_attempted) {
      $summary = [
        [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#attributes' => [
            'class' => ['lp_step_summary_text'],
          ],
          '#value' => t('Score: @score%', [
            '@score' => $score,
          ]),
        ],
        [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#attributes' => [
            'class' => ['lp_step_summary_text'],
          ],
          '#value' => t('Registration date: @date', [
            '@date' => $registration,
          ]),
        ],
        ($is_passed
          ? [
            '#type' => 'html_tag',
            '#tag' => 'span',
            '#attributes' => [
              'class' => ['lp_step_summary_text'],
            ],
            '#value' => t('Validation date: @date', [
              '@date' => $validation,
            ]),
          ]
          : []),
        [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#attributes' => [
            'class' => ['lp_step_summary_text'],
          ],
          '#value' => t('Time spent: @time on the training', [
            '@time' => $time_spent,
          ]),
        ],
      ];
    }
    else {
      $summary = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => [
          'class' => ['lp_step_summary_text'],
        ],
        '#value' => t('Not yet started'),
      ];
    }

    if ($is_passed) {
      $state_class = 'lp_summary_step_state_passed';
    }
    elseif ($is_attempted) {
      $state_class = 'lp_summary_step_state_in_progress';
    }
    else {
      $state_class = 'lp_summary_step_state_not_started';
    }

    $cert_text = $this->t('Download certificate');
    $has_cert = !$group->get('field_certificate')->isEmpty();

    if ($is_passed && $has_cert) {
      $cert_url = Url::fromUri("internal:/certificate/group/$gid/pdf");

      $cert_title = Link::fromTextAndUrl($cert_text, $cert_url)->toRenderable();
      $cert_title['#attributes']['class'][] = 'lp_summary_certificate_text';

      $cert_icon = Link::fromTextAndUrl('', $cert_url)->toRenderable();
      $cert_icon['#attributes']['class'][] = 'lp_summary_certificate_icon';
    }
    else {
      $cert_title = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => [
          'class' => ['lp_summary_certificate_text'],
        ],
        '#value' => $cert_text,
      ];
      $cert_icon = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => [
          'class' => ['lp_summary_certificate_icon'],
        ],
        '#value' => '',
      ];
    }

    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['lp_summary', 'd-flex', 'flex-wrap', 'py-5'],
      ],
      [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['lp_summary_content'],
        ],
        [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => [
              'lp_step_summary_title',
              'h4',
              'mb-0',
              'text-uppercase',
            ],
          ],
          '#value' => t('Training Progress'),
        ],
        [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => ['lp_step_summary_score'],
          ],
          '#value' => t('@score%', ['@score' => $progress]),
        ],
      ],
      [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['lp_summary_content'],
        ],
        [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => [
              'lp_step_summary_title',
              'h4',
              'mb-0',
              'text-uppercase',
            ],
          ],
          '#value' => t('Training Score'),
        ],
        [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => ['lp_step_summary_score'],
          ],
          '#value' => t('@score%', ['@score' => $score]),
        ],
      ],
      [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => [$state_class],
        ],
        '#value' => '',
      ],
      [
        '#type' => 'container',
        '#attributes' => [
          'class' => array_merge(['lp_summary_certificate'],
            $is_passed && $has_cert ? [] : ['lp_summary_certificate_inactive']),
        ],
        $cert_title,
        $cert_icon,
      ],
      [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['mt-4', 'w-100', 'd-flex', 'justify-content-center'],
        ],
        [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => ['lp_step_summary_registration', 'font-italic'],
          ],
          '#value' => t('Registration date: @date', ['@date' => $registration]),
        ],
        [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => [
              'lp_step_summary_validation',
              'ml-3',
              'ml-md-5',
              'font-italic',
            ],
          ],
          '#value' => t('Validation date: @date', ['@date' => $validation]),
        ],
        [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => [
              'lp_step_summary_time_spent',
              'ml-3',
              'ml-md-5',
              'font-italic',
            ],
          ],
          '#value' => t('Time spent: @time', ['@time' => $time_spent]),
        ],
      ],
    ];
  }

  /**
   * Returns training array.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group.
   *
   * @return array
   *   Training array.
   */
  protected function build_training(GroupInterface $group) {
    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['lp_wrapper'],
      ],
      [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#attributes' => [
          'class' => [
            'lp_title',
            'px-3',
            'px-md-5',
            'pt-5',
            'pb-4',
            'mb-0',
            'h4',
            'text-uppercase',
          ],
        ],
        '#value' => t('Training : @name', [
          '@name' => $group->label(),
        ]),
      ],
      $this->build_training_timeline($group),
      [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['lp_timeline_info', 'px-3', 'px-md-5', 'py-3'],
        ],
        [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#attributes' => [
            'class' => ['lp_timeline_icon'],
          ],
          '#value' => '',
        ],
        [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['lp_timeline_info_text'],
          ],
          [
            '#type' => 'html_tag',
            '#tag' => 'span',
            '#value' => t('Timeline'),
          ],
          [
            '#type' => 'html_tag',
            '#tag' => 'span',
            '#attributes' => [
              'class' => ['lp_timeline_info_tooltip'],
            ],
            '#value' => t('In your timeline are shown only successfully passed mandatory steps from your training'),
          ],
        ],
      ],
      $this->build_training_summary($group),
      [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'training_steps_' . $group->id(),
          'class' => ['lp_details'],
        ],
      ],
      [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['lp_details_show'],
          'data-training' => $group->id(),
        ],
        [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#attributes' => [
            'class' => ['lp_details_show_text'],
          ],
          '#value' => t('Show details'),
        ],
      ],
      [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['lp_details_hide'],
        ],
        [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#attributes' => [
            'class' => ['lp_details_hide_text'],
          ],
          '#value' => t('Hide details'),
        ],
      ],
    ];
  }

  /**
   * Loads module panel with a AJAX.
   *
   * @param \Drupal\group\Entity\GroupInterface $training
   *   Training group.
   * @param null|\Drupal\group\Entity\GroupInterface $course
   *   Course group.
   * @param \Drupal\opigno_module\Entity\OpignoModule $opigno_module
   *   Opigno module.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Response.
   */
  public function course_module_panel_ajax(GroupInterface $training, GroupInterface $course, OpignoModule $opigno_module) {
    $training_id = $training->id();
    $course_id = $course->id();
    $module_id = $opigno_module->id();
    $selector = "#module_panel_${training_id}_${course_id}_${module_id}";
    $content = $this->build_module_panel($training, $course, $opigno_module);
    $content['#attributes']['data-ajax-loaded'] = TRUE;
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand($selector, $content));
    return $response;
  }

  /**
   * Loads module panel with a AJAX.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group.
   * @param \Drupal\opigno_module\Entity\OpignoModule $opigno_module
   *   Opigno module.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Response.
   */
  public function training_module_panel_ajax(GroupInterface $group, OpignoModule $opigno_module) {
    $training_id = $group->id();
    $module_id = $opigno_module->id();
    $selector = "#module_panel_${training_id}_${module_id}";
    $content = $this->build_module_panel($group, NULL, $opigno_module);
    $content['#attributes']['data-ajax-loaded'] = TRUE;
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand($selector, $content));
    return $response;
  }

  /**
   * Loads steps for a training with a AJAX.
   *
   * @param \Drupal\group\Entity\Group $group
   *   Group.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Response.
   */
  public function training_steps_ajax(Group $group) {
    $selector = '#training_steps_' . $group->id();
    $content = $this->build_lp_steps($group);
    $content['#attributes']['data-ajax-loaded'] = TRUE;
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand($selector, $content));
    return $response;
  }

  /**
   * Returns training page array.
   *
   * @param int $page
   *   Page id.
   *
   * @return array
   *   Training page array.
   */
  protected function build_page($page = 0) {
    $per_page = 5;

    $user = $this->currentUser();
    $uid = $user->id();

    $query = $this->database->select('group_content_field_data', 'gc');
    $query->innerJoin(
      'groups_field_data',
      'g',
      'g.id = gc.gid'
    );
    // Opigno Module group content.
    $query->leftJoin(
      'group_content_field_data',
      'gc2',
      'gc2.gid = gc.gid AND gc2.type = \'group_content_type_162f6c7e7c4fa\''
    );
    $query->leftJoin(
      'opigno_group_content',
      'ogc',
      'ogc.entity_id = gc2.entity_id AND ogc.is_mandatory = 1'
    );
    $query->leftJoin(
      'user_module_status',
      'ums',
      'ums.user_id = gc.uid AND ums.module = gc2.entity_id AND ums.score >= ogc.success_score_min'
    );
    $query->addExpression('max(ums.started)', 'started');
    $query->addExpression('max(ums.finished)', 'finished');
    $gids = $query->fields('gc', ['gid'])
      ->condition('gc.type', 'learning_path-group_membership')
      ->condition('gc.entity_id', $uid)
      ->groupBy('gc.gid')
      ->orderBy('finished', 'DESC')
      ->orderBy('started', 'DESC')
      ->orderBy('gc.gid', 'DESC')
      ->range($page * $per_page, $per_page)
      ->execute()
      ->fetchCol();
    $groups = Group::loadMultiple($gids);
    return array_map([$this, 'build_training'], $groups);
  }

  /**
   * Get last or best user attempt for Module.
   *
   * @param array $attempts
   *   User module attempts.
   * @param \Drupal\opigno_module\Entity\OpignoModule $module
   *   Module.
   *
   * @return \Drupal\opigno_module\Entity\UserModuleStatus
   *   $attempt
   */
  protected function getTargetAttempt(array $attempts, OpignoModule $module) {
    if ($module->getKeepResultsOption() == 'newest') {
      $attempt = end($attempts);
    }
    else {
      usort($attempts, function ($a, $b) {
        /** @var \Drupal\opigno_module\Entity\UserModuleStatus $a */
        /** @var \Drupal\opigno_module\Entity\UserModuleStatus $b */
        $b_score = opigno_learning_path_get_attempt_score($b);
        $a_score = opigno_learning_path_get_attempt_score($a);
        return $b_score - $a_score;
      });
      $attempt = reset($attempts);
    }

    return $attempt;
  }

  /**
   * Loads next achievements page with a AJAX.
   *
   * @param int $page
   *   Page id.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Response.
   */
  public function page_ajax($page = 0) {
    $selector = '#achievements-wrapper';

    $content = $this->build_page($page);
    if (empty($content)) {
      throw new NotFoundHttpException();
    }

    $response = new AjaxResponse();
    $response->addCommand(new AppendCommand($selector, $content));
    return $response;
  }

  /**
   * Returns index array.
   *
   * @param int $page
   *   Page id.
   *
   * @return array
   *   Index array.
   */
  public function index($page = 0) {
    $content = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'achievements-wrapper',
      ],
      [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['lp_info', 'mb-4', 'py-4', 'pr-3', 'pr-md-5'],
        ],
        [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#attributes' => [
            'class' => ['lp_icon_info'],
          ],
          '#value' => '',
        ],
        [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#attributes' => [
            'class' => ['lp_info_text'],
          ],
          '#value' => t('Consult your results and download the certificates for the trainings.'),
        ],
      ],
      '#attached' => [
        'library' => [
          'opigno_learning_path/achievements',
        ],
      ],
    ];

    $content[] = $this->build_page($page);
    return $content;
  }

}
