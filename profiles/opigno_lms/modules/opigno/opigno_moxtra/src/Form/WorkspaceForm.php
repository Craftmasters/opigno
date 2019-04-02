<?php

namespace Drupal\opigno_moxtra\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\Group;
use Drupal\opigno_moxtra\MoxtraServiceInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for creating/editing a opigno_moxtra_workspace entity.
 */
class WorkspaceForm extends ContentEntityForm {

  /**
   * Moxtra service.
   *
   * @var \Drupal\opigno_moxtra\MoxtraServiceInterface
   */
  protected $moxtraService;

  /**
   * Creates a WorkspaceForm object.
   */
  public function __construct(
    EntityManagerInterface $entity_manager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    TimeInterface $time,
    MoxtraServiceInterface $moxtra_service
  ) {
    parent::__construct(
      $entity_manager,
      $entity_type_bundle_info,
      $time
    );
    $this->moxtraService = $moxtra_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('opigno_moxtra.moxtra_api')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'opigno_moxtra_create_workspace_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $training = NULL) {
    /** @var \Drupal\opigno_moxtra\WorkspaceInterface $entity */
    $entity = $this->entity;

    $form = parent::buildForm($form, $form_state);

    // Get group of workspace.
    $workspace_id = $entity->id();
    $query = \Drupal::service('entity.query')
      ->get('group')
      ->condition('field_workspace', $workspace_id);
    $result = $query->execute();
    if (!empty($result)) {
      $group_id = array_values($result)[0];
    }
    else {
      $group_id = NULL;
    }

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Collaborative workspace name'),
      '#default_value' => $entity->label(),
      '#required' => TRUE,
    ];

    if (!$entity->isNew()) {
      $form['binder_id'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Binder ID'),
        '#default_value' => $entity->getBinderId(),
        '#attributes' => ['disabled' => TRUE],
      ];

      $current_members = $entity->getMembers();

      $default_options = [];
      foreach ($current_members as $current_member) {
        $default_options[$current_member->id()] = $current_member->getAccountName();
      }

      $available_options = $default_options;

      if (!empty($group_id)) {
        /** @var \Drupal\group\Entity\Group $group */
        $group = entity_load('group', $group_id);
        $group_members = $group->getMembers();

        $autocomplete_route_name = 'opigno_moxtra.membership.add_user_to_group_collaborative_workspace';
        $autocomplete_route_parameters = [
          'group' => $group->id(),
          'workspace' => $workspace_id,
        ];

        $form['auto_register'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Automatically register all users of that training'),
          '#default_value' => $entity->getAutoRegister(),
        ];

        foreach ($group_members as $group_member) {
          $group_user = $group_member->getUser();
          if (_opigno_moxtra_is_user_enabled($group_user)) {
            $available_options[$group_user->id()] = $group_user->getAccountName();
          }
        }

        $states = [
          'invisible' => [
            ':input[name="auto_register"]' => ['checked' => TRUE],
          ],
        ];
      }
      else {
        $autocomplete_route_name = 'opigno_moxtra.membership.add_user_to_all_collaborative_workspace';
        $autocomplete_route_parameters = ['workspace' => $workspace_id];
        $users = User::loadMultiple();

        foreach ($users as $user) {
          if (_opigno_moxtra_is_user_enabled($user)) {
            $available_options[$user->id()] = $user->getAccountName();
          }
        }

        // Remove Anonymous user.
        unset($available_options[0]);

        $states = [];
      }

      $form['training_users_autocomplete'] = [
        '#type' => 'textfield',
        '#title' => t('Find existing users of this group'),
        '#autocomplete_route_name' => $autocomplete_route_name,
        '#autocomplete_route_parameters' => $autocomplete_route_parameters,
        '#placeholder' => t('Enter a user’s name or email'),
        '#attributes' => [
          'id' => 'training_users_autocomplete',
        ],
        '#states' => $states,
      ];

      $form['training_users'] = [
        '#type' => 'multiselect',
        '#title' => $this->t('Select users:'),
        '#attributes' => [
          'id' => 'training_users',
          'class' => [
            'row',
          ],
        ],
        '#options' => $available_options,
        '#default_value' => array_keys($default_options),
        '#validated' => TRUE,
        '#process' => [
          ['Drupal\multiselect\Element\MultiSelect', 'processSelect'],
        ],
        '#states' => $states,
      ];

      $form['#attached']['library'][] = 'opigno_learning_path/member_add';
    }

