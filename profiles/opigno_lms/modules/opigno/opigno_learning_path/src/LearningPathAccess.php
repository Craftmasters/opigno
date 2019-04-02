<?php

namespace Drupal\opigno_learning_path;

use Drupal\Component\Utility\Html;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\Group;
use Drupal\opigno_group_manager\Entity\OpignoGroupManagedContent;
use Drupal\opigno_group_manager\OpignoGroupContext;
use Drupal\user\Entity\User;
use Drupal\opigno_learning_path\Entity\LPManagedContent;

/**
 * Class LearningPathAccess.
 *
 * @package Drupal\opigno_learning_path
 */
class LearningPathAccess {

  /**
   * Gets group role.
   */
  public static function getGroupRoles(Group $group) {
    $type = array_shift($group->type->getValue());
    $properties = [
      'group_type' => $type['target_id'],
      'permissions_ui' => TRUE,
    ];
    return \Drupal::entityTypeManager()
      ->getStorage('group_role')
      ->loadByProperties($properties);
  }

  /**
   * Set visibility fields on Learning Path group save.
   */
  public static function setVisibilityFields(Group &$group) {
    if ($visibility = $group->field_learning_path_visibility->value) {
      switch ($visibility) {
        case 'public';
          $group->set('field_anonymous_visibility', 0);
          $group->set('field_requires_validation', 0);
          break;

        case 'private';
          $group->set('field_anonymous_visibility', 1);
          $group->set('field_requires_validation', 1);
          break;
      }
    }
  }

  /**
   * Returns Opigno course/module access flag.
   */
  public static function getGroupContentAccess(EntityInterface $entity, AccountInterface $account) {
    $access = TRUE;

    $contentID = OpignoGroupContext::getCurrentGroupContentId();
    if (!empty($contentID)) {
      $contents = OpignoGroupManagedContent::loadByProperties(['id' => $contentID]);

      if ($contents = array_shift($contents)) {
        $learningPath = $contents->getGroup();
        if (isset($learningPath)
          && $learningPath->getEntityTypeId() === 'learning_path'
          && !$learningPath->access('view', $account)) {
          $access = FALSE;
        }
      }
    }

    return $access;
  }

  /**
   * Returns group user access flag in validation condition.
   */
  public static function statusGroupValidation(Group $group, AccountInterface $account) {
    $access = TRUE;
    if ($membership = $group->getMember($account)) {
      $visibility = $group->field_learning_path_visibility->value;
      $validation = $group->field_requires_validation->value;
      $status = LearningPathAccess::getMembershipStatus($membership->getGroupContent()->id());
      $required_trainings = self::hasUncompletedRequiredTrainings($group, $account);

      if ($visibility === 'semiprivate' && $validation) {
        // For semi-private groups with validation status should be 'Active'.
        if ($status != 1) {
          $access = FALSE;
        }
      }
      // If user unfinished required trainings.
      elseif ($required_trainings) {
        $access = FALSE;
      }
      else {
        // For another groups status should be not 'Blocked'.
        if ($status == 3) {
          $access = FALSE;
        }
      }
    }

    return $access;
  }

  /**
   * Sets roles on membership presave.
   *
   * @param \Drupal\Core\Entity\EntityInterface $membership
   *   Membership object.
   */
  public static function membershipPreSave(EntityInterface &$membership) {
    if ($membership->isNew()) {
      /** @var \Drupal\group\Entity\GroupContentInterface $membership */
      $group = $membership->getGroup();
      $group_is_semiprivate = $group->hasField('field_learning_path_visibility')
        && $group->get('field_learning_path_visibility')->getValue() === 'semiprivate';
      $group_requires_validation = $group->hasField('field_requires_validation')
        && $group->get('field_requires_validation')->getValue();
      $user_join = $membership->getEntity()->id() == $membership->getOwnerId();
      if (!($group_is_semiprivate && $group_requires_validation) && $user_join) {
        // Add 'student' role if user is self-join group
        // and group is not semiprivate with validation.
        $roles = $membership->get('group_roles')->getValue();
        if (!in_array('learning_path-student', $roles)) {
          $roles[] = 'learning_path-student';
          $membership->set('group_roles', $roles);
        }
      }
    }

    LearningPathAccess::setLearningPathCourseMember($membership, 'update');
  }

