<?php

namespace Drupal\opigno_module\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Activity entity.
 *
 * @ingroup opigno_module
 *
 * @ContentEntityType(
 *   id = "opigno_activity",
 *   label = @Translation("Activity"),
 *   bundle_label = @Translation("Activity type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\opigno_module\OpignoActivityListBuilder",
 *     "views_data" = "Drupal\opigno_module\Entity\OpignoActivityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\opigno_module\Form\OpignoActivityForm",
 *       "add" = "Drupal\opigno_module\Form\OpignoActivityForm",
 *       "edit" = "Drupal\opigno_module\Form\OpignoActivityForm",
 *       "delete" = "Drupal\opigno_module\Form\OpignoActivityDeleteForm",
 *     },
 *     "access" = "Drupal\opigno_module\OpignoActivityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\opigno_module\OpignoActivityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "opigno_activity",
 *   data_table = "opigno_activity_field_data",
 *   revision_table = "opigno_activity_revision",
 *   revision_data_table = "opigno_activity_field_revision",
 *   translatable = TRUE,
 *   show_revision_ui = TRUE,
 *   admin_permission = "administer activity entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "uid",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/activity/{opigno_activity}",
 *     "add-page" = "/admin/structure/opigno_activity/add",
 *     "add-form" = "/admin/structure/opigno_activity/add/{opigno_activity_type}",
 *     "edit-form" = "/admin/structure/opigno_activity/{opigno_activity}/edit",
 *     "delete-form" = "/admin/structure/opigno_activity/{opigno_activity}/delete",
 *     "collection" = "/admin/structure/opigno_activity",
 *   },
 *   bundle_entity_type = "opigno_activity_type",
 *   field_ui_base_route = "entity.opigno_activity_type.edit_form"
 * )
 */
class OpignoActivity extends RevisionableContentEntityBase implements OpignoActivityInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'uid' => \Drupal::currentUser()->id(),
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
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
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
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * Returns module.
   */
  public function getModule() {

  }

  /**
   * Returns user answer.
   */
  public function getUserAnswer(OpignoModuleInterface $opigno_module, UserModuleStatusInterface $attempt, AccountInterface $account) {
    $answer_storage = static::entityTypeManager()->getStorage('opigno_answer');
    $query = $answer_storage->getQuery();
    $aid = $query->condition('user_id', $account->id())
      ->condition('user_module_status', $attempt->id())
      ->condition('module', $opigno_module->id())
      ->condition('activity', $this->id())
      ->execute();
    $id = reset($aid);
    return ($id) ? $answer_storage->load($id) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAnswers($return_entities_array = NULL) {
    $answer_storage = static::entityTypeManager()->getStorage('opigno_answer');
    $query = $answer_storage->getQuery();
    $aids = $query->condition('activity', $this->id())->execute();
    if ($return_entities_array) {
      return $answer_storage->loadMultiple($aids);
    }
    return $aids;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Activity entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Activity entity.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Activity is published.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the Module was created.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the Module was last edited.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    return $fields;
  }

}
