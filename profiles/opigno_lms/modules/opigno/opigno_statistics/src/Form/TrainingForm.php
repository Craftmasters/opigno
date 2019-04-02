<?php

namespace Drupal\opigno_statistics\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\group\Entity\GroupInterface;
use Drupal\opigno_statistics\StatisticsPageTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\group\Entity\Group;

/**
 * Implements the training statistics page.
 */
class TrainingForm extends FormBase {

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
   * TrainingForm constructor.
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
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'opigno_statistics_training_form';
  }

  /**
   * Builds training progress.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group.
   * @param \Drupal\Core\Datetime\DrupalDateTime $datetime
   *   Date.
   *
   * @return array
   *   Render array.
   */
  protected function buildTrainingsProgress(GroupInterface $group, DrupalDateTime $datetime) {
    $time_str = $datetime->format(DrupalDateTime::FORMAT);
    $group_bundle = $group->bundle();

    $query = $this->database->select('opigno_learning_path_achievements', 'a');
    $query->addExpression('SUM(a.progress) / COUNT(a.progress) / 100', 'progress');
    $query->addExpression('COUNT(a.completed) / COUNT(a.registered)', 'completion');
    $query->condition('a.uid', 0, '<>');

    $or_group = $query->orConditionGroup();
    $or_group->condition('a.completed', $time_str, '<');
    $or_group->isNull('a.completed');

    if ($group_bundle == 'learning_path') {
      $data = $query->condition('a.gid', $group->id())
        ->condition('a.registered', $time_str, '<')
        ->execute()
        ->fetchAssoc();
    }
    elseif ($group_bundle == 'opigno_class') {
      $query_class = $this->database->select('group_content_field_data', 'g_c_f_d')
        ->fields('g_c_f_d', ['gid'])
        ->condition('entity_id', $group->id())
        ->condition('type', 'group_content_type_27efa0097d858')
        ->execute()
        ->fetchAll();

      $lp_ids = [];
      foreach ($query_class as $result_ids) {
        $lp_ids[] = $result_ids->gid;
      }

      if (empty($lp_ids)) {
        $lp_ids[] = 0;
      }
      $query->condition('a.gid', $lp_ids, 'IN');

      $data = $query->execute()->fetchAssoc();
    }

    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['trainings-progress'],
      ],
      'progress' => $this->buildValueWithIndicator(
        $this->t('Training Progress'),
        $data['progress'],
        NULL,
        t('The training progress is the sum of progress for all the users registered to the training divided by the number of users registered to the training.')
      ),
      'completion' => $this->buildValueWithIndicator(
        $this->t('Training Completion'),
        $data['completion'],
        NULL,
        t('The training completion for a training is the total number of users being successful at the training divided by the number of users registered to the training.')
      ),
    ];
  }

  /**
   * Builds one block for the user metrics.
   *
   * @param string $label
   *   Label.
   * @param string $value
   *   Value.
   * @param string $help_text
   *   Help text.
   *
   * @return array
   *   Render array.
   */
  protected function buildUserMetric($label, $value, $help_text = NULL) {
    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['user-metric'],
      ],
      [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => ['user-metric-value'],
        ],
        '#value' => $value,
        ($help_text) ? [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => ['popover-help'],
            'data-toggle' => 'popover',
            'data-content' => $help_text,
          ],
          '#value' => '?',
        ] : NULL,
      ],
      [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => ['user-metric-label'],
        ],
        '#value' => $label,
      ],
    ];
  }

  /**
   * Builds user metrics.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group.
   *
   * @return array
   *   Render array.
   */
  protected function buildUserMetrics(GroupInterface $group) {
    if ($group->bundle() == 'opigno_class') {
      $condition = 'AND gc.type IN (\'opigno_class-group_membership\')';
    }
    else {
      $condition = 'AND gc.type IN (\'learning_path-group_membership\', \'opigno_course-group_membership\')';
    }

    $query = $this->database->select('users', 'u');
    $query->innerJoin(
      'group_content_field_data',
      'gc',
      "gc.entity_id = u.uid
" . $condition . "
AND gc.gid = :gid",
      [
        ':gid' => $group->id(),
      ]
    );
    $users = $query
      ->condition('u.uid', 0, '<>')
      ->countQuery()
      ->execute()
      ->fetchField();

    $now = $this->time->getRequestTime();
    // Last 7 days.
    $period = 60 * 60 * 24 * 7;

    $query = $this->database->select('users_field_data', 'u');
    $query->innerJoin(
      'group_content_field_data',
      'gc',
      "gc.entity_id = u.uid
" . $condition . "
AND gc.gid = :gid",
      [
        ':gid' => $group->id(),
      ]
    );
    $new_users = $query
      ->condition('u.uid', 0, '<>')
      ->condition('u.created', $now - $period, '>')
      ->countQuery()
      ->execute()
      ->fetchField();

    $query = $this->database->select('users_field_data', 'u');
    $query->innerJoin(
      'group_content_field_data',
      'gc',
      "gc.entity_id = u.uid
" . $condition . "
AND gc.gid = :gid",
      [
        ':gid' => $group->id(),
      ]
    );
    $active_users = $query
      ->condition('u.uid', 0, '<>')
      ->condition('u.login', $now - $period, '>')
      ->countQuery()
      ->execute()
      ->fetchField();

    $users_block = $this->buildUserMetric(
      $this->t('Users'),
      $users,
      t('This is the number of users registered to that training.')
    );
    $new_users_block = $this->buildUserMetric(
      $this->t('New users'),
      $new_users,
      t('@TODO help text New users')
    );
    $active_users_block = $this->buildUserMetric(
      $this->t('Recently active users'),
      $active_users,
      t('This is the number of users who where active in that training within the last 7 days.')
    );

    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['user-metrics'],
      ],
      [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#attributes' => [
          'class' => ['user-metrics-title'],
        ],
        '#value' => $this->t('Users metrics'),
        [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => ['popover-help'],
            'data-toggle' => 'popover',
            'data-content' => t('The metrics below are related to this training'),
          ],
          '#value' => '?',
        ],
      ],
      [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['user-metrics-content'],
        ],
        'users' => $users_block,
        'new_users' => $new_users_block,
        'active_users' => $active_users_block,
      ],
    ];
  }

  /**
   * Builds training content.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group.
   *
   * @return array
   *   Render array.
   */
  protected function buildTrainingContent(GroupInterface $group) {
    $query = $this->database
      ->select('opigno_learning_path_achievements', 'a');
    $query->leftJoin(
      'opigno_learning_path_step_achievements',
      's',
      's.gid = a.gid AND s.uid = a.uid'
    );
    $query->leftJoin(
      'opigno_learning_path_step_achievements',
      'sc',
      'sc.id = s.id AND sc.completed IS NOT NULL'
    );
    $query->addExpression('COUNT(sc.uid)', 'completed');
    $query->addExpression('AVG(s.score)', 'score');
    $query->addExpression('AVG(s.time)', 'time');
    $query->addExpression('MAX(s.entity_id)', 'entity_id');
    $query->addExpression('MAX(s.parent_id)', 'parent_id');
    $query->addExpression('MAX(s.position)', 'position');
    $query->addExpression('MAX(s.typology)', 'typology');
    $query->addExpression('MAX(s.id)', 'id');
    $query->condition('a.uid', 0, '<>');

    $data = $query->fields('s', ['name'])
      ->condition('a.gid', $group->id())
      ->groupBy('s.name')
      ->orderBy('position')
      ->orderBy('parent_id')
      ->execute()
      ->fetchAllAssoc('entity_id');

    $query = $this->database->select('users', 'u');
    $query->innerJoin(
      'group_content_field_data',
      'gc',
      "gc.entity_id = u.uid
AND gc.type IN ('learning_path-group_membership', 'opigno_course-group_membership')
AND gc.gid = :gid",
      [
        ':gid' => $group->id(),
      ]
    );
    $users = $query
      ->condition('u.uid', 0, '<>')
      ->countQuery()
      ->execute()
      ->fetchField();

    $table = [
      '#type' => 'table',
      '#attributes' => [
        'class' => [
          'statistics-table',
          'training-content-list',
          'table-striped',
        ],
      ],
      '#header' => [
        $this->t('Step'),
        $this->t('% Completed'),
        $this->t('Avg score'),
        $this->t('Avg time spent'),
      ],
      '#rows' => [],
    ];

    $entity_ids = array_keys($data);

    // Get relationships between courses and modules.
    $query = \Drupal::database()
      ->select('group_content_field_data', 'g_c_f_d');
    $query->fields('g_c_f_d', ['entity_id', 'gid']);
    $query->condition('g_c_f_d.entity_id', $entity_ids, 'IN');
    $group_content = $query
      ->execute()
      ->fetchAll();

    $modules_relationships = [];

    foreach ($group_content as $content) {
      $modules_relationships[$content->entity_id][] = $content->gid;
    }

    // Sort courses and modules.
    $rows = [];
    foreach ($data as $row) {
      if ($row->typology == 'Course') {
        $rows[] = $row;
        foreach ($data as $module) {
          if (in_array($row->entity_id, $modules_relationships[$module->entity_id])) {
            $rows[] = $module;
          }
        }
      }
      elseif (($row->typology == 'Module' && $row->parent_id == 0)
        || $row->typology == 'ILT' || $row->typology == 'Meeting') {
        $rows[] = $row;
      }
    }

    foreach ($rows as $row) {
      if (!empty($row->name)) {
        $completed = round(100 * $row->completed / $users);
        $score = round($row->score);
        $time = $row->time > 0
          ? $this->date_formatter->formatInterval($row->time)
          : '-';

        $table['#rows'][] = [
          $row->name,
          $this->t('@completed%', ['@completed' => $completed]),
          $this->t('@score%', ['@score' => $score]),
          $time,
        ];
      }
    }

    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['training-content'],
      ],
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#attributes' => [
          'class' => ['training-content-title'],
        ],
        '#value' => $this->t('Training Content'),
      ],
      'list' => $table,
    ];
  }

  /**
   * Builds users results for Learning paths.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group.
   *
   * @return array
   *   Render array.
   */
  protected function buildUsersResultsLp(GroupInterface $group) {
    $query = $this->database->select('users_field_data', 'u');
    $query->innerJoin(
      'group_content_field_data',
      'gc',
      "gc.entity_id = u.uid
AND gc.type IN ('learning_path-group_membership', 'opigno_course-group_membership')
AND gc.gid = :gid",
      [
        ':gid' => $group->id(),
      ]
    );
    $query->leftJoin(
      'opigno_learning_path_achievements',
      'a',
      'a.gid = gc.gid AND a.uid = u.uid'
    );

    $query->condition('u.uid', 0, '<>');

    $data = $query
      ->fields('u', ['uid', 'name'])
      ->fields('a', ['status', 'score', 'time'])
      ->execute()
      ->fetchAll();

    $table = [
      '#type' => 'table',
      '#attributes' => [
        'class' => ['statistics-table', 'users-results-list', 'table-striped'],
      ],
      '#header' => [
        $this->t('User'),
        $this->t('Score'),
        $this->t('Passed'),
        $this->t('Time spent'),
        $this->t('Details'),
      ],
      '#rows' => [],
    ];
    foreach ($data as $row) {
      $score = isset($row->score) ? $row->score : 0;
      $score = [
        'data' => $this->buildScore($score),
      ];

      $status = isset($row->status) ? $row->status : 'pending';
      $status = [
        'data' => $this->buildStatus($status),
      ];

      $time = $row->time > 0
        ? $this->date_formatter->formatInterval($row->time)
        : '-';

      $details_link = Link::createFromRoute(
        '',
        'opigno_statistics.user',
        [
          'user' => $row->uid,
        ]
      )->toRenderable();
      $details_link['#attributes']['class'][] = 'details';
      $details_link = [
        'data' => $details_link,
      ];

      $table['#rows'][] = [
        $row->name,
        $score,
        $status,
        $time,
        $details_link,
      ];
    }

    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['users-results'],
      ],
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#attributes' => [
          'class' => ['users-results-title'],
        ],
        '#value' => $this->t('Users results'),
      ],
      'list' => $table,
    ];
  }

  /**
   * Builds users results for Classes.
   */
  protected function buildUsersResultsClass(GroupInterface $group, $lp_id = NULL) {
    if (!$lp_id) {
      return;
    }

    $members = $group->getMembers();
    $title = Group::load($lp_id)->label();

    foreach ($members as $member) {
      $user = $member->getUser();
      if ($user) {
        $members_ids[$user->id()] = $member->getUser()->id();
      }
    }
    if (empty($members_ids)) {
      $members_ids[] = 0;
    }

    $query = $this->database->select('users_field_data', 'u')
      ->fields('u', ['uid', 'name']);
    $query->condition('u.uid', $members_ids, 'IN');
    $query->condition('u.uid', 0, '<>');
    $query->innerJoin('group_content_field_data', 'g_c', 'g_c.entity_id = u.uid');
    $query->condition('g_c.type', ['learning_path-group_membership', 'opigno_course-group_membership'], 'IN');
    $query->condition('g_c.gid', $lp_id);
    $query->leftJoin('opigno_learning_path_achievements', 'a', 'a.gid = g_c.gid AND a.uid = u.uid');
    $query->fields('a', ['status', 'score', 'time', 'gid']);
    $query->orderBy('u.name', 'ASC');
    $statistic = $query->execute()->fetchAll();

    $table = [
      '#type' => 'table',
      '#attributes' => [
        'class' => ['statistics-table', 'users-results-list', 'table-striped'],
      ],
      '#header' => [
        $this->t('User'),
        $this->t('Score'),
        $this->t('Passed'),
        $this->t('Time spent'),
        $this->t('Details'),
      ],
      '#rows' => [],
    ];

    foreach ($statistic as $row) {
      $score = isset($row->score) ? $row->score : 0;
      $score = [
        'data' => $this->buildScore($score),
      ];

      $status = isset($row->status) ? $row->status : 'pending';
      $status = [
        'data' => $this->buildStatus($status),
      ];

      $time = $row->time > 0
        ? $this->date_formatter->formatInterval($row->time)
        : '-';

      $details_link = Link::createFromRoute(
        '',
        'opigno_statistics.user',
        [
          'user' => $row->uid,
        ]
      )->toRenderable();
      $details_link['#attributes']['class'][] = 'details';
      $details_link = [
        'data' => $details_link,
      ];

      $table['#rows'][] = [
        $row->name,
        $score,
        $status,
        $time,
        $details_link,
      ];
    }

    // Hide links on detail user pages if user don't have permissions.
    $account = \Drupal::currentUser();
    if (!$account->hasPermission('view module results')) {
      unset($table['#header'][4]);
      foreach ($table['#rows'] as $key => $table_row) {
        unset($table['#rows'][$key][4]);
      }
    }

    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['users-results'],
      ],
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#attributes' => [
          'class' => ['users-results-title'],
        ],
        '#value' => $this->t($title),
      ],
      'list' => $table,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $group = NULL) {
    $form['#title'] = $this->t('Training statistics - @training', [
      '@training' => $group->label(),
    ]);

    if ($group->bundle() == 'opigno_class') {
      $query_class = $this->database->select('group_content_field_data', 'g_c_f_d')
        ->fields('g_c_f_d', ['gid'])
        ->condition('entity_id', $group->id())
        ->condition('type', 'group_content_type_27efa0097d858')
        ->execute()
        ->fetchAll();

      $lp_ids = [];
      foreach ($query_class as $result_ids) {
        $lp_ids[] = $result_ids->gid;
      }
    }
    else {
      $lp_ids[] = $group->id();
    }

    if (empty($lp_ids)) {
      $lp_ids[] = 0;
    }

    $query = $this->database
      ->select('opigno_learning_path_achievements', 'a');
    $query->addExpression('YEAR(a.registered)', 'year');
    $query->condition('a.gid', $lp_ids, 'IN');
    $data = $query->groupBy('year')
      ->orderBy('year', 'DESC')
      ->execute()
      ->fetchAll();
    $years = [];
    foreach ($data as $row) {
      $year = $row->year;
      if (!isset($years[$year])) {
        $years[$year] = $year;
      }
    }
    $year_select = [
      '#type' => 'select',
      '#options' => $years,
      '#default_value' => max(array_keys($years)),
      '#ajax' => [
        'event' => 'change',
        'callback' => '::submitFormAjax',
        'wrapper' => 'statistics-trainings-progress',
      ],
    ];
    $year = $form_state->getValue('year', max(array_keys($years)));

    $query = $this->database
      ->select('opigno_learning_path_achievements', 'a');
    $query->addExpression('MONTH(a.registered)', 'month');
    $query->condition('a.gid', $lp_ids, 'IN');
    $data = $query->groupBy('month')
      ->orderBy('month')
      ->execute()
      ->fetchAll();
    $months = [];
    foreach ($data as $row) {
      $month = $row->month;
      if (!isset($months[$month])) {
        $timestamp = mktime(0, 0, 0, $month, 1);
        $months[$month] = $this->date_formatter
          ->format($timestamp, 'custom', 'F');
      }
    }
    $month_select = [
      '#type' => 'select',
      '#options' => $months,
      '#default_value' => max(array_keys($months)),
      '#ajax' => [
        'event' => 'change',
        'callback' => '::submitFormAjax',
        'wrapper' => 'statistics-trainings-progress',
      ],
    ];
    $month = $form_state->getValue('month', max(array_keys($months)));

    $timestamp = mktime(0, 0, 0, $month, 1, $year);
    $datetime = DrupalDateTime::createFromTimestamp($timestamp);
    $datetime->add(new \DateInterval('P1M'));

    $form['trainings_progress'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'statistics-trainings-progress',
      ],
      'year' => $year_select,
      'month' => $month_select,
      'trainings_progress' => $this->buildTrainingsProgress($group, $datetime),
    ];

    if ($group->bundle() == 'opigno_class') {
      $form[] = [
        '#type' => 'container',
        'users' => $this->buildUserMetrics($group),
      ];

      foreach ($lp_ids as $lp_id) {
        $form[] = [
          'training_class_results_' . $lp_id => $this->buildUsersResultsClass($group, $lp_id),
        ];
      }
    }
    else {
      $form[] = [
        '#type' => 'container',
        'users' => $this->buildUserMetrics($group),
        'training_content' => $this->buildTrainingContent($group),
        'user_results' => $this->buildUsersResultsLp($group),
      ];
    }

    $form['#attached']['library'][] = 'opigno_statistics/training';

    return $form;
  }

  /**
   * Ajax form submit.
   */
  public function submitFormAjax(array &$form, FormStateInterface $form_state) {
    return $form['trainings_progress'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
