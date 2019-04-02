<?php

namespace Drupal\opigno_certificate;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Certificate entity.
 *
 * @see \Drupal\opigno_certificate\Entity\OpignoCertificate.
 */
class OpignoCertificateAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\opigno_certificate\Entity\OpignoCertificate $entity */
    switch ($operation) {
      case 'view':
        if ($account->hasPermission('administer certificates')) {
          // Allow admins & platform-level managers to view any content.
          return AccessResult::allowed();
        }

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished certificate entities');
        }

        return AccessResult::allowedIfHasPermission($account, 'view published certificate entities');

      case 'update':
        if ($entity->getOwnerId() === $account->id()) {
          // Allow users to edit its own content.
          return AccessResult::allowed();
        }

        return AccessResult::allowedIfHasPermission($account, 'edit certificate entities');

      case 'delete':
        if ($entity->getOwnerId() === $account->id()) {
          // Allow users to delete its own content.
          return AccessResult::allowed();
        }

        return AccessResult::allowedIfHasPermission($account, 'delete certificate entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {

    return AccessResult::allowedIfHasPermission($account, 'add certificate entities');
  }

}
