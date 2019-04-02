<?php

namespace Drupal\opigno_ilt\Plugin\OpignoGroupManagerContentType;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Url;
use Drupal\opigno_group_manager\ContentTypeBase;
use Drupal\opigno_group_manager\OpignoGroupContent;
use Drupal\opigno_ilt\Entity\ILT;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ContentTypeILT.
 *
 * @OpignoGroupManagerContentType(
 *   id = "ContentTypeILT",
 *   entity_type = "opigno_ilt",
 *   readable_name = "Instructor-Led Training",
 *   description = "Contains the Instructor-Led Trainings",
 *   allowed_group_types = {
 *     "learning_path"
 *   },
 *   group_content_plugin_id = "opigno_ilt_group"
 * )
 */
class ContentTypeILT extends ContentTypeBase {

  /**
   * {@inheritdoc}
   */
  public function shouldShowNext() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserScore($user_id, $entity_id) {
    /** @var \Drupal\opigno_ilt\ILTResultInterface[] $results */
    $results = \Drupal::entityTypeManager()
      ->getStorage('opigno_ilt_result')
      ->loadByProperties([
        'user_id' => $user_id,
        'opigno_ilt' => $entity_id,
      ]);

    $best_score = 0;
    foreach ($results as $result) {
      $score = $result->getScore();
      if ($score > $best_score) {
        $best_score = $score;
      }
    }

    return $best_score / 100;
  }

  /**
   * {@inheritdoc}
   */
  public function getViewContentUrl($entity_id) {
    return Url::fromRoute('entity.opigno_ilt.canonical', [
      'opigno_ilt' => $entity_id,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getStartContentUrl($content_id, $group_id = NULL) {
    return Url::fromRoute('entity.opigno_ilt.canonical', [
      'opigno_ilt' => $content_id,
    ]);
  }

  /**
   * {@inheritdoc}
   *
   * @param int|\Drupal\opigno_ilt\ILTInterface $entity
   *   The entity ID or entity instance.
   */
  public function getContent($entity) {
    // If the value is the ILT ID, load the ILT.
    if (is_numeric($entity) || is_string($entity)) {
      /** @var \Drupal\opigno_ilt\ILTInterface $entity */
      $entity = ILT::load($entity);
    }

    if ($entity === NULL || $entity === FALSE) {
      return FALSE;
    }

    return new OpignoGroupContent(
      $this->getPluginId(),
      $this->getEntityType(),
      $entity->id(),
      $entity->label(),
      $this->getDefaultModuleImageUrl(),
      t('Default image')
    );
  }

  /**
   * Returns default image url.
   */
  public function getDefaultModuleImageUrl() {
    $request = \Drupal::request();
    $path = \Drupal::service('module_handler')->getModule('opigno_module')->getPath();
    return $request->getBasePath() . '/' . $path . '/img/img_module.svg';
  }

  /**
   * {@inheritdoc}
   */
  public function getContentFromRequest(Request $request) {
    $entity = $request->get('opigno_ilt');
    if ($entity === NULL || $entity === FALSE) {
      return FALSE;
    }

    return $this->getContent($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormObject($entity_id = NULL) {
    if (empty($entity_id)) {
      $form = \Drupal::entityTypeManager()->getFormObject($this->getEntityType(), 'add');
      $entity = ILT::create();
    }
    else {
      $form = \Drupal::entityTypeManager()->getFormObject($this->getEntityType(), 'edit');
      $entity = ILT::load($entity_id);
    }

    $form->setEntity($entity);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllContents() {
    try {
      /** @var \Drupal\opigno_ilt\ILTInterface[] $entities */
      $entities = \Drupal::entityTypeManager()
        ->getStorage('opigno_ilt')
        ->loadMultiple();
    }
    catch (InvalidPluginDefinitionException $e) {
      // TODO: Log the error.
      return FALSE;
    }

    $contents = [];
    foreach ($entities as $entity) {
      $contents[] = $this->getContent($entity);
    }
    return $contents;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContents() {
    return $this->getAllContents();
  }

}
