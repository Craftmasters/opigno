<?php

namespace Drupal\Tests\opigno_learning_path\Functional;

use Drupal\opigno_group_manager\Entity\OpignoGroupManagedContent;
use Drupal\opigno_group_manager\Entity\OpignoGroupManagedLink;
use Drupal\opigno_module\Entity\OpignoModule;
use Drupal\user\UserInterface;

/**
 * Tests Opigno Group Link behavior.
 *
 * @group opigno_learning_path
 */
class OpignoLinkConditionTest extends LearningPathBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Install dependencies.
    \Drupal::service('module_installer')->install([
      'opigno_messaging',
      'opigno_learning_path_test',
      'search',
    ]);
    // Install Platon theme.
    \Drupal::service('theme_handler')->install(['platon']);
    \Drupal::service('theme_handler')->setDefault('platon');

  }

  /**
   * Test.
   */
  public function testLinkCondition() {
    // Create training.
    $training = $this->createGroup(['field_learning_path_visibility' => 'public']);
    // Add member to a training.
    $training->addMember($this->groupCreator);
    // Create module and add to a training.
    $parent_module = $this->createOpignoModule();
    $child_module_1 = $this->createOpignoModule();
    $child_module_2 = $this->createOpignoModule();
    // Add modules to training.
    $this->addModuleToTraining($training, $parent_module);
    $this->addModuleToTraining($training, $child_module_1);
    $this->addModuleToTraining($training, $child_module_2);

    // Create links.
    $content_parrent_module = OpignoGroupManagedContent::loadByProperties([
      'group_content_type_id' => 'ContentTypeModule',
      'entity_id' => $parent_module->id(),
    ]);
    $content_parrent_module = reset($content_parrent_module);

    $content_child_module_1 = OpignoGroupManagedContent::loadByProperties([
      'group_content_type_id' => 'ContentTypeModule',
      'entity_id' => $child_module_1->id(),
    ]);
    $content_child_module_1 = reset($content_child_module_1);

    $content_child_module_2 = OpignoGroupManagedContent::loadByProperties([
      'group_content_type_id' => 'ContentTypeModule',
      'entity_id' => $child_module_2->id(),
    ]);
    $content_child_module_2 = reset($content_child_module_2);

    $link_1 = OpignoGroupManagedLink::createWithValues(
      $content_child_module_1->getGroupId(),
      $content_parrent_module->id(),
      $content_child_module_1->id(),
      50
    );
    $link_1->save();

    $link_2 = OpignoGroupManagedLink::createWithValues(
      $content_child_module_2->getGroupId(),
      $content_parrent_module->id(),
      $content_child_module_2->id(),
      0
    );
    $link_2->save();

    $this->drupalGet('/group/' . $training->id() . '/learning-path/start');
    $this->assertSession()->titleEquals($parent_module->getName() . ' | Drupal');
    // Finish latest attempt for parent module with score 0%.
    $attempts = $parent_module->getModuleAttempts($this->groupCreator);
    /* @var \Drupal\opigno_module\Entity\UserModuleStatus $last_attempt */
    $last_attempt = end($attempts);
    $last_attempt->setScore(0);
    $last_attempt->setMaxScore(10);
    $last_attempt->setEvaluated(1);
    $last_attempt->setFinished(time());
    $last_attempt->save();

    $this->drupalGet('/group/' . $training->id() . '/learning-path/nextstep/' . $content_parrent_module->id());
    // User should be redirected to module with required score 0.
    $this->assertSession()->titleEquals($child_module_2->getName() . ' | Drupal');
    $this->finishCurrentModuleAttempt($child_module_2, $this->groupCreator);

    $this->drupalGet('/group/' . $training->id() . '/learning-path/start');
    $this->assertSession()->titleEquals($parent_module->getName() . ' | Drupal');
    // Finish latest attempt for parent module with score 60%.
    $attempts = $parent_module->getModuleAttempts($this->groupCreator);
    $last_attempt = end($attempts);
    $last_attempt->setScore(60);
    $last_attempt->setMaxScore(10);
    $last_attempt->setEvaluated(1);
    $last_attempt->setFinished(time());
    $last_attempt->save();

    $this->drupalGet('/group/' . $training->id() . '/learning-path/nextstep/' . $content_parrent_module->id());
    // User should be redirected to module with required score 50.
    $this->assertSession()->titleEquals($child_module_1->getName() . ' | Drupal');
    $this->finishCurrentModuleAttempt($child_module_1, $this->groupCreator);
  }

  /**
   * Force finish module current attempt.
   *
   * @param \Drupal\opigno_module\Entity\OpignoModule $module
   *   Module entity.
   * @param \Drupal\user\UserInterface $user
   *   User entity.
   *
   * @return \Drupal\opigno_module\Entity\UserModuleStatus
   *   Current attempt.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function finishCurrentModuleAttempt(OpignoModule $module, UserInterface $user) {
    /* @var \Drupal\opigno_module\Entity\UserModuleStatus $current_attempt */
    $current_attempt = $module->getModuleActiveAttempt($user);
    $current_attempt->setEvaluated(1);
    $current_attempt->setFinished(time());
    $current_attempt->save();
    return $current_attempt;
  }

}
