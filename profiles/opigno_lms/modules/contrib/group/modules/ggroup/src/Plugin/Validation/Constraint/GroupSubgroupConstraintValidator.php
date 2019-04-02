<?php

namespace Drupal\ggroup\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\ggroup\GroupHierarchyManagerInterface;
use Drupal\group\Entity\GroupContentInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the GroupSubgroup constraint.
 */
class GroupSubgroupConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The group graph storage service.
   *
   * @var \Drupal\ggroup\Graph\GroupGraphStorageInterface
   */
  protected $groupGraphStorage;

  /**
   * The group hierarchy manager.
   *
   * @var \Drupal\ggroup\GroupHierarchyManagerInterface
   */
  protected $groupHierarchyManager;

  /**
   * Constructs a GroupSubgroupConstraintValidator object.
   *
   * @param \Drupal\ggroup\GroupHierarchyManagerInterface $group_hierarchy_manager
   *   The group hierarchy manager.
   */
  public function __construct(GroupHierarchyManagerInterface $group_hierarchy_manager) {
    $this->groupHierarchyManager = $group_hierarchy_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ggroup.group_hierarchy_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if (!isset($entity)) {
      return;
    }

    if (!($entity instanceof GroupContentInterface)) {
      return;
    }

    if ($entity->getContentPlugin()->getEntityTypeId() !== 'group') {
      return;
    }

    $parent_group = $entity->getGroup();
    $child_group = $entity->getEntity();

    // If the child group already has the parent group as a subgroup, then
    // adding the relationship will cause a circular reference.
    if ($parent_group && $child_group && $this->groupHierarchyManager->groupHasSubgroup($child_group, $parent_group)) {
      $this->context->buildViolation($constraint->message)
        ->setParameter('%parent_group_label', $parent_group->label())
        ->setParameter('%child_group_label', $child_group->label())
        ->addViolation();
    }
  }

}
