<?php

namespace Drupal\Tests\ggroup\Kernel;

use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\Tests\group\Kernel\GroupKernelTestBase;

/**
 * Tests the behavior of subgroup creators.
 *
 * @group group
 */
class SubgroupTest extends GroupKernelTestBase {
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['ggroup', 'ggroup_test_config'];

  /**
   * The account to use as the group creator.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupType;

  /**
   * The account to use as the group creator.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $subGroupType;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    
    $this->installConfig(['ggroup_test_config']);
    $this->installSchema('ggroup', 'group_graph');

    $this->entityTypeManager = $this->container->get('entity_type.manager');

    $this->groupType = $this->entityTypeManager->getStorage('group_type')->load('default');
    $this->subGroupType = $this->entityTypeManager->getStorage('group_type')->load('subgroup');
  }

  /**
   * Tests the addition of a group to a group.
   */
  public function testCreateSubgroup() {
    list($group, $subGroup) = $this->addGroup();
    $this->assertNotEmpty($group->getContentByEntityId('subgroup:' . $this->subGroupType->id(), $subGroup->id()), 'Subgroup is group content');
  }

  /**
   * Tests the removing subgroup from group.
   */
  public function testDeleteSubgroupFromGroupContent() {
    /* @var Group $subGroup */
    list($group, $sub_group) = $this->addGroup();

    foreach (GroupContent::loadByEntity($sub_group) as $group_content) {
      $group_content->delete();

      $this->assertEquals(\Drupal::service('ggroup.group_hierarchy_manager')->groupHasSubgroup($group, $sub_group), FALSE, 'Subgroup is removed');
    }

  }

  /**
   * Tests the removing subgroup.
   */
  public function testDeleteSubgroup() {
    list($group, $sub_group) = $this->addGroup();

    /* @var Group $subGroup */
    $sub_group->delete();

    $this->assertEquals(\Drupal::service('ggroup.group_hierarchy_manager')->groupHasSubgroup($group, $sub_group), FALSE, 'Subgroup is removed');
  }

  /**
   * Create group and attach subgroup to group.
   * 
   * @return array
   *  Return group and subgroup.
   */
  private function addGroup() {
    /* @var Group $group */
    $group = $this->createGroupByType($this->groupType->id(), ['uid' => $this->getCurrentUser()->id()]);
    /* @var Group $subGroup */
    $sub_group = $this->createGroupByType($this->subGroupType->id(), ['uid' => $this->getCurrentUser()->id()]);


    $group->addContent($sub_group, 'subgroup:' . $this->subGroupType->id());

    return [$group, $sub_group];
  }

  /**
   * Creates a group by type.
   *
   * @param string $type
   *   Group type.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return Group
   *   The created group entity.
   */
  private function createGroupByType($type, $values = []) {
    /* @var Group $group */
    $group = $this->entityTypeManager->getStorage('group')->create($values + [
        'type' => $type,
        'label' => $this->randomMachineName(),
      ]);

    $group->enforceIsNew();
    $group->save();

    return $group;
  }

}
