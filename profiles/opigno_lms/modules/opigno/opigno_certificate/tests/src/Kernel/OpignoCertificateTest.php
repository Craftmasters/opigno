<?php

namespace Drupal\Tests\opigno_certificate\Kernel;

use Drupal\Core\Session\AccountInterface;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests the general behavior of opigno_certificate entities.
 *
 * @coversDefaultClass \Drupal\opigno_certificate\Entity\OpignoCertificate
 * @group opigno_certificate
 */
class OpignoCertificateTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'opigno_certificate',
    'opigno_certificate_config_test',
    'view_mode_selector',
  ];

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->installConfig(['opigno_certificate_config_test']);
    $this->installEntitySchema('opigno_certificate');
  }

  /**
   * Tests Opigno certificate creation.
   *
   * @covers ::preSave
   */
  public function testCreate() {
    // Check if default certificate was created properly.
    /** @var \Drupal\opigno_certificate\Entity\OpignoCertificate $opigno_certificate */
    $opigno_certificate = $this->entityTypeManager
      ->getStorage('opigno_certificate')
      ->create([
        'bundle' => 'opigno_certificate',
        'label' => $this->randomString(),
      ]);

    $this->assertTrue($opigno_certificate, 'Default certificate was created successfully.');
    $this->assertEquals(SAVED_NEW, $opigno_certificate->save(), 'Default certificate was saved successfully.');

    // Check if template certificate was created properly.
    /** @var \Drupal\opigno_certificate\Entity\OpignoCertificate $template_certificate */
    $template_certificate = $this->entityTypeManager
      ->getStorage('opigno_certificate')
      ->create([
        'bundle' => 'template',
        'label' => $this->randomString(),
      ]);

    $this->assertTrue($template_certificate, 'Template type certificate was created successfully.');
    $this->assertEquals(SAVED_NEW, $template_certificate->save(), 'Template type certificate was saved successfully.');

  }

  /**
   * Sets the current user so group creation can rely on it.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to set as the current user.
   */
  protected function setCurrentUser(AccountInterface $account) {
    $this->container->get('current_user')->setAccount($account);
  }

  /**
   * Gets the current user so you can run some checks against them.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The current user.
   */
  protected function getCurrentUser() {
    return $this->container->get('current_user')->getAccount();
  }

}
