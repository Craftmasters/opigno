<?php

namespace Drupal\opigno_ilt;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the opigno_ilt_result entity.
 *
 * @see \Drupal\opigno_ilt\Entity\ILTResult.
 */
class ILTResultAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($account->hasPermission('manage group content in any group')) {
      return AccessResult::allowed();
    }

    /** @var \Drupal\opigno_ilt\ILTResultInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view ilt result entities');

      case 'edit':
        return AccessResult::allowedIfHasPermission($account, 'edit ilt result entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete ilt result entities');
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

    return AccessResult::allowedIfHasPermission($account, 'add ilt result entities');
  }

}
