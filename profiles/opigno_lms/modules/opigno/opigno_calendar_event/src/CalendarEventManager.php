<?php

namespace Drupal\opigno_calendar_event;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * The calendar event manager.
 */
class CalendarEventManager {

  use StringTranslationTrait;
  use CalendarEventExceptionLoggerTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The embedded widget.
   *
   * @var \Drupal\opigno_calendar_event\CalendarEventEmbeddedWidget
   */
  protected $embeddedWidget;

  /**
   * The embedded display.
   *
   * @var \Drupal\opigno_calendar_event\CalendarEventEmbeddedDisplay
   */
  protected $embeddedDisplay;

  /**
   * Static cache for referencing fields.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface[][]
   */
  protected $referencingFields = [];

  /**
   * Static cache for calendar event types.
   *
   * @var string[]
   */
  protected $calendarEventTypeIds;

  /**
   * CalendarEventManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * Retrieves the Calendar event manager service.
   *
   * @return static
   */
  public static function get() {
    return \Drupal::service('opigno_calendar_event.manager');
  }

  /**
   * Checks whether a form should host the widget settings.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return bool
   *   TRUE if the form should host the widget settings, FALSE otherwise.
   */
  public function isSettingsForm(FormStateInterface $form_state) {
    /** @var \Drupal\field_ui\Form\FieldConfigEditForm $form_object */
    $form_object = $form_state->getFormObject();
    /** @var \Drupal\field\FieldConfigInterface $field */
    $field = $form_object->getEntity();
    return $field->getTargetEntityTypeId() === 'opigno_calendar_event' && $field->getType() === 'entity_reference';
  }

  /**
   * Adds the widget settings to the specified form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function addEmbeddedWidgetSettings(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\field_ui\Form\FieldConfigEditForm $form_object */
    $form_object = $form_state->getFormObject();
    /** @var \Drupal\field\FieldConfigInterface $field */
    $field = $form_object->getEntity();

    $form['third_party_settings']['opigno_calendar_event']['embedded_widget'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Calendar event widget'),
      '#description' => $this->t('If this is enabled a <em>Show on calendar</em> widget will be displayed on the referenced entity form.'),
      '#default_value' => $field->getThirdPartySetting('opigno_calendar_event', 'embedded_widget'),
    ];
  }

  /**
   * Returns all calendar events referencing the specified entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   An entity object.
   *
   * @return \Drupal\opigno_calendar_event\CalendarEventInterface[]
   *   An array of calendar event entity objects.
   */
  public function getReferencingCalendarEvents(ContentEntityInterface $entity) {
    $calendar_events = [];

    $field_definition = $this->getReferencingFieldDefinition($entity->getEntityTypeId(), $entity->bundle());
    if (!$field_definition) {
      return $calendar_events;
    }

    try {
      $entity_type_id = CalendarEventInterface::ENTITY_TYPE_ID;
      $storage = $this->entityTypeManager->getStorage($entity_type_id);
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      $field_name = $field_definition->getName();
      $bundle_key = $entity_type->getKey('bundle');
      $bundle_id = $field_definition->getTargetBundle();

      // If a user has no access to a group, we assume they would also not have
      // access to the related calendar, so we keep the access check in place.
      $ids = $storage->getQuery()
        ->condition($bundle_key, $bundle_id)
        ->condition($field_name, $entity->id())
        ->execute();

      if ($ids) {
        $calendar_events = $storage->loadMultiple($ids);
      }
      else {
        $values = [
          $bundle_key => $bundle_id,
          $field_name => $entity->id(),
          'displayed' => FALSE,
        ];
        $calendar_events = [$storage->create($values)];
      }
    }
    catch (PluginException $e) {
      $this->logException($e);
    }

    return $calendar_events;
  }

  /**
   * Returns the entity reference for the specified entity type and bundle.
   *
   * @param string $entity_type_id
   *   The referenced entity type ID.
   * @param string|null $bundle_id
   *   (optional) The referenced bundle. Defaults to none.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface|null
   *   The entity reference field definition or NULL if none could be
   *   identified.
   */
  public function getReferencingFieldDefinition($entity_type_id, $bundle_id = NULL) {
    $referencing_field_definition = &$this->referencingFields[$entity_type_id][$bundle_id];

    if (!isset($referencing_field_definition)) {
      $referencing_field_definition = FALSE;

      if (!isset($this->calendarEventTypeIds)) {
        try {
          $types = $this->entityTypeManager
            ->getStorage('opigno_calendar_event_type')
            ->getQuery()
            ->execute();
        }
        catch (InvalidPluginDefinitionException $e) {
          $types = [];
          $this->logException($e);
        }
        $this->calendarEventTypeIds = array_values($types);
      }

      foreach ($this->calendarEventTypeIds as $type_id) {
        $field_definitions = $this->entityFieldManager->getFieldDefinitions(CalendarEventInterface::ENTITY_TYPE_ID, $type_id);
        foreach ($field_definitions as $field_definition) {
          if ($field_definition->getType() !== 'entity_reference') {
            continue;
          }
          if (!$field_definition->getConfig($type_id)->getThirdPartySetting('opigno_calendar_event', 'embedded_widget')) {
            continue;
          }
          if ($field_definition->getFieldStorageDefinition()->getSetting('target_type') !== $entity_type_id) {
            continue;
          }
          $handler = $field_definition->getSetting('handler_settings');
          if (isset($bundle_id) && !isset($handler['target_bundles'][$bundle_id])) {
            continue;
          }
          $referencing_field_definition = $field_definition;
          break 2;
        }
      }
    }

    return $referencing_field_definition ?: NULL;
  }

  /**
   * Returns the embedded widget.
   *
   * @return \Drupal\opigno_calendar_event\CalendarEventEmbeddedWidget
   *   An instance of the embedded widget.
   */
  public function getEmbeddedWidget() {
    if (!isset($this->embeddedWidget)) {
      $this->embeddedWidget = new CalendarEventEmbeddedWidget($this->entityTypeManager);
    }
    return $this->embeddedWidget;
  }

  /**
   * Returns the embedded display.
   *
   * @return \Drupal\opigno_calendar_event\CalendarEventEmbeddedDisplay
   *   An instance of the embedded display.
   */
  public function getEmbeddedDisplay() {
    if (!isset($this->embeddedDisplay)) {
      $this->embeddedDisplay = new CalendarEventEmbeddedDisplay($this->entityTypeManager);
    }
    return $this->embeddedDisplay;
  }

}
