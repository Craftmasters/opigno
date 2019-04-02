<?php

namespace Drupal\opigno_module\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\opigno_module\Controller\ExternalPackageController;

/**
 * Add External package form.
 */
class AddExternalPackageForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_external_package_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $mode = NULL) {
    $is_ppt = ($mode && $mode == 'ppt') ? TRUE : FALSE;
    if ($is_ppt) {
      $form_state->set('mode', $mode);
    }

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#required' => TRUE,
    ];

    $form['package'] = [
      '#title' => $this->t('Package'),
      '#type' => 'file',
      '#description' => !$is_ppt ? $this->t('Here you can upload external package. Allowed extensions: zip h5p') : $this->t('Here you can upload PowerPoint presentation file. Allowed extensions: ppt pptx'),
    ];

    $ajax_id = "ajax-form-entity-external-package";
    $form['#attributes']['class'][] = $ajax_id;
    $form['#attached']['library'][] = 'opigno_module/ajax_form';

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next'),
      '#ajax' => [
        'callback' => 'Drupal\opigno_module\Controller\ExternalPackageController::ajaxFormExternalPackageCallback',
        'wrapper' => $ajax_id,
        'effect' => 'fade',
      ],
    ];

    $form['actions']['submit']['#submit'][] = 'Drupal\opigno_module\Controller\ExternalPackageController::ajaxFormExternalPackageFormSubmit';

    $form['ajax_form_entity'] = [
      '#type' => 'hidden',
      '#value' => [
        'view_mode' => 'default',
        'reload' => TRUE,
        'content_selector' => ".$ajax_id",
        'form_selector' => ".$ajax_id",
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validation is optional.
    $file_field = "package";
    $storage = $form_state->getStorage();
    $is_ppt = (isset($storage['mode']) && $storage['mode'] == 'ppt') ? TRUE : FALSE;
    if (empty($_FILES['files']['name'][$file_field])) {
      // Only need to validate if the field actually has a file.
      $form_state->setError(
        $form['package'],
        $this->t("Files isn't uploaded.")
      );
    }

    // Prepare folder.
    $temporary_file_path = !$is_ppt ? 'public://external_packages' : 'public://' . ExternalPackageController::getPptConversionDir();
    file_prepare_directory($temporary_file_path, FILE_CREATE_DIRECTORY);

    // Prepare file validators.
    $extensions = !$is_ppt ? ['h5p zip'] : ['ppt pptx'];
    $validators = [
      'file_validate_extensions' => $extensions,
    ];
    // Validate file.
    if ($is_ppt) {
      $ppt_dir = ExternalPackageController::getPptConversionDir();
      $public_files_real_path = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
      $ppt_dir_real_path = $public_files_real_path . '/' . $ppt_dir;

      $file = file_save_upload($file_field, $validators, $temporary_file_path, NULL, FILE_EXISTS_REPLACE);

      // Rename uploaded file - remove special chars.
      $file_new = $file[0];
      $filename = $file_new->getFilename();
      $filename_new = preg_replace('/[^a-zA-Z0-9-_\.]/', '-', $filename);
      $file_new->setFilename($filename_new);
      $file_new->setFileUri($temporary_file_path . '/' . $filename_new);
      $file_new->save();
      rename($ppt_dir_real_path . '/' . $filename, $ppt_dir_real_path . '/' . $filename_new);

      if (!empty($file_new)) {
        // Actions on ppt(x) file upload.
        $ppt_dir = ExternalPackageController::getPptConversionDir();
        $public_files_real_path = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
        $ppt_dir_real_path = $public_files_real_path . '/' . $ppt_dir;

        $this->logger('ppt_converter')->notice('$ppt_dir_real_path: ' . $ppt_dir_real_path);

        $images = ExternalPackageController::convertPptSlidesToImages($file_new, $ppt_dir_real_path);

        if ($images) {

          \Drupal::logger('ppt_converter')->notice('$images: <pre><code>' . print_r($images, TRUE) . '</code></pre>');

          // Create H5P package in 'sites/default/files/external_packages_ppt'.
          ExternalPackageController::createH5pCoursePresentationPackage($images, $ppt_dir_real_path, $form_state->getValue('name'));
        }

        if (file_exists($temporary_file_path . '/ppt-content-import.h5p')) {
          // Replace form uploaded file with converted h5p content file.
          $file_new = File::load($file[0]->id());
          $file_new->setFilename('ppt-content-import.h5p');
          $file_new->setFileUri($temporary_file_path . '/ppt-content-import.h5p');
          $file_new->setMimeType('application/octet-stream');
          $file_new->save();

          $file[0] = $file_new;
        }
      }
    }
    else {
      $file = file_save_upload($file_field, $validators, $temporary_file_path);
    }

    if (!$file[0]) {
      return $form_state->setRebuild();
    };
    // Set file id in form state for loading on submit.
    $form_state->set('package', $file[0]->id());

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
