<?php

namespace Drupal\opigno_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Drupal 7 vocabularies source from database.
 *
 * @MigrateSource(
 *   id = "opigno_pm_message",
 * )
 */
class OpignoPmMessage extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('pm_message', 'pm')->fields('pm');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'mid' => $this->t('Private Message ID'),
      'author' => $this->t('UID of the author'),
      'subject' => $this->t('Subject text of the message'),
      'body' => $this->t('Body of the message'),
      'format' => $this->t('The filter_formats.format of the message text.'),
      'timestamp' => $this->t('Time when the message was sent'),
      'has_tokens' => $this->t('Indicates if the message has tokens'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['mid']['type'] = 'integer';
    return $ids;
  }

}