  /**
   * Sets Learning Path content course member.
   */
  public static function setLearningPathCourseMember(EntityInterface $membership, $mode) {
    // Get LP content courses.
    $group = $membership->getGroup();
    $courses = LPManagedContent::loadByProperties([
      'learning_path_id' => $group->id(),
      'lp_content_type_id' => 'ContentTypeCourse',
    ]);

    // Update courses members.
    if ($courses) {
      foreach ($courses as $course) {
        $group = Group::load($course->entity_id->value);
        $account = $membership->getEntity();

        if ($group->getMember($account) && $mode == 'delete') {
          LearningPathAccess::deleteUserStatus($group->getMember($account)->getGroupContent());
          $group->removeMember($account);
        }

        if ($mode == 'update') {
          if (!$group->getMember($account)) {
            $group->addMember($account);
          }
          LearningPathAccess::mergeUserStatus($group->getMember($account)->getGroupContent());
        }
      }
    }
  }

  /**
   * Returns user membership statuses array.
   */
  public static function getMembershipStatusesArray() {
    return [
      '1' => t('Active'),
      '2' => t('Pending'),
      '3' => t('Blocked'),
    ];
  }

  /**
   * Returns user membership status.
   */
  public static function getMembershipStatus($mid, $as_string = FALSE) {
    $query = \Drupal::database()->select('opigno_learning_path_group_user_status', 'us')
      ->fields('us', ['status'])
      ->condition('mid', $mid);
    $result = $query->execute()->fetchField();

    if ($as_string) {
      $statuses = LearningPathAccess::getMembershipStatusesArray();
      $result = $statuses[$result];
    }

    return $result;
  }

  /**
   * Returns user subscription events.
   */
  public static function getUserMemberEvents() {
    return [
      '1' => 'subscribed',
      '2' => 'approval',
      '3' => 'blocked',
    ];
  }

