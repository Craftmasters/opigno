<?php

namespace Drupal\opigno_learning_path;

use Drupal\Core\Url;
use Drupal\group\Entity\Group;
use Drupal\opigno_group_manager\Entity\OpignoGroupManagedContent;
use Drupal\opigno_learning_path\Entity\LPManagedContent;
use Drupal\opigno_module\Entity\OpignoModule;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class LearningPathValidator.
 */
class LearningPathValidator {

  /**
   * Check if the user has passed all the conditions of a learning path.
   */
  public static function userHasPassed($uid, Group $learning_path) {
    // Check if all the mandatory contents are okay
    // and if all the minimum score of the mandatories are good.
    $contents = LPManagedContent::loadByLearningPathId($learning_path->id());
    foreach ($contents as $content) {
      // Get the minimum score required.
      $min_score = $content->getSuccessScoreMin() / 100;

      // Compare the user score with the minimum score required.
      $content_type = $content->getLearningPathContentType();
      $user_score = $content_type->getUserScore($uid, $content->getEntityId());

      // If the minimum score is no good, return FALSE.
      if ($user_score < $min_score) {
        return FALSE;
      }

    }

    // If all the scores are okay, return TRUE.
    return TRUE;
  }

  /**
   * Checks if module have at least one activity.
   *
   * @param \Drupal\opigno_module\Entity\OpignoModule $module
   *   Opigno Module Entity.
   * @param int $redirect_step
   *   Step to redirect.
   *
   * @return bool
   *   Is module valid.
   */
  protected static function moduleValidate(OpignoModule $module, &$redirect_step) {
    $activities = $module->getModuleActivities();
    $is_valid = !empty($activities);
    if (!$is_valid && ($redirect_step === NULL || $redirect_step >= 4)) {
      $redirect_step = 4;

      // Show message only if user click on "next" button from current route.
      $current_route = \Drupal::service('current_route_match');
      $current_step = (int) $current_route->getParameter('current');
      if ($current_step === $redirect_step) {
        $messenger = \Drupal::messenger();
        $messenger->addError(t('Please, add at least one activity to @module module!', [
          '@module' => $module->label(),
        ]));
      }
    }

    return $is_valid;
  }

  /**
   * Checks if course has at least one module, and all modules are valid.
   */
  protected static function courseValidate($course_id, &$redirect_step) {
    $contents = OpignoGroupManagedContent::loadByGroupId($course_id);
    $is_valid = !empty($contents);
    if (!$is_valid && ($redirect_step === NULL || $redirect_step >= 3)) {
      $redirect_step = 3;

      // Show message only if user click on "next" button from current route.
      $current_route = \Drupal::service('current_route_match');
      $current_step = (int) $current_route->getParameter('current');
      if ($current_step === $redirect_step) {
        $messenger = \Drupal::messenger();
        $messenger->addError(t('Please make sure that every course contains at least one module.'));
      }
    }
    else {
      foreach ($contents as $course_content) {
        // Check if all modules in course has at least one activity.
        $module_id = $course_content->getEntityId();
        $module = OpignoModule::load($module_id);
        if (!static::moduleValidate($module, $redirect_step)) {
          $is_valid = FALSE;
        }
      }
    }

    return $is_valid;
  }

  /**
   * Redirect user if one of learning path steps aren't completed.
   */
  public static function stepsValidate(Group $group) {
    $messenger = \Drupal::messenger();

    $current_route = \Drupal::service('current_route_match');
    $current_route_name = $current_route->getRouteName();
    $current_route_step = (int) $current_route->getParameter('current');

    // Validate only group type "learning_path".
    $group_type = opigno_learning_path_get_group_type();
    if ($group_type !== 'learning_path') {
      return TRUE;
    }

    // Step 1 doesn't need validation because it has form validation.
    $current_step = (int) opigno_learning_path_get_current_step();
    if ($current_step === 1) {
      return TRUE;
    };

    $redirect_step = NULL;

    // Get all training content.
    $contents = OpignoGroupManagedContent::loadByGroupId($group->id());
    if (empty($contents)) {
      // Learning path is empty.
      $redirect_step = 2;

      // Show message only if user click on "next" button from current route.
      if ($current_route_step === $redirect_step) {
        $messenger->addError(t('Please, add some course or module!'));
      }
    }
    else {
      // Learning path is created and not empty.
      // Skip 4 step if learning path hasn't any courses.
      $group_courses = $group->getContent('subgroup:opigno_course');
      if (empty($group_courses)
        && $current_route_name === 'opigno_learning_path.learning_path_courses') {
        $redirect_step = 4;
      }

      // Check if training has at least one mandatory entity.
      $has_mandatory = self::hasMandatoryItem($contents);
      if (!$has_mandatory) {
        $redirect_step = 2;

        // Show message only if user click
        // on "next" button from current route.
        if ($current_route_step === $redirect_step) {
          $messenger->addError(t('At least one entity must be mandatory!'));
        }
      }
      else {
        foreach ($contents as $content) {
          $type_id = $content->getGroupContentTypeId();
          switch ($type_id) {
            case 'ContentTypeModule':
              $module_id = $content->getEntityId();
              $module = OpignoModule::load($module_id);
              static::moduleValidate($module, $redirect_step);
              break;

            case 'ContentTypeCourse':
              $course_id = $content->getEntityId();
              static::courseValidate($course_id, $redirect_step);
              break;
          }
        }
      }
    }

    if (isset($redirect_step)) {
      $redirect_route_name = array_search($redirect_step, opigno_learning_path_get_routes_steps());
      if ($redirect_route_name === $current_route_name) {
        // Prevent redirect from current route.
        return FALSE;
      };

      // Redirect to incomplete step.
      $response = new RedirectResponse(Url::fromRoute($redirect_route_name, [
        'group' => $group->id(),
      ])->toString());
      return $response->send();
    }

    return TRUE;
  }

  /**
   * Check if training has at least one mandatory content.
   */
  protected static function hasMandatoryItem($contents) {
    foreach ($contents as $content) {
      if ($content->isMandatory()) {
        return TRUE;
      };
    }
    // If training hasn't mandatory entity return FALSE.
    return FALSE;
  }

}
