<?php

namespace Drupal\opigno_learning_path;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for the opigno_latest_group_activity entity.
 *
 * @package Drupal\opigno_learning_path
 */
class LatestActivityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   *
   * Entity access control. checkAccess is called with the
   * $operation as defined in the routing.yml file.
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\opigno_learning_path\LatestActivityInterface $entity */
    // LatestActivity entity only used internally to track a latest activity
    // in trainings for the latest_active_trainings view.
    // Deny all edit operations to all users.
    // Allow view if the user is a member of the related training.
    if ($operation === 'view') {
      $training = $entity->getTraining();
      $is_member = isset($training) && $training->getMember($account);
      return AccessResult::allowedIf($is_member);
    }

    // Unknown operation, return neutral
    // (will be denied if all access control handlers return neutral).
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   *
   * Separate from the checkAccess because the entity does not yet exist, it
   * will be created during the 'add' process.
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    // Created only programmatically.
    return AccessResult::neutral();
  }

}
