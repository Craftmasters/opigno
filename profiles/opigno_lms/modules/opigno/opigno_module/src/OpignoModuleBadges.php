<?php

namespace Drupal\opigno_module;

use Drupal\group\Entity\Group;

/**
 * Class OpignoModuleBadges.
 *
 * @package Drupal\opigno_module
 */
class OpignoModuleBadges {

  /**
   * Saves/updates badges count.
   *
   * @param int $uid
   *   User ID.
   * @param int $gid
   *   Training ID.
   * @param string $typology
   *   Course or Module string.
   * @param int $entity_id
   *   Module/Course ID.
   *
   * @throws \Exception
   */
  public static function opignoModuleSaveBadge($uid, $gid, $typology, $entity_id) {
    $table_name = 'opigno_module_badges';

    // Get existing badge count.
    $badges = self::opignoModuleGetBadges($uid, $gid, $typology, $entity_id);

    // Update/create badge count.
    $keys = [
      'uid' => $uid,
      'gid' => $gid,
      'entity_id' => $entity_id,
      'typology' => $typology,
    ];
    $fields = [
      'uid' => $uid,
      'gid' => $gid,
      'entity_id' => $entity_id,
      'typology' => $typology,
      'badges' => $badges ? $badges + 1 : 1,
    ];
    $query = \Drupal::database()
      ->merge($table_name)
      ->keys($keys)
      ->fields($fields);

    $query->execute();
  }

  /**
   * Returns badges count for module/course in a training.
   *
   * @param int $uid
   *   User ID.
   * @param int $gid
   *   Training ID.
   * @param string $typology
   *   Course or Module string.
   * @param int $entity_id
   *   Module/Course ID.
   *
   * @return mixed
   *   Badges count or FALSE if empty.
   */
  public static function opignoModuleGetBadges($uid, $gid, $typology, $entity_id) {
    // Get existing badge count.
    $query = \Drupal::database()
      ->select('opigno_module_badges', 'mb')
      ->fields('mb', ['badges'])
      ->condition('uid', $uid)
      ->condition('gid', $gid)
      ->condition('typology', $typology)
      ->condition('entity_id', $entity_id);
    $result = $query->execute()->fetchField();

    return $result ? $result : FALSE;
  }

  /**
   * Returns user modules with active badges.
   *
   * @param int $uid
   *   User ID.
   *
   * @return mixed
   *   Array with modules fields.
   */
  public static function opignoModuleGetUserActiveBadgesModules($uid) {
    $output = [];

    // Get modules.
    $query = \Drupal::database()->select('opigno_learning_path_step_achievements', 'sa');
    $query->join('opigno_module_field_data', 'omfd', 'omfd.id = sa.entity_id AND sa.typology = :typology', [':typology' => 'Module']);
    $query->join('opigno_learning_path_achievements', 'pa', 'pa.gid = sa.gid');
    $query->join('media__field_media_image', 'mi', 'mi.entity_id = omfd.badge_media_image');
    $query->fields('sa', [
      'entity_id',
      'typology',
      'name',
      'score',
      'status',
      'time',
      'completed',
    ]);
    $query->fields('omfd', [
      'badge_name',
      'badge_description',
      'badge_criteria',
    ]);
    $query->fields('mi', ['field_media_image_target_id']);
    $query->fields('pa', ['gid']);
    $query->addField('pa', 'name', 'training');
    $query->condition('sa.typology', 'Module');
    $query->condition('sa.uid', $uid);
    $query->condition('omfd.badge_active', 1);
    $query->orderBy('sa.completed', 'DESC');
    $query->distinct();
    $modules = $query->execute()->fetchAll();

    // Remove duplicates
    // (addField cause of duplicates and distinct doesn't work).
    if ($modules) {
      $modules_keyed = [];
      foreach ($modules as $module) {
        $modules_keyed[$module->entity_id . '-' . $module->gid] = $module;
      }
      $modules = $modules_keyed;
    }

    // Get courses.
    $query = \Drupal::database()->select('opigno_learning_path_step_achievements', 'sa');
    $query->join('opigno_learning_path_achievements', 'pa', 'pa.gid = sa.gid');
    $query->join('group__badge_active', 'ba', 'ba.entity_id = sa.entity_id');
    $query->join('group__badge_media_image', 'bmi', 'bmi.entity_id = sa.entity_id');
    $query->join('media__field_media_image', 'mi', 'mi.entity_id = bmi.badge_media_image_target_id');
    $query->fields('sa', [
      'entity_id',
      'typology',
      'name',
      'score',
      'status',
      'time',
      'completed',
    ]);
    $query->fields('mi', ['field_media_image_target_id']);
    $query->fields('pa', ['gid']);
    $query->addField('pa', 'name', 'training');
    $query->condition('sa.typology', 'Course');
    $query->condition('sa.uid', $uid);
    $query->condition('ba.badge_active_value', 1);
    $query->orderBy('sa.completed', 'DESC');
    $query->distinct();
    $courses = $query->execute()->fetchAll();

    if (!empty($courses)) {
      foreach ($courses as &$course) {
        $entity = Group::load($course->entity_id);
        $course->badge_name = $entity->badge_name->value;
        $course->badge_description = $entity->badge_description->value;
        $course->badge_criteria = $entity->badge_criteria->value;
      }

      // Remove duplicates
      // (addField cause of duplicates and distinct doesn't work).
      $courses_keyed = [];
      foreach ($courses as $course) {
        $courses_keyed[$course->entity_id . '-' . $course->gid] = $course;
      }
      $courses = $courses_keyed;
    }

    if (!empty($courses) && !empty($modules)) {
      $output = array_merge($modules, $courses);
      // Sort by completed date.
      usort($output, 'self::opignoModuleResultsSortByDate');
    }
    elseif (!empty($modules)) {
      $output = $modules;
    }
    elseif (!empty($courses)) {
      $output = $courses;
    }

    return $output;
  }

  /**
   * Custom sort by date function.
   */
  public static function opignoModuleResultsSortByDate($a, $b) {
    return strtotime($a->completed) < strtotime($b->completed);
  }

}
