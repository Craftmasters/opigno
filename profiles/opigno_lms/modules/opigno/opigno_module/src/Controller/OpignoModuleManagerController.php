<?php

namespace Drupal\opigno_module\Controller;

use Drupal\file\Entity\File;
use Drupal\opigno_module\Entity\OpignoActivityType;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\SettingsCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\h5p\Entity\H5PContent;
use Drupal\h5p\H5PDrupal\H5PDrupal;
use Drupal\h5peditor\H5PEditor\H5PEditorUtilities;
use Drupal\image\Entity\ImageStyle;
use Drupal\opigno_module\Entity\OpignoActivity;
use Drupal\opigno_module\Entity\OpignoModule;
use Drupal\opigno_module\Entity\OpignoModuleInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller for all the actions of the Opigno module manager.
 */
class OpignoModuleManagerController extends ControllerBase {

  protected $H5PActivitiesDetails;

  /**
   * OpignoModuleManagerController constructor.
   */
  public function __construct() {
    $this->H5PActivitiesDetails = $this->getH5PActivitiesDetails();
  }

  /**
   * Check the access for the activity create/edit form.
   */
  public function accessItemForm(OpignoModuleInterface $opigno_module, AccountInterface $account) {
    // Allow access if the user is a platform-level content manager.
    if ($account->hasPermission('manage group content in any group')) {
      return AccessResult::allowed();
    }

    $item = \Drupal::routeMatch()->getParameter('item');
    if (isset($item) && $item > 0) {
      // If it is edit form, check activity update access.
      $activity = OpignoActivity::load($item);
      return $activity->access('update', $account, TRUE);
    }

    // Check activity create access.
    $create_access = \Drupal::entityTypeManager()
      ->getAccessControlHandler('opigno_activity')
      ->createAccess('opigno_activity', $account);
    if ($create_access) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

  /**
   * Method called when the manager needs a create or edit form.
   */
  public function getItemForm(OpignoModuleInterface $opigno_module, $type = NULL, $item = 0) {
    // Get the good form from the corresponding content type.
    if ($item > 0) {
      $entity = OpignoActivity::load($item);
      if (!$entity->access('update')) {
        throw new AccessDeniedHttpException();
      }
    }
    else {
      $entity = OpignoActivity::create([
        'type' => $type,
      ]);
    }

    /** @var \Drupal\Core\Entity\EntityFormBuilder $form_builder */
    $form_builder = \Drupal::service('entity.form_builder');
    $form_build = $form_builder->getForm($entity, 'default');

    $form_build['#attached']['library'][] = 'opigno_module/ajax_form';

    // Returns the form.
    return $form_build;
  }

  /**
   * Returns activity preview.
   */
  public function getActivityPreview(OpignoActivity $opigno_activity) {
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder($opigno_activity->getEntityTypeId());
    return $view_builder->view($opigno_activity, 'activity');
  }

  /**
   * Get activities bank for module with 'add' operation.
   *
   * This method does the same think as
   * \Drupal\opigno_module\Controller\LearningPathController::activitiesBank()
   * but is using in Training context (on Learning Path Manager page).
   */
  public function activitiesBankLPM() {
    // Output activities bank view.
    $build['activities_bank'] = views_embed_view('opigno_activities_bank_lp_interface');

    return $build;
  }

  /**
   * Ajax callback.
   */
  public function ajaxCheckedActivities() {
    if (!empty($_POST['data'])) {
      $data = json_decode($_POST['data']);

      $checkboxes_ids = !empty($_SESSION['activities_bank']['checkboxes_ids']) ? $_SESSION['activities_bank']['checkboxes_ids'] : [];
      $activities_ids = !empty($_SESSION['activities_bank']['activities_ids']) ? $_SESSION['activities_bank']['activities_ids'] : [];

      if (!empty($data->checked) && array_search($data->checked, $checkboxes_ids) === FALSE) {
        $checkboxes_ids[] = $data->checked;
        $activities_ids[] = $data->activityID;
      }
      elseif (!empty($data->unchecked)) {
        $key = array_search($data->unchecked, $checkboxes_ids);
        if ($key !== FALSE) {
          unset($checkboxes_ids[$key]);
        }
        $key = array_search($data->activityID, $activities_ids);
        if ($key !== FALSE) {
          unset($activities_ids[$key]);
        }
      }

      $_SESSION['activities_bank'] = [
        'checkboxes_ids' => $checkboxes_ids,
        'activities_ids' => $activities_ids,
      ];
    }

    die;
  }

  /**
   * Ajax form callback.
   */
  public static function ajaxFormEntityCallback(&$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // If errors, returns the form with errors and messages.
    if ($form_state->hasAnyErrors()) {
      return $form;
    }

    $entity = $form_state->getBuildInfo()['callback_object']->getEntity();

    $item = [];
    $item['id'] = $entity->id();
    $item['name'] = $entity->getName();

    $command = new SettingsCommand([
      'formValues' => $item,
      'messages' => drupal_get_messages(NULL, TRUE),
    ], TRUE);

    $response->addCommand($command);
    return $response;
  }

  /**
   * Submit handler added in the form by opigno_module_form_alter().
   *
   * @see opigno_module_form_alter()
   */
  public static function ajaxFormEntityFormSubmit($form, FormState &$form_state) {
    // Gets back the content type and module id.
    $build_info = $form_state->getBuildInfo();
    $params = \Drupal::routeMatch()->getParameters();

    $module = $params->get('opigno_module');
    $type_id = $params->get('type');
    $item_id = $params->get('item');

    // If one information missing, return an error.
    if (!isset($module) || !isset($type_id)) {
      // TODO: Add an error message here.
      return;
    }

    // Get the newly or edited entity.
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $build_info['callback_object']->getEntity();

    // Clear user input.
    $input = $form_state->getUserInput();
    // We should not clear the system items from the user input.
    $clean_keys = $form_state->getCleanValueKeys();
    $clean_keys[] = 'ajax_page_state';

    foreach ($input as $key => $item) {
      if (!in_array($key, $clean_keys)
        && substr($key, 0, 1) !== '_') {
        unset($input[$key]);
      }
    }

    // Store new entity for display in the AJAX callback.
    $input['entity'] = $entity;
    $form_state->setUserInput($input);

    // Rebuild the form state values.
    $form_state->setRebuild();
    $form_state->setStorage([]);

    // Assign activity to module if entity is new.
    if (!isset($item_id)) {
      /** @var \Drupal\opigno_module\Controller\OpignoModuleController $opigno_module_controller */
      $opigno_module_controller = \Drupal::service('opigno_module.opigno_module');
      $opigno_module_controller->activitiesToModule([$entity], $module);
    }
  }

  /**
   * Checks access for the activities overview.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Current user.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Access result.
   */
  public function accessActivitiesOverview(AccountInterface $account) {
    // Allow access if the user is a platform-level content manager.
    if ($account->hasPermission('manage group content in any group')) {
      return AccessResult::allowed();
    }

    // Allow access if the user is a group-level content manager in any group.
    $membership_service = \Drupal::service('group.membership_loader');
    $memberships = $membership_service->loadByUser($account);
    foreach ($memberships as $membership) {
      /** @var \Drupal\group\GroupMembership $membership */
      $group = $membership->getGroup();
      if ($group->access('update', $account)) {
        return AccessResult::allowed();
      }
    }

    return AccessResult::forbidden();
  }

  /**
   * Get the list of the existing activity types.
   */
  public function getActivityTypes() {
    // Get activity types.
    $types = \Drupal::entityTypeManager()->getStorage('opigno_activity_type')->loadMultiple();
    $types = array_filter($types, function ($type) {
      /** @var \Drupal\opigno_module\Entity\OpignoActivityType $type */
      return $type->id() !== 'opigno_h5p';
    });

    $types = array_map(function ($type) {
      /** @var \Drupal\opigno_module\Entity\OpignoActivityType $type */
      return [
        'bundle' => $type->id(),
        'name' => $type->label(),
        'description' => $this->getNonH5PDescription($type),
        'external_package' => FALSE,
      ];
    }, $types);

    // Get H5P libraries.
    /** @var \Drupal\h5p\H5PDrupal\H5PDrupal $interface */
    $interface = H5PDrupal::getInstance();
    $libraries = $interface->loadLibraries();

    // Flatten libraries array.
    $libraries = array_map(function ($library) {
      return $library[0];
    }, $libraries);

    // Filter runnable libraries.
    $libraries = array_filter($libraries, function ($library) {
      return $library->runnable == 1;
    });

    // Get library data.
    $libraries = array_map(function ($library) {
      return [
        'bundle' => 'opigno_h5p',
        'library' => $library->name,
        'name' => 'H5P ' . $library->title,
        'description' => $this->getH5PDescription($library->name),
        'major_version' => $library->major_version,
        'minor_version' => $library->minor_version,
        'external_package' => FALSE,
      ];
    }, $libraries);

    $types = array_merge($types, $libraries);

    // Remove unwanted activities.
    unset(
      $types['H5P.Dialogcards'],
      $types['H5P.MarkTheWords'],
      $types['H5P.Audio'],
      $types['H5P.Summary'],
      $types['H5P.TwitterUserFeed'],
      $types['H5P.AppearIn'],
      $types['H5P.SingleChoiceSet']
    );

    // Set external package types.
    $types['opigno_scorm']['external_package'] = TRUE;
    $types['opigno_tincan']['external_package'] = TRUE;

    if (self::getPptConvertAllow()) {
      $ppt = [
        'bundle' => 'external_package_ppt',
        'name' => $this->t('H5P PPT(X) Course Presentation'),
        'description' => $this->t('This activity allows creation H5P Course Presentation content imported from PowerPoint files. Slides from PowerPoint presentation will be imported as images into H5P Course Presentation slider. Allowed file extensions are ppt/pptx.'),
      ];
      if (array_key_exists('H5P.CoursePresentation', $types)) {
        // Place new item after H5P.CoursePresentation element.
        $types_updated = [];
        foreach ($types as $key => $type) {
          $types_updated[$key] = $type;
          if ($key == 'H5P.CoursePresentation') {
            $types_updated['external_package_ppt'] = $ppt;
          }
        }
        $types = $types_updated;
      }
      else {
        // Place new item at the end of the types array.
        $types['external_package_ppt'] = $ppt;
      }
    }

    $this->array_unshift_assoc($types, 'external_package', [
      'bundle' => 'external_package',
      'name' => $this->t('External package'),
      'description' => $this->t('This activity allows to easily load training packages created externally. The following formats are supported: SCORM 1.2 and 2004, TinCan and H5P.'),
    ]);

    return new JsonResponse($types, Response::HTTP_OK);
  }

  /**
   * Returns H5P Activities Details.
   */
  public function getH5PActivitiesDetails() {
    $editor = H5PEditorUtilities::getInstance();
    $content_types = $editor->ajaxInterface->getContentTypeCache();
    return $content_types;
  }

  /**
   * Returns non H5P description.
   *
   * @param \Drupal\opigno_module\Entity\OpignoActivityType $activity
   *   Activity.
   *
   * @return null|string
   *   Non H5P description.
   */
  public function getNonH5PDescription(OpignoActivityType $activity) {
    $html = NULL;

    $html .= '<p class="summary">' . $activity->getSummary() . '</p>';
    $html .= '<p class="description">' . $activity->getDescription() . '</p>';

    if ($image_id = $activity->getImageId()) {
      if ($image = File::load($image_id)) {
        $image_url = ImageStyle::load('large')->buildUrl($image->getFileUri());
        $html .= '<p class="images">';
        $html .= '<img src="' . $image_url . '" alt="" />';
        $html .= '</p>';
      }
    }

    return $html;
  }

  /**
   * Returns ppt convert allow flag.
   */
  public static function getPptConvertAllow() {
    static $allow;

    if (!isset($allow)) {
      $allow = FALSE;

      $module = FALSE;
      $moduleHandler = \Drupal::service('module_handler');
      if ($moduleHandler->moduleExists('h5p')) {
        $module = TRUE;
      }

      $libreoffice = FALSE;
      if (shell_exec('libreoffice --version')) {
        $libreoffice = TRUE;
      }

      $imagemagick = FALSE;
      if (shell_exec('convert --version')) {
        $imagemagick = TRUE;
      }

      $h5p_library = FALSE;
      $editor = H5PEditorUtilities::getInstance();
      $content_types = $editor->ajaxInterface->getContentTypeCache();
      if ($content_types) {
        foreach ($content_types as $activity) {
          if ($activity->machine_name == 'H5P.CoursePresentation') {
            $h5p_library = TRUE;
            break;
          }
        }
      }

      if ($module && $libreoffice && $imagemagick && $h5p_library) {
        $allow = TRUE;
      }
    }

    return $allow;
  }

  /**
   * Returns H5P description.
   */
  public function getH5PDescription($libTitle) {
    $html = NULL;

    foreach ($this->H5PActivitiesDetails as $H5PActivityDetail) {
      if ($H5PActivityDetail->machine_name == $libTitle) {
        $html .= '<p class="summary">' . $H5PActivityDetail->summary . '</p>';
        $html .= '<p class="description">' . $H5PActivityDetail->description . '</p>';

        $screenshots = json_decode($H5PActivityDetail->screenshots);
        if ($screenshots) {
          $html .= '<p class="images">';
          foreach ($screenshots as $screenshot) {
            $html .= '<img src="' . $screenshot->url . '" alt="" />';
          }
          $html .= '</p>';
        }
        break;
      }
    }

    return $html;
  }

  /**
   * Get the list of the existing activities.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getActivitiesList() {
    $activities = \Drupal::entityTypeManager()
      ->getStorage('opigno_activity')
      ->loadMultiple();

    $list = array_map(function ($activity) {
      /** @var \Drupal\opigno_module\Entity\OpignoActivity $activity */
      $data = [];
      $data['name'] = $activity->label();
      $data['activity_id'] = $activity->id();
      $data['type'] = $activity->bundle();

      // If H5P content, add library info.
      if ($data['type'] === 'opigno_h5p') {
        $value = $activity->get('opigno_h5p')->getValue();
        if ($value && $activity->get('opigno_h5p')->getValue()[0]['h5p_content_id'] !== NULL) {
          $cid = $activity->get('opigno_h5p')->getValue()[0]['h5p_content_id'];

          if ($content = H5PContent::load($cid)) {
            $library = $content->getLibrary();
            $data['library'] = $library->name;
          }
        }
      }

      return $data;
    }, $activities);

    return new JsonResponse($list, Response::HTTP_OK);
  }

  /**
   * Add existing activity to the module.
   */
  public function addActivityToModule(OpignoModule $opigno_module, OpignoActivity $opigno_activity, Request $request) {
    $opigno_module_controller = \Drupal::service('opigno_module.opigno_module');
    $opigno_module_controller->activitiesToModule([$opigno_activity], $opigno_module);

    return new JsonResponse([], Response::HTTP_OK);
  }

  /**
   * Checks access for the activity update weight.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Current user.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Access result.
   */
  public function accessActivityUpdateWeight(AccountInterface $account) {
    $datas = json_decode(\Drupal::request()->getContent());

    // Check that current user can edit all parent Opigno modules.
    /* @var $db_connection \Drupal\Core\Database\Connection */
    $db_connection = \Drupal::service('database');
    $omr_ids = array_map(function ($value) {
      return $value->omr_id;
    }, $datas->acitivies_weight);
    $module_ids = $db_connection
      ->select('opigno_module_relationship', 'omr')
      ->fields('omr', ['parent_id'])
      ->condition('omr_id', $omr_ids, 'IN')
      ->groupBy('parent_id')
      ->execute()
      ->fetchCol();
    $modules = OpignoModule::loadMultiple($module_ids);
    foreach ($modules as $module) {
      /** @var \Drupal\opigno_module\Entity\OpignoModule $module */
      if (!$module->access('update')) {
        return AccessResult::forbidden();
      }
    }

    return AccessResult::allowed();
  }

  /**
   * Update activity weight.
   */
  public function activityUpdateWeight(Request $request) {
    $datas = json_decode($request->getContent());
    if (empty($datas->acitivies_weight)) {
      return new JsonResponse(NULL, Response::HTTP_BAD_REQUEST);
    }

    $db_connection = \Drupal::service('database');
    foreach ($datas->acitivies_weight as $value) {
      $db_connection->merge('opigno_module_relationship')
        ->keys([
          'omr_id' => $value->omr_id,
        ])
        ->fields([
          'weight' => $value->weight,
        ])
        ->execute();
    }
    return new JsonResponse(NULL, Response::HTTP_OK);
  }

  /**
   * Array unshift.
   */
  public function array_unshift_assoc(&$arr, $key, $val) {
    $arr = array_reverse($arr, TRUE);
    $arr[$key] = $val;
    $arr = array_reverse($arr, TRUE);
    return $arr;
  }

}
