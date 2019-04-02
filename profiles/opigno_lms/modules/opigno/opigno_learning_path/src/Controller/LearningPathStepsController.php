<?php

namespace Drupal\opigno_learning_path\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\group\Entity\Group;
use Drupal\opigno_group_manager\Entity\OpignoGroupManagedContent;
use Drupal\opigno_group_manager\OpignoGroupContentTypesManager;
use Drupal\opigno_group_manager\OpignoGroupContext;
use Drupal\opigno_learning_path\Entity\LPResult;
use Drupal\opigno_learning_path\LearningPathAccess;
use Drupal\opigno_learning_path\LearningPathValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LearningPathStepsController.
 */
class LearningPathStepsController extends ControllerBase {

  protected $content_type_manager;

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    OpignoGroupContentTypesManager $content_types_manager,
    Connection $database
  ) {
    $this->content_type_manager = $content_types_manager;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('opigno_group_manager.content_types.manager'),
      $container->get('database')
    );
  }

  /**
   * Start the learning path.
   *
   * This page will redirect the user to the first learning path content.
   */
  public function start(Group $group) {
    // Create empty result attempt if current attempt doesn't exist.
    // Will be used to detect if user already started LP or not.
    $current_attempt = opigno_learning_path_started($group, \Drupal::currentUser());
    if (!$current_attempt) {
      $result = LPResult::createWithValues($group->id(),
        \Drupal::currentUser()
          ->id(), FALSE,
        0
      );
      $result->save();
    }

    $user = $this->currentUser();

    $uid = $user->id();
    $gid = $group->id();

    $is_ajax = \Drupal::request()->isXmlHttpRequest();

    // Load group steps.
    $group_steps = opigno_learning_path_get_steps($gid, $uid);
    $steps = [];

    // Load group courses substeps.
    array_walk($group_steps, function ($step, $key) use ($uid, &$steps) {
      $step['position'] = $key;
      if ($step['typology'] === 'Course') {
        $course_steps = opigno_learning_path_get_steps($step['id'], $uid);
        // Save parent course and position.
        $course_steps = array_map(function ($course_step, $key) use ($step) {
          $course_step['parent'] = $step;
          $course_step['position'] = $key;
          return $course_step;
        }, $course_steps, array_keys($course_steps));
        $steps = array_merge($steps, $course_steps);
      }
      else {
        $steps[] = $step;
      }
    });

    foreach ($steps as $step) {
      if (in_array($step['typology'], ['Meeting', 'ILT'])) {
        // If training starts with a mandatory live meeting
        // or instructor-led training, check requirements.
        $is_mandatory = $step['mandatory'] === 1;

        if ($step['typology'] === 'Meeting') {
          /** @var \Drupal\opigno_moxtra\MeetingInterface $entity */
          $entity = $this->entityTypeManager()
            ->getStorage('opigno_moxtra_meeting')
            ->load($step['id']);

          if (!$entity->isMember($uid)) {
            $is_mandatory = FALSE;
          }
        }
        elseif ($step['typology'] === 'ILT') {
          /** @var \Drupal\opigno_ilt\ILTInterface $entity */
          $entity = $this->entityTypeManager()
            ->getStorage('opigno_ilt')
            ->load($step['id']);

          if (!$entity->isMember($uid)) {
            $is_mandatory = FALSE;
          }
        }

        if ($is_mandatory) {
          $name = $step['name'];
          $required = $step['required score'];
          if ($required > 0) {
            if ($step['best score'] < $required) {
              $course_entity = OpignoGroupManagedContent::load($step['cid']);
              $course_content_type = $this->content_type_manager->createInstance(
                $course_entity->getGroupContentTypeId()
              );
              $current_step_url = $course_content_type->getStartContentUrl(
                $course_entity->getEntityId(),
                $gid
              );

              $content = [
                '#type' => 'markup',
                '#markup' => $this->t('<p>You should first get a minimum score of %required to the step %step before going further. <a href=":link">Try again.</a></p>', [
                  '%step' => $name,
                  '%required' => $required,
                  ':link' => $current_step_url->toString(),
                ]),
              ];
              if ($is_ajax) {
                return (new AjaxResponse())->addCommand(new OpenModalDialogCommand('', $content));
              }
              else {
                return $content;
              }
            }
          }
          else {
            if ($step['attempts'] === 0) {
              $content = [
                '#type' => 'markup',
                '#markup' => $this->t('<p>A required step: %step should be done first.</p>', [
                  '%step' => $name,
                ]),
              ];
              if ($is_ajax) {
                return (new AjaxResponse())->addCommand(new OpenModalDialogCommand('', $content));
              }
              else {
                return $content;
              }
            }
          }
        }
      }
      else {
        break;
      }
    }

    // Skip live meetings and instructor-led trainings from the group steps.
    $steps = array_filter($steps, function ($step) {
      return !in_array($step['typology'], ['Meeting', 'ILT']);
    });

    // Check that training is completed.
    $is_completed = (int) $this->database
      ->select('opigno_learning_path_achievements', 'a')
      ->fields('a')
      ->condition('uid', $user->id())
      ->condition('gid', $group->id())
      ->condition('status', 'completed')
      ->countQuery()
      ->execute()
      ->fetchField() > 0;
    if ($is_completed) {
      // Load steps from cache table.
      $results = $this->database
        ->select('opigno_learning_path_step_achievements', 'a')
        ->fields('a', [
          'id',
          'typology',
          'entity_id',
          'parent_id',
          'position',
        ])
        ->condition('uid', $user->id())
        ->condition('gid', $group->id())
        ->execute()
        ->fetchAllAssoc('id');
      if (!empty($results)) {
        // Check training structure.
        $is_valid = TRUE;
        foreach ($steps as $step) {
          $filtered = array_filter($results, function ($result) use ($results, $step) {
            if (isset($step['parent'])) {
              $step_parent = $step['parent'];
              $result_parent = isset($results[$result->parent_id])
                ? $results[$result->parent_id] : NULL;
              if (!isset($result_parent)
                || $result_parent->typology !== $step_parent['typology']
                || (int) $result_parent->entity_id !== (int) $step_parent['id']
                || (int) $result_parent->position !== (int) $step_parent['position']) {
                return FALSE;
              }
            }

            return $result->typology === $step['typology']
              && (int) $result->entity_id === (int) $step['id']
              && (int) $result->position === (int) $step['position'];
          });

          if (count($filtered) !== 1) {
            $is_valid = FALSE;
            break;
          }
        }

        // If training is changed.
        if (!$is_valid) {
          $form = $this->formBuilder()->getForm('Drupal\opigno_learning_path\Form\DeleteAchievementsForm', $group);
          if ($is_ajax) {
            return (new AjaxResponse())->addCommand(new OpenModalDialogCommand('', $form));
          }
          else {
            return $form;
          }
        }
      }
    }

    // Check if there is resumed step. If is - redirect.
    $step_resumed_cid = opigno_learning_path_resumed_step($steps);
    if ($step_resumed_cid) {
      $content = OpignoGroupManagedContent::load($step_resumed_cid);
      // Find and load the content type linked to this content.
      $content_type = $this->content_type_manager->createInstance($content->getGroupContentTypeId());
      $step_url = $content_type->getStartContentUrl($content->getEntityId(), $group->id());
      // Before redirecting, keep the content ID in context.
      OpignoGroupContext::setCurrentContentId($step_resumed_cid);
      OpignoGroupContext::setGroupId($group->id());
      // Finally, redirect the user to the first step.
      if ($is_ajax) {
        return (new AjaxResponse())->addCommand(new RedirectCommand($step_url->toString()));
      }
      else {
        return $this->redirect($step_url->getRouteName(), $step_url->getRouteParameters(), $step_url->getOptions());
      }
    };

    // Get the first step of the learning path. If no steps, show a message.
    $first_step = reset($steps);
    if ($first_step === FALSE) {
      $content = [
        '#type' => 'markup',
        '#markup' => $this->t('<p>No first step assigned.</p>'),
      ];
      if ($is_ajax) {
        return (new AjaxResponse())->addCommand(new OpenModalDialogCommand('', $content));
      }
      else {
        return $content;
      }
    }

    // Load first step entity.
    $first_step = OpignoGroupManagedContent::load($first_step['cid']);

    // Find and load the content type linked to this content.
    $content_type = $this->content_type_manager->createInstance($first_step->getGroupContentTypeId());

    // Finally, get the "start" URL
    // If no URL, show a message.
    $step_url = $content_type->getStartContentUrl($first_step->getEntityId(), $group->id());
    if (empty($step_url)) {
      $content = [
        '#type' => 'markup',
        '#markup' => $this->t('<p>No URL for the first step.</p>'),
      ];
      if ($is_ajax) {
        return (new AjaxResponse())->addCommand(new OpenModalDialogCommand('', $content));
      }
      else {
        return $content;
      }
    }

    // Before redirecting, keep the content ID in context.
    OpignoGroupContext::setCurrentContentId($first_step->id());
    OpignoGroupContext::setGroupId($group->id());

    // Finally, redirect the user to the first step.
    if ($is_ajax) {
      return (new AjaxResponse())->addCommand(new RedirectCommand($step_url->toString()));
    }
    else {
      return $this->redirect($step_url->getRouteName(), $step_url->getRouteParameters(), $step_url->getOptions());
    }
  }

  /**
   * Redirect the user to the next step.
   */
  public function nextStep(Group $group, OpignoGroupManagedContent $parent_content) {
    // Get the user score of the parent content.
    // First, get the content type object of the parent content.
    $content_type = $this->content_type_manager->createInstance($parent_content->getGroupContentTypeId());
    $user_score = $content_type->getUserScore(\Drupal::currentUser()->id(), $parent_content->getEntityId());

    // If no no score and content is mandatory, show a message.
    if ($user_score === FALSE && $parent_content->isMandatory()) {
      return [
        '#type' => 'markup',
        '#markup' => '<p>No score provided</p>',
      ];
    }

    $user = $this->currentUser();

    $uid = $user->id();
    $gid = $group->id();
    $cid = $parent_content->id();

    // Load group steps.
    $group_steps = opigno_learning_path_get_steps($gid, $uid);
    $steps = [];

    // Load group courses substeps.
    array_walk($group_steps, function ($step) use ($uid, &$steps) {
      if ($step['typology'] === 'Course') {
        $course_steps = opigno_learning_path_get_steps($step['id'], $uid);
        $last_course_step = end($course_steps);
        $course_steps = array_map(function ($course_step) use ($step, $last_course_step) {
          $course_step['parent'] = $step;
          $course_step['is last child'] = $course_step['cid'] === $last_course_step['cid'];
          return $course_step;
        }, $course_steps);
        $steps = array_merge($steps, $course_steps);
      }
      else {
        $steps[] = $step;
      }
    });

    // Find current & next step.
    $count = count($steps);
    $current_step = NULL;
    $current_step_index = 0;
    for ($i = 0; $i < $count - 1; ++$i) {
      if ($steps[$i]['cid'] === $cid || $steps[$i]['required score'] > $steps[$i]['current attempt score']) {
        $current_step_index = $i;
        $current_step = $steps[$i];
        break;
      }
    }

    // Check mandatory step requirements.
    if (isset($current_step) && $current_step['mandatory'] === 1) {
      $name = $current_step['name'];
      $required = $current_step['required score'];
      if ($required > 0) {
        if ($current_step['current attempt score'] < $required) {
          $course_entity = OpignoGroupManagedContent::load($current_step['cid']);
          $course_content_type = $this->content_type_manager->createInstance(
            $course_entity->getGroupContentTypeId()
          );
          $current_step_url = $course_content_type->getStartContentUrl(
            $course_entity->getEntityId(),
            $gid
          );

          OpignoGroupContext::setGroupId($group->id());
          OpignoGroupContext::setCurrentContentId($current_step['cid']);

          return [
            '#type' => 'markup',
            '#markup' => $this->t('<p>You should first get a minimum score of %required to the step %step before going further. <a href=":link">Try again.</a></p>', [
              '%step' => $name,
              '%required' => $required,
              ':link' => $current_step_url->toString(),
            ]),
          ];
        }
      }
      else {
        OpignoGroupContext::setGroupId($group->id());
        OpignoGroupContext::setCurrentContentId($current_step['cid']);

        if ($current_step['attempts'] === 0) {
          return [
            '#type' => 'markup',
            '#markup' => $this->t('<p>A required step: %step should be done first.</p>', [
              '%step' => $name,
            ]),
          ];
        }
      }
    }

    if (isset($current_step['is last child']) && $current_step['is last child']
      && isset($current_step['parent'])) {
      $course = $current_step['parent'];
      // Check mandatory course requirements.
      if ($course['mandatory'] === 1) {
        $name = $course['name'];
        $required = $course['required score'];
        if ($required > 0) {
          if ($course['best score'] < $required) {
            $module_content = OpignoGroupManagedContent::getFirstStep($course['id']);
            $module_content_type = $this->content_type_manager->createInstance(
              $module_content->getGroupContentTypeId()
            );
            $module_url = $module_content_type->getStartContentUrl(
              $module_content->getEntityId(),
              $gid
            );

            OpignoGroupContext::setGroupId($group->id());
            OpignoGroupContext::setCurrentContentId($module_content->id());

            return [
              '#type' => 'markup',
              '#markup' => $this->t('<p>You should first get a minimum score of %required to the step %step before going further. <a href=":link">Try again.</a></p>', [
                '%step' => $name,
                '%required' => $required,
                ':link' => $module_url->toString(),
              ]),
            ];
          }
        }
        else {
          if ($course['attempts'] === 0) {
            $module_content = OpignoGroupManagedContent::getFirstStep($course['id']);

            OpignoGroupContext::setGroupId($group->id());
            OpignoGroupContext::setCurrentContentId($module_content->id());

            return [
              '#type' => 'markup',
              '#markup' => $this->t('<p>A required step: %step should be done first.</p>', [
                '%step' => $name,
              ]),
            ];
          }
        }
      }
    }

    // Skip live meetings and instructor-led trainings.
    $skip_types = ['Meeting', 'ILT'];
    for ($next_step_index = $current_step_index + 1;
      $next_step_index < $count
      && in_array($steps[$next_step_index]['typology'], $skip_types);
      ++$next_step_index) {
      $next_step = $steps[$next_step_index];
      $is_mandatory = $next_step['mandatory'] === 1;

      if ($next_step['typology'] === 'Meeting') {
        /** @var \Drupal\opigno_moxtra\MeetingInterface $entity */
        $entity = $this->entityTypeManager()
          ->getStorage('opigno_moxtra_meeting')
          ->load($next_step['id']);

        if (!$entity->isMember($uid)) {
          $is_mandatory = FALSE;
        }
      }
      elseif ($next_step['typology'] === 'ILT') {
        /** @var \Drupal\opigno_ilt\ILTInterface $entity */
        $entity = $this->entityTypeManager()
          ->getStorage('opigno_ilt')
          ->load($next_step['id']);

        if (!$entity->isMember($uid)) {
          $is_mandatory = FALSE;
        }
      }

      if ($is_mandatory) {
        $name = $next_step['name'];
        $required = $next_step['required score'];
        // But if the live meeting or instructor-led training is
        // a mandatory and not passed,
        // block access to the next step.
        if ($required > 0) {
          if ($next_step['best score'] < $required) {
            return [
              '#type' => 'markup',
              '#markup' => $this->t('<p>You should first get a minimum score of %required to the step %step before going further.</p>', [
                '%step' => $name,
                '%required' => $required,
              ]),
            ];
          }
        }
        else {
          if ($next_step['attempts'] === 0) {
            return [
              '#type' => 'markup',
              '#markup' => $this->t('<p>A required step: %step should be done first.</p>', [
                '%step' => $name,
              ]),
            ];
          }
        }
      }
    }

    $next_step = isset($steps[$next_step_index])
      ? $steps[$next_step_index] : NULL;

    // If there is no next step, show a message.
    if ($next_step === NULL) {
      // Redirect to training home page.
      $this->messenger()->addWarning($this->t('You reached the last content of that training.'));
      return $this->redirect('entity.group.canonical', ['group' => $group->id()]);
    }

    // Load next step entity.
    $next_step = OpignoGroupManagedContent::load($next_step['cid']);

    // Before redirect, change the content context.
    OpignoGroupContext::setCurrentContentId($next_step->id());
    OpignoGroupContext::setGroupId($group->id());

    // Finally, redirect the user to the next step URL.
    $next_step_content_type = $this->content_type_manager->createInstance($next_step->getGroupContentTypeId());
    $next_step_url = $next_step_content_type->getStartContentUrl($next_step->getEntityId(), $group->id());
    return $this->redirect($next_step_url->getRouteName(), $next_step_url->getRouteParameters(), $next_step_url->getOptions());
  }

  /**
   * Show the finish page and save the score.
   */
  public function finish(Group $group) {
    // Get the "user passed" status.
    $current_uid = \Drupal::currentUser()->id();
    $user_passed = LearningPathValidator::userHasPassed($current_uid, $group);

    if ($user_passed) {

      // Store the result in database.
      $current_result_attempt = LPResult::getCurrentLPAttempt($group, \Drupal::currentUser());
      if ($current_result_attempt) {
        $current_result_attempt->setHasPassed($user_passed);
        $current_result_attempt->setFinished(REQUEST_TIME);
        $current_result_attempt->save();
      }
      else {
        // Store the result in database.
        $result = LPResult::createWithValues($group->id(), $current_uid, $user_passed, REQUEST_TIME);
        $result->save();
      }

      return ['#markup' => '<p>You passed!</p>'];
    }
    else {
      return ['#markup' => '<p>You failed!</p>'];
    }
  }

  /**
   * Steps.
   */
  public function contentSteps(Group $group, $current) {
    // Check if user has uncompleted steps.
    LearningPathValidator::stepsValidate($group);
    // Get group type.
    $type = opigno_learning_path_get_group_type();
    // Get all steps.
    $all_steps = opigno_learning_path_get_routes_steps();
    // Get unique steps numbers.
    $unique_steps = array_unique($all_steps);
    // Get next step.
    $next_step = ($current < count($unique_steps)) ? $current + 1 : NULL;
    // If last step.
    if (!$next_step) {
      if ($type == 'learning_path') {
        return $this->redirect('opigno_learning_path.manager.publish', ['group' => $group->id()]);
      }
      else {
        // For courses and classes.
        return $this->redirect('entity.group.canonical', ['group' => $group->id()]);
      }
    }
    // If not last step.
    else {
      if ($type == 'learning_path') {
        // Check for existing courses in the LP.
        // If there are no courses - skip courses step.
        $group_courses = $group->getContent('subgroup:opigno_course');
        if ($current == 2 && empty($group_courses)) {
          $next_step++;
        }
      }
      // For all group types.
      $route = array_search($next_step, opigno_learning_path_get_routes_steps());
      return $this->redirect($route, ['group' => $group->id()]);
    }
  }

  /**
   * Steps list.
   */
  public function listSteps(Group $group) {
    /** @var \Drupal\Core\Datetime\DateFormatterInterface $date_formatter */
    $date_formatter = \Drupal::service('date.formatter');

    $group_id = $group->id();
    $uid = \Drupal::currentUser()->id();

    $steps = opigno_learning_path_get_steps($group_id, $uid);

    $rows = array_map(function ($step) use ($date_formatter) {
      return [
        $step['name'],
        $step['typology'],
        $date_formatter->formatInterval($step['time spent']),
        $step['best score'],
      ];
    }, $steps);

    return [
      '#type' => 'table',
      '#header' => [
        'Name',
        'Typology',
        'Total time spent',
        'Best score achieved',
      ],
      '#rows' => $rows,
    ];
  }

  /**
   * Check if the user has access to any next content from the Learning Path.
   */
  public function nextStepAccess(Group $group, OpignoGroupManagedContent $parent_content) {
    // Check if there is a next step and if the user has access to it.
    // Get the user score of the parent content.
    // First, get the content type object of the parent content.
    $content_type = $this->content_type_manager->createInstance($parent_content->getGroupContentTypeId());
    $user_score = $content_type->getUserScore(\Drupal::currentUser()->id(), $parent_content->getEntityId());

    // If no no score and content is mandatory, return forbidden.
    if ($user_score === FALSE && $parent_content->isMandatory()) {
      return AccessResult::forbidden();
    }

    return AccessResult::allowed();
  }

  /**
   * Check if the user has access to start the Learning Path.
   */
  public function startAccess(Group $group) {
    $user = $this->currentUser();
    $group_visibility = $group->get('field_learning_path_visibility')->getValue()[0]['value'];

    if ($user->isAnonymous() && $group_visibility != 'public') {
      return AccessResult::forbidden();
    }

    $access = LearningPathAccess::statusGroupValidation($group, $user);
    if ($access === FALSE) {
      return AccessResult::forbidden();
    }

    return AccessResult::allowed();
  }

}
