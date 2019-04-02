<?php

namespace Drupal\opigno_module;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the User module status entity.
 *
 * @see \Drupal\opigno_module\Entity\UserModuleStatus.
 */
class UserModuleStatusAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\opigno_module\Entity\UserModuleStatusInterface $entity */
    switch ($operation) {
      case 'view':
        if ($account->hasPermission('view module results')) {
          return AccessResult::allowed();
        }

        if ($entity->getOwnerId() === $account->id() && $account->hasPermission('view own module results')) {
          return AccessResult::allowed();
        }

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished user module status entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published user module status entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit user module status entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete user module status entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add user module status entities');
  }

}