  /**
   * Merges Learning Path group user status.
   *
   * @param \Drupal\Core\Entity\EntityInterface $membership
   *   Membership object.
   *
   * @throws \Exception
   */
  public static function mergeUserStatus(EntityInterface $membership) {
    $message = \Drupal::request()->get('user_message');
    $message = !empty($message) ? Html::escape($message) : '';
    /** @var \Drupal\group\Entity\GroupContentInterface $membership */
    $group = $membership->getGroup();
    $uid = $membership->getEntity()->id();
    $gid = $group->id();
    $visibility = $group->field_learning_path_visibility->value;
    $validation = $group->field_requires_validation->value;
    $is_new = $membership->isNew();
    $user_join = $membership->getEntity()->id() == $membership->getOwnerId();
    if ($is_new && $user_join) {
      $status = in_array($visibility, ['semiprivate', 'private'])
        && $validation ? 2 : 1;
    }
    elseif (!empty($_SESSION['opigno_learning_path_group_membership_edit']['user_status'])) {
      $status = $_SESSION['opigno_learning_path_group_membership_edit']['user_status'];
      unset($_SESSION['opigno_learning_path_group_membership_edit']);
    }
    else {
      $status = 1;
    }

    $query = \Drupal::database()
      ->merge('opigno_learning_path_group_user_status')
      ->key('mid', $membership->id())
      ->insertFields([
        'mid' => $membership->id(),
        'uid' => $uid,
        'gid' => $gid,
        'status' => $status,
        'message' => $message,
      ])
      ->updateFields([
        'uid' => $uid,
        'gid' => $gid,
        'status' => $status,
        'message' => $message,
      ]);
    $result = $query->execute();

    if ($result) {
      if ($group->bundle() === 'learning_path') {
        $token = \Drupal::moduleHandler()->moduleExists('token') ? TRUE : FALSE;
        LearningPathAccess::notifyUsersByMail($group, $uid, $status, $token);

        if ($membership->isNew()
          && $membership->getEntity()->id() == $membership->getOwnerId()) {
          $messenger = \Drupal::messenger();
          if ($status == 2) {
            $messenger->addMessage(t('Thanks ! The subscription to that training requires the validation of an administrator or a teacher. 
              You will receive an email as soon as your subscription has been validated.')
            );
          }
        }
      }
    }
  }

  /**
   * Deletes Learning Path group user status.
   */
  public static function deleteUserStatus(EntityInterface $membership) {
    $query = \Drupal::database()->delete('opigno_learning_path_group_user_status')
      ->condition('mid', $membership->id());
    $result = $query->execute();
    if ($result) {
      /** @var \Drupal\group\Entity\GroupContentInterface $membership */
      $entity = $membership->getEntity();
      $group = $membership->getGroup();
      if (isset($entity) && isset($group)
        && $group->bundle() == 'learning_path') {
        $uid = $entity->id();
        $token = \Drupal::moduleHandler()->moduleExists('token');
        LearningPathAccess::notifyUsersByMail($group, $uid, NULL, $token);
      }
    }
  }

  /**
   * Prepares and sends emails to users.
   */
  public static function notifyUsersByMail(Group $group, $uid, $status, $token = FALSE) {
    $config = \Drupal::config('opigno_learning_path.learning_path_settings');
    $send_to_admins = $config->get('opigno_learning_path_notify_admin');
    $send_to_users = $config->get('opigno_learning_path_notify_users');

    if ($send_to_admins || $send_to_users) {
      $account = User::load($uid);
      $events = LearningPathAccess::getUserMemberEvents();
      $subject = \Drupal::config('system.site')->get('name') . ' ' . t('subscription');
      $host = \Drupal::request()->getSchemeAndHttpHost();

      if ($status) {
        $membership = $group->getMember($account);
        $statusName = LearningPathAccess::getMembershipStatus($membership->getGroupContent()->id(), TRUE);

        $roles = $membership->getRoles();
        $roles_array = [];
        if (!empty($roles)) {
          foreach ($roles as $role) {
            $roles_array[] = $role->label();
          }
        }
      }
      else {
        $status = 1;
        $statusName = t('Unsubscribed');
      }

      $roles = !empty($roles_array) ? implode(', ', $roles_array) : '';

      if ($send_to_admins) {
        $mails = $config->get('opigno_learning_path_notify_admin_mails');

        if (!empty($mails)) {
          $message = $config->get('opigno_learning_path_notify_admin_user_' . $events[$status]);

          $params = [
            'group' => $group,
            'account' => $account,
            'link' => $host . '/group/' . $group->id() . '/members',
            'roles' => $roles,
            'status' => $statusName,
          ];

          LearningPathAccess::replaceGroupUserTokens($message, $params, $token);

          $mails = explode("\r\n", $mails);
          foreach ($mails as $to) {
            if (!empty($to) && !empty($message)) {
              LearningPathAccess::sendMail($to, $subject, $message, $params);
            }
          }
        }
      }

      if ($send_to_users) {
        $to = $account->getEmail();
        $message = $config->get('opigno_learning_path_notify_user_user_' . $events[$status]);

        $params = [
          'group' => $group,
          'account' => $account,
          'link' => $host . '/group/' . $group->id(),
          'roles' => $roles,
          'status' => $statusName,
        ];

        LearningPathAccess::replaceGroupUserTokens($message, $params, $token);

        if (!empty($to) && !empty($message)) {
          LearningPathAccess::sendMail($to, $subject, $message, $params);
        }
      }
    }
  }

  /**
   * Replaces tokens.
   */
  public static function replaceGroupUserTokens(&$text, $params, $token) {
    if ($token) {
      $text = \Drupal::token()->replace($text);
    }

    $text = str_replace([
      '[user]',
      '[group]',
      '[link]',
      '[user-role]',
      '[user-status]',
    ], [
      $params['account']->getAccountName(),
      $params['group']->label(),
      $params['link'],
      $params['roles'],
      $params['status'],
    ], $text);
  }

  /**
   * Sends mail.
   */
  public static function sendMail($to, $subject, $message, $params = []) {
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $mailManager = \Drupal::service('plugin.manager.mail');
    $module = 'opigno_learning_path';
    $key = 'opigno_learning_path_user_subscribe';
    $params['subject'] = $subject;
    $params['message'] = $message;
    $send = TRUE;
    $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);

    return $result['result'];
  }

