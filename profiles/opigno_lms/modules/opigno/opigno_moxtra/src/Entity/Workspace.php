<?php

namespace Drupal\opigno_moxtra\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\opigno_moxtra\WorkspaceInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Defines the Workspace entity.
 *
 * @ContentEntityType(
 *   id = "opigno_moxtra_workspace",
 *   label = @Translation("Collaborative Workspace"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\opigno_moxtra\WorkspaceListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\opigno_moxtra\Form\WorkspaceForm",
 *       "edit" = "Drupal\opigno_moxtra\Form\WorkspaceForm",
 *       "delete" = "Drupal\opigno_moxtra\Form\WorkspaceDeleteForm",
 *     },
 *     "access" = "Drupal\opigno_moxtra\WorkspaceAccessControlHandler",
 *   },
 *   base_table = "opigno_moxtra_workspace",
 *   admin_permission = "administer workspace entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *   },
 *   links = {
 *     "canonical" = "/moxtra/workspace/{opigno_moxtra_workspace}",
 *     "edit-form" = "/moxtra/workspace/{opigno_moxtra_workspace}/edit",
 *     "delete-form" = "/moxtra/workspace/{opigno_moxtra_workspace}/delete",
 *     "collection" = "/admin/content/moxtra/workspace"
 *   },
 * )
 */
class Workspace extends ContentEntityBase implements WorkspaceInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);

    $uid = \Drupal::currentUser()->id();
    $values += [
      'user_id' => $uid,
      'members' => [
        ['target_id' => $uid],
      ],
    ];
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
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
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
  public function getBinderId() {
    return $this->get('binder_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setBinderId($id) {
    $this->set('binder_id', $id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAutoRegister() {
    return $this->get('auto_register')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setAutoRegister($boolean) {
    $this->set('auto_register', $boolean);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addMember($uid) {
    $this->get('members')->appendItem(['target_id' => $uid]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeMember($uid) {
    $values = $this->get('members')->getValue();
    $values = array_filter($values, function ($value) use ($uid) {
      return $value['target_id'] != $uid;
    });
    $this->set('members', $values);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMembersIds() {
    $values = $this->get('members')->getValue();
    return array_map(function ($value) {
      return $value['target_id'];
    }, $values);
  }

  /**
   * {@inheritdoc}
   */
  public function getMembers() {
    $ids = $this->getMembersIds();
    return User::loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function setMembers($uids) {
    $this->set('members', $uids);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Workspace entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Workspace entity.'))
      ->setReadOnly(TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owner'))
      ->setDescription(t('The owner of the Workspace entity.'))
      ->setSettings([
        'handler' => 'default',
        'target_type' => 'user',
      ])
      ->setReadOnly(TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Workspace entity.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setRequired(TRUE);

    $fields['binder_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Binder ID'))
      ->setDescription(t('The ID of the Moxtra binder of the Workspace entity.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ]);

    $fields['auto_register'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Automatically register'))
      ->setDescription(t('Automatically register all users of that workspace.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDefaultValue(TRUE);

    $fields['members'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Members'))
      ->setDescription(t('The members of the Workspace entity.'))
      ->setSettings([
        'handler' => 'default',
        'target_type' => 'user',
      ])
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    $fields['training'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Training'))
      ->setDescription(t('The related Training of the Workspace entity.'))
      ->setSettings([
        'target_type' => 'group',
        'handler' => 'default:group',
        'handler_settings' => [
          'target_bundles' => [
            'learning_path' => 'learning_path',
          ],
          'sort' => [
            'field' => '_none',
          ],
        ],
        'default_value' => 0,
      ])
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    return $fields;
  }

}
