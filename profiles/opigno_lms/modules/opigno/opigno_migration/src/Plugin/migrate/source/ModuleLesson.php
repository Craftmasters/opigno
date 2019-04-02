<?php

namespace Drupal\opigno_migration\Plugin\migrate\source;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\group\Entity\Group;
use Drupal\Core\Database\Database;
use Drupal\opigno_group_manager\Entity\OpignoGroupManagedContent;
use Drupal\Component\Serialization\Json;

/**
 * Drupal 7 node source from database.
 *
 * @MigrateSource(
 *   id = "opigno_module_lesson",
 *   source_module = "node"
 * )
 */
class ModuleLesson extends FieldableEntity {
  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state, $entity_manager);
    $this->moduleHandler = $module_handler;
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
      ->fields('np', [
        'pass_rate',
      ]);
    $query->addField('n', 'uid', 'node_uid');
    $query->addField('nr', 'uid', 'revision_uid');
    $query->innerJoin('node', 'n', static::JOIN);
    $query->innerJoin('quiz_node_properties', 'np', 'np.vid = nr.vid');

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

    // Migrate relationship from modules to groups.
    if (!entity_load('opigno_module', $nid)) {
      // Create entity 'opigno_module'.
      entity_create('opigno_module', [
        'id' => $nid,
        'vid' => $nid,
        'name' => $row->getSourceProperty('title'),
        'module_media_image' => NULL ,
        'description__value' => NULL ,
        'description__format' => NULL ,
        'status' => 1,

      ])->save();

      // Get the params.
      $entityId = $nid;
      $contentType = 'ContentTypeModule';

      $connection = Database::getConnection('default', 'legacy');
      $groups_ids = $connection->select('og_membership', 'om')
        ->fields('om', ['gid'])
        ->condition('entity_type', 'node')
        ->condition('field_name', 'og_group_ref')
        ->condition('etid', $nid)
        ->execute()
        ->fetchAll();

      foreach ($groups_ids as $gid) {
        $group = Group::load($gid->gid);

        // Get queue of module.
        $connection = Database::getConnection('default', 'legacy');
        $modules_ids = $connection->select('opigno_quiz_app_quiz_sort', 'ms')
          ->fields('ms', ['quiz_nid'])
          ->condition('gid', $gid->gid)
          ->orderBy('weight', 'ASC')
          ->execute()
          ->fetchAll();
        $modules_in_group = Json::decode(Json::encode($modules_ids));
        $module_position = array_search($nid, array_column($modules_in_group, 'quiz_nid'));

        $mandatory_lessons = $connection->select('field_data_course_required_quiz_ref', 'rq')
          ->fields('rq', ['course_required_quiz_ref_target_id'])
          ->condition('entity_id', $gid->gid)
          ->execute()
          ->fetchCol();

        $is_mandatory = 0;
        if (!empty($mandatory_lessons) && in_array($nid, $mandatory_lessons)) {
          $is_mandatory = 1;
        }

        $success_score_min = $row->getSourceProperty('pass_rate');
        $success_score_min = !empty($success_score_min) ? $success_score_min : 0;

        // Create the added item as an LP content.
        $coordinate_x = 1;
        $coordinate_y = $module_position + 1;
        $new_content = OpignoGroupManagedContent::createWithValues(
          $gid->gid,
          $contentType,
          $entityId,
          $success_score_min,
          $is_mandatory,
          $coordinate_x,
          $coordinate_y
        );
        $new_content->save();

        // Load Course (Group) entity and save as content using specific plugin.
        $added_entity = \Drupal::entityTypeManager()
          ->getStorage('opigno_module')
          ->load($nid);
        $group->addContent($added_entity, 'opigno_module_group');
      }
    }

    return parent::prepareRow($row);
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
    ];
    return $fields;
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
