<?php

namespace Drupal\opigno_ilt;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the opigno_ilt entity.
 *
 * @see \Drupal\opigno_ilt\Entity\ILT.
 */
class ILTAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(
    EntityInterface $entity,
    $operation,
    AccountInterface $account
  ) {
    if ($account->hasPermission('manage group content in any group')) {
      return AccessResult::allowed();
    }

    /** @var \Drupal\opigno_ilt\ILTInterface $entity */
    switch ($operation) {
      case 'view':
        // Allow view access if user is a platform-level student manager.
        if ($account->hasPermission('manage group members in any group')) {
          return AccessResult::allowed();
        }

        // Allow view access if user is a group-level student manager.
        $training = $entity->getTraining();
        if (isset($training) && $training->hasPermission('score ilt entities', $account)) {
          return AccessResult::allowed();
        }

        $members = $entity->getMembersIds();
        if (!empty($members)) {
          // Deny access if the ILT has a members restriction
          // and the user is not a member of the ILT.
          if (!in_array($account->id(), $members)) {
            return AccessResult::forbidden();
          }
        }
        else {
          // Deny access if the ILT hasn't a members restricton
          // and the user is not a member of the related training.
          $training = $entity->getTraining();
          if (isset($training) && $training->getMember($account) === FALSE) {
            return AccessResult::forbidden();
          }
        }

        return AccessResult::allowedIfHasPermission($account, 'view ilt entities');

      case 'edit':
        if ($entity->getOwnerId() === $account->id()) {
          // Allow users to edit its own content.
          return AccessResult::allowed();
        }

        return AccessResult::allowedIfHasPermission($account, 'edit ilt entities');

      case 'delete':
        if ($entity->getOwnerId() === $account->id()) {
          // Allow users to delete its own content.
          return AccessResult::allowed();
        }

        return AccessResult::allowedIfHasPermission($account, 'delete ilt entities');

      case 'score':
        $training = $entity->getTraining();
        if (isset($training) && $training->hasPermission('score ilt entities', $account)) {
          return AccessResult::allowed();
        }

        return AccessResult::allowedIfHasPermission($account, 'score ilt entities');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    // Allow view access if user is a group-level student manager.
    if ($account->hasPermission('manage group content in any group')) {
      return AccessResult::allowed();
    }

    return AccessResult::allowedIfHasPermission($account, 'add ilt entities');
  }

}
