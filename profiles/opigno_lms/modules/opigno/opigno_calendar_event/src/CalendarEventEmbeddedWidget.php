<?php

namespace Drupal\opigno_calendar_event;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a widget to attach a calendar event to an entity.
 *
 * @todo Consider leveraging embedded forms.
 *
 * @see https://www.drupal.org/project/drupal/issues/1728816
 * @see https://www.drupal.org/project/drupal/issues/2006248
 * @see \Drupal\Core\Form\SubformStateInterface
 * @see \Drupal\Core\Form\SubformState
 */
class CalendarEventEmbeddedWidget {

  use StringTranslationTrait;
  use CalendarEventExceptionLoggerTrait;

  /**
   * The widget element name.
   *
   * @var string
   */
  const ELEMENT_NAME = 'opigno_calendar_event_embedded_widget';

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
   * Alters the parent form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the entity reference field relating a calendar event
   *   with the entity being edited.
   * @param \Drupal\opigno_calendar_event\CalendarEventInterface[] $calendar_events
   *   An array of calendar event entities referencing the entity being edited.
   */
  public function alterForm(array &$form, FormStateInterface $form_state, FieldDefinitionInterface $field_definition, array $calendar_events) {
    $widget_state = &$this->getWidgetState($form, $form_state);
    $widget_state['field_definition'] = $field_definition;

    /** @var \Drupal\Core\Entity\EntityFormInterface $form_object */
    $form_object = $form_state->getFormObject();
    $widget_state['original_label'] = $form_object->getEntity()->label();

    $elements = &$form[static::ELEMENT_NAME];
    $elements = [
      '#parents' => $this->getElementParents($form),
      '#tree' => TRUE,
    ];

    if (count($calendar_events) > 1) {
      $elements += [
        '#type' => 'fieldset',
        '#title' => $this->t('Calendar events'),
      ];
    }

    /** @var \Drupal\opigno_calendar_event\CalendarEventInterface $calendar_event */
    foreach (array_values($calendar_events) as $delta => $calendar_event) {
      $elements[$delta] = [
        '#parents' => $this->getElementParents($form, $delta),
        '#tree' => TRUE,
        '#delta' => $delta,
        '#entity_builders' => [
          [$this, 'buildEntity'],
        ],
      ];

      $item_state = &$this->getItemState($form, $form_state, $delta);
      $item_state['entity'] = $calendar_event;
      $form_display = $this->getItemFormDisplay($form, $form_state, $delta);
      $form_display->buildForm($calendar_event, $elements[$delta], $form_state);
      $this->disableRequired($elements[$delta]);
      $this->attachFieldGroups($elements[$delta], $calendar_event, $form_display);
    }

    $form['#validate'][] = [$this, 'validateForm'];
    $form['actions']['submit']['#submit'][] = [$this, 'submitForm'];
  }

  /**
   * Makes the specified element and its descendants not required.
   *
   * @param array $element
   *   A form element array.
   */
  protected function disableRequired(array &$element) {
    $element['#required'] = FALSE;
    foreach (Element::children($element) as $key) {
      $this->disableRequired($element[$key]);
    }
  }

  /**
   * Attaches field groups to the specified subform element.
   *
   * @param array $element
   *   The subform element.
   * @param \Drupal\opigno_calendar_event\CalendarEventInterface $entity
   *   The calendar event entity.
   * @param \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display
   *   A form display object.
   */
  protected function attachFieldGroups(array &$element, CalendarEventInterface $entity, EntityFormDisplayInterface $form_display) {
    if (!function_exists('field_group_attach_groups')) {
      return;
    }

    $context = [
      'entity_type' => $entity->getEntityTypeId(),
      'bundle' => $entity->bundle(),
      'entity' => $entity,
      'context' => 'form',
      'display_context' => 'form',
      'mode' => $form_display->getMode(),
    ];

    field_group_attach_groups($element, $context);
    $element['#pre_render'][] = 'field_group_form_pre_render';
  }

