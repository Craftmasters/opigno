<?php

namespace Drupal\opigno_notification\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\opigno_notification\OpignoNotificationInterface;

/**
 * Defines the opigno_notification entity.
 *
 * @ingroup opigno_notification
 *
 * @ContentEntityType(
 *   id = "opigno_notification",
 *   label = @Translation("Opigno Notification"),
 *   base_table = "opigno_notification",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\opigno_notification\Entity\Controller\OpignoNotificationListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\opigno_notification\OpignoNotificationAccessControlHandler",
 *   },
 * )
 */
class OpignoNotification extends ContentEntityBase implements OpignoNotificationInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the OpignoNotification entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the OpignoNotification entity.'))
      ->setReadOnly(TRUE);

    $fields['created'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Creation time'))
      ->setDescription(t('The creation time of the notification.'))
      ->setReadOnly(TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setDescription(t('The user ID of the notification receiver.'))
      ->setRequired(TRUE)
      ->setSettings([
        'default_value' => 0,
        'target_type' => 'user',
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => 0,
      ]);

    $fields['message'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Message'))
      ->setDescription(t('The message of the notification.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 1,
      ]);

    $fields['has_read'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Has Read'))
      ->setDescription(t('The status of the notification.'))
      ->setRequired(TRUE)
      ->setSettings([
        'default_value' => 0,
      ]);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);

    $values += [
      'created' => \Drupal::time()->getRequestTime(),
      'has_read' => FALSE,
    ];
  }

  /**
   * Returns unread notifications count.
   *
   * @param \Drupal\user\Entity\User|null $account
   *   User for which notifications will be counted.
   *   Current user if not specified.
   *
   * @return int
   *   Unread notifications count.
   */
  public static function unreadCount($account = NULL) {
    if ($account === NULL) {
      $account = \Drupal::currentUser();
    }

    $query = \Drupal::entityQuery('opigno_notification');
    $query->condition('uid', $account->id());
    $query->condition('has_read', FALSE);$query->count();
    $query->count();
    $result = $query->execute();

    return (int) $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    $value = $this->get('created')->getValue();

    if (!isset($value)) {
      return NULL;
    }

    return $value[0]['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function getUser() {
    $value = $this->get('uid')->getValue();

    if (!isset($value)) {
      return NULL;
    }

    return $value[0]['target_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function setUser($value) {
    $this->set('uid', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage() {
    $value = $this->get('message')->getValue();

    if (!isset($value)) {
      return NULL;
    }

    return $value[0]['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function setMessage($value) {
    $this->set('message', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHasRead() {
    $value = $this->get('has_read')->getValue();

    if (!isset($value)) {
      return NULL;
    }

    return $value[0]['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function setHasRead($value) {
    $this->set('has_read', $value);
    return $this;
  }

}
