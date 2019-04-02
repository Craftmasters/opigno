<?php

namespace Drupal\opigno_moxtra\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\opigno_moxtra\MeetingInterface;
use Drupal\opigno_moxtra\MeetingResultInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Workspace entity.
 *
 * @ContentEntityType(
 *   id = "opigno_moxtra_meeting_result",
 *   label = @Translation("Live Meeting Result"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\opigno_moxtra\MeetingResultListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\opigno_moxtra\Form\MeetingResultForm",
 *       "edit" = "Drupal\opigno_moxtra\Form\MeetingResultForm",
 *       "delete" = "Drupal\opigno_moxtra\Form\MeetingResultDeleteForm",
 *     },
 *     "access" = "Drupal\opigno_moxtra\MeetingResultAccessControlHandler",
 *   },
 *   base_table = "opigno_moxtra_meeting_result",
 *   admin_permission = "administer meeting result entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/moxtra/meeting/{opigno_moxtra_meeting_result}",
 *     "edit-form" = "/moxtra/meeting/{opigno_moxtra_meeting_result}/edit",
 *     "delete-form" = "/moxtra/meeting/{opigno_moxtra_meeting_result}/delete",
 *     "collection" = "/admin/content/moxtra/meeting_result"
 *   },
 * )
 */
class MeetingResult extends ContentEntityBase implements MeetingResultInterface {

  /**
   * {@inheritdoc}
   */
  public function getMeeting() {
    return $this->get('meeting')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setMeeting(MeetingInterface $meeting) {
    $this->set('meeting', $meeting->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMeetingId() {
    return $this->get('meeting')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setMeetingId($id) {
    $this->set('meeting', $id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUser() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setUser(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setUserId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatusString() {
    return $this->get('status')->value == 1
      ? t('Attended')
      : t('Absent');
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($value) {
    $this->set('status', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getScore() {
    return $this->get('score')->value;
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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Meeting Result entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Meeting Result entity.'))
      ->setReadOnly(TRUE);

    $fields['meeting'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Meeting'))
      ->setDescription(t('The Live Meeting of the Meeting Result entity.'))
      ->setSettings([
        'handler' => 'default',
        'target_type' => 'opigno_moxtra_meeting',
      ])
      ->setRequired(TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setDescription(t('The User of the Meeting Result entity.'))
      ->setSettings([
        'handler' => 'default',
        'target_type' => 'user',
      ])
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDescription(t('The Status of the Meeting Result entity.'));

    $fields['score'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Score'))
      ->setDescription(t('The Score of the Meeting Result entity.'));

    return $fields;
  }

}