  /**
   * Entity builder method.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param \Drupal\opigno_calendar_event\CalendarEventInterface $calendar_event
   *   The calendar event entity to be built.
   * @param array $element
   *   The subform element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The parent form state.
   */
  public function buildEntity($entity_type_id, CalendarEventInterface $calendar_event, array &$element, FormStateInterface $form_state) {
    $widget_state = $this->getWidgetState($form_state->getCompleteForm(), $form_state);
    /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
    $field_definition = $widget_state['field_definition'];
    $ref_field_name = $field_definition->getName();

    /** @var \Drupal\Core\Entity\ContentEntityFormInterface $form_object */
    $form_object = $form_state->getFormObject();
    $referenced_entity = $form_object->getEntity();
    $calendar_event->set($ref_field_name, $referenced_entity);

    // Make sure the calendar event has a valid title and keep the calendar
    // event label synchronized with the referenced entity, unless they are
    // explicitly diverging.
    $calendar_event_label = $calendar_event->label();
    if (!$calendar_event_label || $widget_state['original_label'] === $calendar_event_label) {
      $label = $referenced_entity->label();
    }
    else {
      $label = $calendar_event_label;
    }
    $calendar_event->set('title', $label ?: $this->t('Calendar event'));
  }

  /**
   * Validation handler.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateForm(array $form, FormStateInterface $form_state) {
    $widget_state = &$this->getWidgetState($form, $form_state);
    $has_errors = $form_state->hasAnyErrors();
    $has_nested_errors = FALSE;

    foreach (array_keys($widget_state['items']) as $delta) {
      $item_form_object = $this->getItemFormObject($form, $form_state, $delta);
      $item_form_state = $this->getItemFormState($form, $form_state, $delta);
      /* @var \Drupal\opigno_calendar_event\CalendarEventInterface $calendar_event */
      $calendar_event = $item_form_object->validateForm($form[static::ELEMENT_NAME][$delta], $item_form_state);

