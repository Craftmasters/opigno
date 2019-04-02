<?php

namespace Drupal\opigno_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Provides a 'OpignoPmThread' migrate source.
 *
 * @MigrateSource(
 *  id = "opigno_pm_thread_delete_time"
 * )
 */
class OpignoPmThreadDeleteTime extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('pm_thread_delete_time_migration', 'pmd')->fields('pmd');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('Deletion ID'),
      'owner' => $this->t('Owner User ID'),
      'delete_time' => $this->t('Whether the user has deleted this message'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['id']['type'] = 'integer';
    return $ids;
  }

}