  /**
   * Creates database table for Learning Path group user statuses.
   */
  public static function createUserStatusTable() {
    $schema = Database::getConnection()->schema();
    if (!$schema->tableExists('opigno_learning_path_group_user_status')) {
      $spec = [
        'description' => 'Learning Path group user statuses',
        'fields' => [
          'id' => [
            'type' => 'serial',
            'not null' => TRUE,
          ],
          'mid' => [
            'description' => 'Membership ID',
            'type' => 'int',
            'unsigned' => TRUE,
            'not null' => TRUE,
          ],
          'uid' => [
            'description' => 'User ID',
            'type' => 'int',
            'unsigned' => TRUE,
            'not null' => TRUE,
          ],
          'gid' => [
            'description' => 'Group ID',
            'type' => 'int',
            'unsigned' => TRUE,
            'not null' => TRUE,
          ],
          'status' => [
            'description' => 'Member status',
            'type' => 'int',
            'unsigned' => TRUE,
            'not null' => TRUE,
            'default' => 0,
          ],
          'message' => [
            'description' => 'User message',
            'type' => 'varchar',
            'length' => 200,
            'not null' => TRUE,
            'default' => '',
          ],
        ],
        'primary key' => ['id'],
        'indexes' => [
          'mid' => ['mid'],
          'gid' => ['gid'],
          'uid' => ['uid'],
        ],
        'foreign keys' => [
          'group_content' => ['mid' => 'id'],
          'users' => ['uid' => 'uid'],
          'groups' => ['gid' => 'id'],
        ],
      ];
      $schema->createTable('opigno_learning_path_group_user_status', $spec);
    }
  }

  /**
   * Returns uncompleted required trainings flag.
   *
   * @param \Drupal\group\Entity\Group $group
   *   Group.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User account.
   *
   * @return bool|array
   *   Return FALSE or Array with unfinished training.
   */
  public static function hasUncompletedRequiredTrainings(Group $group, AccountInterface $account) {
    $trainings = $group->get('field_required_trainings')->getValue();
    if (!$trainings) {
      return FALSE;
    }

    $database = \Drupal::database();
    // Array of uncompleted required trainings.
    $uncompleted = [];
    if (is_array($trainings)) {
      for ($i = 0; $i < count($trainings); $i++) {
        $gid = $trainings[$i]['target_id'];
        // Check that training is completed.
        $is_completed = $database->select('opigno_learning_path_achievements', 'a')
          ->fields('a')
          ->condition('uid', $account->id())
          ->condition('gid', $gid)
          ->condition('status', 'completed')
          ->execute()->fetchObject();
        if (!$is_completed) {
          array_push($uncompleted, $gid);
        }
      }
    }

    return $uncompleted;
  }

  /**
   * Returns user course/class access flag.
   *
   * @param \Drupal\group\Entity\Group $group
   *   Group.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User account.
   *
   * @return bool
   *   Check if user has access to Class or Course.
   */
  public static function checkCourseClassAccess(Group $group, AccountInterface $account) {
    $access = FALSE;
    $type = $group->getGroupType()->id();
    // Check for class.
    if ($type == 'opigno_class') {
      $is_member = $group->getMember($account);
      $access = $is_member ? TRUE : FALSE;
    }
    // Check for course.
    elseif ($type == 'opigno_course') {
      $database = Database::getConnection();
      // Get all trainings where user is a member and course is a content.
      $query = $database->select('opigno_group_content', 'ogc');
      $query->leftJoin('group_content_field_data', 'gc', 'ogc.group_id = gc.gid');
      $results = $query->fields('ogc', ['group_id'])
        ->condition('ogc.entity_id', $group->id())
        ->condition('ogc.group_content_type_id', 'ContentTypeCourse')
        ->condition('gc.type', 'learning_path-group_membership')
        ->condition('gc.entity_id', $account->id())
        ->execute()->fetchAll();

      $access = $results ? TRUE : FALSE;
    }

    return $access;
  }

}
