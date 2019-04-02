<?php

namespace Drupal\Tests\opigno_statistics\Functional;

/**
 * Common tests for Opigno Statistics.
 *
 * @group opigno_statistics
 */
class OpignoStatisticsTest extends OpignoStatisticsBrowserTestBase {

  /**
   * Tests statistics pages access.
   */
  public function testOpignoStatisticsPagesAccess() {
    // Create Global statistics manager.
    $statistics_manager = $this->drupalCreateUser();
    $statistics_manager->addRole('statistics_reader');
    $statistics_manager->save();
    $this->drupalLogin($statistics_manager);
    $this->accountSwitcher->switchTo($statistics_manager);
    // Create test training.
    $training = $this->createGroup();
    // Test access to a statistics dashboard.
    $this->drupalGet('/statistics/dashboard');
    $assertSession = $this->assertSession();
    $assertSession->addressEquals('/statistics/dashboard');
    $assertSession->statusCodeEquals(200, 'Global statistics manager has access to a statistics dashboard page.');
    // Test access to a statistics for training
    // where Global statistics manager is not a member.
    $this->drupalGet('/statistics/training/' . $training->id());
    $assertSession = $this->assertSession();
    $assertSession->pageTextContains($training->label());
    $assertSession->statusCodeEquals(200, 'Global statistics manager has access to a statistics any training page.');
    // Test access to a user profile page.
    $this->drupalGet('/user/' . $this->groupCreator->id());
    // @todo: fix issue with user_picture field (considered as unknown)
    $this->assertSession()->statusCodeEquals(200, 'Global statistics manager has access to a user profile page.');
  }

}
