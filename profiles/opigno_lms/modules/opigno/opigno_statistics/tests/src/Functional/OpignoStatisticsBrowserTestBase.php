<?php

namespace Drupal\Tests\opigno_statistics\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Provides a base class for Opigno Statistics tests.
 */
abstract class OpignoStatisticsBrowserTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'opigno_statistics',
    'opigno_learning_path_test',
    'field_group',
    'block',
    'user',
  ];

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A test user with group creation rights.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $groupCreator;

  /**
   * Account switcher.
   *
   * @var \Drupal\Core\Session\AccountSwitcherInterface
   */
  protected $accountSwitcher;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->accountSwitcher = $this->container->get('account_switcher');

    /* @var $entityFieldManager Drupal\Core\Entity\EntityFieldManager */
    $entityFieldManager = \Drupal::service('entity_field.manager');
    $fields = $entityFieldManager->getFieldDefinitions('group', 'learning_path');
    if (isset($fields['field_learning_path_media_image'])) {
      $fields['field_learning_path_media_image']->delete();
    }

    $this->groupCreator = $this->drupalCreateUser($this->getGlobalPermissions());
    $this->drupalLogin($this->groupCreator);
  }

  /**
   * Gets the global (site) permissions for the group creator.
   *
   * @return string[]
   *   The permissions.
   */
  protected function getGlobalPermissions() {
    return [
      'view the administration theme',
      'access administration pages',
      'create learning_path group',
    ];
  }

  /**
   * Creates a group.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\group\Entity\Group
   *   The created group entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createGroup(array $values = []) {
    /* @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = \Drupal::service('entity_type.manager');
    /* @var \Drupal\group\Entity\Group $group */
    $group = $entity_type_manager->getStorage('group')->create($values + [
      'type' => 'learning_path',
      'label' => $this->randomMachineName(),
    ]);

    $group->enforceIsNew();
    $group->save();

    return $group;
  }

}
