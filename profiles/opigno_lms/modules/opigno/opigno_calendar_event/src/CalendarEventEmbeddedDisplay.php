<?php

namespace Drupal\opigno_calendar_event;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a display to attach a calendar event to an entity.
 */
class CalendarEventEmbeddedDisplay {

  use StringTranslationTrait;

  /**
   * The widget element name.
   *
   * @var string
   */
  const ELEMENT_NAME = 'opigno_calendar_event_embedded_display';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * CalendarEventManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Builds en embedded calendar event display.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   Field definition.
   * @param \Drupal\opigno_calendar_event\CalendarEventInterface[] $calendar_events
   *   Calendar events.
   *
   * @return array
   *   Calendar events build.
   */
  public function build(FieldDefinitionInterface $field_definition, array $calendar_events) {
    $build = [];

    $element = &$build[static::ELEMENT_NAME];

    $element = $this->entityTypeManager
      ->getViewBuilder(CalendarEventInterface::ENTITY_TYPE_ID)
      ->viewMultiple($calendar_events, 'embedded_display');

    $element['#field_name'] = $field_definition->getName();
    $element['#bundle'] = $field_definition->getTargetBundle();

    return $build;
  }

}
