<?php

namespace Drupal\opigno_moxtra;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the opigno_moxtra_meeting entity.
 *
 * @see \Drupal\opigno_moxtra\Entity\Meeting.
 */
class MeetingAccessControlHandler extends EntityAccessControlHandler {

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
        $members = $entity->getMembersIds();
        if (!empty($members)) {
          // Deny access if the meeting has a members restriction
          // and the user is not a member of the meeting.
          if (!in_array($account->id(), $members)) {
            return AccessResult::forbidden();
          }
        }
        else {
          // Deny access if the meeting hasn't a members restricton
          // and the user is not a member of the related training.
          $training = $entity->getTraining();
          if (isset($training) && $training->getMember($account) === FALSE) {
            return AccessResult::forbidden();
          }
        }

        return AccessResult::allowedIfHasPermission($account, 'view meeting entities');

      case 'edit':
        if ($entity->getOwnerId() === $account->id()) {
          // Allow users to edit its own content.
          return AccessResult::allowed();
        }

        return AccessResult::allowedIfHasPermission($account, 'edit meeting entities');

      case 'delete':
        if ($entity->getOwnerId() === $account->id()) {
          // Allow users to delete its own content.
          return AccessResult::allowed();
        }

        return AccessResult::allowedIfHasPermission($account, 'delete meeting entities');
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

    return AccessResult::allowedIfHasPermission($account, 'add meeting entities');
  }

}