    if ($training) {
      $form_state->set('training', $training);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\opigno_moxtra\WorkspaceInterface $entity */
    $entity = $this->entity;
    $is_new = FALSE;

    $storage = $form_state->getStorage();
    if ($entity->isNew() && $entity->hasField('training') && !empty($storage['training'])) {
      // Set Workspace related Training if been creating from training page.
      $entity->set('training', [['target_id' => $storage['training']]]);
    }

    $binder_id = $entity->getBinderId();
    if (empty($binder_id)) {
      // Create binder in the Moxtra.
      $user_id = $this->currentUser()->id();
      $name = $form_state->getValue('name');
      $response = $this->moxtraService->createWorkspace($user_id, $name);
      $entity->setBinderId($response['data']['id']);
      $is_new = TRUE;
    }

    $auto_register = $form_state->getValue('auto_register');
    // Set auto_register false for workspaces not in groups.
    if (empty($auto_register) && !$is_new) {
      $entity->setAutoRegister(0);
      $auto_register = 0;
    }

    // Update list of members if auto registration of members is switched off.
    if (!$is_new && $auto_register == 0) {
      $new_list_of_members = $form_state->getValue('training_users');
      $old_list_of_members = $entity->getMembersIds();
      $owner_id = $entity->getOwnerId();

      // Check if owner of the group tried to delete himself.
      // If yes then add him back to the members.
      // Owner of the group must be in the members list.
      if (!in_array($owner_id, $new_list_of_members)) {
        $new_list_of_members[$owner_id] = $owner_id;
      }

      // Set updated list of members.
      $entity->setMembers($new_list_of_members);

      // Add users to workspace.
      foreach ($new_list_of_members as $member_id) {
        if (!in_array($member_id, $old_list_of_members)) {
          // Add user to the binder in the Moxtra.
          $moxtra_api = _opigno_moxtra_get_moxtra_api();
          $moxtra_api->addUsersToWorkspace($owner_id, $binder_id, [$member_id]);
        }
      }

      // Remove users from workspace.
      foreach ($old_list_of_members as $member_id) {
        if (!in_array($member_id, $new_list_of_members)) {
          // Add user to the binder in the Moxtra.
          $moxtra_api = _opigno_moxtra_get_moxtra_api();
          $moxtra_api->removeUserFromWorkspace($owner_id, $binder_id, $member_id);
        }
      }
    }
    elseif (!$is_new && $auto_register == 1) {
      // Add all members of group with access to the Moxtra into the workspace.
      $workspace_id = $entity->id();
      $query = \Drupal::service('entity.query')
        ->get('group')
        ->condition('field_workspace', $workspace_id);
      $result = $query->execute();
      $group_id = array_values($result)[0];
      $group = entity_load('group', $group_id);
      $group_members = $group->getMembers();
      $current_workspace_members = $entity->getMembersIds();

      // Find users which needs to add into workspace.
      foreach ($group_members as $group_member) {
        $group_user = $group_member->getUser();
        if (_opigno_moxtra_is_user_enabled($group_user)) {
          $new_list_of_members[$group_user->id()] = $group_user->id();
          $owner_id = $entity->getOwnerId();
          if (!in_array($group_user->id(), $current_workspace_members)) {
            $moxtra_api = _opigno_moxtra_get_moxtra_api();
            $moxtra_api->addUsersToWorkspace($owner_id, $binder_id, [$group_user->id()]);
          }
        }
      }
      $entity->setMembers($new_list_of_members);
    }

    // Save entity.
    $status = parent::save($form, $form_state);

    // Set status message.
    $workspace_link = $entity->toLink()->toString();
    if ($status == SAVED_UPDATED) {
      $message = $this->t('The Collaborative Workspace %workspace has been updated.', [
        '%workspace' => $workspace_link,
      ]);
    }
    else {
      $message = $this->t('The Collaborative Workspace %workspace has been created.', [
        '%workspace' => $workspace_link,
      ]);
    }
    $this->messenger()->addMessage($message);

    // Set redirect.
    $form_state->setRedirect('opigno_moxtra.workspace', [
      'opigno_moxtra_workspace' => $entity->id(),
    ]);
    return $status;
  }

}
