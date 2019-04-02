<?php

namespace Drupal\opigno_module\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Answer entity.
 *
 * @ingroup opigno_module
 *
 * @ContentEntityType(
 *   id = "opigno_answer",
 *   label = @Translation("Answer"),
 *   bundle_label = @Translation("Answer type"),
 *   handlers = {
 *     "storage" = "Drupal\opigno_module\OpignoAnswerStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\opigno_module\OpignoAnswerListBuilder",
 *     "views_data" = "Drupal\opigno_module\Entity\OpignoAnswerViewsData",
 *     "translation" = "Drupal\opigno_module\OpignoAnswerTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\opigno_module\Form\OpignoAnswerForm",
 *       "add" = "Drupal\opigno_module\Form\OpignoAnswerForm",
 *       "edit" = "Drupal\opigno_module\Form\OpignoAnswerForm",
 *       "delete" = "Drupal\opigno_module\Form\OpignoAnswerDeleteForm",
 *     },
 *     "access" = "Drupal\opigno_module\OpignoAnswerAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\opigno_module\OpignoAnswerHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "opigno_answer",
 *   data_table = "opigno_answer_field_data",
 *   revision_table = "opigno_answer_revision",
 *   revision_data_table = "opigno_answer_field_revision",
 *   translatable = TRUE,
 *   admin_permission = "administer answer entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "bundle" = "type",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_user",
 *     "revision_created" = "revision_created",
 *     "revision_log_message" = "revision_log_message",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/opigno_answer/{opigno_answer}",
 *     "add-page" = "/admin/structure/opigno_answer/add",
 *     "add-form" = "/admin/structure/opigno_answer/add/{opigno_answer_type}",
 *     "edit-form" = "/admin/structure/opigno_answer/{opigno_answer}/edit",
 *     "delete-form" = "/admin/structure/opigno_answer/{opigno_answer}/delete",
 *     "collection" = "/admin/structure/opigno_answer",
 *     "version-history" = "/admin/structure/opigno_answer/{opigno_answer}/revisions",
 *     "revision" = "/admin/structure/opigno_answer/{opigno_answer}/revisions/{opigno_answer_revision}/view",
 *   },
 *   bundle_entity_type = "opigno_answer_type",
 *   field_ui_base_route = "entity.opigno_answer_type.edit_form"
 * )
 */
class OpignoAnswer extends RevisionableContentEntityBase implements OpignoAnswerInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getActivity() {
    return $this->get('activity')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getModule() {
    return $this->get('module')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setUserModuleStatus(UserModuleStatusInterface $user_attempt) {
    $this->set('user_module_status', $user_attempt);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isEvaluated() {
    return (bool) $this->get('evaluated')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setEvaluated($value) {
    $this->set('evaluated', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setScore($value) {
    $this->set('score', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getScore() {
    return (int) $this->get('score')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }

    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public function preSaveRevision(EntityStorageInterface $storage, \stdClass $record) {
    parent::preSaveRevision($storage, $record);

    $revision_log_field_name = static::getRevisionMetadataKey($this->getEntityType(), 'revision_log_message');
    $revision_created_field_name = static::getRevisionMetadataKey($this->getEntityType(), 'revision_created');

    $new_revision = $this->isNewRevision();

    if (!$new_revision && isset($this->original)
      && (!isset($record->$revision_log_field_name)
        || $record->$revision_log_field_name === '')) {
      $record->$revision_log_field_name = $this->original->getRevisionLogMessage();
    }

    if ($new_revision
      && (!isset($record->$revision_created_field_name)
        || empty($record->$revision_created_field_name))) {
      $record->$revision_created_field_name = $record->id == $this->id()
        ? $this->getCreatedTime()
        : \Drupal::time()->getRequestTime();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Answer entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default');

    $fields['activity'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Activity'))
      ->setSetting('target_type', 'opigno_activity')
      ->setDisplayConfigurable('view', TRUE)
      ->setReadOnly(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ]);

    $fields['module'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Opigno module'))
      ->setSetting('target_type', 'opigno_module')
      ->setDisplayConfigurable('view', TRUE)
      ->setReadOnly(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ]);

    $fields['evaluated'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Evaluation status'))
      ->setDescription(t('A boolean indicating whether the answer is evaluated.'))
      ->setDefaultValue(FALSE);

    $fields['user_module_status'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User Module status'))
      ->setSetting('target_type', 'user_module_status');

    $fields['score'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Score'))
      ->setDescription(t('The score the user obtained for this answer'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
