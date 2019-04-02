<?php

namespace Drupal\Tests\opigno_learning_path\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Provides a base class for Learning path group functional javascript tests.
 */
abstract class LearningPathBrowserTestBase extends BrowserTestBase {

  use TrainingContentTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'opigno_learning_path',
    'opigno_catalog',
    'multiselect',
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

}
