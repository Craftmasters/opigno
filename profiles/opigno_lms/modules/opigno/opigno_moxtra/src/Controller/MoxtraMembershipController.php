<?php

namespace Drupal\opigno_moxtra\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for the actions related to moxtra membership.
 */
class MoxtraMembershipController extends ControllerBase {

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
   * MoxtraMembershipController constructor.
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
   * Returns users of current group for the autocompletion.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions.
   */
  public function addToGroupWorkspaceUserAutocomplete() {
    $matches = [];
    $string = \Drupal::request()->query->get('q');

    if ($string) {
      $like_string = '%' . $this->connection->escapeLike($string) . '%';
      /** @var \Drupal\group\Entity\Group $curr_group */
      $curr_group = \Drupal::routeMatch()
        ->getParameter('group');

      $workspace = \Drupal::routeMatch()
        ->getParameter('workspace');

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

      $current_members = $workspace->getMembersIds();

      /** @var \Drupal\user\Entity\User $user */
      foreach ($users as $user) {
        $id = $user->id();
        $name = $user->getDisplayName();

        // Remove users that are not members of current group
        // or already in workspace.
        if ($curr_group->getMember($user) === FALSE || in_array($id, $current_members) || !_opigno_moxtra_is_user_enabled($user)) {
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
   * Returns all users for the autocompletion.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions.
   */
  public function addToAllWorkspaceUserAutocomplete() {
    $matches = [];
    $string = \Drupal::request()->query->get('q');

    if ($string) {
      $like_string = '%' . $this->connection->escapeLike($string) . '%';

      $workspace = \Drupal::routeMatch()
        ->getParameter('workspace');

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

      $current_members = $workspace->getMembersIds();

      /** @var \Drupal\user\Entity\User $user */
      foreach ($users as $user) {
        $id = $user->id();
        $name = $user->getDisplayName();

        // Remove users that are already in workspace.
        if (in_array($id, $current_members) || $id == 0 || !_opigno_moxtra_is_user_enabled($user)) {
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

}
