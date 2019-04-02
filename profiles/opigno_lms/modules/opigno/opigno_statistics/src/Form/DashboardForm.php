<?php

namespace Drupal\opigno_statistics\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\opigno_statistics\StatisticsPageTrait;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Database\Database;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements the statistics dashboard.
 */
class DashboardForm extends FormBase {

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
   * DashboardForm constructor.
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
    return 'opigno_statistics_dashboard_form';
  }

  /**
   * Builds active users per day graph.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $datetime
   *   Date.
   * @param mixed $lp_ids
   *   LP ID.
   *
   * @return array
   *   Render array.
   *
   * @throws \Exception
   */
  protected function buildUsersPerDay(DrupalDateTime $datetime, $lp_ids = NULL) {
    $max_time = $datetime->format(DrupalDateTime::FORMAT);
    // Last month.
    $min_datetime = $datetime->sub(new \DateInterval('P1M'));
    $min_time = $min_datetime->format(DrupalDateTime::FORMAT);

    $query = $this->database
      ->select('opigno_statistics_user_login', 'u');
    $query->addExpression('DAY(u.date)', 'hour');
    $query->addExpression('COUNT(DISTINCT u.uid)', 'count');

    if (is_array($lp_ids)) {
      $query->leftJoin('group_content_field_data', 'g_c_f_d', 'u.uid = g_c_f_d.entity_id');
      $query->condition('g_c_f_d.gid', $lp_ids, 'IN');
      $query->condition('g_c_f_d.type', 'learning_path-group_membership');
    }

    $query->condition('u.uid', 0, '<>');

    $data = $query
      ->condition('u.date', [$min_time, $max_time], 'BETWEEN')
      ->groupBy('hour')
      ->execute()
      ->fetchAllAssoc('hour');

    for ($i = 1; $i <= 31; ++$i) {
      if (isset($data[$i])) {
        $data[$i] = $data[$i]->count;
      }
      else {
        $data[$i] = 0;
      }
    }

    return [
      '#type' => 'container',
      [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#attributes' => [
          'class' => ['users-per-day-title'],
        ],
        '#value' => $this->t('Number of active users per day'),
        [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => ['popover-help'],
            'data-toggle' => 'popover',
            'data-content' => t('This chart persents the number of unique user login per day.'),
          ],
          '#value' => '?',
        ],
      ],
      [
        '#type' => 'inline_template',
        '#template' => '<svg class="users-per-day" viewBox="-20 -20 500 220">
  {% for i in 0..h_lines %}
    {% set y = height - height * i / h_lines %}
    <line x1="{{ padding }}" y1="{{ y }}" x2="{{ padding + day_x_step * (max_day - min_day) }}" y2="{{ y }}"></line>
    <text x="0" y="{{ y }}">{{ (max_count * i / h_lines)|round }}</text>
  {% endfor %}

  {% for i in min_day..max_day %}
    {% set x = -5 + padding + day_x_step * (i - min_day) %}
    {% set y = padding + height %}
    <text x="{{ x }}" y="{{ y }}">{{ i }}</text>
  {% endfor %}

  <path d="
  {% set y = height - height * data[min_day] / max_count %}
  M{{ padding }},{{ y }}
  {% for i in (min_day + 1)..max_day %}
    {% set x = padding + day_x_step * (i - min_day) %}
    {% set y = height - height * data[i] / max_count %}
    L{{ x }},{{ y }}
  {% endfor %}
  "></path>

  {% for i in min_day..max_day %}
    {% set x = padding + day_x_step * (i - min_day) %}
    {% set y = height - height * data[i] / max_count %}
    <circle cx="{{ x }}" cy="{{ y }}" r="4"></circle>
  {% endfor %}
</svg>',
        '#context' => [
          'data' => $data,
          'min_day' => 1,
          'max_day' => 31,
          'day_x_step' => 15,
          'height' => 175,
          'h_lines' => 5,
          'max_count' => max(max($data), 5),
          'padding' => 20,
        ],
      ],
    ];
  }

  /**
   * Builds trainings progress.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $datetime
   *   Date.
   * @param mixed $lp_ids
   *   LP ID.
   *
   * @return array
   *   Render array.
   *
   * @throws \Exception
   */
  protected function buildTrainingsProgress(DrupalDateTime $datetime, $lp_ids = NULL) {
    $progress = 0;
    $completion = 0;

    $time_str = $datetime->format(DrupalDateTime::FORMAT);

    $query = $this->database->select('opigno_learning_path_achievements', 'a');
    $query->addExpression('SUM(a.progress) / COUNT(a.progress) / 100', 'progress');
    $query->addExpression('COUNT(a.completed) / COUNT(a.registered)', 'completion');
    $query->fields('a', ['name'])
      ->groupBy('a.name')
      ->orderBy('a.name')
      ->condition('a.registered', $time_str, '<');

    if (is_array($lp_ids)) {
      $query->condition('a.gid', $lp_ids, 'IN');
      $query->leftJoin('group_content_field_data', 'g_c_f_d', 'a.uid = g_c_f_d.entity_id AND g_c_f_d.gid = a.gid');
      $query->condition('g_c_f_d.type', 'learning_path-group_membership');
    }

    $query->condition('a.uid', 0, '<>');
    $or_group = $query->orConditionGroup();
    $or_group->condition('a.completed', $time_str, '<');
    $or_group->isNull('a.completed');

    $data = $query
      ->execute()
      ->fetchAll();

    $count = count($data);
    if ($count > 0) {
      foreach ($data as $row) {
        $progress += $row->progress;
        $completion += $row->completion;
      }

      $progress /= $count;
      $completion /= $count;
    }

    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['trainings-progress'],
      ],
      'progress' => $this->buildValueWithIndicator(
        $this->t('Training Progress'),
        $progress,
        NULL,
        t('Training progress is calculated as the sum of training progress for all published trainings divided by the total number of published trainings.
		The training progress for a training is the sum of progress for all the users registered to the training divided by the number of users registered to the training.')
      ),
      'completion' => $this->buildValueWithIndicator(
        $this->t('Training Completion'),
        $completion,
        NULL,
        t('Training completion is calculated as the sum of training completion rate for all published trainings divided by the total number of published trainings.
		The training completion for a training is the total number of users being successful at the training divided by the number of users registered to the training.')
      ),
      'users' => $this->buildUsersPerDay($datetime, $lp_ids),
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
   * @return array
   *   Render array.
   */
  protected function buildUserMetrics($lp_ids = NULL) {
    $connection = Database::getConnection();
    $query = $connection
      ->select('users', 'u');
    if (is_array($lp_ids)) {
      $query->leftJoin('group_content_field_data', 'g_c_f_d', 'u.uid = g_c_f_d.entity_id');
      $query->condition('g_c_f_d.type', 'learning_path-group_membership');
      $query->condition('g_c_f_d.gid', $lp_ids, 'IN');
    }
    $query->condition('u.uid', 0, '<>');
    $query->groupBy('u.uid');
    $users = $query->countQuery()->execute()->fetchField();

    $now = $this->time->getRequestTime();
    // Last 7 days.
    $period = 60 * 60 * 24 * 7;

    $query = $connection
      ->select('users_field_data', 'u');
    if (is_array($lp_ids)) {
      $query->leftJoin('group_content_field_data', 'g_c_f_d', 'u.uid = g_c_f_d.entity_id');
      $query->condition('g_c_f_d.type', 'learning_path-group_membership');
      $query->condition('g_c_f_d.gid', $lp_ids, 'IN');
    }
    $query->condition('u.uid', 0, '<>');
    $query->condition('u.created', $now - $period, '>');
    $query->groupBy('u.uid');
    $new_users = $query->countQuery()->execute()->fetchField();

    $query = $connection
      ->select('users_field_data', 'u');
    if (is_array($lp_ids)) {
      $query->leftJoin('group_content_field_data', 'g_c_f_d', 'u.uid = g_c_f_d.entity_id');
      $query->condition('g_c_f_d.type', 'learning_path-group_membership');
      $query->condition('g_c_f_d.gid', $lp_ids, 'IN');
    }
    $query->condition('u.uid', 0, '<>');
    $query->condition('u.access', $now - $period, '>');
    $query->groupBy('u.uid');
    $active_users = $query->countQuery()->execute()->fetchField();

    $users_block = $this->buildUserMetric(
      $this->t('Users'),
      $users,
      t('This is the total number of users on your Opigno instance')
    );
    $new_users_block = $this->buildUserMetric(
      $this->t('New users'),
      $new_users,
      t('This is the number of new users who registered to your Opigno instance during the last 7 days.')
    );
    $active_users_block = $this->buildUserMetric(
      $this->t('Recently active users'),
      $active_users,
      t('This is the number of users who logged in to your Opigno instance during the last 7 days.')
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
            'data-content' => t('The data below is related to your global Opigno platform (for all trainings).'),
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
   * Builds trainings listing.
   *
   * @return array
   *   Render array.
   */
  protected function buildTrainingsList($lp_ids) {
    $query = $this->database->select('opigno_learning_path_achievements', 'a');
    $query->addExpression('COUNT(a.completed)', 'users_completed');
    $query->addExpression('AVG(a.time)', 'time');
    $query->fields('a', ['gid', 'name']);

    if (is_array($lp_ids)) {
      $query->condition('a.gid', $lp_ids, 'IN');
    }

    $data = $query
      ->groupBy('a.gid')
      ->groupBy('a.name')
      ->orderBy('a.name')
      ->distinct()
      ->execute()
      ->fetchAll();

    $query = $this->database->select('opigno_learning_path_group_user_status', 's');
    $query->addField('s', 'gid');
    $query->condition('s.uid', 0, '<>');
    $query->addExpression('COUNT(*)', 'count');
    $query->groupBy('s.gid');
    $groups = $query->execute()->fetchAllAssoc('gid');

    $table = [
      '#type' => 'table',
      '#attributes' => [
        'class' => ['statistics-table', 'trainings-list', 'table-striped'],
      ],
      '#header' => [
        $this->t('Training'),
        $this->t('Nb of users'),
        $this->t('Nb completed'),
        $this->t('Avg time spent'),
        $this->t('Details'),
      ],
      '#rows' => [],
    ];

    foreach ($data as $row) {
      $time = max(0, round($row->time));
      $time_str = $time > 0
        ? $this->date_formatter->formatInterval($time)
        : '-';

      $details_link = Link::createFromRoute(
        '',
        'opigno_statistics.training',
        [
          'group' => $row->gid,
        ]
      )->toRenderable();
      $details_link['#attributes']['class'][] = 'details';
      $details_link = [
        'data' => $details_link,
      ];

      $table['#rows'][] = [
        $row->name,
        isset($groups[$row->gid]) ? $groups[$row->gid]->count : '',
        $row->users_completed,
        $time_str,
        $details_link,
      ];
    }

    return $table;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $query = $this->database
      ->select('opigno_learning_path_achievements', 'a');
    $query->addExpression('YEAR(a.registered)', 'year');
    $data = $query
      ->groupBy('year')
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
    $max_year = !empty($years) ? max(array_keys($years)) : NULL;
    $year_select = [
      '#type' => 'select',
      '#options' => $years,
      '#default_value' => $max_year,
      '#ajax' => [
        'event' => 'change',
        'callback' => '::submitFormAjax',
        'wrapper' => 'statistics-trainings-progress',
      ],
    ];
    $year = $form_state->getValue('year', $max_year);

    $query = $this->database
      ->select('opigno_learning_path_achievements', 'a');
    $query->addExpression('MONTH(a.registered)', 'month');
    $data = $query
      ->groupBy('month')
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
    $max_month = !empty($months) ? max(array_keys($months)) : NULL;
    $month_select = [
      '#type' => 'select',
      '#options' => $months,
      '#default_value' => $max_month,
      '#ajax' => [
        'event' => 'change',
        'callback' => '::submitFormAjax',
        'wrapper' => 'statistics-trainings-progress',
      ],
    ];
    $month = $form_state->getValue('month', $max_month);

    $timestamp = mktime(0, 0, 0, $month, 1, $year);
    $datetime = DrupalDateTime::createFromTimestamp($timestamp);
    $datetime->add(new \DateInterval('P1M'));

    // Check if user has limited permissions for global statistic.
    $account = \Drupal::currentUser();
    $lp_ids = NULL;
    if (!($account->hasPermission('view global statistics')
      || $account->hasPermission('view any user statistics')
      || $account->id() == 1)) {
      $lp_ids = $this->checkLimitPermissions($account);
    }

    $form['trainings_progress'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'statistics-trainings-progress',
      ],
      'year' => $year_select,
      'month' => $month_select,
      'trainings_progress' => $this->buildTrainingsProgress($datetime, $lp_ids),
    ];

    $form[] = [
      '#type' => 'container',
      'users' => $this->buildUserMetrics($lp_ids),
      'trainings_list' => $this->buildTrainingsList($lp_ids),
    ];

    $form['#attached']['library'][] = 'opigno_statistics/dashboard';

    return $form;
  }

  /**
   * Get array of learning paths ID's where user have role 'student manager'.
   */
  public function checkLimitPermissions(AccountInterface $account) {
    $connection = Database::getConnection();
    $query = $connection
      ->select('group_content_field_data', 'g_c_f_d')
      ->fields('g_c_f_d', ['gid']);
    $query->leftJoin('group_content__group_roles', 'g_c_g_r', 'g_c_f_d.id = g_c_g_r.entity_id');
    $query->condition('g_c_g_r.group_roles_target_id', 'learning_path-user_manager');
    $query->condition('g_c_f_d.entity_id', $account->id());
    $query->condition('g_c_f_d.type', 'learning_path-group_membership');
    $result = $query->execute()->fetchAllAssoc('gid');

    $lp_ids = [];
    foreach ($result as $row) {
      $lp_ids[] = $row->gid;
    }

    return $lp_ids;
  }

  /**
   * Access callback to check that the user can access to view global statistic.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function access(AccountInterface $account) {
    $uid = $account->id();

    if ($account->hasPermission('view global statistics')
      || $account->hasPermission('view any user statistics')
      || $uid == 1) {
      return AccessResult::allowed();
    }
    else {
      // Check if user has role 'student manager' in any of trainings.
      $connection = Database::getConnection();
      $query_c = $connection
        ->select('group_content_field_data', 'g_c_f_d')
        ->fields('g_c_f_d', ['gid']);
      $query_c->leftJoin('group_content__group_roles', 'g_c_g_r', 'g_c_f_d.id = g_c_g_r.entity_id');
      $query_c->condition('g_c_g_r.group_roles_target_id', 'learning_path-user_manager');
      $query_c->condition('g_c_f_d.entity_id', $uid);
      $query_c->condition('g_c_f_d.type', 'learning_path-group_membership');
      $lp_counts = $query_c->countQuery()->execute()->fetchField();

      if ($lp_counts > 0) {
        return AccessResultAllowed::allowed()->mergeCacheMaxAge(0);
      }
      else {
        return AccessResultAllowed::forbidden()->mergeCacheMaxAge(0);
      }
    }
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
  public function getCacheMaxAge() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
