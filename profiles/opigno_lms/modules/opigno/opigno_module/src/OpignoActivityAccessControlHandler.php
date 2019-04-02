<?php

namespace Drupal\opigno_module;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Activity entity.
 *
 * @see \Drupal\opigno_module\Entity\OpignoActivity.
 */
class OpignoActivityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\opigno_module\Entity\OpignoActivityInterface $entity */
    switch ($operation) {
      case 'view':
        if ($account->hasPermission('manage group content in any group')) {
          // Allow admins & platform-level managers to view any content.
          return AccessResult::allowed();
        }

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished activity entities');
        }

        return AccessResult::allowedIfHasPermission($account, 'view published activity entities');

      case 'update':
        if ($account->hasPermission('manage group content in any group')) {
          // Allow admins & platform-level managers to edit any content.
          return AccessResult::allowed();
        }

        if ($entity->getOwnerId() === $account->id()) {
          // Allow users to edit its own content.
          return AccessResult::allowed();
        }

        return AccessResult::allowedIfHasPermission($account, 'edit activity entities');

      case 'delete':
        if ($account->hasPermission('manage group content in any group')) {
          // Allow admins & platform-level managers to delete any content.
          return AccessResult::allowed();
        }

        if ($entity->getOwnerId() === $account->id()) {
          // Allow users to delete its own content.
          return AccessResult::allowed();
        }

        return AccessResult::allowedIfHasPermission($account, 'delete activity entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    if ($account->hasPermission('manage group content in any group')) {
      return AccessResult::allowed();
    }

    // Allow user to create activities
    // if the user is a group-level content manager in any group.
    $membership_service = \Drupal::service('group.membership_loader');
    $memberships = $membership_service->loadByUser($account);
    foreach ($memberships as $membership) {
      /** @var \Drupal\group\GroupMembership $membership */
      $group = $membership->getGroup();
      if ($group->access('update', $account)) {
        return AccessResult::allowed();
      }
    }

    return AccessResult::allowedIfHasPermission($account, 'add activity entities');
  }

}
