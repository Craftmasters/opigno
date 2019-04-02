<?php

namespace Drupal\opigno_migration\Form;

use Drupal\migrate_drupal_ui\Form\MigrateUpgradeFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\migrate_drupal\Plugin\MigrateFieldPluginManagerInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate_drupal_ui\Batch\MigrateUpgradeImportBatch;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Migrate Upgrade review form.
 *
 * This confirmation form uses the source_module and destination_module
 * properties on the source, destination and field plugins as well as the
 * system data from the source to determine if there is a migration path for
 * each module in the source.
 *
 * @internal
 */
class OpignoUpgradeReviewForm extends MigrateUpgradeFormBase {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The migration plugin manager service.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $pluginManager;

  /**
   * The field plugin manager service.
   *
   * @var \Drupal\migrate_drupal\Plugin\MigrateFieldPluginManagerInterface
   */
  protected $fieldPluginManager;

  /**
   * The migrations.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface[]
   */
  protected $migrations;

  /**
   * List of extensions that do not need an upgrade path.
   *
   * This property is an array where the keys are the major Drupal core version
   * from which we are upgrading, and the values are arrays of extension names
   * that do not need an upgrade path.
   *
   * @var array[]
   */
  protected $noUpgradePaths = [
    '6' => [
      'blog',
      'blogapi',
      'calendarsignup',
      'color',
      'content_copy',
      'content_multigroup',
      'content_permissions',
      'date_api',
      'date_locale',
      'date_php4',
      'date_popup',
      'date_repeat',
      'date_timezone',
      'date_tools',
      'datepicker',
      'ddblock',
      'event',
      'fieldgroup',
      'filefield_meta',
      'help',
      'i18nstrings',
      'imageapi',
      'imageapi_gd',
      'imageapi_imagemagick',
      'imagecache_ui',
      'jquery_ui',
      'nodeaccess',
      'number',
      'openid',
      'php',
      'ping',
      'poll',
      'throttle',
      'tracker',
      'translation',
      'trigger',
      'variable',
      'variable_admin',
      'views_export',
      'views_ui',
    ],
    '7' => [
      'blog',
      'bulk_export',
      'contextual',
      'ctools',
      'ctools_access_ruleset',
      'ctools_ajax_sample',
      'ctools_custom_content',
      'dashboard',
      'date_all_day',
      'date_api',
      'date_context',
      'date_migrate',
      'date_popup',
      'date_repeat',
      'date_repeat_field',
      'date_tools',
      'date_views',
      'entity',
      'entity_feature',
      'entity_token',
      'entityreference',
      'field_ui',
      'help',
      'openid',
      'overlay',
      'page_manager',
      'php',
      'poll',
      'search_embedded_form',
      'search_extra_type',
      'search_node_tags',
      'simpletest',
      'stylizer',
      'term_depth',
      'title',
      'toolbar',
      'translation',
      'trigger',
      'views_content',
      'views_ui',
    ],
  ];

  /**
   * ReviewForm constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   The migration plugin manager service.
   * @param \Drupal\migrate_drupal\Plugin\MigrateFieldPluginManagerInterface $field_plugin_manager
   *   The field plugin manager service.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempstore_private
   *   The private tempstore factory.
   */
  public function __construct(StateInterface $state, MigrationPluginManagerInterface $migration_plugin_manager, MigrateFieldPluginManagerInterface $field_plugin_manager, PrivateTempStoreFactory $tempstore_private) {
    parent::__construct($tempstore_private);
    $this->state = $state;
    $this->pluginManager = $migration_plugin_manager;
    $this->fieldPluginManager = $field_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('plugin.manager.migration'),
      $container->get('plugin.manager.migrate.field'),
      $container->get('tempstore.private')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'opigno_migration_upgrade_review';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get all the data needed for this form.
    $version = 7;
    $this->migrations = [
      'opigno_users' => 'Opigno: Users',
      'opigno_taxonomy_vocabulary' => 'Opigno: Taxonomy vocabulary',
      'opigno_taxonomy_term' => 'Opigno: Taxonomy term',
      'opigno_forum_migration_forum' => 'Opigno Forum: Topics',
      'opigno_forum_migration_forum_comment' => 'Opigno Forum: Topics comments',
      'opigno_files' => 'Opigno: Migrate public files',
      'opigno_certificate' => 'Opigno: Certificates',
      'opigno_learning_path_course' => 'Opigno: Convert courses to learning paths',
      'opigno_learning_path_class' => 'Opigno: Convert classes to classes',
      'opigno_module_lesson' => 'Opigno: Convert lessons to modules',
      'opigno_activity_scorm' => 'Opigno: Migrate SCORM questions',
      'opigno_activity_tincan' => 'Opigno: Migrate TinCan questions',
      'opigno_activity_long_answer' => 'Opigno: Migrate Long Answer questions',
      'opigno_activity_file_upload' => 'Opigno: Migrate File Upload questions',
      'opigno_activity_slide' => 'Opigno: Migrate Slide activity',
      'opigno_events' => 'Opigno: Calendar Events',
      'opigno_pm_message' => 'Opigno: Private Messages',
      'opigno_pm_thread_delete_time' => 'Opigno: Private Messages Thread Delete Time',
      'opigno_pm_thread_access_time' => 'Opigno: Private Messages Thread Access Time',
      'opigno_pm_thread' => 'Opigno: Private Messages Thread',
      'opigno_activity_h5p' => 'Opigno: Migrate H5P questions',
    ];
    // Fetch the system data at the first opportunity.
    $system_data = $this->store->get('system_data');

    // If data is missing or this is the wrong step, start over.
    if (!$version || !$this->migrations || !$system_data) {
      return $this->restartUpgradeForm();
    }

    $form = parent::buildForm($form, $form_state);
    $form['#title'] = $this->t('What will be upgraded?');

    // Available migrations.
    $available_module_list = [
      '#type' => 'table',
      '#header' => [
        $this->t('Drupal 8'),
      ],
    ];

    foreach ($this->migrations as $key => $migration) {
      $available_module_list[$key]['drupal8'] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $migration,
        '#attributes' => [
          'class' => [
            'upgrade-analysis-report__status-icon',
            'upgrade-analysis-report__status-icon--checked',
          ],
        ],
      ];
    }

    $form['status_report_page'] = [
      '#theme' => 'status_report_page',
      '#general_info' => $available_module_list,
    ];

    $form['#attached']['library'][] = 'migrate_drupal_ui/base';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config['source_base_path'] = $this->store->get('source_base_path');
    $_SESSION['source_base_path'] = $this->store->get('source_base_path');
    $batch = [
      'title' => $this->t('Running upgrade'),
      'progress_message' => '',
      'operations' => [
        [
          [MigrateUpgradeImportBatch::class, 'run'],
          [array_keys($this->migrations), $config],
        ],
      ],
      'finished' => [
        MigrateUpgradeImportBatch::class, 'finished',
      ],
    ];
    batch_set($batch);
    $form_state->setRedirect('<front>');
    $this->store->set('step', 'overview');
    $this->state->set('migrate_drupal_ui.performed', REQUEST_TIME);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Perform upgrade to Opigno 2');
  }

}
