<?php

namespace Drupal\opigno_learning_path;

use Drupal\Core\Access\AccessException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for the learning_path_result entity.
 *
 * @package Drupal\opigno_learning_path
 */
class LPResultAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   *
   * Entity access control. checkAccess is called with the
   * $operation as defined in the routing.yml file.
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\opigno_learning_path\Entity\LPResult $entity */
    /** @var \Drupal\group\Entity\Group $group */
    $group = $entity->getLearningPath();
    $is_owner = $entity->getUserId() == $account->id();
    if (empty($group) || !is_object($group)) {
      return AccessResult::neutral();
    }

    if ($group->getGroupType()->id() !== 'learning_path') {
      throw new AccessException('LPResult associated with wrong group type!');
    }

    switch ($operation) {
      case 'view':
        // Allow user to view their own results.
        return AccessResult::allowedIf($is_owner
          && $group->hasPermission('view own results', $account)
          || $group->hasPermission('view all results', $account));

      case 'edit':
        return AccessResult::allowedIf($is_owner
          && $group->hasPermission('edit own results', $account)
          || $group->hasPermission('edit all results', $account));

      case 'delete':
        return AccessResult::allowedIf($is_owner
          && $group->hasPermission('delete own results', $account)
          || $group->hasPermission('delete all results', $account));
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
