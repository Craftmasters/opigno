<?php

namespace Drupal\opigno_notification;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the opigno_notification entity.
 */
class OpignoNotificationAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add opigno notification');
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view opigno notification');

      case 'edit':
        return AccessResult::allowedIfHasPermission($account, 'edit opigno notification');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete opigno notification');
    }

    return AccessResult::allowed();
  }

}
