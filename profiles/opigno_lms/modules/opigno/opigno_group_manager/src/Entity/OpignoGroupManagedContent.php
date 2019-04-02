<?php

namespace Drupal\opigno_group_manager\Entity;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\group\Entity\Group;

/**
 * Defines the Opigno Group Content entity.
 *
 * @ingroup opigno_group_manager
 *
 * @ContentEntityType(
 *   id = "opigno_group_content",
 *   label = @Translation("Opigno Group Content"),
 *   base_table = "opigno_group_content",
 *   entity_keys = {
 *     "id" = "id",
 *     "group_id" = "group_id",
 *     "group_content_type_id" = "group_content_type_id",
 *     "entity_id" = "entity_id",
 *     "success_score_min" = "success_score_min",
 *     "is_mandatory" = "is_mandatory",
 *     "coordinate_x" = "coordinate_x",
 *     "coordinate_y" = "coordinate_y",
 *   }
 * )
 */
class OpignoGroupManagedContent extends ContentEntityBase {

  /**
   * Helper to create a new LPManagedContent with the values passed in param.
   *
   * It's not saved automatically. You need to do $obj->save().
   *
   * @param int $group_id
   *   The learning path group ID.
   * @param string $group_content_type_id
   *   The content type plugin ID.
   * @param int $entity_id
   *   The drupal entity ID.
   * @param int $success_score_min
   *   The minimum success score to pass the learning path.
   * @param int $is_mandatory
   *   Set if the content is mandatory to pass the learning path.
   * @param int $coordinate_x
   *   The X coordinate for this content in the learning path.
   * @param int $coordinate_y
   *   The Y coordinate for this content in the learning path.
   *
   * @return \Drupal\Core\Entity\EntityInterface|self
   *   LPManagedContent object.
   */
  public static function createWithValues(
    $group_id,
    $group_content_type_id,
    $entity_id,
    $success_score_min = 0,
    $is_mandatory = 0,
    $coordinate_x = 0,
    $coordinate_y = 0
  ) {
    $values = [
      'group_id' => $group_id,
      'group_content_type_id' => $group_content_type_id,
      'entity_id' => $entity_id,
      'success_score_min' => $success_score_min,
      'is_mandatory' => $is_mandatory,
      'coordinate_x' => $coordinate_x,
      'coordinate_y' => $coordinate_y,
    ];

    return parent::create($values);
  }

  /**
   * Returns group entity ID.
   *
   * @return int
   *   The group entity ID.
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
   * Returns group.
   *
   * @return \Drupal\group\Entity\Group
   *   Group.
   */
  public function getGroup() {
    return $this->get('group_id')->entity;
  }

  /**
   * Sets group ID.
   *
   * @param \Drupal\group\Entity\Group $group
   *   The group entity.
   *
   * @return $this
   */
  public function setGroup(Group $group) {
    $this->setGroupId($group->id());
    return $this;
  }

  /**
   * Returns group content type ID.
   *
   * @return string
   *   The group content type plugin ID.
   */
  public function getGroupContentTypeId() {
    return $this->get('group_content_type_id')->value;
  }

  /**
   * Sets group content type ID.
   *
   * @param string $group_content_type_id
   *   The group content type plugin ID.
   *
   * @return $this
   */
  public function setGroupContentTypeId($group_content_type_id) {
    $this->set('group_content_type_id', $group_content_type_id);
    return $this;
  }

  /**
   * Returns entity ID.
   *
   * @return int
   *   The drupal entity ID.
   */
  public function getEntityId() {
    return $this->get('entity_id')->value;
  }

  /**
   * Sets entity ID.
   *
   * @param int $entity_id
   *   The drupal entity ID.
   *
   * @return $this
   */
  public function setEntityId($entity_id) {
    $this->set('entity_id', $entity_id);
    return $this;
  }

  /**
   * Returns success score min.
   *
   * @return int
   *   The minimum score to success this learning path.
   */
  public function getSuccessScoreMin() {
    return $this->get('success_score_min')->value;
  }

  /**
   * Sets success score min.
   *
   * @param int $success_score_min
   *   The minimum score to success this learning path,.
   *
   * @return $this
   */
  public function setSuccessScoreMin($success_score_min) {
    $this->set('success_score_min', $success_score_min);
    return $this;
  }

  /**
   * Returns mandatory flag.
   *
   * @return bool
   *   TRUE if this content is mandatory to success this learning path.
   *   FALSE otherwise.
   */
  public function isMandatory() {
    return $this->get('is_mandatory')->value;
  }

  /**
   * Sets mandatory flag.
   *
   * @param bool $is_mandatory
   *   TRUE if this content is mandatory to success this learning path.
   *   FALSE otherwise.
   *
   * @return $this
   */
  public function setMandatory($is_mandatory) {
    $this->set('is_mandatory', $is_mandatory);
    return $this;
  }

  /**
   * Returns X coordinate.
   *
   * @return int
   *   The X coordinate.
   */
  public function getCoordinateX() {
    return $this->get('coordinate_x')->value;
  }

  /**
   * Sets X coordinate.
   *
   * @param int $coordinate_x
   *   The X coordinate.
   *
   * @return $this
   */
  public function setCoordinateX($coordinate_x) {
    $this->set('coordinate_x', $coordinate_x);
    return $this;
  }

  /**
   * Returns Y coordinate.
   *
   * @return int
   *   The Y coordinate.
   */
  public function getCoordinateY() {
    return $this->get('coordinate_y')->value;
  }

  /**
   * Sets Y coordinate.
   *
   * @param int $coordinate_y
   *   The Y coordinate.
   *
   * @return $this
   */
  public function setCoordinateY($coordinate_y) {
    $this->set('coordinate_y', $coordinate_y);
    return $this;
  }

