<?php

namespace Drupal\Tests\opigno_learning_path\FunctionalJavascript;

/**
 * Tests user access to a Training interface (with Angular app).
 *
 * @group opigno_learning_path
 */
class TrainingEditInterfaceTest extends LearningPathWebDriverTestBase {

  /**
   * Tests Training Interface Access.
   */
  public function testTrainingInterfaceAccess() {
    /********
     * Test access for user with group role: content manager.
     ******/
    $content_manager = $this->createUser();
    $content_manager->save();
    $this->drupalLogin($content_manager);
    $this->accountSwitcher->switchTo($content_manager);
    // Create training.
    $training = $this->createGroup(['uid' => $this->groupCreator]);
    // Add local content manager to a training.
    $this->addGroupRoleForUser($training, $content_manager, ['learning_path-content_manager']);
    $training->addMember($content_manager);
    // Add module with activities to a training.
    $module_1 = $this->createOpignoModule([
      'uid' => $this->groupCreator->id(),
    ]);
    $this->addModuleToTraining($training, $module_1);
    // Add another module to a training where LCM is an owner.
    $module_2 = $this->createOpignoModule([
      'uid' => $content_manager->id(),
    ]);
    $this->addModuleToTraining($training, $module_2);

    $this->drupalGet('/group/' . $training->id() . '/inner-modules');

    // Wait 2 seconds when Angular app will be loaded.
    $this->getSession()->wait(2000);

    $page = $this->getSession()->getPage();
    $this->assertEquals(FALSE, $page->hasContent($module_1->getName()), 'Local content manager can not see not own modules.');
    $this->assertEquals(TRUE, $page->hasContent($module_2->getName()), 'Local content manager can see own modules.');
    $this->assertEquals(TRUE, $page->hasButton('Add activity'), 'Local content manager can add activities to own modules.');

  }

}
