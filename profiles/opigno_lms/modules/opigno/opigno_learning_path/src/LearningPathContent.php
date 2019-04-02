<?php

namespace Drupal\opigno_learning_path;

use Drupal\opigno_learning_path\Entity\LPManagedContent;

/**
 * Class LearningPathContent.
 */
class LearningPathContent {
  private $learningPathContentTypeId;
  private $entityType;
  private $entityId;
  private $title;
  private $imageUrl;
  private $imageAlt;

  /**
   * LearningPathContent constructor.
   */
  public function __construct($learning_path_content_type_id, $entity_type, $entity_id, $title, $image_url, $image_alt) {
    $this->setLearningPathContentTypeId($learning_path_content_type_id);
    $this->setEntityType($entity_type);
    $this->setEntityId($entity_id);
    $this->setTitle($title);
    $this->setImageUrl($image_url);
    $this->setImageAlt($image_alt);
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return implode('.', [
      self::class,
      $this->entityType,
      $this->entityId,
    ]);
  }

  /**
   * Returns LP content properties array to manager.
   *
   * @param \Drupal\opigno_learning_path\Entity\LPManagedContent|null $content
   *   LP content.
   *
   * @return array
   *   LP content properties array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function toManagerArray(LPManagedContent $content = NULL) {
    if ($content === NULL) {
      $cid = '';
      $is_mandatory = FALSE;
      $success_score_min = 0;
      $parents_links = [];
    }
    else {
      $cid = $content->id();
      $is_mandatory = $content->isMandatory();
      $success_score_min = $content->getSuccessScoreMin();
      $parents_links = $content->getParentsLinks();
    }

    $this_array = [
      'cid' => $cid,
      'entityId' => $this->getEntityId(),
    // TODO: Remove this. Avoid duplicate.
      'entityType' => $this->getLearningPathContentTypeId(),
    // TODO: Remove this. Avoid duplicate.
      'entityBundle' => $this->getLearningPathContentTypeId(),
      'contentType' => $this->getLearningPathContentTypeId(),
      'title' => $this->getTitle(),
      'imageUrl' => $this->getImageUrl(),
      'imageAlt' => $this->getImageAlt(),
      'isMandatory' => $is_mandatory,
      'successScoreMin' => $success_score_min,
    ];

    $parents = [];
    foreach ($parents_links as $link) {
      if (get_class($link) != 'Drupal\opigno_learning_path\Entity\LPManagedLink') {
        continue;
      }

      $parents[] = ['cid' => $link->getParentContentId(), 'minScore' => $link->getRequiredScore()];
    }
    $this_array['parents'] = $parents;

    return $this_array;
  }

  /**
   * Returns entity type.
   *
   * @return string
   *   Entity type.
   */
  public function getEntityType() {
    return $this->entityType;
  }

  /**
   * Sets entity type.
   *
   * @param string $entity_type
   *   Entity type.
   */
  public function setEntityType($entity_type) {
    $this->entityType = $entity_type;
  }

  /**
   * Returns entity ID.
   *
   * @return string
   *   Entity ID.
   */
  public function getEntityId() {
    return $this->entityId;
  }

  /**
   * Sets entity ID.
   *
   * @param string $entity_id
   *   Entity ID.
   */
  public function setEntityId($entity_id) {
    $this->entityId = $entity_id;
  }

  /**
   * Returns entity title.
   *
   * @return string
   *   Entity title.
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * Sets entity title.
   *
   * @param string $entity_title
   *   Entity title.
   */
  public function setTitle($entity_title) {
    $this->title = $entity_title;
  }

  /**
   * Returns LP content type ID.
   *
   * @return mixed
   *   LP content type ID.
   */
  public function getLearningPathContentTypeId() {
    return $this->learningPathContentTypeId;
  }

  /**
   * Sets LP content type ID.
   *
   * @param mixed $learning_path_content_type_id
   *   LP content type ID.
   */
  public function setLearningPathContentTypeId($learning_path_content_type_id) {
    $this->learningPathContentTypeId = $learning_path_content_type_id;
  }

  /**
   * Returns image url.
   *
   * @return mixed
   *   Image url.
   */
  public function getImageUrl() {
    return $this->imageUrl;
  }

  /**
   * Sets image url.
   *
   * @param mixed $image_url
   *   Image url.
   */
  public function setImageUrl($image_url) {
    $this->imageUrl = $image_url;
  }

  /**
   * Returns image alt.
   *
   * @return mixed
   *   Image alt.
   */
  public function getImageAlt() {
    return $this->imageAlt;
  }

  /**
   * Sets image alt.
   *
   * @param mixed $image_alt
   *   Image alt.
   */
  public function setImageAlt($image_alt) {
    $this->imageAlt = $image_alt;
  }

  /**
   * Returns Class parent groups ids.
   *
   * @param int $id
   *   Group id.
   *
   * @return mixed
   *   Groups ids.
   */
  public static function getClassGroups($id) {
    $db_connection = \Drupal::service('database');
    $parents = $db_connection->select('group_content_field_data', 'g_c_f_d')
      ->fields('g_c_f_d', ['gid'])
      ->condition('entity_id', $id)
      ->condition('type', 'group_content_type_27efa0097d858')
      ->execute()
      ->fetchAll();

    return $parents;
  }

  /**
   * Returns Group membership created timestamp.
   *
   * @param int $gid
   *   Group ID.
   * @param int $uid
   *   User ID.
   *
   * @return mixed
   *   Group membership created timestamp.
   */
  public static function getGroupMembershipTimestamp($gid, $uid) {
    $db_connection = \Drupal::service('database');
    $timestamp = $db_connection->select('group_content_field_data', 'g_c_f_d')
      ->fields('g_c_f_d', ['created'])
      ->condition('gid', $gid)
      ->condition('entity_id', $uid)
      ->execute()
      ->fetchField();

    return $timestamp;
  }

}
