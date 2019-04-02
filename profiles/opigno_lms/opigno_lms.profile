<?php

/**
 * @file
 * Enables modules and site configuration for a opigno_lms site installation.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;

/**
 * Implements hook_preprocess_template().
 */
function opigno_lms_preprocess_install_page(&$variables) {
  $variables['site_version'] = '2.3';
}

/**
 * Implements hook_form_FORM_ID_alter() for install_configure_form().
 *
 * Allows the profile to alter the site configuration form.
 */
function opigno_lms_form_install_configure_form_alter(&$form, FormStateInterface $form_state) {
  $messenger = \Drupal::messenger();

  // Check if Tincan PHP library is installed.
  $has_library = opigno_tincan_api_tincanphp_is_installed();
  if (!$has_library) {
    $messenger->addWarning(Markup::create("Please install the TinCanPHP library using Composer, with the command: <em>composer require rusticisoftware/tincan:@stable</em>"));
  }
  else {
    // Check if the LRS settings are set.
    $config = \Drupal::config('opigno_tincan_api.settings');
    $endpoint = $config->get('opigno_tincan_api_endpoint');
    $username = $config->get('opigno_tincan_api_username');
    $password = $config->get('opigno_tincan_api_password');

    if (empty($endpoint) || empty($username) || empty($password)) {
      $messenger->addWarning(t(
        'Please configure the LRS connection in the @setting_page.',
        [
          '@setting_page' => Link::createFromRoute('settings page', 'opigno_tincan_api.settings_form')
            ->toString()
        ]
      ));
      return;
    }
  }

  // Send message for install pdf.js library if it's not installed.
  $pdf_js_library = file_exists('libraries/pdf.js/build/pdf.js') && file_exists('libraries/pdf.js/build/pdf.worker.js');
  if (!$pdf_js_library) {
    $message = t('pdf.js library is not installed. Please install it from <a href="@library">here</a> and place in <em>libraries/</em> folder', ['@library' => 'http://mozilla.github.io/pdf.js/getting_started/']);
    $messenger->addWarning(Markup::create($message));
  }

}

/**
 * Implements opigno_lms_check_opigno_lms_updates().
 *
 * Check if new Opigno LMS release is available.
 *
 * Return TRUE or FALSE.
 *
 */
function opigno_lms_check_opigno_lms_updates() {
  // Get all available updates.
  $available = update_get_available();

  if (isset($available['opigno_lms'])) {
    $all_releases = array_keys($available['opigno_lms']['releases']);
    $last_release = $all_releases[0];
    $current_release = opigno_lms_get_current_opigno_lms_release();
    $has_updates = ($last_release != $current_release);

    return $has_updates;
  }
}

/**
 * Implements opigno_lms_get_current_opigno_lms_release().
 *
 * Get current Opigno LMS release version.
 *
 * Return string with current release version or FALSE.
 *
 */
function opigno_lms_get_current_opigno_lms_release() {
  $profile = \Drupal::installProfile();
  if ($profile != 'opigno_lms') {
    return FALSE;
  };
  $info = system_get_info('module', $profile);
  if (!empty($info) && isset($info['version'])) {
    if (!isset($info['version'])) {
      \Drupal::logger('opigno_learning_path')
        ->notice(t('Opigno LMS version is undefined!'));
      return FALSE;
    }
    else {
      return $info['version'];
    }
  }
  return FALSE;
}

