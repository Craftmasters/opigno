<?php

namespace Drupal\opigno_moxtra\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\opigno_moxtra\MeetingInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Defines the Workspace entity.
 *
 * @ContentEntityType(
 *   id = "opigno_moxtra_meeting",
 *   label = @Translation("Live Meeting"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\opigno_moxtra\MeetingListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\opigno_moxtra\Form\MeetingForm",
 *       "edit" = "Drupal\opigno_moxtra\Form\MeetingForm",
 *       "delete" = "Drupal\opigno_moxtra\Form\MeetingDeleteForm",
 *     },
 *     "access" = "Drupal\opigno_moxtra\MeetingAccessControlHandler",
 *   },
 *   base_table = "opigno_moxtra_meeting",
 *   admin_permission = "administer meeting entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *   },
 *   links = {
 *     "canonical" = "/moxtra/meeting/{opigno_moxtra_meeting}",
 *     "edit-form" = "/moxtra/meeting/{opigno_moxtra_meeting}/edit",
 *     "delete-form" = "/moxtra/meeting/{opigno_moxtra_meeting}/delete",
 *     "collection" = "/admin/content/moxtra/meeting"
 *   },
 *   field_ui_base_route = "opigno_moxtra.live_meeting.settings",
 * )
 */
class Meeting extends ContentEntityBase implements MeetingInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);

    $uid = \Drupal::currentUser()->id();
    $values += [
      'user_id' => $uid,
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
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
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
  public function getSessionKey() {
    return $this->get('session_key')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSessionKey($key) {
    $this->set('session_key', $key);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStartDate() {
    return $this->get('date')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndDate() {
    return $this->get('date')->end_value;
  }

  /**
   * {@inheritdoc}
   */
  public function getDate() {
    return $this->get('date')->getValue()[0];
  }

  /**
   * {@inheritdoc}
   */
  public function setDate($date) {
    $this->set('date', $date);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTrainingId() {
    return $this->get('training')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setTrainingId($id) {
    $this->set('training', ['target_id' => $id]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTraining() {
    return $this->get('training')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setTraining($training) {
    $this->set('training', ['target_id' => $training->id()]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCalendarEventId() {
    return $this->get('calendar_event')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setCalendarEventId($id) {
    $this->set('calendar_event', ['target_id' => $id]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCalendarEvent() {
    return $this->get('calendar_event')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setCalendarEvent($event) {
    $this->set('calendar_event', ['target_id' => $event->id()]);
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
  public function setMembersIds($ids) {
    $this->set('members', $ids);
    return $this;
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
  public function addNotifiedMember($uid) {
    $this->get('notified_members')->appendItem(['target_id' => $uid]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeNotifiedMember($uid) {
    $values = $this->get('notified_members')->getValue();
    $values = array_filter($values, function ($value) use ($uid) {
      return $value['target_id'] != $uid;
    });
    $this->set('notified_members', $values);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNotifiedMembersIds() {
    $values = $this->get('notified_members')->getValue();
    return array_map(function ($value) {
      return $value['target_id'];
    }, $values);
  }

  /**
   * {@inheritdoc}
   */
  public function setNotifiedMembersIds($ids) {
    $this->set('notified_members', $ids);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNotifiedMembers() {
    $ids = $this->getNotifiedMembersIds();
    return User::loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function isMember($user_id) {
    $members_ids = $this->getMembersIds();
    if (empty($members_ids)) {
      $training = $this->getTraining();
      if (isset($training)) {
        $members_ids = array_map(function ($member) {
          /** @var \Drupal\group\GroupMembership $member */
          return $member->getUser()->id();
        }, $training->getMembers());
      }
    }

    if (!in_array($user_id, $members_ids)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Meeting entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Meeting entity.'))
      ->setReadOnly(TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owner'))
      ->setDescription(t('The owner of the Meeting entity.'))
      ->setSettings([
        'target_type' => 'user',
        'handler' => 'default',
      ])
      ->setReadOnly(TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the Meeting entity.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['binder_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Binder ID'))
      ->setDescription(t('The ID of the Moxtra binder of the Meeting entity.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ]);

    $fields['session_key'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Session key'))
      ->setDescription(t('The session key of the Moxtra binder of the Meeting entity.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ]);

    $fields['date'] = BaseFieldDefinition::create('daterange')
      ->setName('date')
      ->setLabel(t('Date'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['training'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Related training'))
      ->setDescription(t('The related training of the Meeting entity.'))
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
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['calendar_event'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Related calendar event'))
      ->setDescription(t('The related calendar event of the Meeting entity.'))
      ->setSettings([
        'target_type' => 'opigno_calendar_event',
        'handler' => 'default',
      ]);

    $fields['members'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Members'))
      ->setDescription(t('The members of the Meeting entity.'))
      ->setSettings([
        'target_type' => 'user',
        'handler' => 'default',
      ])
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    $fields['notified_members'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Notified Members'))
      ->setDescription(t('Members of the Meeting entity that received notification by email.'))
      ->setSettings([
        'target_type' => 'user',
        'handler' => 'default',
      ])
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    return $fields;
  }

}
