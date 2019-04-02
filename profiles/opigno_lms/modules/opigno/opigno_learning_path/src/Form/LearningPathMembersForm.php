<?php

namespace Drupal\opigno_learning_path\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\opigno_learning_path\LearningPathAccess;
use Drupal\views\Views;

/**
 * Group overview form.
 */
class LearningPathMembersForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'learning_path_members_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $student_manager_role = 'learning_path-user_manager';
    $content_manager_role = 'learning_path-content_manager';
    $class_manager_role = 'opigno_class-class_manager';

    /** @var \Drupal\group\Entity\Group $group */
    $group = \Drupal::routeMatch()->getParameter('group');
    $group_bundle = $group->bundle();

    // If not a learning_path or class, returns
    // default '/group/{group}/members' view.
    if (!in_array($group_bundle, [
      'opigno_class',
      'learning_path',
    ])) {
      $view = Views::getView('group_members');

      if (!$view || !$view->access('page_1')) {
        return $form;
      }

      $form[] = [
        '#type' => 'view',
        '#name' => 'group_members',
        '#display_id' => 'page_1',
        '#title' => $view->getTitle(),
        '#arguments' => [
          $group->id(),
        ],
      ];

      return $form;
    }

    $content = $group->getContent();
    $users = [];
    $classes = [];

    /** @var \Drupal\group\Entity\GroupContentInterface $item */
    foreach ($content as $item) {
      $entity = $item->getEntity();
      if ($entity === NULL) {
        continue;
      }

      $type = $entity->getEntityTypeId();
      $bundle = $entity->bundle();

      if ($type === 'user') {
        $users[$entity->id()] = [
          'group content' => $item,
          'entity' => $entity,
        ];
      }
      elseif ($type === 'group' && $bundle === 'opigno_class') {
        $classes[$entity->id()] = [
          'group content' => $item,
          'entity' => $entity,
        ];
      }
    }

    $individual_members = $users;

    if ($group_bundle != 'opigno_class') {
      $form[] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['class'],
        ],
        'search' => [
          '#type' => 'textfield',
          '#autocomplete_route_name' => 'opigno_learning_path.membership.find_users_in_group_autocomplete',
          '#autocomplete_route_parameters' => [
            'group' => $group->id(),
          ],
          '#placeholder' => t('Search a user'),
          '#attributes' => [
            'id' => 'class_members_search',
            'class' => [
              'class_members_search',
            ],
          ],
        ],
      ];
    }

    foreach ($classes as $class) {
      $members = array_filter($users, function ($user) use ($class) {
        /** @var \Drupal\group\Entity\Group $class_entity */
        $class_entity = $class['entity'];
        return $class_entity->getMember($user['entity']) !== FALSE;
      });
      $member_count = count($members);

      $individual_members = array_diff_key($individual_members, $members);

      $rows = array_map(function ($member_info) {
        /** @var \Drupal\user\Entity\User $user_entity */
        $user_entity = $member_info['entity'];
        return [
          'class' => 'class_members_row',
          'id' => 'student_' . $user_entity->id(),
          'data' => [
            $user_entity->getDisplayName(),
          ],
        ];
      }, $members);

      /** @var \Drupal\group\Entity\GroupContentInterface $class_group_content */
      $class_group_content = $class['group content'];
      $member_since_value = $class_group_content
        ->get('created')
        ->getValue()[0]['value'];
      $member_since = date('d/m/Y', $member_since_value);

      /** @var \Drupal\group\Entity\Group $class_entity */
      $class_entity = $class['entity'];

      $form[] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['class'],
        ],
        'class_delete' => [
          '#type' => 'submit',
          '#value' => $this->t('&times;'),
          '#submit' => [],
          '#attributes' => [
            'id' => 'class_delete_' . $class_entity->id(),
            'class' => ['class_delete'],
          ],
        ],
        'title' => [
          '#type' => 'html_tag',
          '#tag' => 'h2',
          '#value' => $class_entity->label(),
          '#attributes' => [
            'class' => ['class_title'],
          ],
        ],
        'member_since' => [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $this->t('In the learning path since : @date', [
            '@date' => $member_since,
          ]),
          '#attributes' => [
            'class' => ['class_member_since'],
          ],
        ],
        'members' => [
          '#type' => 'table',
          '#attributes' => [
            'class' => ['class_members'],
          ],
          '#header' => [
            $this->t('<span class="class_members_count">@count</span> Members', [
              '@count' => $member_count,
            ]),
          ],
          '#rows' => $rows,
        ],
        'hide' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['class_hide'],
          ],
          [
            '#type' => 'html_tag',
            '#tag' => 'span',
            '#value' => $this->t('Hide'),
            '#attributes' => [
              'class' => ['class_hide_text'],
            ],
          ],
        ],
        'show' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['class_show'],
          ],
          [
            '#type' => 'html_tag',
            '#tag' => 'span',
            '#value' => $this->t('Show'),
            '#attributes' => [
              'class' => ['class_show_text'],
            ],
          ],
        ],
      ];
    }

    if (!empty($individual_members)) {
      $rows = array_map(function ($member_info) use ($group, $student_manager_role, $content_manager_role, $class_manager_role) {
        /** @var \Drupal\group\Entity\GroupContentInterface $user_group_content */
        $user_group_content = $member_info['group content'];
        $member_since_value = $user_group_content
          ->get('created')
          ->getValue()[0]['value'];

        /** @var \Drupal\user\Entity\User $user_entity */
        $user_entity = $member_info['entity'];
        $member = $group->getMember($user_entity);
        $roles = $member->getRoles();
        $has_sm_role = isset($roles[$student_manager_role]);
        $has_cm_role = isset($roles[$content_manager_role]);
        $has_class_manager_role = isset($roles[$class_manager_role]);
        $member_pending = FALSE;

        if ($group->hasField('field_learning_path_visibility')) {
          $visibility = $group->field_learning_path_visibility->value;
          $validation = $group->field_requires_validation->value;
          $member_pending = $visibility === 'semiprivate' && $validation
            && !LearningPathAccess::statusGroupValidation($group, $user_entity);
        }

        if ($member_pending) {
          $text = $this->t('Waiting for validation');
          $member_since = ['#markup' => $text];
        }
        else {
          $member_since = date('d/m/Y', $member_since_value);
          $member_since = ['#markup' => $member_since];
        }

        $gid = $group->id();
        $cid = $user_group_content->id();
        $delete_url = Url::fromUri("internal:/group/$gid/content/$cid/delete");

        return [
          'class' => 'class_members_row',
          'id' => 'individual_' . $user_entity->id(),
          'data' => [
            $user_entity->getDisplayName(),
            [
              'class' => 'class_member_since' . ($member_pending
                ? ' class_member_since_pending' : ''),
              'id' => 'class_member_validate_' . $user_entity->id(),
              'data' => $member_since,
            ],
            [
              'data' => [
                '#type' => 'submit',
                '#value' => $this->t('Toggle Student Manager'),
                '#submit' => [],
                '#attributes' => [
                  'id' => 'class_member_toggle_sm_' . $user_entity->id(),
                  'class' => array_merge(['class_member_toggle_sm'],
                    $has_sm_role ? ['class_member_toggle_sm_active'] : []
                  ),
                ],
              ],
            ],
            [
              'data' => [
                '#type' => 'submit',
                '#value' => $this->t('Toggle Content Manager'),
                '#submit' => [],
                '#attributes' => [
                  'id' => 'class_member_toggle_cm_' . $user_entity->id(),
                  'class' => array_merge(['class_member_toggle_cm'],
                    $has_cm_role ? ['class_member_toggle_cm_active'] : []),
                ],
              ],
            ],
            [
              'data' => [
                '#type' => 'submit',
                '#value' => $this->t('Toggle Class Manager'),
                '#submit' => [],
                '#attributes' => [
                  'id' => 'class_member_toggle_class_manager_' . $user_entity->id(),
                  'class' => array_merge(['class_member_toggle_class_manager'],
                    $has_class_manager_role ? ['class_member_toggle_class_manager_active'] : []),
                ],
              ],
            ],
            [
              'class' => 'class_member_delete_wrapper',
              'data' => [
                '#type' => 'link',
                '#title' => ['data' => ['#markup' => '&times;']],
                '#url' => $delete_url,
                '#attributes' => [
                  'id' => 'class_member_delete_' . $user_entity->id(),
                  'class' => ['class_member_delete'],
                ],
              ],
            ],
          ],
        ];
      }, $individual_members);
      $member_count = count($individual_members);

      $form[] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['class'],
        ],
        'title' => [
          '#type' => 'html_tag',
          '#tag' => 'h2',
          '#value' => $this->t('Learners not in a class'),
          '#attributes' => [
            'class' => ['class_title'],
          ],
        ],
        'search' => [
          '#type' => 'textfield',
          '#autocomplete_route_name' => 'opigno_learning_path.membership.find_users_in_group_autocomplete',
          '#autocomplete_route_parameters' => [
            'group' => $group->id(),
          ],
          '#placeholder' => t('Search a user'),
          '#attributes' => [
            'id' => 'individual_members_search',
            'class' => [
              'class_members_search',
            ],
          ],
        ],
        'members' => [
          '#type' => 'table',
          '#attributes' => [
            'class' => ['class_members'],
          ],
          '#header' => [
            [
              'class' => 'class_members_header_member_count',
              'data' => $this->t('<span class="class_members_count">@count</span> Members', [
                '@count' => $member_count,
              ]),
            ],
            [
              'class' => 'class_members_header_member_since',
              'data' => $this->t('Enrolled Since'),
            ],
            $this->t('Student Manager'),
            $this->t('Content Manager'),
            $this->t('Class Manager'),
            '',
          ],
          '#rows' => $rows,
        ],
        'hide' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['class_hide'],
          ],
          [
            '#type' => 'html_tag',
            '#tag' => 'span',
            '#value' => $this->t('Hide'),
            '#attributes' => [
              'class' => ['class_hide_text'],
            ],
          ],
        ],
        'show' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['class_show'],
          ],
          [
            '#type' => 'html_tag',
            '#tag' => 'span',
            '#value' => $this->t('Show'),
            '#attributes' => [
              'class' => ['class_show_text'],
            ],
          ],
        ],
      ];
    }

    // Remove not needed roles for classes.
    if ($group_bundle == 'opigno_class') {
      unset($form[0]['title']);
      foreach ($form[0]['members']['#rows'] as $key => $row) {
        unset($form[0]['members']['#rows'][$key]['data'][2]);
        unset($form[0]['members']['#rows'][$key]['data'][3]);
      }

      unset($form[0]['members']['#header'][2]);
      unset($form[0]['members']['#header'][3]);
    }
    // Remove not needed roles for learning paths.
    elseif ($group_bundle == 'learning_path') {
      $form_array_keys = array_keys($form);
      $last_key = end($form_array_keys);
      foreach ($form[$last_key]['members']['#rows'] as $key => $row) {
        unset($form[$last_key]['members']['#rows'][$key]['data'][4]);
      }
      unset($form[$last_key]['members']['#header'][4]);
    }

    $form['#attached']['library'][] = 'opigno_learning_path/member_overview';
    $form['#attached']['library'][] = 'opigno_learning_path/member_add';
    $form['#attached']['drupalSettings']['opigno_learning_path']['gid'] = $group->id();
    $form['#attached']['drupalSettings']['opigno_learning_path']['student_manager_role'] = $student_manager_role;
    $form['#attached']['drupalSettings']['opigno_learning_path']['content_manager_role'] = $content_manager_role;
    $form['#attached']['drupalSettings']['opigno_learning_path']['class_manager_role'] = $class_manager_role;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
