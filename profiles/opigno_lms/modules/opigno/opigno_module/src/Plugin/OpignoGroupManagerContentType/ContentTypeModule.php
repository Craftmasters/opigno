<?php

namespace Drupal\opigno_module\Plugin\OpignoGroupManagerContentType;

use Drupal\file\Entity\File;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Url;
use Drupal\opigno_group_manager\ContentTypeBase;
use Drupal\opigno_group_manager\OpignoGroupContent;
use Drupal\opigno_module\Entity\OpignoModule;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Drupal\image\Entity\ImageStyle;

/**
 * Class ContentTypeModule.
 *
 * @package Drupal\opigno_module\Plugin\ContentTypeModule
 *
 * @OpignoGroupManagerContentType(
 *   id = "ContentTypeModule",
 *   entity_type = "opigno_module",
 *   readable_name = "Module",
 *   description = "Contains the Opigno modules",
 *   allowed_group_types = {
 *     "opigno_course",
 *     "learning_path"
 *   },
 *   group_content_plugin_id = "opigno_module_group"
 * )
 */
class ContentTypeModule extends ContentTypeBase {

  /**
   * Get the URL object of the main view page of a specific entity.
   *
   * @param int $entity_id
   *   The entity ID.
   *
   * @return \Drupal\Core\Url
   *   The tool entity URL.
   */
  public function getViewContentUrl($entity_id) {
    return Url::fromRoute('entity.opigno_module.canonical', ['opigno_module' => $entity_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function getStartContentUrl($entity_id, $group_id = NULL) {
    return Url::fromRoute('opigno_module.take_module', [
      'group' => $group_id,
      'opigno_module' => $entity_id,
    ]);
  }

  /**
   * Get the score of the user for a specific entity.
   *
   * @param int $user_id
   *   The user ID.
   * @param int $entity_id
   *   The entity ID.
   *
   * @return float|false
   *   The score between 0 and 1. FALSE if no score found.
   */
  public function getUserScore($user_id, $entity_id) {
    // Get the module and the concerned user.
    $opigno_module = OpignoModule::load($entity_id);
    $user = User::load($user_id);

    // For each attempt, check the score and save the best one.
    $user_attempts = $opigno_module->getModuleAttempts($user);
    $best_score = 0;
    foreach ($user_attempts as $user_attempt) {
      /* @var $user_attempt UserModuleStatus */

      // Get the scores.
      $score = $user_attempt->getScore();
      // Divide the score to receive value between 0 and 1
      // instead of percents count.
      $actual_score = $score / 100;

      // Save the best score.
      if ($actual_score > $best_score) {
        $best_score = $actual_score;
      }

    }

    // Finally, return the BEST !
    return $best_score;
  }

  /**
   * Get the entity as a LearningPathContent.
   *
   * @param int $entity_id
   *   The entity ID.
   *
   * @return \Drupal\opigno_group_manager\OpignoGroupContent|false
   *   The content loaded in a LearningPathContent.
   *   FALSE if not possible to load.
   */
  public function getContent($entity_id) {
    $module = OpignoModule::load($entity_id);
    $request = \Drupal::request();
    return new OpignoGroupContent(
      $this->getPluginId(),
      $this->getEntityType(),
      $entity_id,
      $module->label(),
      ($image_url = $this->getModuleImageUrl($module)) ? $image_url : $this->getDefaultModuleImageUrl(),
      ($image_url) ? $this->getModuleImageAlt($module) : t('Default image')
    );
  }

  /**
   * Get all the published entities in an array of LearningPathContent.
   *
   * @return \Drupal\opigno_group_manager\OpignoGroupContent[]|false
   *   The published contents or FALSE in case of error.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getAvailableContents() {
    try {
      /** @var \Drupal\opigno_module\Entity\OpignoModule[] $modules */
      $modules = \Drupal::entityTypeManager()->getStorage('opigno_module')->loadByProperties(['status' => 1]);
    }
    catch (InvalidPluginDefinitionException $e) {
      // TODO: Log the error.
      return FALSE;
    }

    $request = \Drupal::request();
    $contents = [];
    foreach ($modules as $module) {
      $contents[] = new OpignoGroupContent(
        $this->getPluginId(),
        $this->getEntityType(),
        $module->id(),
        $module->getName(),
        ($image_url = $this->getModuleImageUrl($module)) ? $image_url : $this->getDefaultModuleImageUrl(),
        ($image_url) ? $this->getModuleImageAlt($module) : t('Default image')
      );
    }
    return $contents;
  }

  /**
   * Get all the entities in an array of LearningPathContent.
   *
   * @return \Drupal\opigno_group_manager\OpignoGroupContent[]|false
   *   The contents or FALSE in case of error.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getAllContents() {
    try {
      /** @var \Drupal\opigno_module\Entity\OpignoModule[] $modules */
      $modules = \Drupal::entityTypeManager()->getStorage('opigno_module')->loadByProperties([]);
    }
    catch (InvalidPluginDefinitionException $e) {
      // TODO: Log the error.
      return FALSE;
    }

