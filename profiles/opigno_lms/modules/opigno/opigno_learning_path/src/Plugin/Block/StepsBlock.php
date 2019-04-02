<?php

namespace Drupal\opigno_learning_path\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\group\Entity\Group;
use Drupal\opigno_group_manager\Entity\OpignoGroupManagedContent;
use Drupal\opigno_group_manager\OpignoGroupContext;
use Drupal\Core\Cache\Cache;

/**
 * Provides a 'article' block.
 *
 * @Block(
 *   id = "lp_steps_block",
 *   admin_label = @Translation("LP Steps block")
 * )
 */
class StepsBlock extends BlockBase {

  /**
   * Returns score.
   */
  protected function buildScore($step) {
    $is_attempted = $step['attempts'] > 0;

    if ($is_attempted) {
      $score = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $step['best score'],
        '#attributes' => [
          'class' => ['lp_steps_block_score'],
        ],
      ];
    }
    else {
      $score = ['#markup' => '&dash;'];
    }

    return [
      'data' => $score,
    ];
  }

  /**
   * Returns state.
   */
  protected function buildState($step) {
    $uid = \Drupal::currentUser()->id();
    $status = opigno_learning_path_get_step_status($step, $uid);
    $markups = [
      'pending' => '<span class="lp_steps_block_step_pending"></span>',
      'failed' => '<span class="lp_steps_block_step_failed"></span>'
      . $this->t('Failed'),
      'passed' => '<span class="lp_steps_block_step_passed"></span>'
      . $this->t('Passed'),
    ];
    $markup = isset($markups[$status]) ? $markups[$status] : '&dash;';
    return [
      'data' => [
        '#markup' => $markup,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // Every new route this block will rebuild.
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $user = \Drupal::currentUser();

    $uid = $user->id();
    $route_name = \Drupal::routeMatch()->getRouteName();
    if ($route_name == 'opigno_module.group.answer_form') {
      $group = \Drupal::routeMatch()->getParameter('group');
      $gid = $group->id();
    }
    else {
      $gid = OpignoGroupContext::getCurrentGroupId();
      if (isset($gid) && is_numeric($gid)) {
        $group = Group::load($gid);
      }
    }

    if (empty($group)) {
      return [];
    }

    $title = $group->label();

    $group_steps = opigno_learning_path_get_steps($gid, $uid);
    $steps = [];

    // Load courses substeps.
    array_walk($group_steps, function ($step) use ($uid, &$steps) {
      if ($step['typology'] === 'Course') {
        $course_steps = opigno_learning_path_get_steps($step['id'], $uid);
        $steps = array_merge($steps, $course_steps);
      }
      else {
        $steps[] = $step;
      }
    });

    /** @var \Drupal\user\UserInterface $user */
    $user = \Drupal::currentUser();
    $steps = array_filter($steps, function ($step) use ($user) {
      if ($step['typology'] === 'Meeting') {
        // If the user have not the collaborative features role.
        if (!$user->hasPermission('view meeting entities')) {
          return FALSE;
        }

        // If the user is not a member of the meeting.
        /** @var \Drupal\opigno_moxtra\MeetingInterface $meeting */
        $meeting = \Drupal::entityTypeManager()
          ->getStorage('opigno_moxtra_meeting')
          ->load($step['id']);
        if (!$meeting->isMember($user->id())) {
          return FALSE;
        }
      }
      elseif ($step['typology'] === 'ILT') {
        // If the user is not a member of the ILT.
        /** @var \Drupal\opigno_ilt\ILTInterface $ilt */
        $ilt = \Drupal::entityTypeManager()
          ->getStorage('opigno_ilt')
          ->load($step['id']);
        if (!$ilt->isMember($user->id())) {
          return FALSE;
        }
      }

      return TRUE;
    });

    $score = opigno_learning_path_get_score($gid, $uid);
    $progress = opigno_learning_path_progress($gid, $uid);
    $progress = round(100 * $progress);

    $is_passed = opigno_learning_path_is_passed($group, $uid);

    if ($is_passed) {
      $state_class = 'lp_steps_block_summary_state_passed';
      $state_title = $this->t('Passed');
    }
    else {
      $state_class = 'lp_steps_block_summary_state_pending';
      $state_title = $this->t('In progress');
    }
    // Get group context.
    $cid = OpignoGroupContext::getCurrentGroupContentId();
    if (!$cid) {
      return [];
    }
    $gid = OpignoGroupContext::getCurrentGroupId();
    $step_info = [];
    // Reindex steps array.
    $steps = array_values($steps);
    for ($i = 0; $i < count($steps); $i++) {
      // Build link for first step.
      if ($i == 0) {
        // Load first step entity.
        $first_step = OpignoGroupManagedContent::load($steps[$i]['cid']);
        /* @var \Drupal\opigno_group_manager\OpignoGroupContentTypesManager $content_types_manager*/
        $content_types_manager = \Drupal::service('opigno_group_manager.content_types.manager');
        $content_type = $content_types_manager->createInstance($first_step->getGroupContentTypeId());
        $step_url = $content_type->getStartContentUrl($first_step->getEntityId(), $gid);
        $link = Link::createFromRoute($steps[$i]['name'], $step_url->getRouteName(), $step_url->getRouteParameters())
          ->toString();
      }
      else {
        // Get link to module.
        $parent_content_id = $steps[$i - 1]['cid'];
        $link = Link::createFromRoute($steps[$i]['name'], 'opigno_learning_path.steps.next', [
          'group' => $gid,
          'parent_content' => $parent_content_id,
        ])
          ->toString();
      }

      array_push($step_info, [
        $link,
        $this->buildScore($steps[$i]),
        $this->buildState($steps[$i]),
      ]);

    }

    $summary = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['lp_steps_block_summary'],
      ],
      [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => [
          'class' => [$state_class],
        ],
        '#value' => '',
      ],
      [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#attributes' => [
          'class' => ['lp_steps_block_summary_title'],
        ],
        '#value' => $state_title,
      ],
      [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#attributes' => [
          'class' => ['lp_steps_block_summary_score'],
        ],
        '#value' => $this->t('Average score : @score%', [
          '@score' => $score,
        ]),
      ],
      [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#attributes' => [
          'class' => ['lp_steps_block_summary_progress'],
        ],
        '#value' => $this->t('Progress : @progress%', [
          '@progress' => $progress,
        ]),
      ],
    ];

    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['lp_steps_block'],
      ],
      $summary,
      [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $title,
        '#attributes' => [
          'class' => ['lp_steps_block_title'],
        ],
      ],
      [
        '#type' => 'table',
        '#header' => [
          $this->t('Name'),
          $this->t('Score'),
          $this->t('State'),
        ],
        '#rows' => $step_info,
        '#attributes' => [
          'class' => ['lp_steps_block_table'],
        ],
      ],
      '#attached' => [
        'library' => [
          'opigno_learning_path/steps_block',
        ],
      ],
    ];
  }

}
