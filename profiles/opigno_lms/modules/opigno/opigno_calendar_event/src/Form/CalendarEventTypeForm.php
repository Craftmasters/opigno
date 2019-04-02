<?php

namespace Drupal\opigno_calendar_event\Form;

use Drupal\opigno_calendar_event\CalendarEventExceptionLoggerTrait;
use Drupal\opigno_calendar_event\CalendarEventInterface;
use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for calendar event type forms.
 */
class CalendarEventTypeForm extends BundleEntityFormBase {

  use CalendarEventExceptionLoggerTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * CalendarEventTypeForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /* @noinspection PhpParamsInspection */
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\opigno_calendar_event\Entity\CalendarEventType $type */
    $type = $this->entity;

    $form['label'] = [
      '#title' => $this->t('Label'),
      '#type' => 'textfield',
      '#default_value' => $type->label(),
      '#description' => $this->t('The human-readable name of this calendar event type.'),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $type->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => [
        'exists' => ['Drupal\opigno_calendar_event\Entity\CalendarEventType', 'load'],
        'source' => ['label'],
      ],
      '#description' => $this->t('A unique machine-readable name for this calendar event type. It must only contain lowercase letters, numbers, and underscores.'),
    ];

    $form['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#default_value' => $type->get('description'),
      '#description' => $this->t('This text will be displayed on the <em>Add new calendar event</em> page.'),
    ];

    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $options = $this->getDateFieldTypes();
    $default_value = $type->isNew() ? key(array_reverse($options)) : $type->get('date_field_type');
    /** @var \Drupal\opigno_calendar_event\CalendarEventStorage $storage */
    $storage = $this->entityTypeManager->getStorage(CalendarEventInterface::ENTITY_TYPE_ID);
    // @todo Add a validation constraint for this once config entity validation
    //   is supported. See https://www.drupal.org/project/drupal/issues/1818574.
    $disabled = !$type->isNew() && $storage->hasBundleData($type->id());
    $description = !$disabled ?
      $this->t('Choose which kind of date this calendar event type will use: Drupal provides the <em>Date</em> and <em>Date range</em> types, contributed modules may define more.') :
      $this->t('This setting cannot be changed because there is data for this calendar event type.');

    $form['advanced']['date_field_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Date type'),
      '#description' => $description,
      '#options' => $options,
      '#default_value' => $default_value,
      '#disabled' => $disabled,
    ];

    return $this->protectBundleIdElement($form);
  }

  /**
   * Returns a list of available date field types.
   *
   * @return string[]
   *   An associative array of date field type label keyed by type ID.
   */
  protected function getDateFieldTypes() {
    // @todo Leverage the Calendar API to retrieve supported field types, when
    //   one is available.
    $types = [
      'timestamp' => $this->t('Timestamp'),
      'datetime' => $this->t('Date'),
      'daterange' => $this->t('Date range'),
    ];
    if ($this->moduleHandler->moduleExists('date_recur')) {
      $types['date_recur'] = $this->t('Recurring dates');
    }
    return $types;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $id = trim($form_state->getValue('type'));
    // '0' is invalid, since elsewhere we check it using empty().
    if ($id == '0') {
      $form_state->setErrorByName('type', $this->t("Invalid machine-readable name. Enter a name other than %invalid.", ['%invalid' => $id]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\opigno_calendar_event\Entity\CalendarEventType $type */
    $type = $this->entity;

    try {
      $type->set('type', trim($type->id()));
      $type->set('name', trim($type->label()));
      $status = $type->save();

      $t_args = ['%name' => $type->label()];
      if ($status == SAVED_UPDATED) {
        $this->messenger()->addStatus($this->t('The calendar event type %name has been updated.', $t_args));
      }
      elseif ($status == SAVED_NEW) {
        $this->messenger()->addStatus($this->t('The calendar event %name has been added.', $t_args));
        $context = array_merge($t_args, ['link' => $type->link($this->t('View'), 'collection')]);
        $this->logger('opigno_calendar_event')
          ->notice('Added calendar event type %name.', $context);
      }

      $form_state->setRedirectUrl($type->urlInfo('collection'));
    }
    catch (EntityStorageException $e) {
      $this->logException($e);
      $this->messenger()->addError($this->t('The calendar event type could not be saved.'));
      $form_state->setRebuild(TRUE);
    }
  }

}
