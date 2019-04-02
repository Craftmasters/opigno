<?php

namespace Drupal\opigno_module;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\opigno_module\Entity\OpignoAnswerInterface;

/**
 * Defines the storage handler class for Answer entities.
 *
 * This extends the base storage class, adding required special handling for
 * Answer entities.
 *
 * @ingroup opigno_module
 */
class OpignoAnswerStorage extends SqlContentEntityStorage implements OpignoAnswerStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(OpignoAnswerInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {opigno_answer_revision} WHERE id=:id ORDER BY vid',
     [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {opigno_answer_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(OpignoAnswerInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {opigno_answer_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('opigno_answer_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
