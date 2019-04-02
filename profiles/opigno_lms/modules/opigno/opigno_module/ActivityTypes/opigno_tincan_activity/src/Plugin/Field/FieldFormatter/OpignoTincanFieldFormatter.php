<?php

namespace Drupal\opigno_tincan_activity\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;

/**
 * Plugin implementation of the 'opigno_evaluation_method_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "opigno_tincan_field_formatter",
 *   label = @Translation("Opigno Tincan"),
 *   field_types = {
 *     "opigno_tincan_package"
 *   }
 * )
 */
class OpignoTincanFieldFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      $tincan_content_service = \Drupal::service('opigno_tincan_activity.tincan');
      $uri = $tincan_content_service->getExtractPath($file);
      $url = file_create_url($uri);
      $package_properties = $tincan_content_service->tincanLoadByFileEntity($file);
      $launch_file = $package_properties->launch_filename;

      // Create query parameters for Tincan launch file.
      $tincan_answer_assistant = \Drupal::service('opigno_tincan_activity.answer_assistant');
      $config = \Drupal::config('opigno_tincan_api.settings');
      $endpoint = $config->get('opigno_tincan_api_endpoint');
      $username = $config->get('opigno_tincan_api_username');
      $password = $config->get('opigno_tincan_api_password');

      $user = \Drupal::currentUser();
      $auth = 'Basic ' . base64_encode($username . ':' . $password);

      $actor = [
        'mbox_sha1sum' => sha1('mailto:' . $user->getEmail()),
        'name' => $user->getAccountName(),
      ];
      $activity = \Drupal::routeMatch()->getParameter('opigno_activity');
      $registration = $tincan_answer_assistant->getRegistration($activity, $user);

      $launch_file .=
        '?endpoint=' . rawurlencode($endpoint) .
        '&auth=' . rawurlencode($auth) .
        '&actor=' . rawurlencode(json_encode($actor)) .
        '&registration=' . rawurlencode($registration);

      // Returning data.
      $elements[$delta] = [
        '#type' => 'inline_template',
        '#template' => '<iframe style="{{ style }}" src="{{ url }}"></iframe>',
        '#context' => [
          'url' => $url . '/' . $launch_file,
          'style' => "width: 100%; min-height: 800px; border: 0;",
        ],
      ];
    }

    return $elements;
  }

}
