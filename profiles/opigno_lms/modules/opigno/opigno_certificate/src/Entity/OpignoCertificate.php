<?php

namespace Drupal\opigno_certificate\Entity;

use Drupal\opigno_certificate\OpignoCertificateInterface;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Defines the opigno_certificate entity class.
 *
 * @ContentEntityType(
 *   id = "opigno_certificate",
 *   label = @Translation("Certificate"),
 *   label_collection = @Translation("Certificate"),
 *   label_singular = @Translation("certificate"),
 *   label_plural = @Translation("certificates"),
 *   label_count = @PluralTranslation(
 *     singular = "@count certificate",
 *     plural = "@count certificates"
 *   ),
 *   base_table = "opigno_certificate",
 *   data_table = "opigno_certificate_field_data",
 *   revision_table = "opigno_certificate_revision",
 *   revision_data_table = "opigno_certificate_field_revision",
 *   show_revision_ui = TRUE,
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "revision_id" = "vid",
 *     "uuid" = "uuid",
 *     "published" = "status",
 *     "uid" = "uid",
 *     "bundle" = "bundle",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log"
 *   },
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "access" = "Drupal\opigno_certificate\OpignoCertificateAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\opigno_certificate\CertificateForm",
 *       "add" = "Drupal\opigno_certificate\CertificateForm",
 *       "edit" = "Drupal\opigno_certificate\CertificateForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\opigno_certificate\CertificateRouteProvider",
 *     },
 *   },
 *   field_ui_base_route = "opigno_certificate.settings",
 *   common_reference_target = TRUE,
 *   links = {
 *     "canonical" = "/certificate/{opigno_certificate}",
 *     "delete-form" = "/certificate/{opigno_certificate}/delete",
 *     "edit-form" = "/certificate/{opigno_certificate}/edit",
 *     "revision" = "/certificate/{opigno_certificate}/revisions/{opigno_certificate_revision}/view",
 *     "add-page" = "/certificate/add",
 *     "add-form" = "/certificate/add/{bundle}",
 *   }
 * )
 */
class OpignoCertificate extends EditorialContentEntityBase implements OpignoCertificateInterface {

  /**
   * The view mode selector field.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  protected $viewModeSelectorField;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // If no revision author has been set explicitly,
    // make the opigno_certificate owner the revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSaveRevision(EntityStorageInterface $storage, \stdClass $record) {
    parent::preSaveRevision($storage, $record);

    if (!$this->isNewRevision() && isset($this->original) && (!isset($record->revision_log) || $record->revision_log === '')) {
      // If we are updating an existing opigno_certificate
      // without adding a new revision, we need
      // to make sure $entity->revision_log is reset whenever it is empty.
      // Therefore, this code allows us to avoid clobbering an existing log
      // entry with an empty one.
      $record->revision_log = $this->original->revision_log->value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->set('label', $label);
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
    return $this->getEntityKey('uid');
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
  public function getRevisionAuthor() {
    return $this->getRevisionUser();
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionAuthorId($uid) {
    $this->setRevisionUserId($uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getViewModeSelectorField() {
    if (!isset($this->viewModeSelectorField)) {
      $this->viewModeSelectorField = FALSE;
      /** @var \Drupal\Core\Field\FieldDefinitionInterface[] $fields */
      $fields = $this->getFieldDefinitions();
      foreach ($fields as $field) {
        if ($field->getType() == 'view_mode_selector') {
          $this->viewModeSelectorField = $field;
          break;
        }
      }
    }
    return $this->viewModeSelectorField ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['referencing_entity'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Referencing entity'))
      ->setComputed(TRUE)
      ->setRevisionable(FALSE)
      ->setTranslatable(FALSE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The username of the opigno_certificate author.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback('Drupal\opigno_certificate\Entity\OpignoCertificate::getCurrentUserId')
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
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['status']
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 120,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the opigno_certificate was created.'))
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
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the certificate was last edited.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    return $fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

}
