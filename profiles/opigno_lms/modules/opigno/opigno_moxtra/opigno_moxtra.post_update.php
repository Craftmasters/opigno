<?php

/**
 * @file
 * Contains opigno_moxtra.post_update.
 */

use Drupal\opigno_moxtra\Entity\Meeting;
use Drupal\user\Entity\User;

/**
 * Updates users status with Opigno API.
 */
function opigno_moxtra_post_update_update_user_status(&$sandbox) {
  $config = \Drupal::config('opigno_moxtra.settings');
  $org_id = $config->get('org_id');
  $status = $config->get('status');
  if (!empty($org_id) && $status === TRUE) {
    $opigno_api = \Drupal::service('opigno_moxtra.opigno_api');

    if (!isset($sandbox['last_uid'])) {
      $sandbox['last_uid'] = 0;
      $sandbox['current'] = 0;
      $sandbox['total'] = \Drupal::entityQuery('user')->count()->execute();
    }

    $uids = \Drupal::entityQuery('user')
      ->condition('uid', $sandbox['last_uid'], '>')
      ->range(0, 10)
      ->execute();
    /** @var \Drupal\user\Entity\User[] $users */
    $users = User::loadMultiple($uids);
    foreach ($users as $user) {
      $active = $user->isActive() && $user->hasRole(OPIGNO_MOXTRA_COLLABORATIVE_FEATURES_RID);
      if ($active) {
        $data = [
          'uid' => $user->id(),
          'name' => $user->getDisplayName(),
          'timezone' => $user->getTimeZone(),
        ];
        $opigno_api->updateUser($data);
        $opigno_api->enableUser($data);
      }
      else {
        $opigno_api->disableUser($user->id());
      }

      $sandbox['last_uid'] = $user->id();
      $sandbox['current']++;
      $sandbox['#finished'] = $sandbox['current'] / $sandbox['total'];
    }
  }
}

/**
 * Recreates calendar events related to the live meetings.
 */
function opigno_moxtra_post_update_recreate_meeting_calendar_events(&$sandbox) {
  if (!isset($sandbox['last_id'])) {
    $sandbox['last_id'] = 0;
    $sandbox['current'] = 0;
    $sandbox['total'] = \Drupal::entityQuery('opigno_moxtra_meeting')
      ->count()
      ->execute();
  }

  $ids = \Drupal::entityQuery('opigno_moxtra_meeting')
    ->condition('id', $sandbox['last_id'], '>')
    ->range(0, 10)
    ->execute();
  /** @var \Drupal\opigno_moxtra\MeetingInterface[] $meetings */
  $meetings = Meeting::loadMultiple($ids);
  foreach ($meetings as $meeting) {
    $event = $meeting->getCalendarEvent();
    $event->delete();

    $meeting->set('calendar_event', NULL);
    $meeting->save();

    $sandbox['last_id'] = $meeting->id();
    $sandbox['current']++;
    $sandbox['#finished'] = $sandbox['current'] / $sandbox['total'];
  }
}
