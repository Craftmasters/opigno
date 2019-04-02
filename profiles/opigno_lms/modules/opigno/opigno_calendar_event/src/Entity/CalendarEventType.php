<?php

namespace Drupal\opigno_calendar_event\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the "Calendar event type" configuration entity.
 *
 * @ConfigEntityType(
 *   id = "opigno_calendar_event_type",
 *   label = @Translation("Calendar event type"),
 *   handlers = {
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\opigno_calendar_event\Form\CalendarEventTypeForm",
 *       "edit" = "Drupal\opigno_calendar_event\Form\CalendarEventTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *    "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "list_builder" = "Drupal\opigno_calendar_event\CalendarEventTypeListBuilder",
 *   },
 *   admin_permission = "administer calendar settings",
 *   config_prefix = "type",
 *   bundle_of = "opigno_calendar_event",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/calendar-event-types/add",
 *     "edit-form" = "/admin/structure/calendar-event-types/manage/{opigno_calendar_event_type}",
 *     "delete-form" = "/admin/structure/calendar-event-types/manage/{opigno_calendar_event_type}/delete",
 *     "collection" = "/admin/structure/calendar-event-types",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "date_field_type",
 *   }
 * )
 */
class CalendarEventType extends ConfigEntityBundleBase {

  /**
   * The machine name of this calendar event type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the calendar event type.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this calendar event type.
   *
   * @var string
   */
  protected $description;

}