  /**
   * Returns parents links.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]|OpignoGroupManagedLink[]
   *   Parents links.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function getParentsLinks() {
    return OpignoGroupManagedLink::loadByProperties([
      'group_id' => $this->getGroupId(),
      'child_content_id' => $this->id(),
    ]);
  }

  /**
   * Returns children links.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]|OpignoGroupManagedLink[]
   *   Children links.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function getChildrenLinks() {
    return OpignoGroupManagedLink::loadByProperties([
      'group_id' => $this->getGroupId(),
      'parent_content_id' => $this->id(),
    ]);
  }

  /**
   * Checks if this content has a child.
   *
   * @return bool
   *   TRUE if this content has a child. FALSE otherwise.
   *
   * @throws InvalidPluginDefinitionException
   */
  public function hasChildLink() {
    return !empty($this->getChildrenLinks());
  }

  /**
   * Get the content type object of this content.
   *
   * @return \Drupal\opigno_group_manager\ContentTypeBase|object
   *   Group content type.
   */
  public function getGroupContentType() {
    $manager = \Drupal::getContainer()->get('opigno_group_manager.content_types.manager');
    return $manager->createInstance($this->getGroupContentTypeId());
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['group_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('Group')
      ->setCardinality(1)
      ->setSetting('target_type', 'group');

    $fields['group_content_type_id'] = BaseFieldDefinition::create('string')
      ->setLabel('Group Content Type ID')
      ->setDescription('The content type ID (should be a plugin ID)');

    $fields['entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel('Entity ID')
      ->setDescription('The entity ID');

    $fields['success_score_min'] = BaseFieldDefinition::create('integer')
      ->setLabel('Success score minimum')
      ->setDescription('The minimum score to success this content')
      ->setDefaultValue(0);

    $fields['is_mandatory'] = BaseFieldDefinition::create('boolean')
      ->setLabel('Is mandatory')
      ->setDescription('Indicate if this content is mandatory to succeed the Group')
      ->setDefaultValue(FALSE);

    $fields['coordinate_x'] = BaseFieldDefinition::create('integer')
      ->setLabel('Coordinate X')
      ->setDescription('The X coordinate in this Group manager')
      ->setDefaultValue(0);

    $fields['coordinate_y'] = BaseFieldDefinition::create('integer')
      ->setLabel('Coordinate Y')
      ->setDescription('The Y coordinate in this Group manager')
      ->setDefaultValue(0);

    return $fields;
  }

  /**
   * Load one or more LPManagedContent filtered by the properties.
   *
   * The available properties are the entity_keys specified
   * in the header of this LPManagedContent class.
   *
   * Best is to avoid to use this method
   * and create a specific method for your search,
   * like the method loadByLearningPathId.
   *
   * @param array $properties
   *   The properties to search for.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]|self[]
   *   LPManagedContent objects.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @see LPManagedContent::loadByLearningPathId()
   */
  public static function loadByProperties(array $properties) {
    return \Drupal::entityTypeManager()->getStorage('opigno_group_content')->loadByProperties($properties);
  }

  /**
   * Load the contents linked to a specific group.
   *
   * @param int $group_id
   *   The Group entity ID.
   *
   * @return array|\Drupal\Core\Entity\EntityInterface[]|self[]
   *   Group managed content object.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function loadByGroupId($group_id) {
    try {
      return self::loadByProperties(['group_id' => $group_id]);
    }
    catch (InvalidPluginDefinitionException $e) {
      return [];
    }
  }

  /**
   * Deletes the content from database.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function delete() {
    // First, delete all the links associated to this content.
    $as_child_links = OpignoGroupManagedLink::loadByProperties(['group_id' => $this->getGroupId(), 'child_content_id' => $this->id()]);
    $as_parent_links = OpignoGroupManagedLink::loadByProperties(['group_id' => $this->getGroupId(), 'parent_content_id' => $this->id()]);
    /** @var OpignoGroupManagedLink[] $all_links */
    $all_links = array_merge($as_child_links, $as_parent_links);

    // TODO: Maybe use the entityStorage to bulk delete ?
    // Delete the links.
    foreach ($all_links as $link) {
      $link->delete();
    }

    parent::delete();
  }

  /**
   * Returns first step.
   */
  public static function getFirstStep($learning_path_id) {
    // The first step is the content who has no parents.
    // First, get all the contents.
    $contents = self::loadByGroupId($learning_path_id);

    // Then, check which content has no parent link.
    foreach ($contents as $content) {
      $parents = $content->getParentsLinks();
      if (empty($parents)) {
        return $content;
      }
    }

    return FALSE;
  }

  /**
   * Get the next LPManagedContent object according to the user score.
   *
   * @param int $user_score
   *   The user score for this content.
   *
   * @return bool|OpignoGroupManagedContent
   *   FALSE if no next content.
   *   The next OpignoGroupManagedContent if there is a next content.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getNextStep($user_score) {
    // Get the child link that has the required_score
    // higher than the $score param
    // and that has the higher required_score.
    $query = \Drupal::entityTypeManager()->getStorage('opigno_group_link')->getQuery();
    $query->condition('parent_content_id', $this->id());
    $query->condition('required_score', $user_score, '<=');
    $query->sort('required_score', 'DESC');

    // To preserve the same order between multiple queries
    // when the required_score is equal.
    $query->sort('id');

    $query->range(0, 1);
    $result = $query->execute();

    // If no result, return FALSE.
    if (empty($result)) {
      return FALSE;
    }

    // If a result is found, return the next content object.
    $next_step_id = array_pop($result);
    $next_step_link = OpignoGroupManagedLink::load($next_step_id);
    return $next_step_link->getChildContent();
  }

}
