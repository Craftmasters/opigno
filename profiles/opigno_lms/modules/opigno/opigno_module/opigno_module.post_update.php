<?php

/**
 * @file
 * Contains opigno module post update functions.
 */

use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchemaConverter;

/**
 * Update opigno_answer to be revisionable.
 */
function opigno_module_post_update_make_opigno_answer_revisionable(&$sandbox) {
  $schema_converter = new SqlContentEntityStorageSchemaConverter(
    'opigno_answer',
    \Drupal::entityTypeManager(),
    \Drupal::entityDefinitionUpdateManager(),
    \Drupal::service('entity.last_installed_schema.repository'),
    \Drupal::keyValue('entity.storage_schema.sql'),
    \Drupal::database()
  );

  $schema_converter->convertToRevisionable(
    $sandbox,
    [
      'user_id',
    ]
  );
}
