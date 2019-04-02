<?php

namespace Drupal\opigno_moxtra;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the opigno_moxtra_meeting_result entity.
 *
 * @see \Drupal\opigno_moxtra\Entity\MeetingResult.
 */
class MeetingResultAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($account->hasPermission('manage group content in any group')) {
      return AccessResult::allowed();
    }

    /** @var \Drupal\opigno_moxtra\MeetingInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view meeting result entities');

      case 'edit':
        return AccessResult::allowedIfHasPermission($account, 'edit meeting result entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete meeting result entities');
    }

    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    if ($account->hasPermission('manage group content in any group')) {
      return AccessResult::allowed();
    }

    return AccessResult::allowedIfHasPermission($account, 'add meeting result entities');
  }

}
