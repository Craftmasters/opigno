<?php

/**
 * @file
 * Builds placeholder replacement tokens for opigno_certificate-related data.
 */

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function opigno_certificate_token_info() {
  $type = [
    'name' => t('Certificates'),
    'description' => t('Tokens related to certificates.'),
    'needs-data' => 'opigno_certificate',
  ];

  $opigno_certificate['referencing_entity'] = [
    'name' => t("Referencing entity"),
    'type' => 'entity',
  ];

  return [
    'types' => ['group' => $type],
    'tokens' => ['group' => $opigno_certificate],
  ];
}

/**
 * Implements hook_tokens().
 */
function opigno_certificate_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $url_options = ['absolute' => TRUE];
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = LanguageInterface::LANGCODE_DEFAULT;
  }
  $replacements = [];

  if ($type == 'opigno_certificate' && !empty($data['opigno_certificate'])) {
    $opigno_certificate = $data['opigno_certificate'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        // Default values for the chained tokens handled below.
        // @todo: the referencing_entity tokens don't work, but also aren't
        // necessary if we don't use the token browser.
        case 'referencing_entity':
          $entity = $opigno_certificate->referencing_entity->entity;
          if ($entity) {
            $bubbleable_metadata->addCacheableDependency($entity);
            $replacements[$original] = $entity->label();
          }
          break;

        case 'created':
          $target = $opigno_certificate->get('referencing_entity')->getValue()[0];

          if (!empty($target)) {
            $gid = $target['target_id'];
            $uid = \Drupal::currentUser()->id();
            $completed_on = opigno_learning_path_completed_on($gid, $uid);
            $date_formatter = \Drupal::service('date.formatter');
            $replacements[$original] = $date_formatter->format($completed_on);
          }
          break;

        case 'changed':
          $date_format = DateFormat::load('medium');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = format_date($opigno_certificate->getChangedTime(), 'medium', '', NULL, $langcode);
          break;
      }
    }

    if ($opigno_certificate->referencing_entity->entity && $referencing_entity_tokens = $token_service->findWithPrefix($tokens, 'referencing_entity')) {
      $entity_type_id = $opigno_certificate->referencing_entity->entity->getEntityTypeId();
      $replacements += $token_service->generate($entity_type_id, $referencing_entity_tokens, [$entity_type_id => $opigno_certificate->referencing_entity->entity], $options, $bubbleable_metadata);
    }

  }

  return $replacements;
}
