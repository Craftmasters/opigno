<?php

namespace Drupal\opigno_migration\EventSubscriber;

use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate\Event\MigrateRollbackEvent;
use Drupal\opigno_group_manager\Entity\OpignoGroupManagedLink;
use Drupal\Core\Database\Database;
use Drupal\group\Entity\Group;
use Drupal\media\Entity\Media;
use Drupal\opigno_module\Entity\OpignoActivity;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\user\Entity\User;

/**
 * Creates relationships between modules into group after migrations.
 */
class GroupsMigrateSubscriber implements EventSubscriberInterface {

  /**
   * The key value factory.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  protected $keyValue;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * GroupsMigrateSubscriber constructor.
   */
  public function __construct() {}

  /**
   * Executes on post row save.
   *
   * @param \Drupal\migrate\Event\MigratePostRowSaveEvent $event
   *   The migrate post row save event.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function onPostRowSave(MigratePostRowSaveEvent $event) {
    $migration_id = $event->getMigration()->id();

    if ($migration_id == 'opigno_learning_path_course' || $migration_id == 'opigno_learning_path_class') {
      $gid = $event->getRow()->getSourceProperty('nid');
      $group = Group::load($gid);

      $connection = Database::getConnection('default', 'legacy');
      $members_ids = $connection->select('og_membership', 'om')
        ->fields('om', ['etid'])
        ->condition('entity_type', 'user')
        ->condition('field_name', 'og_user_node')
        ->condition('	gid', $gid)
        ->execute()
        ->fetchAll();

      foreach ($members_ids as $member_id) {
        $account = user_load($member_id->etid);
        $group->addMember($account);

        // Migrate group roles of user.
        $query = $connection
          ->select('og_role', 'o_r')
          ->fields('o_r', ['name', 'rid']);
        $query->leftJoin('og_users_roles', 'o_u_r', 'o_r.rid = o_u_r.rid');
        $query->condition('o_r.gid', $gid);
        $query->condition('o_u_r.uid', $member_id->etid);
        $legacy_roles = $query->execute()->fetchAll();

        $member = $group->getMember($account);
        $group_content = $member->getGroupContent();
        $new_roles = [];

        foreach ($legacy_roles as $role) {
          switch ($role->name) {
            case 'student':
              $new_roles[] = ['target_id' => 'opigno_class-student'];
              break;

            case 'manager':
              if ($migration_id == 'opigno_learning_path_class') {
                $new_roles[] = ['target_id' => 'opigno_class-class_manager'];
              }
              elseif ($migration_id == 'opigno_learning_path_course') {
                $new_roles[] = ['target_id' => 'learning_path-user_manager'];
                $new_roles[] = ['target_id' => 'learning_path-content_manager'];
              }
              break;
          }
        }

        $group_content->set('group_roles', $new_roles);
        $group_content->save();
      }

      // Migration of 'Documents Library'.
      if ($migration_id == 'opigno_learning_path_course') {
        $tid = $group->get('field_learning_path_folder')->getValue()[0]['target_id'];
        $uid = $group->getOwnerId();

        $connection = Database::getConnection('default', 'legacy');
        $query = $connection
          ->select('file_managed', 'f_m')
          ->fields('f_m', ['filename', 'fid']);
        $query->leftJoin('field_data_tft_file', 'f_d_t_f', 'f_d_t_f.tft_file_fid = f_m.fid');
        $query->condition('f_d_t_f.bundle', 'tft_file');
        $query->leftJoin('og_membership', 'o_m', 'o_m.etid = f_d_t_f.entity_id');
        $query->condition('o_m.gid', $gid);
        $document_files = $query->execute()->fetchAll();

        foreach ($document_files as $file) {
          $media_entity = Media::create([
            'bundle' => 'tft_file',
            'uid' => $uid,
            'name' => $file->filename,
            'tft_file' => [
              'target_id' => $file->fid,
            ],
            'tft_folder' => [
              'target_id' => $tid,
            ],
          ]);

          $media_entity->save();
        }
      }
    }

    // Add relationships for Long answer activity.
    if (in_array($migration_id, [
      'opigno_activity_long_answer',
      'opigno_activity_file_upload',
      'opigno_activity_h5p',
      'opigno_activity_scorm',
      'opigno_activity_tincan',
      'opigno_activity_slide',
    ])) {

      $row = $event->getRow();
      $nid = $row->getSourceProperty('nid');
      $vid = $row->getSourceProperty('vid');

      if ($migration_id == 'opigno_activity_h5p') {
        // Create H5P activity.
        $activity = OpignoActivity::create([
          'id' => $nid,
          'vid' => $vid,
          'type' => 'opigno_h5p',
          'uid' => $row->getSourceProperty('node_uid'),
          'status' => $row->getSourceProperty('status'),
          'created' => $row->getSourceProperty('created'),
          'changed' => $row->getSourceProperty('changed'),
          'name' => $row->getSourceProperty('title'),
          'opigno_h5p' => $vid,
        ]);
        $activity->save();

        // Copy h5p content files to new location.
        if (!empty($_SESSION['source_base_path'])) {
          $db_connection = Database::getConnection('default', 'legacy');
          $query = $db_connection->select('variable', 'v')
            ->fields('v', ['value'])
            ->condition('name', 'h5p_default_path');
          $result = $query->execute()->fetchField();
          if ($result) {
            $h5p_source_path = unserialize($result);
          }
          else {
            $h5p_source_path = 'h5p';
          }

          $h5p_dest_path = \Drupal::config('h5p.settings')->get('h5p_default_path');
          $h5p_dest_path = !empty($h5p_dest_path) ? $h5p_dest_path : 'h5p';

          shell_exec('cp -r ' . $_SESSION['source_base_path'] . '/sites/default/files/' . $h5p_source_path . '/content/' . $vid . ' ' . DRUPAL_ROOT . '/sites/default/files/' . $h5p_dest_path . '/content/' . $vid);
        }
      }

      // Create activity relationships.
      $db_connection = Database::getConnection('default', 'legacy');
      $query = $db_connection->select('quiz_node_relationship', 'qr')
        ->fields('qr')
        ->condition('child_nid', $nid)
        ->condition('child_vid', $vid);
      $result = $query->execute()->fetchAll();
      if ($result) {
        $relations = [];
        foreach ($result as $item) {
          $relations[$item->parent_nid][] = $item;
        }
        if ($relations) {
          $rels = [];
          foreach ($relations as $relation) {
            if (count($relation) == 1) {
              $rels[] = $relation[0];
            }
            elseif (count($relation) > 1) {
              $max_vid = 0;
              $max_vid_key = 0;
              foreach ($relation as $key => $item) {
                if ($item->parent_vid > $max_vid) {
                  $max_vid = $item->parent_vid;
                  $max_vid_key = $key;
                }
              }
              $rels[] = $relation[$max_vid_key];
            }
          }
        }

        if ($rels) {
          $db_connection = \Drupal::service('database');
          foreach ($rels as $item) {
            $fields = [
              'parent_id' => $item->parent_nid,
              'parent_vid' => $item->parent_nid,
              'child_id' => $item->child_nid,
              'child_vid' => $item->child_nid,
              'activity_status' => $item->question_status,
              'weight' => $item->weight,
              'max_score' => $item->max_score,
              'auto_update_max_score' => $item->auto_update_max_score,
            ];
            try {
              $db_connection->insert('opigno_module_relationship')
                ->fields($fields)
                ->execute();
            }
            catch (\Exception $e) {
              \Drupal::logger('opigno_groups_migration')
                ->error($e->getMessage());
            }
          }
        }
      }
    }
  }

  /**
   * Executes on post import.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The migrate import event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function onPostImport(MigrateImportEvent $event) {
    $migration_id = $event->getMigration()->id();
    $completed = $event->getMigration()->allRowsProcessed();
    $params['source_count'] = $event->getMigration()->getSourcePlugin()->count();
    $params['processed_count'] = $event->getMigration()->getIdMap()->processedCount();

    // Set links between modules.
    if ($migration_id == 'opigno_module_lesson' && $completed) {
      $connection = Database::getConnection();
      $modules = $connection->select('opigno_group_content', 'gc')
        ->fields('gc', ['id', 'group_id', 'entity_id', 'coordinate_y'])
        ->condition('group_content_type_id', 'ContentTypeModule')
        ->orderBy('group_id', 'ASC')
        ->orderBy('coordinate_y', 'ASC')
        ->execute()
        ->fetchAll();

      if (isset($modules[0])) {
        $current_gid = $modules[0]->group_id;

        foreach ($modules as $key => $module) {
          if ($key == 0) {
            continue;
          }
          if ($current_gid == $module->group_id) {
            $parent_id = $modules[$key - 1]->id;
            $child_id = $module->id;
            OpignoGroupManagedLink::createWithValues(
              $current_gid,
              $parent_id,
              $child_id
            )->save();
          }
          else {
            $current_gid = $module->group_id;
          }
        }
      }
    }

    // Add classes into learning paths.
    if ($migration_id == 'opigno_learning_path_class' && $completed) {
      $connection = Database::getConnection('default', 'legacy');
      $classes_ref = $connection->select('field_revision_opigno_class_courses', 'c')
        ->fields('c', ['revision_id', 'opigno_class_courses_target_id'])
        ->condition('bundle', 'class')
        ->execute()
        ->fetchAll();

      foreach ($classes_ref as $class_ref) {
        $class = Group::load($class_ref->revision_id);
        $learning_path = Group::load($class_ref->opigno_class_courses_target_id);

        if (!empty($learning_path) && !empty($class)) {
          $learning_path->addContent($class, 'subgroup:opigno_class');

          // Add class members to the users.
          $members = $class->getMembers();

          foreach ($members as $member) {
            /** @var \Drupal\group\GroupMembership $member */
            $user = $member->getUser();
            $learning_path->addMember($user);
            opigno_learning_path_save_step_achievements($learning_path->id(), $member->getUser()->id(), 0, 0);
          }
        }
      }
    }

    // Migrate all statistic of groups.
    if ($migration_id == 'opigno_activity_h5p' && $completed) {
      $lp_ids = \Drupal::entityQuery('group')->condition('type', 'learning_path')->execute();
      // Migrate general statistic of Learning paths (courses).
      // First get legacy statistic of Learning paths.
      $connection = Database::getConnection('default', 'legacy');
      $lps_statistic = $connection->select('opigno_statistics_user_course', 'o_s_u_c')
        ->fields('o_s_u_c', [
          'opigno_statistics_user_course_pk',
          'uid',
          'username',
          'course_nid',
          'status',
          'course_name',
          'score',
          'timestamp',
        ])
        ->condition('course_nid', $lp_ids, 'IN')
        ->orderBy('course_nid', 'ASC')
        ->execute()
        ->fetchAll();

      // Insert legacy statistic of Learning paths.
      $lps_results = [];
      foreach ($lps_statistic as $lp_stat) {
        if ($lp_stat->status == '1') {
          $status = 'completed';
          $progress = 100;
          $date_compleated = date('Y-m-d H-i-s', $lp_stat->timestamp);
        }
        else {
          $status = 'pending';
          $progress = 0;
          $date_compleated = NULL;
        }
        $connection = Database::getConnection();
        $connection->update('opigno_learning_path_achievements')
          ->fields([
            'status' => $status,
            'score' => $lp_stat->score,
            'progress' => $progress,
            'registered' => date('Y-m-d H-i-s', $lp_stat->timestamp),
            'completed' => $date_compleated,
          ])
          ->condition('gid', $lp_stat->course_nid)
          ->condition('uid', $lp_stat->uid)
          ->condition('name', $lp_stat->course_name)
          ->execute();

        $lps_results[$lp_stat->opigno_statistics_user_course_pk] = [
          'lp_id' => $lp_stat->course_nid,
          'uid' => $lp_stat->uid,
        ];
      }

      // Migrate general statistic of modules (lessons).
      $connection = Database::getConnection('default', 'legacy');
      $modules_statistic = $connection->select('opigno_statistics_user_course_details', 'o_s_u_c_d')
        ->fields('o_s_u_c_d', [
          'opigno_statistics_user_course_details_pk',
          'opigno_statistics_user_course_fk',
          'type',
          'entity_id',
          'entity_name',
          'score',
          'timestamp',
          'status',
          'required',
        ])
        ->execute()
        ->fetchAll();

      $modules_ids = \Drupal::entityQuery('opigno_module')->execute();
      foreach ($modules_statistic as $module_stat) {
        if (in_array($module_stat->entity_id, $modules_ids)) {
          $uid = $lps_results[$module_stat->opigno_statistics_user_course_fk]['uid'];
          $module_id = $module_stat->entity_id;

          $connection = Database::getConnection('default', 'legacy');

          $all_module_results = $connection->select('quiz_node_results', 'n')
            ->fields('n', ['time_end', 'time_start'])
            ->condition('nid', $module_id)
            ->condition('uid', $uid)
            ->condition('time_end', 0, '>')
            ->condition('is_evaluated', 1)
            ->execute()
            ->fetchAll();

          $module_total_time = 0;

          foreach ($all_module_results as $mod_res) {
            $module_total_time += $mod_res->time_end - $mod_res->time_start;
          }

          $module_result = $connection->select('quiz_node_results', 'n')
            ->fields('n', ['result_id', 'time_end', 'time_start'])
            ->condition('nid', $module_id)
            ->condition('uid', $uid)
            ->condition('is_evaluated', 1)
            ->orderBy('score', 'DESC')
            ->orderBy('vid', 'DESC')
            ->range(0, 1)
            ->execute()
            ->fetchAll();

          $h5p_results = $connection->select('quiz_h5p_user_results', 'h5p_r')
            ->distinct()
            ->fields('h5p_r')
            ->condition('result_id', $module_result[0]->result_id)
            ->execute()
            ->fetchAll();

          if ($module_stat->status == '1') {
            $status = 'passed';
            $completed = date('Y-m-d H-i-s', $module_stat->timestamp);
          }
          else {
            $status = 'failed';
            $completed = NULL;
          }

          $connection = Database::getConnection();
          $connection->insert('opigno_learning_path_step_achievements')
            ->fields([
              'uid',
              'entity_id',
              'name',
              'typology',
              'gid',
              'parent_id',
              'position',
              'status',
              'score',
              'time',
              'completed',
              'mandatory',
            ])
            ->values([
              'uid' => $lps_results[$module_stat->opigno_statistics_user_course_fk]['uid'],
              'entity_id' => $module_stat->entity_id,
              'name' => $module_stat->entity_name,
              'typology' => 'Module',
              'gid' => $lps_results[$module_stat->opigno_statistics_user_course_fk]['lp_id'],
              'parent_id' => 0,
              'position' => 0,
              'status' => $status,
              'score' => $module_stat->score,
              'time' => $module_total_time,
              'completed' => $completed,
              'mandatory' => 0,
            ])
            ->execute();

          $values = [
          // 'id' => $module_stat->opigno_statistics_user_course_details_pk,.
            'langcode' => 'en',
            'user_id' => $uid,
            'module' => $module_stat->entity_id,
            'score' => $module_stat->score,
            'max_score' => $module_stat->score,
            'given_answers' => NULL,
            'total_questions' => NULL,
            'percent' => NULL,
            'last_activity' => NULL,
            'current_activity' => NULL,
            'evaluated' => $module_stat->status,
            'started' => $module_stat->timestamp - $module_total_time,
            'finished' => $module_stat->timestamp,
          ];
          $entity = entity_create('user_module_status', $values);
          $entity->save();
          $ums_id = $entity->id();

          $connection = Database::getConnection();
          $query = $connection->insert('opigno_h5p_user_answer_results')->fields(['id', 'parent_id', 'question_id', 'question_vid', 'answer_id', 'answer_vid',
            'score_scaled', 'score_raw', 'score_min', 'score_max', 'interaction_type', 'description', 'correct_responses_pattern', 'response', 'additionals',
          ]);

          foreach ($h5p_results as $h5p_answer_result) {
            $query->values([
              'id' => $h5p_answer_result->id,
              'parent_id' => $h5p_answer_result->parent_id,
              'question_id' => $h5p_answer_result->question_nid,
              'question_vid' => $h5p_answer_result->question_vid,
              'answer_id' => $h5p_answer_result->result_id,
              'answer_vid' => $h5p_answer_result->result_id,
              'score_scaled' => $h5p_answer_result->score_scaled,
              'score_raw' => $h5p_answer_result->score_raw,
              'score_min' => $h5p_answer_result->score_min,
              'score_max' => $h5p_answer_result->score_max,
              'interaction_type' => $h5p_answer_result->interaction_type,
              'description' => $h5p_answer_result->description,
              'correct_responses_pattern' => $h5p_answer_result->correct_responses_pattern,
              'response' => $h5p_answer_result->response,
              'additionals' => $h5p_answer_result->additionals,
            ]);
          }

          $query->execute();

          $connection = Database::getConnection('default', 'legacy');
          $module_type = $connection->select('node', 'n')
            ->fields('n', ['type'])
            ->condition('nid', $module_id)
            ->execute()
            ->fetchField();

          // Check type of question.
          if ($module_type == 'quiz') {
            // Get all questions of that lesson.
            $questions = $connection->select('quiz_node_relationship', 'q_n_r')
              ->distinct()
              ->fields('q_n_r', ['child_nid', 'max_score'])
              ->condition('parent_nid', $module_id)
              ->execute()
              ->fetchAll();

            $questions_info = [];
            foreach ($questions as $question) {
              $questions_info[$question->child_nid] = [
                'child_nid' => $question->child_nid,
                'max_score' => $question->max_score,
              ];
            }

            foreach ($questions_info as $question) {
              // Get type of question.
              $question_type = $connection->select('node', 'n')
                ->fields('n', ['type'])
                ->condition('nid', $question['child_nid'])
                ->execute()
                ->fetchField();

              switch ($question_type) {
                case 'h5p_content':
                  $question_result = $connection->select('quiz_h5p_user_results', 'h5p_r')
                    ->fields('h5p_r', [
                      'score_scaled',
                      'correct_responses_pattern',
                      'response',
                      'interaction_type',
                      '	description',
                    ])
                    ->condition('question_nid', $question['child_nid'])
                    ->condition('result_id', $module_result[0]->result_id)
                    ->execute()
                    ->fetchAll();

                  $score = $question['max_score'] * $question_result[0]->score_scaled;

                  $values = [
                    'type' => 'opigno_h5p',
                    'user_id' => $lps_results[$module_stat->opigno_statistics_user_course_fk]['uid'],
                    'activity' => $question['child_nid'],
                    'module' => $module_id,
                    'score' => $score,
                    'evaluated' => 1,
                    'user_module_status' => $ums_id,
                  ];
                  $entity = entity_create('opigno_answer', $values);
                  $entity->save();
                  break;

                case 'opigno_scorm_quiz_question':
                  $question_result = $connection->select('opigno_scorm_quiz_user_results', 'scorm_r')
                    ->fields('scorm_r', ['score_scaled'])
                    ->condition('question_nid', $question['child_nid'])
                    ->condition('result_id', $module_result[0]->result_id)
                    ->execute()
                    ->fetchAll();

                  $score = $question['max_score'] * $question_result[0]->score_scaled;

                  $values = [
                    'type' => 'opigno_scorm',
                    'user_id' => $lps_results[$module_stat->opigno_statistics_user_course_fk]['uid'],
                    'activity' => $question['child_nid'],
                    'module' => $module_id,
                    'score' => $score,
                    'evaluated' => 1,
                    'user_module_status' => $ums_id,
                  ];
                  $entity = entity_create('opigno_answer', $values);
                  $entity->save();
                  break;

                case 'opigno_tincan_question_type':
                  $question_result = $connection->select('quiz_node_results_answers', 'q_n_r_a')
                    ->fields('q_n_r_a', ['points_awarded'])
                    ->condition('result_id', $module_result[0]->result_id)
                    ->condition('question_nid', $question['child_nid'])
                    ->execute()
                    ->fetchAll();

                  $score = $question_result[0]->points_awarded;

                  $values = [
                    'type' => 'opigno_tincan',
                    'user_id' => $lps_results[$module_stat->opigno_statistics_user_course_fk]['uid'],
                    'activity' => $question['child_nid'],
                    'module' => $module_id,
                    'score' => $score,
                    'evaluated' => 1,
                    'user_module_status' => $ums_id,
                  ];
                  $entity = entity_create('opigno_answer', $values);
                  $entity->save();
                  break;

                case 'long_answer':
                  $question_result = $connection->select('quiz_long_answer_user_answers', 'q_l_a')
                    ->fields('q_l_a', ['score', 'is_evaluated', 'answer'])
                    ->condition('result_id', $module_result[0]->result_id)
                    ->condition('question_nid', $question['child_nid'])
                    ->execute()
                    ->fetchAll();

                  $values = [
                    'type' => 'opigno_long_answer',
                    'user_id' => $lps_results[$module_stat->opigno_statistics_user_course_fk]['uid'],
                    'activity' => $question['child_nid'],
                    'module' => $module_id,
                    'score' => $question_result[0]->score,
                    'evaluated' => $question_result[0]->is_evaluated,
                    'user_module_status' => $ums_id,
                    'opigno_body' => $question_result[0]->answer,
                  ];
                  $entity = entity_create('opigno_answer', $values);
                  $entity->save();
                  break;

                case 'quizfileupload':
                  $question_result = $connection->select('quiz_fileupload_user_answers', 'q_f_a')
                    ->fields('q_f_a', ['score', 'is_evaluated', 'fid'])
                    ->condition('result_id', $module_result[0]->result_id)
                    ->condition('question_nid', $question['child_nid'])
                    ->execute()
                    ->fetchAll();

                  $values = [
                    'type' => 'opigno_file_upload',
                    'user_id' => $lps_results[$module_stat->opigno_statistics_user_course_fk]['uid'],
                    'activity' => $question['child_nid'],
                    'module' => $module_id,
                    'score' => $question_result[0]->score,
                    'evaluated' => $question_result[0]->is_evaluated,
                    'user_module_status' => $ums_id,
                    'opigno_file' => $question_result[0]->fid,
                  ];
                  $entity = entity_create('opigno_answer', $values);
                  $entity->save();
                  break;

                case 'quiz_directions':
                  $question_result = $connection->select('quiz_node_results_answers', 'q_n_r_a')
                    ->fields('q_n_r_a', ['points_awarded'])
                    ->condition('result_id', $module_result[0]->result_id)
                    ->condition('question_nid', $question['child_nid'])
                    ->execute()
                    ->fetchAll();

                  $score = $question_result[0]->points_awarded;

                  $values = [
                    'type' => 'opigno_slide',
                    'user_id' => $lps_results[$module_stat->opigno_statistics_user_course_fk]['uid'],
                    'activity' => $question['child_nid'],
                    'module' => $module_id,
                    'score' => $score,
                    'evaluated' => 1,
                    'user_module_status' => $ums_id,
                  ];
                  $entity = entity_create('opigno_answer', $values);
                  $entity->save();
                  break;
              }
            }
          }
        }
      }

      // Get and record correct time spending for learning paths.
      $connection = Database::getConnection();
      $all_lps = $connection->select('opigno_learning_path_achievements', 'o_l_p_a')
        ->fields('o_l_p_a', ['uid', 'gid', 'completed'])
        ->execute()
        ->fetchAll();

      foreach ($all_lps as $lp) {
        $all_modules_of_lp = $connection->select('opigno_learning_path_step_achievements', 'o_l_p_s_a')
          ->fields('o_l_p_s_a', ['time', 'completed'])
          ->condition('uid', $lp->uid)
          ->condition('gid', $lp->gid)
          ->execute()
          ->fetchAll();

        // Count total time for learning path.
        $total_time = 0;
        foreach ($all_modules_of_lp as $lp_module) {
          $total_time += $lp_module->time;
        }

        // Save total time for learning path.
        $connection->update('opigno_learning_path_achievements')
          ->fields([
            'time' => $total_time,
          ])
          ->condition('gid', $lp->gid)
          ->condition('uid', $lp->uid)
          ->execute();
      }
    }
  }

  /**
   * The migrate pre rollback event.
   *
   * @param \Drupal\migrate\Event\MigrateRollbackEvent $event
   *   Event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function onPreRollback(MigrateRollbackEvent $event) {
    $migration_id = $event->getMigration()->id();

    // Remove relationships.
    if (in_array($migration_id, [
      'opigno_activity_long_answer',
      'opigno_activity_file_upload',
      'opigno_activity_h5p',
    ])) {

      // Delete migrated activities and relationships.
      $db_connection = \Drupal::service('database');
      $nids = $db_connection->select('migrate_map_' . $migration_id, 'mm')
        ->fields('mm', ['destid1'])
        ->execute()->fetchCol();
      if ($nids) {
        if ($migration_id != 'opigno_activity_h5p') {
          // Delete migrated relationships.
          try {
            $db_connection->delete('opigno_module_relationship')
              ->condition('child_id', $nids, 'IN')
              ->execute();
          }
          catch (\Exception $e) {
            \Drupal::logger('opigno_groups_migration')->error($e->getMessage());
          }
        }
        else {
          // Delete migrated relationships.
          try {
            $db_connection->delete('opigno_activity__opigno_h5p')
              ->condition('opigno_h5p_h5p_content_id ', $nids, 'IN')
              ->execute();
          }
          catch (\Exception $e) {
            \Drupal::logger('opigno_groups_migration')->error($e->getMessage());
          }

          $nids = $db_connection->select('migrate_map_' . $migration_id, 'mm')
            ->fields('mm', ['sourceid1'])
            ->execute()->fetchCol();
          if ($nids) {
            // Delete migrated activities.
            $activities = \Drupal::entityTypeManager()->getStorage('opigno_activity')->loadMultiple($nids);
            foreach ($activities as $activity) {
              $activity->delete();
            }

            // Delete migrated relationships.
            try {
              $db_connection->delete('opigno_module_relationship')
                ->condition('child_id', $nids, 'IN')
                ->execute();
            }
            catch (\Exception $e) {
              \Drupal::logger('opigno_groups_migration')->error($e->getMessage());
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      MigrateEvents::POST_IMPORT => 'onPostImport',
      MigrateEvents::POST_ROW_SAVE => 'onPostRowSave',
      MigrateEvents::PRE_ROLLBACK => 'onPreRollback',
    ];
  }

}
