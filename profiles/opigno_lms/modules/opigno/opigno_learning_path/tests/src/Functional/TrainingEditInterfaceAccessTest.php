<?php

namespace Drupal\Tests\opigno_learning_path\Functional;

/**
 * Tests an access to Training edit interface pages.
 *
 * @group opigno_learning_path
 */
class TrainingEditInterfaceAccessTest extends LearningPathBrowserTestBase {

  /**
   * Tests which users can subscribe and start a training.
   */
  public function testAccessEditPageTraining() {
    // Create training.
    $training = $this->createGroup();
    // Add dummy module with activities.
    $module = $this->createOpignoModule();
    $training = $this->addModuleToTraining($training, $module);
    // Create student and add to a training.
    $student_1 = $this->createUser();
    $training->addMember($student_1);

    /********
     * Test access for user with global role: user manager.
     ******/
    $global_user_manager = $this->createUser();
    $global_user_manager->addRole('user_manager');
    $global_user_manager->save();
    $this->drupalLogin($global_user_manager);
    $this->accountSwitcher->switchTo($global_user_manager);
    // Check access to training pages for global user manager.
    $this->drupalGet('/group/' . $training->id() . '/edit/');
    $this->assertSession()->statusCodeEquals(403, 'Global user manager has not access to training edit description page.');
    $this->drupalGet('/group/' . $training->id() . '/manager');
    $this->assertSession()->statusCodeEquals(403, 'Global user manager has not access to training edit course and modules page.');
    $this->drupalGet('/group/' . $training->id() . '/inner-modules');
    $this->assertSession()->statusCodeEquals(403, 'Global user manager has not access to training edit activities page.');
    $this->drupalGet('/group/' . $training->id() . '/members');
    $this->assertSession()->statusCodeEquals(200, 'Global user manager has access to training membership overview page.');
    $this->drupalGet('/group/' . $training->id() . '/content/add/group_membership');
    $this->assertSession()->statusCodeEquals(200, 'Global user manager can add new members to a training.');
    // User manager can't see some links in left sidebar menu.
    $this->assertSession()->linkNotExists('Learning Path Manager');
    $this->assertSession()->linkNotExists('Modules');
    $this->assertSession()->linkNotExists('Activities');
    $this->assertSession()->linkExists('Members');
    $this->drupalGet('admin/people');
    $this->assertSession()->statusCodeEquals(200, 'Global user manager has access to admin user manage page.');

    /********
     * Test access for user with global role: content manager.
     ******/
    $global_content_manager = $this->createUser();
    $global_content_manager->addRole('content_manager');
    $global_content_manager->save();
    $this->drupalLogin($global_content_manager);
    $this->accountSwitcher->switchTo($global_content_manager);
    // Check access to training pages for global content manager.
    $this->drupalGet('/group/' . $training->id() . '/edit/');
    $this->assertSession()->statusCodeEquals(200, 'Global content manager has access to training edit description page.');
    $this->drupalGet('/group/' . $training->id() . '/manager');
    $this->assertSession()->statusCodeEquals(200, 'Global content manager has access to training edit course and modules page.');
    $this->drupalGet('/group/' . $training->id() . '/inner-modules');
    $this->assertSession()->statusCodeEquals(200, 'Global content manager has access to training edit activities page.');
    // User manager can't see some links in left sidebar menu.
    $this->assertSession()->linkExists('Learning Path Manager');
    $this->assertSession()->linkExists('Modules');
    $this->assertSession()->linkExists('Activities');
    $this->assertSession()->linkNotExists('Members');
    $this->drupalGet('/group/' . $training->id() . '/members');
    $this->assertSession()->statusCodeEquals(403, 'Global content manager has not access to training membership overview page.');
    // Check if content manager can not add a new user to a training.
    $this->drupalGet('/group/' . $training->id() . '/content/add/group_membership');
    $this->assertSession()->statusCodeEquals(403, 'Global content manager can not add new members to a training.');

    /********
     * Test access for user with group role: student manager.
     ******/
    $student_manager = $this->createUser();
    $student_manager->save();
    $this->drupalLogin($student_manager);
    $this->accountSwitcher->switchTo($student_manager);
    // Add user to a training.
    $training->addMember($student_manager);
    $this->addGroupRoleForUser($training, $student_manager, ['learning_path-user_manager']);
    $this->drupalGet('/group/' . $training->id() . '/members');
    $this->assertSession()->statusCodeEquals(200, 'Local student manager has access to user manage page in training where he is a member.');
    // User manager can't see some links in left sidebar menu.
    $this->assertSession()->linkNotExists('Learning Path Manager');
    $this->assertSession()->linkNotExists('Modules');
    $this->assertSession()->linkNotExists('Activities');
    $this->assertSession()->linkExists('Members');
    $this->drupalGet('/group/' . $training->id() . '/content/add/group_membership');
    $this->assertSession()->statusCodeEquals(200, 'Local student manager can add new members to a training.');
    $this->drupalGet('admin/people');
    $this->assertSession()->statusCodeEquals(403, 'Local student manager has not access to admin user manage page.');

    /********
     * Test access for user with group role: content manager.
     ******/
    $content_manager = $this->createUser();
    $content_manager->save();
    $this->drupalLogin($content_manager);
    $this->accountSwitcher->switchTo($content_manager);
    $this->addGroupRoleForUser($training, $content_manager, ['learning_path-content_manager']);
    $training->addMember($content_manager);
    $this->drupalGet('/group/' . $training->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200, 'Local content manager has access to training edit page.');
    // Local content manager can't see some links in left sidebar menu.
    $this->assertSession()->linkExists('Learning Path Manager');
    $this->assertSession()->linkExists('Modules');
    $this->assertSession()->linkExists('Activities');
    $this->assertSession()->linkNotExists('Members');

  }

}
