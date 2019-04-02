<?php

namespace Drupal\opigno_learning_path\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\group\Entity\Group;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for the actions related to LP membership.
 */
class LearningPathMembershipController extends ControllerBase {

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * LearningPathMembershipController constructor.
   */
  public function __construct(
    Connection $connection,
    FormBuilderInterface $formBuilder
  ) {
    $this->connection = $connection;
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('form_builder')
    );
  }

  /**
   * Callback for opening the create members modal form.
   */
  public function createMembersFormModal() {
    $form = $this->formBuilder->getForm('Drupal\opigno_learning_path\Form\LearningPathCreateMemberForm');
    $command = new OpenModalDialogCommand($this->t('Create new members'), $form);

    $response = new AjaxResponse();
    $response->addCommand($command);
    return $response;
  }

  /**
   * Callback for opening the create members modal form.
   */
  public function createUserFormModal() {
    $form = $this->formBuilder->getForm('Drupal\opigno_learning_path\Form\LearningPathCreateUserForm');
    $command = new OpenModalDialogCommand($this->t('2/2 create a new user'), $form);

    $response = new AjaxResponse();
    $response->addCommand($command);
    return $response;
  }

  /**
   * Callback for opening the create members modal form.
   */
  public function createClassFormModal() {
    $form = $this->formBuilder->getForm('Drupal\opigno_learning_path\Form\LearningPathCreateClassForm');
    $command = new OpenModalDialogCommand($this->t('Create a new class'), $form);

    $response = new AjaxResponse();
    $response->addCommand($command);
    return $response;
  }

  /**
   * Returns response for the autocompletion.
   *
   * @param \Drupal\group\Entity\Group $group
   *   Group.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions.
   */
  public function addUserToTrainingAutocomplete(Group $group) {
    $matches = [];
    $string = \Drupal::request()->query->get('q');
    if ($string !== NULL) {
      $like_string = '%' . $this->connection->escapeLike($string) . '%';
      // Find users by email or name.
      $query = \Drupal::entityQuery('user')
        ->condition('uid', 0, '<>');
      $cond_group = $query
        ->orConditionGroup()
        ->condition('mail', $like_string, 'LIKE')
        ->condition('name', $like_string, 'LIKE');
      $query = $query
        ->condition($cond_group)
        ->sort('name')
        ->range(0, 20);

      $uids = $query->execute();
      $users = User::loadMultiple($uids);

      /** @var \Drupal\user\Entity\User $user */
      foreach ($users as $user) {
        $id = $user->id();
        $name = $user->getDisplayName();

        // Skip users that already members of the current group.
        if ($group->getMember($user) !== FALSE) {
          continue;
        }

        $matches[] = [
          'value' => "$name (User #$id)",
          'label' => "$name (User #$id)",
          'type' => 'user',
          'id' => 'user_' . $id,
        ];
      }

      // Find classes by name.
      $query = \Drupal::entityQuery('group')
        ->condition('type', 'opigno_class')
        ->condition('label', $like_string, 'LIKE')
        ->sort('label')
        ->range(0, 20);

      $gids = $query->execute();
      $groups = Group::loadMultiple($gids);

      /** @var \Drupal\group\Entity\Group $group */
      foreach ($groups as $group) {
        $id = $group->id();
        $name = $group->label();

        $matches[] = [
          'value' => "$name (Group #$id)",
          'label' => "$name (Group #$id)",
          'type' => 'group',
          'id' => 'class_' . $id,
        ];
      }
    }

    return new JsonResponse($matches);
  }

  /**
   * Returns response for the autocompletion.
   *
   * @param \Drupal\group\Entity\Group $group
   *   Group.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions.
   */
  public function addUserToClassAutocomplete(Group $group) {
    $matches = [];
    $string = \Drupal::request()->query->get('q');
    if ($string !== NULL) {
      $like_string = '%' . $this->connection->escapeLike($string) . '%';
      // Find users by email or name.
      $query = \Drupal::entityQuery('user')
        ->condition('uid', 0, '<>');
      $cond_group = $query
        ->orConditionGroup()
        ->condition('mail', $like_string, 'LIKE')
        ->condition('name', $like_string, 'LIKE');
      $query = $query
        ->condition($cond_group)
        ->sort('name')
        ->range(0, 20);

      $uids = $query->execute();
      $users = User::loadMultiple($uids);

      /** @var \Drupal\user\Entity\User $user */
      foreach ($users as $user) {
        $id = $user->id();
        $name = $user->getDisplayName();

        $matches[] = [
          'value' => "$name ($id)",
          'label' => "$name ($id)",
          'type' => 'user',
          'id' => 'user_' . $id,
        ];
      }
    }

    return new JsonResponse($matches);
  }

  /**
   * Returns users of current group for the autocompletion.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions.
   */
  public function findUsersInGroupAutocomplete() {
    $matches = [];
    $string = \Drupal::request()->query->get('q');

    if ($string) {
      $like_string = '%' . $this->connection->escapeLike($string) . '%';
      /** @var \Drupal\group\Entity\Group $curr_group */
      $curr_group = \Drupal::routeMatch()
        ->getParameter('group');

      // Find users by email or name.
      $query = \Drupal::entityQuery('user')
        ->condition('uid', 0, '<>');

      $cond_group = $query
        ->orConditionGroup()
        ->condition('mail', $like_string, 'LIKE')
        ->condition('name', $like_string, 'LIKE');

      $query = $query
        ->condition($cond_group)
        ->sort('name');

      $uids = $query->execute();
      $users = User::loadMultiple($uids);

      /** @var \Drupal\user\Entity\User $user */
      foreach ($users as $user) {
        $id = $user->id();
        $name = $user->getDisplayName();

        // Remove users that are not members of current group.
        if ($curr_group->getMember($user) === FALSE) {
          continue;
        }

        $matches[] = [
          'value' => "$name ($id)",
          'label' => $name,
          'id' => $id,
        ];
      }
    }

    return new JsonResponse($matches);
  }

  /**
   * Ajax callback used in opingo_learning_path_member_overview.js.
   *
   * Removes member from learning path.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Response.
   */
  public function deleteUser() {
    /** @var \Drupal\group\Entity\Group $group */
    $group = \Drupal::routeMatch()->getParameter('group');
    if (!isset($group)) {
      throw new NotFoundHttpException();
    }

    $uid = \Drupal::request()->query->get('user_id');
    $user = User::load($uid);
    if (!isset($user)) {
      throw new NotFoundHttpException();
    }

    $member = $group->getMember($user);
    if (!isset($member)) {
      throw new NotFoundHttpException();
    }

    $group->removeMember($user);
    return new JsonResponse();
  }

  /**
   * Ajax callback used in opingo_learning_path_member_overview.js.
   *
   * Removes class from learning path.
   */
  public function deleteClass() {
    /** @var \Drupal\group\Entity\Group $group */
    $group = \Drupal::routeMatch()->getParameter('group');

    $class_id = \Drupal::request()->query->get('class_id');
    $class = Group::load($class_id);

    if (!isset($group) || !isset($class)) {
      throw new NotFoundHttpException();
    }

    $content = $group->getContent();
    $account = $this->currentUser();

    /** @var \Drupal\group\Entity\GroupContentInterface $item */
    foreach ($content as $item) {
      $entity = $item->getEntity();
      $type = $entity->getEntityTypeId();
      $bundle = $entity->bundle();

      if ($type === 'group' && $bundle === 'opigno_class'
        && $entity->id() === $class->id()) {
        $item->delete();
        break;
      }
    }

    return new JsonResponse();
  }

  /**
   * Ajax callback used in opingo_learning_path_member_overview.js.
   *
   * Toggles user role in learning path.
   */
  public function toggleRole() {
    /** @var \Drupal\group\Entity\Group $group */
    $group = \Drupal::routeMatch()->getParameter('group');
    $query = \Drupal::request()->query;
    $uid = $query->get('uid');
    $user = User::load($uid);
    $role = $query->get('role');
    if (!isset($group) || !isset($user) || !isset($role)) {
      throw new NotFoundHttpException();
    }

    $member = $group->getMember($user);
    if (!isset($member)) {
      throw new NotFoundHttpException();
    }

    $group_content = $member->getGroupContent();
    $values = $group_content->get('group_roles')->getValue();
    $found = FALSE;

    foreach ($values as $index => $value) {
      if ($value['target_id'] === $role) {
        $found = TRUE;
        unset($values[$index]);
        break;
      }
    }

    if ($found === FALSE) {
      $values[] = ['target_id' => $role];
    }

    $group_content->set('group_roles', $values);
    $group_content->save();

    return new JsonResponse();
  }

  /**
   * Ajax callback used in opingo_learning_path_member_overview.js.
   *
   * Validates user role in learning path.
   */
  public function validate() {
    /** @var \Drupal\group\Entity\Group $group */
    $group = \Drupal::routeMatch()->getParameter('group');
    $gid = $group->id();

    $uid = \Drupal::request()->query->get('user_id');
    $user = User::load($uid);

    if (!isset($group) || !isset($user)) {
      throw new NotFoundHttpException();
    }

    $member = $group->getMember($user);
    if (!isset($member)) {
      throw new NotFoundHttpException();
    }

    $group_content = $member->getGroupContent();

    $query = \Drupal::database()
      ->merge('opigno_learning_path_group_user_status')
      ->key('mid', $group_content->id())
      ->insertFields([
        'mid' => $group_content->id(),
        'uid' => $uid,
        'gid' => $gid,
        'status' => 1,
        'message' => '',
      ])
      ->updateFields([
        'uid' => $uid,
        'gid' => $gid,
        'status' => 1,
        'message' => '',
      ]);
    $result = $query->execute();

    if ($result) {
      // Invalidate cache.
      $tags = $member->getCacheTags();
      \Drupal::service('cache_tags.invalidator')
        ->invalidateTags($tags);

      // Set notification.
      opigno_set_message($uid, t('Enrollment validated to a new training @name', [
        '@name' => $group->label(),
      ]));

      // Send email.
      $module = 'opigno_learning_path';
      $key = 'opigno_learning_path_membership_validated';
      $email = $user->getEmail();
      $lang = $user->getPreferredLangcode();
      $params = [];
      $params['subject'] = $this->t('Your membership to the training @training has been approved', [
        '@training' => $group->label(),
      ]);
      $site_config = \Drupal::config('system.site');
      $link = $group->toUrl()->setAbsolute()->toString();
      $args = [
        '@username' => $user->getDisplayName(),
        '@training' => $group->label(),
        ':link' => $link,
        '@link_text' => $link,
        '@platform' => $site_config->get('name'),
      ];
      $params['message'] = $this->t('Dear @username

Your membership to the training @training has been approved. You can now access this training at: <a href=":link">@link_text</a>

@platform', $args);

      \Drupal::service('plugin.manager.mail')
        ->mail($module, $key, $email, $lang, $params);
    }

    return new JsonResponse();
  }

}
