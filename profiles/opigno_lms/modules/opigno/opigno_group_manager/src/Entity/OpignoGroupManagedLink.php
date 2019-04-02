<?php

namespace Drupal\opigno_group_manager\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\group\Entity\Group;

/**
 * Defines the Opigno Group Link entity.
 *
 * @ingroup opigno_group_manager
 *
 * @ContentEntityType(
 *   id = "opigno_group_link",
 *   label = @Translation("Opigno Group Link"),
 *   base_table = "opigno_group_link",
 *   entity_keys = {
 *     "id" = "id",
 *     "group_id" = "group_id",
 *     "parent_content_id" = "parent_content_id",
 *     "child_content_id" = "child_content_id",
 *     "required_score" = "required_score",
 *   }
 * )
 */
class OpignoGroupManagedLink extends ContentEntityBase {

  /**
   * Helper method to create a new LPManagedLink.
   *
   * It's not saved on creation.
   * You have to do $obj->save() to save it in DB.
   *
   * @param int $group_id
   *   The group entity ID.
   * @param int $parent_content_id
   *   The parent content ID.
   * @param int $child_content_id
   *   The child content ID.
   * @param int $required_score
   *   The required score to go from the parent to the child content.
   *
   * @return \Drupal\Core\Entity\EntityInterface|self
   *   OpignoGroupManagedLink object.
   */
  public static function createWithValues(
    $group_id,
    $parent_content_id,
    $child_content_id,
    $required_score = 0
  ) {
    $values = [
      'group_id' => $group_id,
      'parent_content_id' => $parent_content_id,
      'child_content_id' => $child_content_id,
      'required_score' => $required_score,
    ];
    return parent::create($values);
  }

  /**
   * Returns group entity ID.
   *
   * @return int
   *   The Group entity ID.
   */
  public function getGroupId() {
    return $this->get('group_id')->target_id;
  }

  /**
   * Sets group entity ID.
   *
   * @param int $group_id
   *   The Group entity ID.
   *
   * @return $this
   */
  public function setGroupId($group_id) {
    $this->set('group_id', $group_id);
    return $this;
  }

  /**
   * Returns group entity.
   *
   * @return \Drupal\group\Entity\Group
   *   The group entity object.
   */
  public function getGroup() {
    return $this->get('group_id')->entity;
  }

  /**
   * Sets group entity.
   *
   * @param \Drupal\group\Entity\Group $group
   *   The group entity object.
   *
   * @return $this
   */
  public function setGroup(Group $group) {
    $this->setGroupId($group->id());
    return $this;
  }

  /**
   * Returns parent content ID.
   *
   * @return int
   *   The parent content ID.
   */
  public function getParentContentId() {
    return $this->get('parent_content_id')->target_id;
  }

  /**
   * Sets parent content ID.
   *
   * @param int $parent_content_id
   *   The parent content ID.
   *
   * @return $this
   */
  public function setParentContentId($parent_content_id) {
    $this->set('parent_content_id', $parent_content_id);
    return $this;
  }

  /**
   * Returns parent content.
   *
   * @return OpignoGroupManagedContent
   *   The parent learning path content object.
   */
  public function getParentContent() {
    return $this->get('parent_content_id')->entity;
  }

  /**
   * Sets parent content.
   *
   * @param OpignoGroupManagedContent $parent_content
   *   The parent learning path content object.
   *
   * @return $this
   */
  public function setParentContent(OpignoGroupManagedContent $parent_content) {
    $this->setParentContentId($parent_content->id());
    return $this;
  }

  /**
   * Returns child content ID.
   *
   * @return int
   *   The child content ID.
   */
  public function getChildContentId() {
    return $this->get('child_content_id')->target_id;
  }

  /**
   * Sets child content ID.
   *
   * @param int $child_content_id
   *   The child content ID.
   *
   * @return $this
   */
  public function setChildContentId($child_content_id) {
    $this->set('child_content_id', $child_content_id);
    return $this;
  }

  /**
   * Returns child content.
   *
   * @return OpignoGroupManagedContent
   *   The child content object.
   */
  public function getChildContent() {
    return $this->get('child_content_id')->entity;
  }

  /**
   * Sets child content.
   *
   * @param OpignoGroupManagedContent $child_content
   *   The child content object.
   *
   * @return $this
   */
  public function setChildContent(OpignoGroupManagedContent $child_content) {
    $this->setChildContentId($child_content->id());
    return $this;
  }

  /**
   * Returns minimum to go score from parent content to child content.
   *
   * @return int
   *   The minimum score to go from parent content to child content.
   */
  public function getRequiredScore() {
    return $this->get('required_score')->value;
  }

  /**
   * Sets minimum to go score from parent content to child content.
   *
   * @param int $required_score
   *   The minimum score to go from parent content to child content.
   *
   * @return $this
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

    $fields['group_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('Group')
      ->setDescription('The Group entity containing this link')
      ->setCardinality(1)
      ->setSetting('target_type', 'group');

    $fields['parent_content_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('The parent group content')
      ->setDescription('The parent group content')
      ->setCardinality(1)
      ->setSetting('target_type', 'opigno_group_content');

    $fields['child_content_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('The child group content')
      ->setDescription('The child group content')
      ->setCardinality(1)
      ->setSetting('target_type', 'opigno_group_content');

    $fields['required_score'] = BaseFieldDefinition::create('integer')
      ->setLabel('Required score')
      ->setDescription('The required score to go from parent to child content');

    return $fields;
  }

  /**
   * Load one or more LPManagedLink filtered by the properties given in param.
   *
   * The available properties are the entity_keys
   * specified in the header of this class.
   *
   * Best is to avoid to use this method
   * and create a specific method for your search,
   * like the method LPManagedContent::loadByLearningPathId.
   *
   * @param array $properties
   *   The properties to search for.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]|self[]
   *   LPManagedLink objects.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @see LPManagedContent::loadByLearningPathId()
   */
  public static function loadByProperties(array $properties) {
    return \Drupal::entityTypeManager()->getStorage('opigno_group_link')->loadByProperties($properties);
  }

}
