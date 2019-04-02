<?php

namespace Drupal\Tests\opigno_class\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests Opigno Class membership.
 *
 * @group opigno_class
 */
class OpignoClassMembershipTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_browser',
    'media',
    'opigno_class',
    'multiselect',
    'field_group',
    'block',
    'user',
  ];

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  public $user;

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
    \Drupal::service('module_installer')->install([
      'opigno_learning_path',
    ]);

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->accountSwitcher = $this->container->get('account_switcher');

    /* @var $entityFieldManager Drupal\Core\Entity\EntityFieldManager */
    $entityFieldManager = \Drupal::service('entity_field.manager');
    $fields = $entityFieldManager->getFieldDefinitions('group', 'learning_path');
    if (isset($fields['field_learning_path_media_image'])) {
      $fields['field_learning_path_media_image']->delete();
    }

    // Create logged administrator user.
    $this->user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($this->user);
  }

  /**
   * Tests that the home page loads with a 200 response.
   */
  public function testLoad() {
    // Create test user.
    $user = $this->drupalCreateUser();

    // Create class group.
    $entity_type_manager = \Drupal::service('entity_type.manager');
    $class = $entity_type_manager->getStorage('group')->create([
      'type' => 'opigno_class',
      'label' => $this->randomMachineName(),
      'uid' => $this->user,
    ]);
    $class->enforceIsNew();
    $class->save();

    // Create LP training group.
    $lp = $entity_type_manager->getStorage('group')->create([
      'type' => 'learning_path',
      'label' => $this->randomMachineName(),
      'uid' => $this->user,
    ]);
    $lp->enforceIsNew();
    $lp->save();

    // Add Class to LP group.
    $lp->addContent($class, 'subgroup:opigno_class');
    // Add user to Class.
    $class->addMember($user);
    // Get LP members.
    $members = $lp->getMembers();
    $this->assertTrue($members, 'Error getting members of LP training');
    $users_ids = [];
    if ($members) {
      foreach ($members as $member) {
        $account = $member->getUser();
        $uid = $account->id();
        $users_ids[$uid] = $uid;
      }
    }
    $this->assertArrayHasKey($user->id(), $users_ids, "User from Class wasn't added to Class LP training");

    // Remove user from Class.
    $class->removeMember($user);
    // Get LP members.
    $members = $lp->getMembers();
    $this->assertTrue($members, 'Error getting members of LP training');
    $users_ids = [];
    if ($members) {
      foreach ($members as $member) {
        $account = $member->getUser();
        $uid = $account->id();
        $users_ids[$uid] = $uid;
      }
    }
    $this->assertArrayNotHasKey($user->id(), $users_ids, "User from Class wasn't removed from Class LP training");
  }

}
