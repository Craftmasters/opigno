<?php

namespace Drupal\opigno_calendar_event;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Storage class for calendar events.
 */
class CalendarEventStorage extends SqlContentEntityStorage {

  /**
   * Checks whether there is stored data for the specififed bundle.
   *
   * @param string $bundle_id
   *   The bundle ID.
   *
   * @return bool
   *   TRUE if there is stored data for the specified bundle, FALSE otherwise.
   */
  public function hasBundleData($bundle_id) {
    $bundle_key = $this->getEntityType()->getKey('bundle');
    return (bool) $this->getQuery()
      ->accessCheck(FALSE)
      ->condition($bundle_key, $bundle_id)
      ->range(0, 1)
      ->execute();
  }

}
