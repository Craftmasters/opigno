<?php

namespace Drupal\opigno_ilt\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\opigno_ilt\ILTInterface;
use Drupal\opigno_ilt\ILTResultInterface;
use Drupal\user\UserInterface;

/**
 * Defines the ILT Result entity.
 *
 * @ContentEntityType(
 *   id = "opigno_ilt_result",
 *   label = @Translation("Instructor-Led Training Result"),
 *   handlers = {
 *     "view_builder" = "Drupal\opigno_ilt\ILTResultViewBuilder",
 *     "list_builder" = "Drupal\opigno_ilt\ILTResultListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\opigno_ilt\Form\ILTResultForm",
 *       "edit" = "Drupal\opigno_ilt\Form\ILTResultForm",
 *       "delete" = "Drupal\opigno_ilt\Form\ILTResultDeleteForm",
 *     },
 *     "access" = "Drupal\opigno_ilt\ILTResultAccessControlHandler",
 *   },
 *   base_table = "opigno_ilt_result",
 *   admin_permission = "administer ilt result entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/ilt-result/{opigno_ilt_result}",
 *     "edit-form" = "/ilt-result/{opigno_ilt_result}/edit",
 *     "delete-form" = "/ilt-result/{opigno_ilt_result}/delete",
 *     "collection" = "/admin/content/ilt-result"
 *   },
 * )
 */
class ILTResult extends ContentEntityBase implements ILTResultInterface {

  /**
   * {@inheritdoc}
   */
  public function getILT() {
    return $this->get('opigno_ilt')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setILT(ILTInterface $opigno_ilt) {
    $this->set('opigno_ilt', $opigno_ilt->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getILTId() {
    return $this->get('opigno_ilt')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setILTId($id) {
    $this->set('opigno_ilt', $id);
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
      ->setDescription(t('The ID of the ILT Result entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the ILT Result entity.'))
      ->setReadOnly(TRUE);

    $fields['opigno_ilt'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('ILT'))
      ->setDescription(t('The ILT of the ILT Result entity.'))
      ->setSettings([
        'handler' => 'default',
        'target_type' => 'opigno_ilt',
      ])
      ->setRequired(TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setDescription(t('The User of the ILT Result entity.'))
      ->setSettings([
        'handler' => 'default',
        'target_type' => 'user',
      ])
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDescription(t('The Status of the ILT Result entity.'));

    $fields['score'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Score'))
      ->setDescription(t('The Score of the ILT Result entity.'));

    return $fields;
  }

}
