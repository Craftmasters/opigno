<?php

namespace Drupal\opigno_calendar_event\Entity;

use Drupal\opigno_calendar_event\CalendarEventInterface;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity\BundleFieldDefinition;

/**
 * Defines the "Calendar event" entity class.
 *
 * @ContentEntityType(
 *   id = \Drupal\opigno_calendar_event\CalendarEventInterface::ENTITY_TYPE_ID,
 *   label = @Translation("Calendar event"),
 *   bundle_label = @Translation("Calendar event type"),
 *   bundle_entity_type = "opigno_calendar_event_type",
 *   handlers = {
 *     "storage" = "Drupal\opigno_calendar_event\CalendarEventStorage",
 *     "storage_schema" = "Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "access" = "Drupal\entity\EntityAccessControlHandler",
 *     "permission_provider" = "Drupal\entity\EntityPermissionProvider",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\opigno_calendar_event\Form\CalendarEventForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "edit" = "Drupal\opigno_calendar_event\Form\CalendarEventForm",
 *     },
 *    "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler"
 *   },
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "bundle" = "type",
 *     "label" = "title",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *     "published" = "status",
 *     "uid" = "uid",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log"
 *   },
 *   links = {
 *     "canonical" = "/calendar/event/{opigno_calendar_event}",
 *     "add-page" = "/calendar/event/add",
 *     "add-form" = "/calendar/event/add/{opigno_calendar_event_type}",
 *     "delete-form" = "/calendar/event/{opigno_calendar_event}/delete",
 *     "edit-form" = "/calendar/event/{opigno_calendar_event}/edit",
 *     "create" = "/calendar/event",
 *   },
 *   field_ui_base_route = "entity.opigno_calendar_event_type.edit_form",
 *   base_table = "opigno_calendar_event",
 *   data_table = "opigno_calendar_event_field_data",
 *   revision_table = "opigno_calendar_event_revision",
 *   revision_data_table = "opigno_calendar_event_field_revision",
 * )
 */
class CalendarEvent extends EditorialContentEntityBase implements CalendarEventInterface {

  /**
   * Static cache for date field types.
   *
   * @var string[]
   */
  protected static $date_field_types = [];

  /**
   * The date field name.
   *
   * @var string
   */
  protected $dateFieldName;

  /**
   * {@inheritdoc}
   */
  public function getDateItems() {
    if (!isset($this->dateFieldName)) {
      $type = static::getCalendarEventType($this->bundle());
      $this->dateFieldName = static::getDateFieldName($type);
    }
    return $this->get($this->dateFieldName);
  }

  /**
   * Determines the date field name for the specified bundle.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $type
   *   The calendar event type entity.
   *
   * @return string
   *   The date field type.
   */
  public static function getDateFieldName(ConfigEntityInterface $type) {
    return 'date_' . static::getDateFieldType($type);
  }

  /**
   * Determines the date field type for the specified bundle.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $type
   *   The calendar event type entity.
   *
   * @return string
   *   The date field type.
   */
  protected static function getDateFieldType(ConfigEntityInterface $type) {
    $date_field_type = &static::$date_field_types[$type->id()];

    if (!isset($date_field_type)) {
      $date_field_type = $type->get('date_field_type');
      if (!$date_field_type) {
        $date_field_type = 'timestamp';
        \Drupal::logger('opigno_calendar_event')
          ->critical('No date type defined for bundle "@bundle".', ['@bundle' => $type->id()]);
      }
    }

    return $date_field_type;
  }

  /**
   * Returns a calendar event type entity.
   *
   * @param string $type_id
   *   The calendar event type ID.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface
   *   The calendar event entity.
   */
  protected static function getCalendarEventType($type_id) {
    try {
      /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $type */
      $type = \Drupal::entityTypeManager()
        ->getStorage('opigno_calendar_event_type')
        ->load($type_id);
    }
    catch (InvalidPluginDefinitionException $e) {
      throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
    }
    return $type;
  }

  /**
   * {@inheritdoc}
   */
  public function isDisplayed() {
    return (bool) $this->get('displayed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDisplayed($displayed) {
    $this->set('displayed', $displayed);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Title'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'text_default',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['displayed'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Show on calendar'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDefaultValue(TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Authored by'))
      ->setDescription(new TranslatableMarkup('The username of the calendar event author.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback('Drupal\opigno_calendar_event\Entity\CalendarEvent::getCurrentUserId')
      ->setTranslatable(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Authored on'))
      ->setDescription(new TranslatableMarkup('The time that the calendar event was created.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Changed'))
      ->setDescription(new TranslatableMarkup('The time that the calendar event was last edited.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    $fields[static::getRevisionMetadataKey($entity_type, 'revision_log_message')]
      ->setDisplayOptions('form', []);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    $fields = [];

    $type = static::getCalendarEventType($bundle);
    if (!$type) {
      \Drupal::logger('opigno_calendar_event')
        ->error('Invalid bundle %bundle specified when providing bundle field definitions.', ['%bundle' => $bundle]);
      return $fields;
    }

    $field_type = static::getDateFieldType($type);
    $field_name = static::getDateFieldName($type);

    $fields[$field_name] = BundleFieldDefinition::create($field_type)
      ->setName($field_name)
      ->setTargetEntityTypeId($entity_type->id())
      ->setLabel(new TranslatableMarkup('Date'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => $field_type . '_default',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => $field_type . '_default',
        'weight' => -3,
      ]);

    return $fields;
  }

  /**
   * Provides storage field definitions for this entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   *
   * @return \Drupal\Core\Field\FieldStorageDefinitionInterface[]
   *   An array of field storage definitions.
   */
  public static function storageFieldDefinitions(EntityTypeInterface $entity_type) {
    $storage_definitions = [];

    // Retrieve storage definitions for our date fields.
    $entity_type_id = $entity_type->id();
    /** @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info */
    $bundle_info = \Drupal::service('entity_type.bundle.info');
    foreach ($bundle_info->getBundleInfo($entity_type_id) as $bundle => $info) {
      $storage_definitions += static::bundleFieldDefinitions($entity_type, $bundle, []);
    }

    return $storage_definitions;
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
