<?php

namespace Drupal\opigno_calendar_event_group;

use Drupal\opigno_calendar_event\CalendarEventExceptionLoggerTrait;
use Drupal\opigno_calendar_event\CalendarEventInterface;
use Drupal\opigno_calendar_event\CalendarEventManager;
use Drupal\opigno_calendar_event\Form\CalendarEventEmbeddedWidget;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a widget to select calendar event groups.
 */
class GroupSelectorWidget implements ContainerInjectionInterface {

  use StringTranslationTrait;
  use CalendarEventExceptionLoggerTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The calendar event manager variable.
   *
   * @var \Drupal\opigno_calendar_event\CalendarEventManager
   */
  protected $calendarEventManager;

  /**
   * GroupSelectorWidget constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\opigno_calendar_event\CalendarEventManager $calendar_event_manager
   *   The calendar event manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CalendarEventManager $calendar_event_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->calendarEventManager = $calendar_event_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /* @noinspection PhpParamsInspection. */
    return new static(
      $container->get('entity_type.manager'),
      $container->get('opigno_calendar_event.manager')
    );
  }

  /**
   * Alters the parent form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function alterForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\ContentEntityFormInterface $form_object */
    $form_object = $form_state->getFormObject();
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $form_object->getEntity();
    if ($entity->isNew()) {
      return;
    }

    $groups = $this->getSelectableGroups($entity);
    if (!$groups) {
      return;
    }

    $elements = &$form[CalendarEventEmbeddedWidget::ELEMENT_NAME];
    foreach (Element::children($elements) as $delta) {
      if (is_int($delta)) {
        $this->addGroupPicker($elements[$delta], $groups, $form, $form_state);
      }
    }
  }

  /**
   * Adds the groups selector.
   *
   * @param array $element
   *   The form element corresponding to a single calendar event form.
   * @param \Drupal\group\Entity\GroupInterface[] $groups
   *   An array of selectable groups.
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function addGroupPicker(array &$element, array $groups, array $form, FormStateInterface $form_state) {
    if (!isset($element['displayed'])) {
      return;
    }

    $options = array_map(
      function (GroupInterface $group) {
        return $group->label();
      },
      $groups
    );
    $options['_displayed'] = $this->t('Global calendar');

    $widget = $this->calendarEventManager->getEmbeddedWidget();
    $state = $widget->getItemState($form, $form_state, $element['#delta']);
    /** @var \Drupal\opigno_calendar_event\CalendarEventInterface $calendar_event */
    $calendar_event = $state['entity'];
    $default_values = array_map(
      function ($item) {
        return $item['target_id'];
      },
      $calendar_event->get('display_groups')->getValue()
    );

    $widget_element = &$element['displayed']['widget']['value'];
    if (!empty($widget_element['#default_value'])) {
      $default_values[] = '_displayed';
    }

    $widget_element = [
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $default_values,
    ] + $widget_element;

    $element['#entity_builders'][] = [$this, 'buildEntity'];
  }

  /**
   * Returns the groups the entity belongs to.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   An entity object.
   *
   * @return \Drupal\group\Entity\GroupInterface[]
   *   An array of group entities.
   */
  protected function getSelectableGroups(ContentEntityInterface $entity) {
    $groups = [];

    try {
      /** @var \Drupal\group\Entity\Storage\GroupContentStorageInterface $storage */
      $storage = $this->entityTypeManager->getStorage('group_content');

      // @todo Scalability improvement: use an aggregate entity query instead.
      /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
      foreach ($storage->loadByEntity($entity) as $group_content) {
        $group = $group_content->getGroup();
        $groups[$group->id()] = $group;
      }
    }
    catch (InvalidPluginDefinitionException $e) {
      $this->logException($e);
    }

    return $groups;
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
    $widget = $this->calendarEventManager->getEmbeddedWidget();
    $values = $widget->getItemValues($form_state->getCompleteForm(), $form_state, $element['#delta']);
    // Handle the "global calendar visibility" option.
    if (isset($values['displayed']['value']['_displayed'])) {
      $calendar_event->setDisplayed($values['displayed']['value']['_displayed'] !== 0);
      unset($values['displayed']['value']['_displayed']);
    }
    // Handle per-group visibility.
    if (!empty($values['displayed']['value'])) {
      $items = array_filter($values['displayed']['value']);
      $calendar_event->set('display_groups', $items);
    }
  }

}
