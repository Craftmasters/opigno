<?php

namespace Drupal\opigno_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Provides a 'OpignoPmThread' migrate source.
 *
 * @MigrateSource(
 *  id = "opigno_pm_thread"
 * )
 */
class OpignoPmThread extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('pm_index', 'pmi')->fields('pmi', array_keys($this->fields()));

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'mid' => $this->t('Private Message ID'),
      'thread_id' => $this->t('Messages thread ID'),
      'recipient' => $this->t('ID of the recipient object, typically user'),
      'is_new' => $this->t('Whether the user has read this message'),
      'deleted' => $this->t('Whether the user has deleted this message'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $thread_id = $row->getSourceProperty('thread_id');

    $query = $this->select('pm_index', 'pmi')
      ->fields('pmi', ['recipient'])
      ->condition('thread_id', $thread_id)
      ->distinct();
    $recipients = $query->execute()->fetchCol();
    if ($recipients) {
      // Set thread members.
      $row->setSourceProperty('recipients', $recipients);
    }

    $query = $this->select('pm_index', 'pmi')
      ->fields('pmi', ['mid'])
      ->condition('thread_id', $thread_id)
      ->distinct();
    $mids = $query->execute()->fetchCol();
    if ($mids) {
      // Set thread messages attachment.
      $row->setSourceProperty('mids', $mids);

      // Get thread first message subject.
      $query = $this->select('pm_message', 'pm')
        ->fields('pm', ['subject'])
        ->condition('mid', min($mids));
      $subject = $query->execute()->fetchField();
      if ($subject) {
        // Set thread subject.
        $row->setSourceProperty('subject', $subject);
      }
    }

    $db_connect = \Drupal::service('database');
    $query = $db_connect->select('pm_thread_delete_time_migration', 'pd');
    $query->join('migrate_map_opigno_pm_thread_delete_time', 'pdm', 'pdm.sourceid1 = pd.id');
    $query->fields('pdm', ['destid1'])
      ->condition('thread_id', $thread_id);
    $result = $query->execute()->fetchCol();
    if ($result) {
      // Set thread last_delete_time property.
      $row->setSourceProperty('last_delete_time_ids', $result);
      // Set thread last_access_time property.
      $row->setSourceProperty('last_access_time_ids', $result);
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['mid']['type'] = 'integer';
    return $ids;
  }

}
