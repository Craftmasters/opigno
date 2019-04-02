<?php

namespace Drupal\opigno_learning_path\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\group\Entity\Group;

/**
 * Defines the Learning Path Link entity.
 *
 * @ingroup opigno_learning_path
 *
 * @ContentEntityType(
 *   id = "learning_path_link",
 *   label = @Translation("Learning Path Link"),
 *   base_table = "learning_path_link",
 *   entity_keys = {
 *     "id" = "id",
 *     "learning_path_id" = "learning_path_id",
 *     "parent_content_id" = "parent_content_id",
 *     "child_content_id" = "child_content_id",
 *     "required_score" = "required_score",
 *   }
 * )s
 */
class LPManagedLink extends ContentEntityBase {

  /**
   * Helper method to create a new LPManagedLink.
   *
   * It's not saved on creation. You have to do $obj->save() to save it in DB.
   *
   * @param int $learning_path_id
   *   The learning path group ID.
   * @param int $parent_content_id
   *   The parent content ID.
   * @param int $child_content_id
   *   The child content ID.
   * @param int $required_score
   *   The required score to go from the parent to the child content.
   *
   * @return \Drupal\Core\Entity\EntityInterface|self
   *   LPManagedLink object.
   */
  public static function createWithValues(
    $learning_path_id,
    $parent_content_id,
    $child_content_id,
    $required_score = 0
  ) {
    $values = [
      'learning_path_id' => $learning_path_id,
      'parent_content_id' => $parent_content_id,
      'child_content_id' => $child_content_id,
      'required_score' => $required_score,
    ];
    return parent::create($values);
  }

  /**
   * Returns learning path ID.
   */
  public function getLearningPathId() {
    return $this->get('learning_path_id')->target_id;
  }

  /**
   * Sets learning path ID.
   */
  public function setLearningPathId($learning_path_id) {
    $this->set('learning_path_id', $learning_path_id);
    return $this;
  }

  /**
   * Returns learning path group object.
   */
  public function getLearningPath() {
    return $this->get('learning_path_id')->entity;
  }

  /**
   * Sets learning path group object.
   */
  public function setLearningPath(Group $learning_path) {
    if ($learning_path->getGroupType()->id() != 'learning_path') {
      return FALSE;
    }

    $this->setLearningPathId($learning_path->id());
    return $this;
  }

  /**
   * Returns parent content ID.
   */
  public function getParentContentId() {
    return $this->get('parent_content_id')->target_id;
  }

  /**
   * Sets parent content ID.
   */
  public function setParentContentId($parent_content_id) {
    $this->set('parent_content_id', $parent_content_id);
    return $this;
  }

  /**
   * Returns parent learning path content object.
   */
  public function getParentContent() {
    return $this->get('parent_content_id')->entity;
  }

  /**
   * Sets parent learning path content object.
   */
  public function setParentContent(LPManagedContent $parent_content) {
    $this->setParentContentId($parent_content->id());
    return $this;
  }

  /**
   * Returns child content ID.
   */
  public function getChildContentId() {
    return $this->get('child_content_id')->target_id;
  }

  /**
   * Sets child content ID.
   */
  public function setChildContentId($child_content_id) {
    $this->set('child_content_id', $child_content_id);
    return $this;
  }

  /**
   * Returns child content object.
   */
  public function getChildContent() {
    return $this->get('child_content_id')->entity;
  }

  /**
   * Sets child content object.
   */
  public function setChildContent(LPManagedContent $child_content) {
    $this->setChildContentId($child_content->id());
    return $this;
  }

  /**
   * Returns minimum score to go from parent content to child content.
   */
  public function getRequiredScore() {
    return $this->get('required_score')->value;
  }

  /**
   * Sets minimum score to go from parent content to child content.
   */
  public function setRequiredScore($required_score) {
    $this->set('required_score', $required_score);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['learning_path_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('Learning Path')
      ->setDescription('The learning path containing this link')
      ->setCardinality(1)
      ->setSetting('target_type', 'group')
      ->setSetting('handler_settings',
        [
          'target_bundles' => ['opigno_learning_path' => 'opigno_learning_path'],
        ]);

    $fields['parent_content_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('The parent learning path content')
      ->setDescription('The parent learning path content')
      ->setCardinality(1)
      ->setSetting('target_type', 'learning_path_content');

    $fields['child_content_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('The child learning path content')
      ->setDescription('The child learning path content')
      ->setCardinality(1)
      ->setSetting('target_type', 'learning_path_content');

    $fields['required_score'] = BaseFieldDefinition::create('integer')
      ->setLabel('Required score')
      ->setDescription('The required score to go from parent to child content');

    return $fields;
  }

  /**
   * Load one or more LPManagedLink filtered by the properties given in param.
   *
   * The available properties are the entity_keys specified
   * in the header of this class.
   *
   * Best is to avoid to use this method
   * and create a specific method for your search,
   * like the method LPManagedContent::loadByLearningPathId.
   */
  public static function loadByProperties($properties) {
    return \Drupal::entityTypeManager()->getStorage('learning_path_link')->loadByProperties($properties);
  }

}
