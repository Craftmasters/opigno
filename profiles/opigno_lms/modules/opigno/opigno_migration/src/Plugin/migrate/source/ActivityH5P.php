<?php

namespace Drupal\opigno_migration\Plugin\migrate\source;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\h5p\H5PDrupal\H5PDrupal;
use Drupal\h5peditor\H5PEditor\H5PEditorUtilities;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;
use Drupal\opigno_migration\H5PMigrationClasses\H5PEditorAjaxMigrate;
use Drupal\opigno_migration\H5PMigrationClasses\H5PStorageMigrate;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Drupal 7 h5p content source from database.
 *
 * @MigrateSource(
 *   id = "opigno_activity_h5p",
 *   source_module = "node"
 * )
 */
class ActivityH5P extends FieldableEntity {
  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  protected $librariesMap;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state, $entity_manager);
    $this->moduleHandler = $module_handler;

    $librariesMap = [];
    // Get old libraries machine names.
    $query = $this->select('h5p_libraries', 'l')
      ->fields('l', ['library_id', 'machine_name']);
    $old_libs = $query->execute()->fetchAllKeyed(0, 1);

    if ($old_libs) {
      $db_connection = \Drupal::service('database');
      foreach ($old_libs as $library_id => $machine_name) {
        // Get new library id with highest version.
        $query = $db_connection->select('h5p_libraries', 'l')
          ->fields('l', [
            'library_id',
            'machine_name',
            'major_version',
            'minor_version',
          ])
          ->orderBy('major_version', 'DESC')
          ->orderBy('minor_version', 'DESC')
          ->condition('machine_name', $machine_name);
        $result = $query->execute()->fetchAllAssoc('library_id');
        if ($result) {
          $new_lib = reset($result);
          // Set mapping: old id -> new id.
          $librariesMap[$library_id] = $new_lib->library_id;
        }
        else {
          // Upload new library.
          \Drupal::logger('opigno_groups_migration')->notice('Uploading ' . $machine_name);

          $editor = H5PEditorUtilities::getInstance();
          $h5pEditorAjax = new H5PEditorAjaxMigrate($editor->ajax->core, $editor, $editor->ajax->storage);
          $url = 'https://api.h5p.org/v1/content-types/' . $machine_name;
          @set_time_limit(0);
          try {
            $client = \Drupal::httpClient();
            $response = $client->request('POST', $url);
            $response_data = (string) $response->getBody();
          }
          catch (\Exception $e) {
            \Drupal::logger('opigno_groups_migration')->error($e->getMessage());
          }

          if (!empty($response_data) && empty($response->error)) {
            $interface = H5PDrupal::getInstance();
            $h5p_path = $interface->getOption('default_path', 'h5p');
            $temp_id = uniqid('h5p-');
            $temporary_file_path = "public://{$h5p_path}/temp/{$temp_id}";
            file_prepare_directory($temporary_file_path, FILE_CREATE_DIRECTORY);
            $name = $temp_id . '.h5p';
            $target = $temporary_file_path . DIRECTORY_SEPARATOR . $name;

            $file = file_unmanaged_save_data($response_data, $target);
            if ($file) {
              $file_service = \Drupal::service('file_system');
              $dir = $file_service->realpath($temporary_file_path);
              $interface->getUploadedH5pFolderPath($dir);
              $interface->getUploadedH5pPath("{$dir}/{$name}");

              if ($h5pEditorAjax->isValidPackage(TRUE)) {
                // Add new libraries from file package.
                $storage = new H5PStorageMigrate($h5pEditorAjax->core->h5pF, $h5pEditorAjax->core);

                // Serialize metadata array in libraries.
                if (!empty($storage->h5pC->librariesJsonData)) {
                  foreach ($storage->h5pC->librariesJsonData as &$library) {
                    if (array_key_exists('metadataSettings', $library) && is_array($library['metadataSettings'])) {
                      $metadataSettings = serialize($library['metadataSettings']);
                      $library['metadataSettings'] = $metadataSettings;
                    }
                  }
                }

                $storage->saveLibraries();
                // Clean up.
                $h5pEditorAjax->storage->removeTemporarilySavedFiles($h5pEditorAjax->core->h5pF->getUploadedH5pFolderPath());
              }
            }
          }

          // Get new library id with highest version.
          $query = $db_connection->select('h5p_libraries', 'l')
            ->fields('l', [
              'library_id',
              'machine_name',
              'major_version',
              'minor_version',
            ])
            ->orderBy('major_version', 'DESC')
            ->orderBy('minor_version', 'DESC')
            ->condition('machine_name', $machine_name);
          $result = $query->execute()->fetchAllAssoc('library_id');
          if ($result) {
            $new_lib = reset($result);
            // Set mapping: old id -> new id.
            $librariesMap[$library_id] = $new_lib->library_id;
          }
        }
      }
    }

    $this->librariesMap = $librariesMap;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('state'),
      $container->get('entity.manager'),
      $container->get('module_handler')
    );
  }

  /**
   * The join options between the node and the node_revisions table.
   */
  const JOIN = 'n.vid = nr.vid';

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Select node in its last revision.
    $query = $this->select('node_revision', 'nr')
      ->fields('n', [
        'nid',
        'type',
        'language',
        'status',
        'created',
        'changed',
        'comment',
        'promote',
        'sticky',
        'tnid',
        'translate',
      ])
      ->fields('nr', [
        'vid',
        'title',
        'log',
        'timestamp',
      ])
      ->fields('h', [
        'content_id',
        'json_content',
        'filtered',
        'main_library_id',
        'slug',
      ]);
    $query->addField('n', 'uid', 'node_uid');
    $query->addField('nr', 'uid', 'revision_uid');
    $query->innerJoin('node', 'n', static::JOIN);
    $query->innerJoin('h5p_nodes', 'h', 'h.content_id = nr.vid');

    // If the content_translation module is enabled, get the source langcode
    // to fill the content_translation_source field.
    if ($this->moduleHandler->moduleExists('content_translation')) {
      $query->leftJoin('node', 'nt', 'n.tnid = nt.nid');
      $query->addField('nt', 'language', 'source_langcode');
    }
    $this->handleTranslations($query);

    if (isset($this->configuration['node_type'])) {
      $query->condition('n.type', $this->configuration['node_type']);
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'nid' => $this->t('Node ID'),
      'type' => $this->t('Type'),
      'title' => $this->t('Title'),
      'node_uid' => $this->t('Node authored by (uid)'),
      'revision_uid' => $this->t('Revision authored by (uid)'),
      'created' => $this->t('Created timestamp'),
      'changed' => $this->t('Modified timestamp'),
      'status' => $this->t('Published'),
      'promote' => $this->t('Promoted to front page'),
      'sticky' => $this->t('Sticky at top of lists'),
      'revision' => $this->t('Create new revision'),
      'language' => $this->t('Language (fr, en, ...)'),
      'tnid' => $this->t('The translation set id for this node'),
      'timestamp' => $this->t('The timestamp the latest revision of this node was created.'),
      'content_id' => $this->t('Primary Key: The unique identifier for this node(vid by default).'),
      'json_content' => $this->t('The content in json format.'),
      'filtered' => $this->t('Filtered version of json_content.'),
      'slug' => $this->t('Human readable content identifier that is unique.'),
      'library_id' => $this->t('The identifier of a h5p library this content uses.'),
      'dependency_type' => $this->t('dynamic, preloaded or editor.'),
      'weight' => $this->t('Determines the order in which the preloaded libraries will be loaded.'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $nid = $row->getSourceProperty('nid');
    $vid = $row->getSourceProperty('vid');
    $type = $row->getSourceProperty('type');

    $entity_translatable = $this->isEntityTranslatable('node') && (int) $this->variableGet('language_content_type_' . $type, 0) === 4;
    $language = $entity_translatable ? $this->getEntityTranslationSourceLanguage('node', $nid) : $row->getSourceProperty('language');

    // Get Field API field values.
    foreach ($this->getFields('node', $type) as $field_name => $field) {
      // Ensure we're using the right language if the entity and the field are
      // translatable.
      $field_language = $entity_translatable && $field['translatable'] ? $language : NULL;
      $row->setSourceProperty($field_name, $this->getFieldValues('node', $field_name, $nid, $vid, $field_language));
    }

    // Make sure we always have a translation set.
    if ($row->getSourceProperty('tnid') == 0) {
      $row->setSourceProperty('tnid', $row->getSourceProperty('nid'));
    }

    // If the node title was replaced by a real field using the Drupal 7 Title
    // module, use the field value instead of the node title.
    if ($this->moduleExists('title')) {
      $title_field = $row->getSourceProperty('title_field');
      if (isset($title_field[0]['value'])) {
        $row->setSourceProperty('title', $title_field[0]['value']);
      }
    }

    // Set mapped library id.
    $library_exists = FALSE;
    $library_id = $row->getSourceProperty('main_library_id');
    $db_connection = \Drupal::service('database');
    if (!empty($this->librariesMap[$library_id])) {
      $row->setSourceProperty('library_id', $this->librariesMap[$library_id]);
      $library_exists = TRUE;
    }

    if ($library_exists) {
      // Create content libraries mapped data.
      $query = $this->select('h5p_nodes_libraries', 'l')
        ->fields('l')
        ->condition('content_id', $vid);
      $result = $query->execute()->fetchAll();
      if ($result) {
        foreach ($result as $key => $content_libraries) {
          if (!empty($this->librariesMap[$content_libraries['library_id']])) {
            // Insert content libraries if new library exists.
            $result[$key]['library_id'] = $this->librariesMap[$content_libraries['library_id']];
            try {
              $db_connection->insert('h5p_content_libraries')
                ->fields($result[$key])
                ->execute();
            }
            catch (\Exception $e) {
              \Drupal::logger('opigno_groups_migration')->error($e->getMessage());
            }
          }
        }
      }

      // Set content sub-libraries.
      $json_content = $row->getSourceProperty('json_content');
      $filtered = $row->getSourceProperty('filtered');
      $start_str = '"library":"';
      if (strpos($json_content, $start_str) !== FALSE) {
        $h5p_content = [
          'json_content' => $json_content,
          'filtered' => $filtered,
        ];

        foreach ($h5p_content as $key => $content) {
          if (!empty($content)) {
            $end_str = '",';

            $lastPos = 0;
            $positions = [];
            while (($lastPos = strpos($content, $start_str, $lastPos)) !== FALSE) {
              $positions[] = $lastPos;
              $lastPos = $lastPos + strlen($start_str);
            }

            if ($positions) {
              $old_libs = [];
              $new_libs = [];
              foreach ($positions as $start) {
                $end = strpos($content, $end_str, $start);
                $old_lib = substr($content, $start + strlen($start_str), $end - $start - strlen($start_str));
                $old_libs[] = $old_lib;

                $old_lib_array = explode(' ', $old_lib);
                $machine_name = $old_lib_array[0];

                $db_connection = \Drupal::service('database');
                // Get new library id with highest version.
                $query = $db_connection->select('h5p_libraries', 'l')
                  ->fields('l', [
                    'library_id',
                    'machine_name',
                    'major_version',
                    'minor_version',
                  ])
                  ->orderBy('major_version', 'DESC')
                  ->orderBy('minor_version', 'DESC')
                  ->condition('machine_name', $machine_name);
                $result = $query->execute()->fetchAll();
                if ($result) {
                  $new_lib_array = array_shift($result);
                  $new_lib = $machine_name . ' ' . $new_lib_array->major_version . '.' . $new_lib_array->minor_version;
                  $new_libs[] = $new_lib;
                }
              }

              $content = str_replace($old_libs, $new_libs, $content);
              $row->setSourceProperty($key, $content);
            }
          }
        }
      }

      return parent::prepareRow($row);
    }
    else {
      \Drupal::logger('opigno_groups_migration')->error('Missing library, id in source system: ' . $library_id);
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['nid']['type'] = 'integer';
    $ids['nid']['alias'] = 'n';
    return $ids;
  }

  /**
   * Adapt our query for translations.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The generated query.
   */
  protected function handleTranslations(SelectInterface $query) {
    // Check whether or not we want translations.
    if (empty($this->configuration['translations'])) {
      // No translations: Yield untranslated nodes, or default translations.
      $query->where('n.tnid = 0 OR n.tnid = n.nid');
    }
    else {
      // Translations: Yield only non-default translations.
      $query->where('n.tnid <> 0 AND n.tnid <> n.nid');
    }
  }

}
