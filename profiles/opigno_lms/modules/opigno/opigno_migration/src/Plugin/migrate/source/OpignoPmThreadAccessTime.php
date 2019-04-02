<?php

namespace Drupal\opigno_migration\Plugin\migrate\source;

use Drupal\Core\Database\Database;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Provides a 'OpignoPmThread' migrate source.
 *
 * @MigrateSource(
 *  id = "opigno_pm_thread_access_time"
 * )
 */
class OpignoPmThreadAccessTime extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $db_connection = Database::getConnection('default', 'default');
    $query = $db_connection->select('pm_thread_delete_time_migration', 'pdm');
    $query->fields('pdm', ['id', 'owner']);
    if ($db_connection->schema()->tableExists('migrate_map_opigno_pm_thread_delete_time')) {
      $query->fields('pmd', ['sourceid1', 'destid1']);
      $query->innerJoin('migrate_map_opigno_pm_thread_delete_time', 'pmd', 'pdm.id = pmd.sourceid1');
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('Source deletion ID'),
      'owner' => $this->t('Owner User ID'),
      'sourceid1' => $this->t('Source deletion ID'),
      'destid1' => $this->t('Deletion ID'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $row->setSourceProperty('access_time', time());

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['id']['type'] = 'integer';
    return $ids;
  }

}