    $request = \Drupal::request();
    $contents = [];
    foreach ($modules as $module) {
      $contents[] = new OpignoGroupContent(
        $this->getPluginId(),
        $this->getEntityType(),
        $module->id(),
        $module->getName(),
        ($image_url = $this->getModuleImageUrl($module)) ? $image_url : $this->getDefaultModuleImageUrl(),
        ($image_url) ? $this->getModuleImageAlt($module) : t('Default image')
      );
    }
    return $contents;
  }

  /**
   * Try to get the content from a Request object.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Drupal\opigno_group_manager\OpignoGroupContent|false
   *   The content if possible. FALSE otherwise.
   */
  public function getContentFromRequest(Request $request) {
    // If the route is not an Opigno module route, leave the function.
    $route_name = $request->attributes->get('_route');
    if (
      !in_array($route_name, [
        'entity.opigno_module.canonical',
        'opigno_module.take_module',
        'opigno_module.group.answer_form',
        'opigno_module.my_results',
        'opigno_module.group.my_results',
        'opigno_module.module_result',
        'opigno_module.module_result_form',
      ])
      || $request->attributes->has('opigno_module') == FALSE
    ) {
      return FALSE;
    }

    /* @var $opigno_module OpignoModule */
    $opigno_module = $request->attributes->get('opigno_module');

    if (empty($opigno_module) || $opigno_module->getEntityTypeId() != 'opigno_module') {
      return FALSE;
    }

    return new OpignoGroupContent(
      $this->getPluginId(),
      $this->getEntityType(),
      $opigno_module->id(),
      $opigno_module->getName(),
      ($image_url = $this->getModuleImageUrl($opigno_module)) ? $image_url : $this->getDefaultModuleImageUrl(),
      ($image_url) ? $this->getModuleImageAlt($opigno_module) : t('Default image')
    );
  }

  /**
   * Get the form object based on the entity ID.
   *
   * If no entity given in parameter, return the entity creation form object.
   *
   * @param int $entity_id
   *   The entity ID.
   *
   * @return \Drupal\Core\Entity\EntityFormInterface
   *   Form.
   */
  public function getFormObject($entity_id = NULL) {
    $form = \Drupal::entityTypeManager()->getFormObject($this->getEntityType(), 'default');

    if (empty($entity_id)) {
      $entity = OpignoModule::create();
    }
    else {
      $entity = OpignoModule::load($entity_id);
    }
    $form->setEntity($entity);

    return $form;
  }

  /**
   * Return TRUE if the page should show the "next" action button.
   *
   * Even if the score does not permit the user to go next.
   *
   * Returning TRUE will not automatically show the button.
   * The button will show up only if this method returns
   * TRUE and if there is a next step available
   * and if the user is able to go to this next content.
   *
   * @return bool
   *   Next flag.
   */
  public function shouldShowNext() {
    // If the route is the good one, show the next/finish button.
    if (\Drupal::routeMatch()->getRouteName() == 'entity.opigno_module.canonical') {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Returns module image alt.
   */
  public function getModuleImageAlt($module) {
    $media = $module->get('module_media_image')->entity;
    if ($image = $media->get('field_media_image')->getValue()) {
      return isset($image[0]['alt']) ? $image[0]['alt'] : NULL;
    }
    return NULL;
  }

  /**
   * Returns module image url.
   */
  public function getModuleImageUrl($module) {
    $image_url = NULL;

    if (!$module) {
      return $image_url;
    }

    $media = $module->get('module_media_image')->entity;
    if ($media) {
      $image = $media->get('field_media_image')->getValue();
    }
    if (isset($image)) {
      $image = File::load($image[0]['target_id']);
      $style = ImageStyle::load('learning_path_thumbnail');
      if ($style) {
        $image_url = $style->buildUrl($image->getFileUri());
      }
    }

    return $image_url;
  }

  /**
   * Returns module default image url.
   */
  public function getDefaultModuleImageUrl() {
    $request = \Drupal::request();
    $path = \Drupal::service('module_handler')->getModule('opigno_module')->getPath();
    return $request->getBasePath() . '/' . $path . '/img/img_module.svg';
  }

}
