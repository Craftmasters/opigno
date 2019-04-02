<?php

namespace Drupal\opigno_module\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\h5p\H5PDrupal\H5PDrupal;
use Drupal\opigno_module\Entity\OpignoActivity;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\SettingsCommand;

/**
 * Class ActivitiesBrowserController.
 *
 * @package Drupal\opigno_module\Controller
 */
class ExternalPackageController extends ControllerBase {

  /**
   * External package form ajax callback.
   */
  public static function ajaxFormExternalPackageCallback(&$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // If errors, returns the form with errors and messages.
    if ($form_state->hasAnyErrors()) {
      return $form;
    }

    if ($entity = $form_state->getUserInput()['activity']) {
      $item = [];
      $item['id'] = $entity->id();
      $item['name'] = $entity->getName();

      $command = new SettingsCommand([
        'formValues' => $item,
        'messages' => drupal_get_messages(NULL, TRUE),
      ], TRUE);
    }
    else {
      $command = new SettingsCommand([
        'messages' => drupal_get_messages(NULL, TRUE),
      ], TRUE);
    }

    $response->addCommand($command);

    return $response;
  }

  /**
   * Custom submit handler added via the function opigno_module_form_alter().
   *
   * @see opigno_module_form_alter()
   */
  public static function ajaxFormExternalPackageFormSubmit($form, FormState &$form_state) {
    $fid = $form_state->get('package');
    $file = File::load($fid);
    $params = \Drupal::routeMatch()->getParameters();
    $module = $params->get('opigno_module');

    // If one information missing, return an error.
    if (!isset($module)) {
      // TODO: Add an error message here.
      return;
    }

    if (!empty($file)) {
      // Get file extension.
      $extension = ExternalPackageController::getFileExtension($file->getFilename());
      switch ($extension) {
        case 'zip':
          // Check file type. Can be "scorm" or "tincan".
          $type = ExternalPackageController::checkPackageType($file);

          if (!$type) {
            \Drupal::messenger()->addError(t("Package does not contain required files."));
            return $form_state->setRebuild();
          }
          // Create activity.
          $entity = ExternalPackageController::createActivityByPackageType($file, $type, $form, $form_state);
          break;

        case 'h5p':
          $entity = ExternalPackageController::createActivityByPackageType($file, 'h5p', $form, $form_state);

          $storage = $form_state->getStorage();
          if (!empty($storage['mode']) && $storage['mode'] == 'ppt') {
            $ppt_dir = ExternalPackageController::getPptConversionDir();
            $public_files_real_path = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
            $ppt_dir_real_path = $public_files_real_path . '/' . $ppt_dir;
            // Clean up extra files.
            self::cleanDirectoryFiles($ppt_dir_real_path, [$file]);
          }
          break;
      }
      if (!$entity) {
        \Drupal::messenger()->addWarning(t("Can't create activity."));
      }
      else {
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
        $input['activity'] = $entity;
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
    }

    return $form_state->setRebuildInfo([t("Can't create an activity. File can't be uploaded.")]);
  }

  /**
   * Function for checking what package type was downloaded.
   *
   * @return string|bool
   *   Returned 'scorm' or 'tincan' or FALSE.
   */
  protected function checkPackageType($file) {
    // Unzip file.
    $path = \Drupal::service('file_system')->realpath($file->getFileUri());
    $zip = new \ZipArchive();
    $result = $zip->open($path);
    if ($result === TRUE) {
      $extract_dir = 'public://external_package_extracted/package_' . $file->id();
      $zip->extractTo($extract_dir);
      $zip->close();

      // This is a standard: these files must always be here.
      $scorm_file = $extract_dir . '/imsmanifest.xml';
      $tincan_file = $extract_dir . '/tincan.xml';

      if (file_exists($scorm_file)) {
        $package_type = 'scorm';
      }
      elseif (file_exists($tincan_file)) {
        $package_type = 'tincan';
      }
      // Delete extracted archive.
      file_unmanaged_delete_recursive($extract_dir);

      if (isset($package_type)) {
        return $package_type;
      }

    }
    return FALSE;
  }

  /**
   * Function for creating activity depending file type.
   */
  protected function createActivityByPackageType(File $file, $package_type, array &$form, FormStateInterface $form_state, $ppt_dir_real_path = NULL) {

    switch ($package_type) {
      // Create Scorm_package activity.
      case 'scorm':
        $activity = OpignoActivity::create([
          'type' => 'opigno_scorm',
          'name' => $form_state->getValue('name'),
          'opigno_scorm_package' => [
            'target_id' => $file->id(),
          ],
        ]);

        $activity->save();
        break;

      // Create Tincan_package activity.
      case 'tincan':
        // Check if Tincan PHP library is installed.
        $has_library = opigno_tincan_api_tincanphp_is_installed();
        if (!$has_library) {
          \Drupal::logger('opigno_module')->error($this->t('Impossible to create a new TinCan Package activity. Tincan PHP Library is not installed.'));
          return FALSE;
        };
        // Check if the LRS settings are set.
        $config = \Drupal::config('opigno_tincan_api.settings');
        $endpoint = $config->get('opigno_tincan_api_endpoint');
        $username = $config->get('opigno_tincan_api_username');
        $password = $config->get('opigno_tincan_api_password');

        if (empty($endpoint) || empty($username) || empty($password)) {
          \Drupal::logger('opigno_module')->error($this->t('Impossible to create a new TinCan Package activity. LRS is not configured.'));
          return FALSE;
        }
        $activity = OpignoActivity::create([
          'type' => 'opigno_tincan',
          'name' => $form_state->getValue('name'),
          'opigno_tincan_package' => [
            'target_id' => $file->id(),
          ],
        ]);

        $activity->save();
        break;

      // Create Interactive content activity.
      case 'h5p':
        $storage = $form_state->getStorage();
        $mode = NULL;
        if (!empty($storage['mode']) && $storage['mode'] == 'ppt') {
          $mode = 'ppt';
        }

        $h5p_content_id = ExternalPackageController::createH5pContent($file, $mode);
        if (!$h5p_content_id) {
          \Drupal::messenger()->addError($this->t("Can't create h5p content. Wrong h5p package."));
          return FALSE;
        };
        $activity = OpignoActivity::create([
          'type' => 'opigno_h5p',
          'name' => $form_state->getValue('name'),
          'opigno_h5p' => [
            'h5p_content_id' => $h5p_content_id,
          ],
        ]);

        $activity->save();
        break;
    }

    return $activity;
  }

  /**
   * Function for creating h5p content for Interactive content activity.
   *
   * @param \Drupal\file\Entity\File $file
   *   File object.
   * @param string $mode
   *   Kind of creating functionality.
   *
   * @return int|bool
   *   Return h5p content id or FALSE.
   */
  protected function createH5pContent(File $file, $mode = NULL) {
    $file_field = 'package';

    // Prepare temp folder.
    $interface = H5PDrupal::getInstance('interface', $file_field);
    $temporary_file_path = ($mode && $mode == 'ppt') ? 'public://' . ExternalPackageController::getPptConversionDir() : 'public://external_packages';

    // Tell H5P Core where to look for the files.
    $interface->getUploadedH5pPath(\Drupal::service('file_system')
      ->realpath($file->getFileUri()));
    $interface->getUploadedH5pFolderPath(\Drupal::service('file_system')
      ->realpath($temporary_file_path));

    // Call upon H5P Core to validate the contents of the package.
    $validator = H5PDrupal::getInstance('validator', $file_field);
    $validator->isValidPackage();

    // Store the uploaded file.
    $storage = H5PDrupal::getInstance('storage', $file_field);

    $content = [
      'id' => NULL,
      'uploaded' => TRUE,
      'disable' => 0,
    ];

    // Save and update content id.
    $storage->savePackage($content);
    $content_id = $storage->contentId;

    if (!$content_id) {
      return FALSE;
    };
    return $content_id;
  }

  /**
   * Returns file extension.
   *
   * @param string $file_name
   *   File name.
   *
   * @return string
   *   File extension.
   */
  protected function getFileExtension($file_name) {
    return substr(strrchr($file_name, '.'), 1);
  }

  /**
   * Converts PowerPoint files to images per slide.
   *
   * @param \Drupal\File\entity\File $file
   *   PowerPoint file (ppt/pptx).
   * @param string $ppt_dir_real_path
   *   Ppt conversion directory real path.
   *
   * @return array
   *   Array of image files links.
   */
  public static function convertPptSlidesToImages(File $file, $ppt_dir_real_path) {
    $current_dir = getcwd();

    \Drupal::logger('ppt_converter')->notice('Current dir: ' . $current_dir);

    if (chdir($ppt_dir_real_path)) {
      $path_info = pathinfo($file->getFilename());

      \Drupal::logger('ppt_converter')->notice('Changed dir: ' . getcwd());
      \Drupal::logger('ppt_converter')->notice('File $path_info: <pre><code>' . print_r($path_info, TRUE) . '</code></pre>');
      \Drupal::logger('ppt_converter')->notice('Starting convert to PDF.');

      // Convert to pdf.
      $libreoffice_configs_path = self::getLibreofficeConfigsDir();
      $a1 = microtime(TRUE);
      $result = exec('libreoffice -env:UserInstallation=file://' . $libreoffice_configs_path . ' --headless --invisible --convert-to pdf ' . $path_info['basename']);
      $a2 = microtime(TRUE);
      $converting_time = $a2 - $a1;

      \Drupal::logger('ppt_converter')->notice('Convert to pdf finished. Time of converting: ' . $converting_time);

      if ($result) {
        \Drupal::logger('ppt_converter')->notice('Starting convert to jpg.');

        // Convert to images.
        exec("convert " . $path_info['filename'] . ".pdf -geometry x720 -gravity Center " . $path_info['filename'] . ".jpg");

        \Drupal::logger('ppt_converter')->notice('Convert to jpg finished.');

        $files = file_scan_directory($ppt_dir_real_path, '/.*\.(jpg)$/');

        // Sort images by slides order.
        foreach ($files as &$f) {
          $filename_exploded = explode('-', $f->name);
          $f->weight = end($filename_exploded);
        }
        usort($files, 'self::opignoH5pSlidesSortByWeight');

        chdir($current_dir);

        \Drupal::logger('ppt_converter')->notice('Return to dir: ' . getcwd());

        return $files;
      }
    }

    chdir($current_dir);

    \Drupal::logger('ppt_converter')->notice('Return to dir: ' . getcwd());

    return [];
  }

  /**
   * Custom sort by date function.
   */
  public static function opignoH5pSlidesSortByWeight($a, $b) {
    return $a->weight > $b->weight;
  }

  /**
   * Creates H5P content package file.
   *
   * @param array|mixed $images
   *   Array of images with properties.
   * @param string $ppt_dir_real_path
   *   Real path to ppt directory.
   * @param string $title
   *   Presentation activity title.
   */
  public static function createH5pCoursePresentationPackage(array $images, $ppt_dir_real_path, $title) {
    $libraries = [
      'H5P.CoursePresentation',
      'FontAwesome',
      'H5P.FontIcons',
      'H5P.JoubelUI',
      'Drop',
      'Tether',
      'H5P.Transition',
    ];

    $libraries_data = [];
    foreach ($libraries as $library) {
      $libraries_data[$library] = self::getH5PLibraryData($library);
    }

    $h5p_json_string = '{"title":"Interactive Content","language":"und","mainLibrary":"H5P.CoursePresentation","embedTypes":["div"],"preloadedDependencies":[{"machineName":"H5P.CoursePresentation","majorVersion":"1","minorVersion":"17"},{"machineName":"FontAwesome","majorVersion":"4","minorVersion":"5"},{"machineName":"H5P.FontIcons","majorVersion":"1","minorVersion":"0"},{"machineName":"H5P.JoubelUI","majorVersion":"1","minorVersion":"3"},{"machineName":"Drop","majorVersion":"1","minorVersion":"0"},{"machineName":"Tether","majorVersion":"1","minorVersion":"0"},{"machineName":"H5P.Transition","majorVersion":"1","minorVersion":"0"}]}';
    $h5p_array = json_decode($h5p_json_string);

    // Update libraries numbers to last versions.
    foreach ($h5p_array->preloadedDependencies as $key => &$dependency) {
      $dependency->majorVersion = $libraries_data[$dependency->machineName]->major_version;
      $dependency->minorVersion = $libraries_data[$dependency->machineName]->minor_version;
    }

    $h5p_json_string = json_encode($h5p_array);

    $content_json_string = '{"presentation":{"slides":[],"keywordListEnabled":true,"globalBackgroundSelector":{},"keywordListAlwaysShow":false,"keywordListAutoHide":false,"keywordListOpacity":90},"l10n":{"slide":"Slide","score":"Score","yourScore":"Your Score","maxScore":"Max Score","goodScore":"Congratulations! You got @percent correct!","okScore":"Nice effort! You got @percent correct!","badScore":"You got @percent correct.","total":"Total","totalScore":"Total Score","showSolutions":"Show solutions","retry":"Retry","title":"Title","author":"Author","lisence":"License","license":"License","exportAnswers":"Export text","copyright":"Rights of use","hideKeywords":"Hide keywords list","showKeywords":"Show keywords list","fullscreen":"Fullscreen","exitFullscreen":"Exit fullscreen","prevSlide":"Previous slide","nextSlide":"Next slide","currentSlide":"Current slide","lastSlide":"Last slide","solutionModeTitle":"Exit solution mode","solutionModeText":"Solution Mode","summaryMultipleTaskText":"Multiple tasks","scoreMessage":"You achieved:","shareFacebook":"Share on Facebook","shareTwitter":"Share on Twitter","shareGoogle":"Share on Google+","summary":"Summary","solutionsButtonTitle":"Show comments","printTitle":"Print","printIngress":"How would you like to print this presentation?","printAllSlides":"Print all slides","printCurrentSlide":"Print current slide","noTitle":"No title","accessibilitySlideNavigationExplanation":"Use left and right arrow to change slide in that direction whenever canvas is selected.","accessibilityCanvasLabel":"Presentation canvas. Use left and right arrow to move between slides.","containsNotCompleted":"@slideName contains not completed interaction","containsCompleted":"@slideName contains completed interaction","slideCount":"Slide @index of @total","containsOnlyCorrect":"@slideName only has correct answers","containsIncorrectAnswers":"@slideName has incorrect answers","shareResult":"Share Result"},"override":{"activeSurface":false,"hideSummarySlide":false,"enablePrintButton":false,"social":{"showFacebookShare":true,"facebookShare":{"url":"@currentpageurl","quote":"I scored @score out of @maxScore on a task at @currentpageurl."},"showTwitterShare":false,"twitterShare":{"statement":"I scored @score out of @maxScore on a task at @currentpageurl.","url":"@currentpageurl","hashtags":"h5p, course"},"showGoogleShare":false,"googleShareUrl":"@currentpageurl"}}}';
    $content_array = json_decode($content_json_string);

    foreach ($images as $key => $image) {
      $dimensions = getimagesize($image->uri);
      $content_array->presentation->slides[] = [
        'slideBackgroundSelector' => [
          'imageSlideBackground' => [
            'path' => 'images/' . $image->filename,
            'mime' => 'image/jpeg',
            'copyright' => [
              'license' => 'MIT',
            ],
            'width' => $dimensions[0],
            'height' => $dimensions[1],
          ],
        ],
        'keywords' => [
          [
            'main' => $title . ' - slide ' . $key,
          ],
        ],
      ];
    }

    $content_json_string = json_encode($content_array);

    $zip = new \ZipArchive();
    $zip->open($ppt_dir_real_path . '/content.zip', constant('ZipArchive::CREATE'));
    foreach ($images as $image) {
      $zip->addFile($image->uri, 'content/images/' . $image->filename);
    }

    file_put_contents($ppt_dir_real_path . '/content.json', $content_json_string);
    $zip->addFile($ppt_dir_real_path . '/content.json', 'content/content.json');

    file_put_contents($ppt_dir_real_path . '/h5p.json', $h5p_json_string);
    $zip->addFile($ppt_dir_real_path . '/h5p.json', 'h5p.json');

    $zip->close();

    rename($ppt_dir_real_path . '/content.zip', $ppt_dir_real_path . '/ppt-content-import.h5p');
  }

  /**
   * Cleans up ppt conversion directory.
   *
   * @param string $ppt_dir_real_path
   *   Real path to Drupal public files.
   * @param array $files
   *   Uploaded file objects.
   */
  public static function cleanDirectoryFiles($ppt_dir_real_path, array $files = NULL) {
    // Delete uploaded files from database.
    if ($files) {
      foreach ($files as $file) {
        $db_connection = \Drupal::service('database');
        $db_connection->delete('file_managed')
          ->condition('uri', $file->getFileUri())
          ->condition('fid', $file->id())
          ->execute();
      }
    }

    // Delete files in a directory.
    $files = file_scan_directory($ppt_dir_real_path, '/.*\.*$/');
    if ($files) {
      foreach ($files as $file) {
        if (file_exists($file->uri)) {
          file_unmanaged_delete($file->uri);
        }
      }
    }
  }

  /**
   * Returns ppt conversion directory for current user.
   */
  public static function getPptConversionDir() {
    $user = \Drupal::currentUser();
    return OPIGNO_MODULE_PPT_TEMP_DIR . '/' . $user->id();
  }

  /**
   * Returns library data.
   *
   * @param string $machine_name
   *   Library machine name.
   *
   * @return array|mixed
   *   H5P library data.
   */
  public static function getH5PLibraryData($machine_name) {
    $db_connection = \Drupal::service('database');
    // Get new library id with highest version.
    $query = $db_connection->select('h5p_libraries', 'l')
      ->fields('l', [
        'library_id',
        'machine_name',
        'major_version',
        'minor_version',
      ])
      ->orderBy('major_version', 'DESC')
      ->orderBy('minor_version', 'DESC')
      ->condition('machine_name', $machine_name);
    $result = $query->execute()->fetchAllAssoc('library_id');

    if ($result) {
      return reset($result);
    }

    return [];
  }

  /**
   * Returns libreoffice configurations directory.
   */
  public static function getLibreofficeConfigsDir() {
    $config = \Drupal::configFactory()->get('opigno_module.settings');
    $output = $config->get('libreoffice_configs_path', '/tmp/LibreOffice_Conversion_${USER}');
    $output = !empty($output) ? $output : '/tmp/LibreOffice_Conversion_${USER}';
    return $output;
  }

}
