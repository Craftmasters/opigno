<?php

namespace Drupal\opigno_messaging;

/**
 * Class OpignoMessageThread.
 *
 * @package Drupal\opigno_messaging
 */
class OpignoMessageThread {

  /**
   * Gets message treads of current user.
   *
   * @param int $uid
   *   User uid.
   *
   * @return bool|array
   *   User threads.
   */
  public static function getUserThreads($uid) {
    $db_connection = \Drupal::service('database');
    $query = $db_connection->select('private_message_thread__members', 'tm');
    $query->fields('tm', ['entity_id'])
      ->condition('tm.members_target_id', $uid);
    $result = $query->execute()->fetchCol();

    if ($result) {
      return $result;
    }

    return FALSE;
  }

  /**
   * Returns unread threads count.
   *
   * @return int
   *   Unread threads count.
   */
  public static function getUnreadThreadCount() {
    $unread_thread_count = 0;
    $pm_service = \Drupal::service('private_message.service');
    $uid = \Drupal::currentUser()->id();
    if ($uid > 0 && isset($pm_service)) {
      $db_connection = \Drupal::service('database');

      // Threads user last access timestamp.
      $query = $db_connection->select('private_message_thread__last_access_time', 'pmtlat');
      $query->join('pm_thread_access_time', 'pmtat', 'pmtat.id = pmtlat.last_access_time_target_id AND pmtat.owner = :uid', [':uid' => $uid]);
      $query->join('private_message_threads', 'pmt', 'pmt.id = pmtlat.entity_id');
      $query->join('pm_thread_delete_time', 'pmtdt', 'pmtdt.id = pmtat.id');
      $query->fields('pmtlat', ['entity_id']);
      $query->fields('pmtat', ['access_time']);
      $query->fields('pmt', ['updated']);
      $query->fields('pmtdt', ['delete_time']);
      $access_timestamp = $query->execute()->fetchAllAssoc('entity_id');

      if ($access_timestamp) {
        foreach ($access_timestamp as $access) {
          // Check if current user is thread owner.
          $query = $db_connection->select('private_messages', 'pm')
            ->fields('pm')
            ->condition('owner', $uid)
            ->condition('created', $access->updated);
          $owner = $query->execute()->fetchField();

          if (!$owner && $access->updated >= $access->access_time) {
            // Thread updated and current user isn't the owner of last update.
            if ($access->delete_time < $access->updated) {
              $unread_thread_count++;
            }
          }
        }
      }
    }

    return $unread_thread_count;
  }

}
