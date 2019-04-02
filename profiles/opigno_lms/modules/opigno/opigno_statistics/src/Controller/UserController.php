<?php

namespace Drupal\opigno_statistics\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Ajax\AfterCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Link;
use Drupal\file\Entity\File;
use Drupal\group\Entity\GroupInterface;
use Drupal\opigno_module\Entity\OpignoActivity;
use Drupal\opigno_module\Entity\OpignoModule;
use Drupal\opigno_statistics\StatisticsPageTrait;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\opigno_module\OpignoModuleBadges;

/**
 * Class UserController.
 */
class UserController extends ControllerBase {

  use StatisticsPageTrait;

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Time.
   *
   * @var \Drupal\Component\Datetime\Time
   */
  protected $time;

  /**
   * Date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $date_formatter;

  /**
   * UserController constructor.
   */
  public function __construct(
    Connection $database,
    TimeInterface $time,
    DateFormatterInterface $date_formatter
  ) {
    $this->database = $database;
    $this->time = $time;
    $this->date_formatter = $date_formatter;
  }

  /**
   * Create.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('datetime.time'),
      $container->get('date.formatter')
    );
  }

  /**
   * Builds render array for a user info block.
   *
   * @param \Drupal\user\UserInterface $user
   *   User.
   *
   * @return array
   *   Render array.
   */
  public function buildUserInfo(UserInterface $user) {
    $user_picture = $user->get('user_picture')->getValue();
    if (isset($user_picture[0]['target_id'])) {
      $user_picture = File::load($user_picture[0]['target_id']);
      $user_picture = ImageStyle::load('medium')->buildUrl($user_picture->getFileUri());
    }
    else {
      $user_picture = base_path() . drupal_get_path('theme', 'platon') . '/images/picto-profile-default.svg';
    }

    $created_timestamp = $user->getCreatedTime();
    $accessed_timestamp = $user->getLastAccessedTime();

    $date_joined = $this->date_formatter->format($created_timestamp, 'custom', 'Y-m-d');
    $last_access = $this->date_formatter->format($accessed_timestamp, 'custom', 'Y-m-d');
    $member_for = $this->date_formatter->formatTimeDiffSince($created_timestamp);

    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['user-info'],
      ],
      [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['user-info-photo-wrapper'],
        ],
        isset($user_picture) ? [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => ['user-info-photo'],
            'style' => 'background-image: url(' . $user_picture . ')',
          ],
        ] : [],
      ],
      [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['user-info-text-wrapper'],
        ],
        [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#attributes' => [
            'class' => ['user-info-icon'],
          ],
        ],
        [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#attributes' => [
            'class' => ['user-info-name'],
          ],
          '#value' => $user->getDisplayName(),
        ],
        [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#attributes' => [
            'class' => ['user-info-email'],
          ],
          '#value' => $user->getEmail(),
        ],
        [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#attributes' => [
            'class' => ['user-info-date-joined'],
          ],
          '#value' => $this->t('Date joined @date', [
            '@date' => $date_joined,
          ]),
        ],
        [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#attributes' => [
            'class' => ['user-info-last-access'],
          ],
          '#value' => $this->t('Last access @date', [
            '@date' => $last_access,
          ]),
        ],
        [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#attributes' => [
            'class' => ['user-info-member-for'],
          ],
          '#value' => $this->t('Member for @time', [
            '@time' => $member_for,
          ]),
        ],
      ],
    ];
  }

  /**
   * Builds render array for a user badges block.
   *
   * @param \Drupal\user\UserInterface $user
   *   User.
   *
   * @return array
   *   Render array.
   */
  public function buildBadges(UserInterface $user) {
    $output = [];

    if (\Drupal::moduleHandler()->moduleExists('opigno_module')) {
      try {
        $opigno_module_fields = \Drupal::entityTypeManager()->getStorage('opigno_module')->getFieldStorageDefinitions();
      }
      catch (\Exception $e) {
        $this->messenger()->addMessage($e->getMessage(), 'error');
        return [];
      }
      if (array_key_exists('badge_active', $opigno_module_fields)) {
        $uid = $user->id();

        // Get user modules and courses with active badges.
        $modules = OpignoModuleBadges::opignoModuleGetUserActiveBadgesModules($uid);

        // Prepare active badges data.
        $rows = [];
        if ($modules) {
          foreach ($modules as $module) {
            if (!empty($module->field_media_image_target_id)) {
              $fid = $module->field_media_image_target_id;
              $file = File::load($fid);
              $variables = [
                'style_name' => 'thumbnail',
                'uri' => $file->getFileUri(),
              ];
              $image = \Drupal::service('image.factory')->get($file->getFileUri());
              if ($image->isValid()) {
                $variables['width'] = $image->getWidth();
                $variables['height'] = $image->getHeight();
              }
              else {
                $variables['width'] = $variables['height'] = NULL;
              }
              $logo_render_array = [
                '#theme' => 'image_style',
                '#width' => $variables['width'],
                '#height' => $variables['height'],
                '#style_name' => $variables['style_name'],
                '#uri' => $variables['uri'],
              ];
              $badge_image = render($logo_render_array);
            }
            else {
              $badge_image = '';
            }

            $badge_description_html = !empty($module->badge_description) ? ['data' => ['#markup' => $module->badge_description]] : '';

            // Get existing badge count.
            $badges = OpignoModuleBadges::opignoModuleGetBadges($uid, $module->gid, $module->typology, $module->entity_id);

            if (!empty($module->name) && !empty($module->badge_criteria) && !empty($badges)) {
              if (($module->badge_criteria == 'finished' && in_array($module->status, ['passed', 'failed'])) ||
                  ($module->badge_criteria == 'success' && $module->status == 'passed')) {

                $rows[] = [
                  'data-id' => $module->entity_id,
                  'data-typology' => strtolower($module->typology),
                  'data' => [
                    $module->training,
                    $badge_image,
                    $module->badge_name,
                    $badge_description_html,
                    $badges,
                  ],
                ];
              }
            }
          }
        }

        if ($rows) {
          $output = [
            '#type' => 'container',
            '#attributes' => [
              'class' => ['badges'],
            ],
            [
              '#type' => 'table',
              '#attributes' => [
                'class' => ['statistics-table', 'table-striped'],
              ],
              '#header' => [
                $this->t('Training'),
                '',
                $this->t('Badge'),
                $this->t('Description'),
                $this->t('Earned'),
              ],
              '#rows' => $rows,
            ],
          ];
        }
      }
    }

    return $output;
  }

  /**
   * Returns max score that user can have in this module & activity.
   *
   * @param \Drupal\opigno_module\Entity\OpignoModule $module
   *   Module.
   * @param \Drupal\opigno_module\Entity\OpignoActivity $activity
   *   Activity.
   *
   * @return int
   *   Score.
   */
  protected function getActivityMaxScore(
    OpignoModule $module,
    OpignoActivity $activity
  ) {
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
   * Build render array for a user module details.
   *
   * @param \Drupal\user\UserInterface $user
   *   User.
   * @param \Drupal\group\Entity\GroupInterface $training
   *   Training.
   * @param null|\Drupal\group\Entity\GroupInterface $course
   *   Course.
   * @param \Drupal\opigno_module\Entity\OpignoModule $module
   *   Module.
   *
   * @return array
   *   Render array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function buildModuleDetails(
    UserInterface $user,
    GroupInterface $training,
    $course,
    OpignoModule $module
  ) {
    $parent = isset($course) ? $course : $training;
    $step = opigno_learning_path_get_module_step($parent->id(), $user->id(), $module);
    $completed_on = $step['completed on'];
    $completed_on = $completed_on > 0
      ? $this->date_formatter->format($completed_on, 'custom', 'F d, Y')
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
          return (int) $this->getActivityMaxScore($module, $activity);
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
      $max_score = (int) $this->getActivityMaxScore($module, $activity);

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
              'class' => [isset($answer) ? 'step_state_passed' : 'step_state_failed'],
            ],
            '#value' => '',
          ],
        ],
      ];
    }, $activities);

    $activities = [
      '#type' => 'table',
      '#attributes' => [
        'class' => ['module_panel_activities_overview'],
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

    if (isset($attempt)) {
      $details = Link::createFromRoute('Details', 'opigno_module.module_result', [
        'opigno_module' => $module->id(),
        'user_module_status' => $attempt->id(),
      ])->toRenderable();
      $details['#attributes']['target'] = '_blank';
    }
    else {
      $details = [];
    }

    return [
      '#type' => 'container',
      '#attributes' => [
        'id' => $id,
        'class' => ['module_panel'],
      ],
      [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['module_panel_header'],
        ],
        [
          '#markup' => '<a href="#" class="module_panel_close">&times;</a>',
        ],
        [
          '#type' => 'html_tag',
          '#tag' => 'h3',
          '#attributes' => [
            'class' => ['module_panel_title'],
          ],
          '#value' => $step['name'] . ' ' . (!empty($completed_on) ? t('completed') : ''),
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
          'class' => ['module_panel_content'],
        ],
        !empty($completed_on) ? [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => t('@name completed on @date', [
            '@name' => $step['name'],
            '@date' => $completed_on,
          ]),
        ] : [],
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
            'class' => ['module_panel_overview_title'],
          ],
          '#value' => t('Activities Overview'),
        ],
        $activities,
        $details,
      ],
    ];
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
   * Builds render array for a user course details.
   *
   * @param \Drupal\user\UserInterface $user
   *   User.
   * @param \Drupal\group\Entity\GroupInterface $training
   *   Training.
   * @param \Drupal\group\Entity\GroupInterface $course
   *   Course.
   *
   * @return array
   *   Render array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function buildCourseDetails(
    UserInterface $user,
    GroupInterface $training,
    GroupInterface $course
  ) {
    // Load group steps.
    $steps = opigno_learning_path_get_steps($course->id(), $user->id());

    $query = $this->database->select(
      'opigno_learning_path_step_achievements',
      'sa'
    );
    $query->leftJoin(
      'opigno_learning_path_step_achievements',
      'sa2',
      'sa2.parent_id = sa.id'
    );
    $query = $query
      ->fields('sa2', ['entity_id', 'typology', 'name', 'score', 'status'])
      ->condition('sa.uid', $user->id())
      ->condition('sa.gid', $training->id())
      ->condition('sa.entity_id', $course->id())
      ->condition('sa.parent_id', 0);

    $modules = $query->execute()->fetchAll();
    $rows = array_map(function ($step) use ($modules, $training, $course, $user) {
      $module = NULL;
      foreach ($modules as $mod) {
        if ($mod->entity_id === $step['id']) {
          $module = $mod;
          break;
        }
      }

      $id = isset($module) ? $module->entity_id : $step['id'];
      $name = isset($module) ? $module->name : $step['name'];
      $score = isset($module) ? $module->score : 0;
      $status = isset($module) ? $module->status : 'pending';
      $typology = strtolower(isset($module) ? $module->typology : $step['typology']);

      $score = isset($score) ? $score : 0;
      $score = ['data' => $this->buildScore($score)];

      $status = isset($status) ? $status : 'pending';
      $status = ['data' => $this->buildStatus($status)];

      switch ($typology) {
        case 'module':
          $training_gid = $training->id();
          $course_gid = $course->id();
          $module_id = $id;
          $details = Link::createFromRoute('', 'opigno_statistics.user.course_module_details', [
            'user' => $user->id(),
            'training' => $training->id(),
            'course' => $course->id(),
            'module' => $id,
          ])->toRenderable();
          $details['#attributes']['class'][] = 'details';
          $details['#attributes']['class'][] = 'course-module-details-open';
          $details['#attributes']['data-user'] = $user->id();
          $details['#attributes']['data-training'] = $training_gid;
          $details['#attributes']['data-course'] = $course_gid;
          $details['#attributes']['data-id'] = $module_id;
          $details = [
            'data' => [
              $details,
              [
                '#type' => 'container',
                '#attributes' => [
                  'class' => ['module-panel-wrapper'],
                ],
                [
                  '#type' => 'html_tag',
                  '#tag' => 'span',
                  '#attributes' => [
                    'id' => "module_panel_${training_gid}_${course_gid}_${module_id}",
                  ],
                ],
              ],
            ],
          ];
          break;

        default:
          $details = '';
          break;
      }

      return [
        $name,
        $score,
        $status,
        $details,
      ];
    }, $steps);

    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['course-modules'],
      ],
      [
        '#type' => 'table',
        '#attributes' => [
          'class' => ['statistics-table', 'course-modules-list'],
        ],
        '#header' => [],
        '#rows' => $rows,
      ],
    ];
  }

  /**
   * Builds render array for a user training details.
   *
   * @param \Drupal\user\UserInterface $user
   *   User.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Training.
   *
   * @return array
   *   Render array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function buildTrainingDetails(UserInterface $user, GroupInterface $group) {
    $gid = $group->id();
    $uid = $user->id();

    // Load group steps.
    $steps = opigno_learning_path_get_steps($gid, $uid, $only_modules_and_courses = TRUE);
    $steps_count = count($steps);

    $query = $this->database
      ->select('opigno_learning_path_achievements', 'a')
      ->fields('a', ['score', 'progress', 'time', 'completed'])
      ->condition('a.gid', $gid)
      ->condition('a.uid', $uid);
    $training_data = $query->execute()->fetchAssoc();

    $query = $this->database
      ->select('opigno_learning_path_step_achievements', 'sa')
      ->fields('sa', [
        'entity_id',
        'typology',
        'name',
        'score',
        'status',
        'time',
        'completed',
      ])
      ->condition('sa.gid', $gid)
      ->condition('sa.uid', $uid)
      ->condition('sa.parent_id', 0);
    $modules = $query->execute()->fetchAll();
    $passed_modules = array_filter($modules, function ($module) {
      return $module->status === 'passed';
    });
    $passed_modules_count = count($passed_modules);

    $content = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['training-details-content'],
      ],
    ];
    $content[] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['module-passed'],
      ],
      'module_passed' => $this->buildValueWithIndicator(
        $this->t('Module Passed'),
        $passed_modules_count / $steps_count,
        $this->t('@passed/@total', [
          '@passed' => $passed_modules_count,
          '@total' => $steps_count,
        ])
      ),
    ];
    $content[] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['completion'],
      ],
      'completion' => $this->buildValueWithIndicator(
        $this->t('Completion'),
        $training_data['progress'] / 100
      ),
    ];
    $content[] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['score'],
      ],
      'completion' => $this->buildValueWithIndicator(
        $this->t('Score'),
        $training_data['score'] / 100
      ),
    ];

    $time = isset($training_data['time']) && $training_data['time'] > 0
      ? $this->date_formatter->formatInterval($training_data['time']) : '-';

    if (isset($training_data['completed'])) {
      $datetime = DrupalDateTime::createFromFormat(
        DrupalDateTime::FORMAT,
        $training_data['completed']
      );
      $timestamp = $datetime->getTimestamp();
      $completed_on = $this->date_formatter->format($timestamp, 'custom', 'F d Y');
    }
    else {
      $completed_on = '-';
    }

    $content[] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['right-block'],
      ],
      [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['time'],
        ],
        [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['value-wrapper'],
          ],
          [
            '#type' => 'html_tag',
            '#tag' => 'span',
            '#attributes' => [
              'class' => ['label'],
            ],
            '#value' => $this->t('Time spent'),
          ],
          [
            '#type' => 'html_tag',
            '#tag' => 'span',
            '#attributes' => [
              'class' => ['value'],
            ],
            '#value' => $time,
          ],
        ],
      ],
      [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['completed'],
        ],
        [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['value-wrapper'],
          ],
          [
            '#type' => 'html_tag',
            '#tag' => 'span',
            '#attributes' => [
              'class' => ['label'],
            ],
            '#value' => $this->t('Completed on'),
          ],
          [
            '#type' => 'html_tag',
            '#tag' => 'span',
            '#attributes' => [
              'class' => ['value'],
            ],
            '#value' => $completed_on,
          ],
        ],
      ],
    ];

    $rows = array_map(function ($step) use ($modules, $uid, $gid) {
      $module = NULL;
      foreach ($modules as $mod) {
        if ($mod->entity_id === $step['id']) {
          $module = $mod;
          break;
        }
      }

      $id = isset($module) ? $module->entity_id : $step['id'];
      $name = isset($module) ? $module->name : $step['name'];
      $score = isset($module) ? $module->score : 0;
      $status = isset($module) ? $module->status : 'pending';
      $typology = strtolower(isset($module) ? $module->typology : $step['typology']);

      $score = isset($score) ? $score : 0;
      $score = ['data' => $this->buildScore($score)];

      $status = isset($status) ? $status : 'pending';
      $status = ['data' => $this->buildStatus($status)];

      switch ($typology) {
        case 'course':
          $details = Link::createFromRoute('', 'opigno_statistics.user.course_details', [
            'user' => $uid,
            'training' => $gid,
            'course' => $id,
          ])->toRenderable();
          $details['#attributes']['class'][] = 'details';
          $details['#attributes']['class'][] = 'course-details-open';
          $details['#attributes']['data-user'] = $uid;
          $details['#attributes']['data-training'] = $gid;
          $details['#attributes']['data-id'] = $id;
          $details = ['data' => $details];
          break;

        case 'module':
          $module_id = $id;
          $details = Link::createFromRoute('', 'opigno_statistics.user.training_module_details', [
            'user' => $uid,
            'training' => $gid,
            'module' => $module_id,
          ])->toRenderable();
          $details['#attributes']['class'][] = 'details';
          $details['#attributes']['class'][] = 'training-module-details-open';
          $details['#attributes']['data-user'] = $uid;
          $details['#attributes']['data-training'] = $gid;
          $details['#attributes']['data-id'] = $module_id;
          $details = [
            'data' => [
              $details,
              [
                '#type' => 'container',
                '#attributes' => [
                  'class' => ['module-panel-wrapper'],
                ],
                [
                  '#type' => 'html_tag',
                  '#tag' => 'span',
                  '#attributes' => [
                    'id' => "module_panel_${gid}_${module_id}",
                  ],
                ],
              ],
            ],
          ];
          break;

        default:
          $details = '';
          break;
      }

      $is_course = $typology === 'course';
      return [
        'class' => $is_course ? 'course' : 'module',
        'data-training' => $gid,
        'data-id' => $id,
        'data' => [
          $name,
          $score,
          $status,
          $details,
        ],
      ];
    }, $steps);

    $content[] = [
      '#type' => 'html_tag',
      '#tag' => 'hr',
    ];

    $content[] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['training-modules'],
      ],
      [
        '#type' => 'table',
        '#attributes' => [
          'class' => ['statistics-table', 'training-modules-list', 'mb-0'],
        ],
        '#header' => [
          $this->t('Course / Module'),
          $this->t('Results'),
          $this->t('State'),
          '',
        ],
        '#rows' => $rows,
      ],
    ];

    return $content;
  }

  /**
   * Loads module panel with a AJAX.
   *
   * @param \Drupal\user\UserInterface $user
   *   User.
   * @param \Drupal\group\Entity\GroupInterface $training
   *   Training.
   * @param null|\Drupal\group\Entity\GroupInterface $course
   *   Course.
   * @param \Drupal\opigno_module\Entity\OpignoModule $module
   *   Module.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   AJAX response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function ajaxLoadCourseModuleDetails(
    UserInterface $user,
    GroupInterface $training,
    GroupInterface $course,
    OpignoModule $module
  ) {
    $training_id = $training->id();
    $course_id = $course->id();
    $module_id = $module->id();
    $selector = "#module_panel_${training_id}_${course_id}_${module_id}";
    $content = $this->buildModuleDetails($user, $training, $course, $module);
    $content['#attributes']['data-ajax-loaded'] = TRUE;
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand($selector, $content));
    return $response;
  }

  /**
   * Loads module panel with a AJAX.
   *
   * @param \Drupal\user\UserInterface $user
   *   User.
   * @param \Drupal\group\Entity\GroupInterface $training
   *   Training.
   * @param \Drupal\opigno_module\Entity\OpignoModule $module
   *   Module.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   AJAX response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function ajaxLoadTrainingModuleDetails(
    UserInterface $user,
    GroupInterface $training,
    OpignoModule $module
  ) {
    $training_id = $training->id();
    $module_id = $module->id();
    $selector = "#module_panel_${training_id}_${module_id}";
    $content = $this->buildModuleDetails($user, $training, NULL, $module);
    $content['#attributes']['data-ajax-loaded'] = TRUE;
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand($selector, $content));
    return $response;
  }

  /**
   * Loads a user course details with the AJAX.
   *
   * @param \Drupal\user\UserInterface $user
   *   User.
   * @param \Drupal\group\Entity\GroupInterface $training
   *   Training.
   * @param \Drupal\group\Entity\GroupInterface $course
   *   Course.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   AJAX response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function ajaxLoadCourseDetails(
    UserInterface $user,
    GroupInterface $training,
    GroupInterface $course
  ) {
    $training_gid = $training->id();
    $course_gid = $course->id();
    $selector = ".training-modules-list tr.course[data-training=\"$training_gid\"][data-id=\"$course_gid\"]";
    $content = [
      [
        '#type' => 'html_tag',
        '#tag' => 'tr',
        '#attributes' => [
          'data-training' => $training_gid,
          'data-id' => $course_gid,
          'class' => ['course-active'],
        ],
        [
          '#type' => 'html_tag',
          '#tag' => 'td',
          '#attributes' => [
            'colspan' => 3,
          ],
          '#value' => $course->label(),
        ],
        [
          '#type' => 'html_tag',
          '#tag' => 'td',
          'close' => [
            '#type' => 'html_tag',
            '#tag' => 'span',
            '#attributes' => [
              'data-training' => $training_gid,
              'data-id' => $course_gid,
              'class' => ['course-close'],
            ],
          ],
        ],
      ],
      [
        '#type' => 'html_tag',
        '#tag' => 'tr',
        '#attributes' => [
          'data-training' => $training_gid,
          'data-id' => $course_gid,
          'class' => ['course-details'],
        ],
        [
          '#type' => 'html_tag',
          '#tag' => 'td',
          '#attributes' => [
            'colspan' => 4,
          ],
          'details' => $this->buildCourseDetails($user, $training, $course),
        ],
      ],
    ];
    $content['#attached']['library'][] = 'opigno_statistics/user';
    $response = new AjaxResponse();
    $response->addCommand(new AfterCommand($selector, $content));
    return $response;
  }

  /**
   * Loads a user training details with the AJAX.
   *
   * @param \Drupal\user\UserInterface $user
   *   User.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Training.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   AJAX response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function ajaxLoadTrainingDetails(
    UserInterface $user,
    GroupInterface $group
  ) {
    $gid = $group->id();
    $selector = ".trainings-list tr.training[data-training=\"$gid\"]";
    $content = [
      [
        '#type' => 'html_tag',
        '#tag' => 'tr',
        '#attributes' => [
          'data-training' => $gid,
          'class' => ['training-active'],
        ],
        [
          '#type' => 'html_tag',
          '#tag' => 'td',
          '#attributes' => [
            'colspan' => 4,
          ],
          '#value' => $group->label(),
        ],
        [
          '#type' => 'html_tag',
          '#tag' => 'td',
          'close' => [
            '#type' => 'html_tag',
            '#tag' => 'span',
            '#attributes' => [
              'data-training' => $gid,
              'class' => ['training-close'],
            ],
          ],
        ],
      ],
      [
        '#type' => 'html_tag',
        '#tag' => 'tr',
        '#attributes' => [
          'data-training' => $gid,
          'class' => ['training-details'],
        ],
        [
          '#type' => 'html_tag',
          '#tag' => 'td',
          '#attributes' => [
            'colspan' => 5,
          ],
          'details' => $this->buildTrainingDetails($user, $group),
        ],
      ],
    ];
    $content['#attached']['library'][] = 'opigno_statistics/user';
    $response = new AjaxResponse();
    $response->addCommand(new AfterCommand($selector, $content));
    return $response;
  }

  /**
   * Builds render array for a user trainings list.
   *
   * @param \Drupal\user\UserInterface $user
   *   User.
   *
   * @return array
   *   Render array.
   */
  public function buildTrainingsList(UserInterface $user) {
    $query = $this->database
      ->select('opigno_learning_path_achievements', 'a')
      ->fields('a', ['gid', 'name', 'score', 'status', 'time'])
      ->condition('a.uid', $user->id())
      ->groupBy('a.gid')
      ->groupBy('a.name')
      ->groupBy('a.score')
      ->groupBy('a.status')
      ->groupBy('a.time')
      ->orderBy('a.name');

    $rows = $query->execute()->fetchAll();
    $rows = array_map(function ($row) use ($user) {
      $uid = $user->id();
      $gid = $row->gid;
      $name = $row->name;

      $score = isset($row->score) ? $row->score : 0;
      $score = [
        'data' => $this->buildScore($score),
      ];

      $status = isset($row->status) ? $row->status : 'pending';
      $status = [
        'data' => $this->buildStatus($status),
      ];

      $time_spent = $row->time > 0
        ? $this->date_formatter->formatInterval($row->time) : '-';
      $details = Link::createFromRoute('', 'opigno_statistics.user.training_details', [
        'user' => $uid,
        'group' => $gid,
      ])->toRenderable();
      $details['#attributes']['class'][] = 'details';
      $details['#attributes']['data-user'] = $uid;
      $details['#attributes']['data-training'] = $gid;
      $details = ['data' => $details];

      return [
        'class' => 'training',
        'data-training' => $gid,
        'data' => [
          $name,
          $score,
          $status,
          $time_spent,
          $details,
        ],
      ];
    }, $rows);

    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['trainings-list'],
      ],
      [
        '#type' => 'table',
        '#attributes' => [
          'class' => ['statistics-table', 'trainings-list', 'table-striped'],
        ],
        '#header' => [
          $this->t('Training'),
          $this->t('Score'),
          $this->t('Passed'),
          $this->t('Time spent'),
          $this->t('Details'),
        ],
        '#rows' => $rows,
      ],
    ];
  }

  /**
   * Builds render array for a user course statistics page.
   *
   * @param \Drupal\user\UserInterface $user
   *   User.
   * @param \Drupal\group\Entity\GroupInterface $training
   *   Training.
   * @param \Drupal\group\Entity\GroupInterface $course
   *   Course.
   * @param \Drupal\opigno_module\Entity\OpignoModule $module
   *   Module.
   *
   * @return array
   *   Render array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function courseModule(
    UserInterface $user,
    GroupInterface $training,
    GroupInterface $course,
    OpignoModule $module
  ) {
    $content = [];
    $content[] = $this->buildModuleDetails($user, $training, $course, $module);
    $content['#attached']['library'][] = 'opigno_statistics/user';
    return $content;
  }

  /**
   * Builds render array for a user course statistics page.
   *
   * @param \Drupal\user\UserInterface $user
   *   User.
   * @param \Drupal\group\Entity\GroupInterface $training
   *   Training.
   * @param \Drupal\opigno_module\Entity\OpignoModule $module
   *   Module.
   *
   * @return array
   *   Render array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function trainingModule(
    UserInterface $user,
    GroupInterface $training,
    OpignoModule $module
  ) {
    $content = [];
    $content[] = $this->buildModuleDetails($user, $training, NULL, $module);
    $content['#attached']['library'][] = 'opigno_statistics/user';
    return $content;
  }

  /**
   * Builds render array for a user course statistics page.
   */
  public function course(
    UserInterface $user,
    GroupInterface $training,
    GroupInterface $course
  ) {
    $content = [];
    $content[] = $this->buildCourseDetails($user, $training, $course);
    $content['#attached']['library'][] = 'opigno_statistics/user';
    return $content;
  }

  /**
   * Builds render array for a user training statistics page.
   */
  public function training(UserInterface $user, GroupInterface $group) {
    $content = [];
    $content[] = $this->buildTrainingDetails($user, $group);
    $content['#attached']['library'][] = 'opigno_statistics/user';
    return $content;
  }

  /**
   * Builds render array for a user statistics index page.
   *
   * @param \Drupal\user\UserInterface $user
   *   User.
   *
   * @return array
   *   Render array.
   */
  public function index(UserInterface $user) {
    $content = [];
    $content[] = $this->buildUserInfo($user);
    $content[] = $this->buildBadges($user);
    $content[] = $this->buildTrainingsList($user);
    $content['#attached']['library'][] = 'opigno_statistics/user';
    return $content;
  }

}