      // If the calendar event widget is not populated we can safely ignore the
      // subform validation errors, otherwise we need to proxy them to the
      // parent form state.
      if ($this->isCalendarEventPopulated($calendar_event)) {
        foreach ($item_form_state->getErrors() as $name => $error) {
          $has_nested_errors = TRUE;
          $form_state->setErrorByName($name, $error);
        }
      }
    }

    // Form state tracks errors in a static member so we need to make sure
    // subforms do not affect the main form state if they are not populated.
    if (!$has_errors && !$has_nested_errors) {
      $form_state->clearErrors();
    }
  }

  /**
   * Submit handler.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submitForm(array $form, FormStateInterface $form_state) {
    $widget_state = $this->getWidgetState($form, $form_state);

    foreach (array_keys($widget_state['items']) as $delta) {
      $item_form_object = $this->getItemFormObject($form, $form_state, $delta);
      $item_form_state = $this->getItemFormState($form, $form_state, $delta);
      /** @var \Drupal\opigno_calendar_event\CalendarEventInterface $calendar_event */
      $calendar_event = $item_form_object->buildEntity($form[static::ELEMENT_NAME][$delta], $item_form_state);

      try {
        if ($this->isCalendarEventPopulated($calendar_event)) {
          $calendar_event->save();
        }
        elseif (!$calendar_event->isNew()) {
          $calendar_event->delete();
        }
      }
      catch (EntityStorageException $e) {
        $this->logException($e);
      }
    }
  }

  /**
   * Returns the state of the whole widget.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   An associative array of widget state data.
   */
  public function &getWidgetState(array $form, FormStateInterface $form_state) {
    $storage = &$form_state->getStorage();
    $parents = array_merge(['field_storage', '#parents'], $form['#parents'], ['#fields', static::ELEMENT_NAME]);
    $widget_state = &NestedArray::getValue($storage, $parents, $key_exists);
    if (!$key_exists) {
      NestedArray::setValue($storage, $parents, []);
      $widget_state = &NestedArray::getValue($storage, $parents, $key_exists);
    }
    return $widget_state;
  }

  /**
   * Returns the state of an individual subform item.
   *
   * @param array $form
   *   The parent form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The parent form state.
   * @param int $delta
   *   The item delta.
   *
   * @return array
   *   An associative array of widget state data.
   */
  public function &getItemState(array $form, FormStateInterface $form_state, $delta) {
    $widget_state = &$this->getWidgetState($form, $form_state);
    if (!isset($widget_state['items'][$delta])) {
      $widget_state['items'][$delta] = [];
    }
    return $widget_state['items'][$delta];
  }

  /**
   * Returns the form display for the specified subform item.
   *
   * @param array $form
   *   The parent form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The parent form state.
   * @param int $delta
   *   The item delta.
   *
   * @return \Drupal\Core\Entity\Display\EntityFormDisplayInterface
   *   An entity form display object.
   */
  protected function getItemFormDisplay(array $form, FormStateInterface $form_state, $delta) {
    $widget_state = &$this->getWidgetState($form, $form_state);
    $item_state = &$widget_state['items'][$delta];

    if (!isset($item_state['form_display'])) {
      $entity = $widget_state['items'][$delta]['entity'];
      $widget_state['items'][$delta]['form_display'] = EntityFormDisplay::collectRenderDisplay($entity, 'embedded_widget');
    }

    return $item_state['form_display'];
  }

  /**
   * Returns a entity form object for the specified subform item.
   *
   * @param array $form
   *   The parent form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The parent form state.
   * @param int $delta
   *   The item delta.
   *
   * @return \Drupal\Core\Entity\ContentEntityFormInterface
   *   A Calendar event entity form object.
   */
  protected function getItemFormObject(array $form, FormStateInterface $form_state, $delta) {
    $widget_state = &$this->getWidgetState($form, $form_state);
    $item_state = &$widget_state['items'][$delta];

    if (!isset($item_state['form_object'])) {
      /** @var \Drupal\Core\Entity\ContentEntityFormInterface $form_object */
      $form_object = $this->entityTypeManager->getFormObject(CalendarEventInterface::ENTITY_TYPE_ID, 'default');
      $form_object->setEntity($item_state['entity']);
      $item_form_state = $this->getItemFormState($form, $form_state, $delta);
      $form_object->setFormDisplay($item_state['form_display'], $item_form_state);
      $item_state['form_object'] = $form_object;
    }

    return $item_state['form_object'];
  }

  /**
   * Returns the form state of the specified subform item.
   *
   * @param array $form
   *   The parent form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The parent form state.
   * @param int $delta
   *   The item delta.
   *
   * @return \Drupal\Core\Form\FormStateInterface
   *   A form state object.
   */
  protected function getItemFormState(array $form, FormStateInterface $form_state, $delta) {
    $widget_state = &$this->getWidgetState($form, $form_state);
    $item_state = &$widget_state['items'][$delta];

    if (!isset($item_state['form_state'])) {
      $item_state['form_state'] = clone $form_state;
      $item_values = array_intersect_key($form_state->getValues(), [static::ELEMENT_NAME => TRUE]);
      $item_state['form_state']->setValues($item_values);
    }

    return $item_state['form_state'];
  }

  /**
   * Returns the submitted values for the specified subform item.
   *
   * @param array $form
   *   The parent form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The parent form state.
   * @param int $delta
   *   The item delta.
   *
   * @return array
   *   An associative array of submitted form values.
   */
  public function &getItemValues(array $form, FormStateInterface $form_state, $delta) {
    return NestedArray::getValue($form_state->getValues(), $this->getElementParents($form, $delta));
  }

  /**
   * Returns the parent element names for the specified subform.
   *
   * @param array $form
   *   The parent form array.
   * @param int|null $delta
   *   (optional) A subform item delta. Defaults to none.
   *
   * @return string[]
   *   An array of element names.
   */
  protected function getElementParents(array $form, $delta = NULL) {
    $parents = $form['#parents'];
    $parents[] = static::ELEMENT_NAME;
    if (isset($delta)) {
      $parents[] = $delta;
    }
    return $parents;
  }

  /**
   * Checks whether the calendar event widget is populated.
   *
   * @param \Drupal\opigno_calendar_event\CalendarEventInterface $calendar_event
   *   A calendar event entity built from the related submitted values.
   *
   * @return bool
   *   TRUE if the widget was populated, FALSE otherwise.
   */
  protected function isCalendarEventPopulated(CalendarEventInterface $calendar_event) {
    return $calendar_event->isDisplayed() || !$calendar_event->getDateItems()->isEmpty();
  }

}
