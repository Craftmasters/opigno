<?php

namespace Drupal\opigno_moxtra;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the opigno_moxtra_workspace entity.
 *
 * @see \Drupal\opigno_moxtra\Entity\Workspace.
 */
class WorkspaceAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($account->hasPermission('manage group content in any group')) {
      return AccessResult::allowed();
    }

    /** @var \Drupal\opigno_moxtra\WorkspaceInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view workspace entities');

      case 'edit':
        if ($entity->getOwnerId() === $account->id()) {
          // Allow users to edit its own content.
          return AccessResult::allowed();
        }

        return AccessResult::allowedIfHasPermission($account, 'edit workspace entities');

      case 'delete':
        if ($entity->getOwnerId() === $account->id()) {
          // Allow users to delete its own content.
          return AccessResult::allowed();
        }

        return AccessResult::allowedIfHasPermission($account, 'delete workspace entities');
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

    return AccessResult::allowedIfHasPermission($account, 'add workspace entities');
  }

}
