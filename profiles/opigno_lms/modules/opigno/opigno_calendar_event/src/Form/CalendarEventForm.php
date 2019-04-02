<?php

namespace Drupal\opigno_calendar_event\Form;

use Drupal\opigno_calendar_event\CalendarEventExceptionLoggerTrait;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for calendar event type forms.
 */
class CalendarEventForm extends ContentEntityForm {

  use CalendarEventExceptionLoggerTrait;

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $calendar_event = $this->entity;
    $insert = $calendar_event->isNew();

    parent::save($form, $form_state);

    try {
      $link = $calendar_event->toLink($this->t('View'))->toString();
      $context = ['%title' => $calendar_event->label(), 'link' => $link];
      $t_args = [
        '%title' => $calendar_event->toLink($calendar_event->label())->toString(),
      ];
      $redirect_url = $calendar_event->toUrl();

      if ($insert) {
        $this->logger('content')
          ->notice('%title calendar event created.', $context);
        $this->messenger()->addStatus($this->t('The calendar event %title has been created.', $t_args));
      }
      else {
        $this->logger('content')
          ->notice('%title calendar event updated.', $context);
        $this->messenger()->addStatus($this->t('The calendar event %title has been updated.', $t_args));
      }
    }
    catch (EntityMalformedException $e) {
      $this->logException($e);
    }

    if ($calendar_event->id()) {
      if (isset($redirect_url) && $calendar_event->access('view')) {
        $form_state->setRedirectUrl($redirect_url);
      }
      else {
        $form_state->setRedirect('<front>');
      }
    }
    else {
      // In the unlikely case something went wrong on save, the calendar event
      // will be rebuilt and the form redisplayed.
      $this->messenger()->addError($this->t('The calendar event could not be saved.'));
      $form_state->setRebuild();
    }
  }

}
