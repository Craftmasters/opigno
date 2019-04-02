<?php

namespace Drupal\Tests\opigno_learning_path\Functional;

use Drupal\opigno_module\Entity\UserModuleStatus;

/**
 * Tests Opigno module score display.
 *
 * @group opigno_learning_path
 */
class OpignoModuleScoreTest extends LearningPathBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    \Drupal::service('module_installer')->install([
      'opigno_statistics',
      'opigno_messaging',
      'opigno_learning_path_test',
      'search',
    ]);

    \Drupal::service('theme_handler')->install(['platon']);
    \Drupal::service('theme_handler')->setDefault('platon');

  }

  /**
   * Test Opigno Module score on different pages (achievements, statistics etc).
   *
   * Depends on keep_results option (all, newest, best).
   */
  public function testModuleScoreDisplay() {
    // Create training.
    $training = $this->createGroup(['field_learning_path_visibility' => 'public']);
    // Add member to a training.
    $training->addMember($this->groupCreator);
    // Create module and add to a training.
    $module = $this->createOpignoModule(['keep_results' => $this->getKeepResultsOption('best')]);
    $training = $this->addModuleToTraining($training, $module);

    // Create finished Module attempt with score 100%.
    /* @see \Drupal\opigno_module\Entity\UserModuleStatus */
    $this->createAndFinishAttempt($training, $module, $this->groupCreator, 100);

    $this->drupalGet('/group/' . $training->id());
    $this->assertSession()->pageTextContains('100%');
    $this->drupalGet('/achievements');
    $content = $this->getSession()->getPage()->find('css', '.lp_step_summary_score');
    $this->assertEquals('Score: 100%', $content->getText(), 'Best score displays on achievements');
    $this->drupalGet('/statistics/training/' . $training->id());
    $content = $this->getSession()->getPage()->find('css', '.training-content-list');
    $this->assertEquals(1, substr_count($content->getText(), '100%'), 'Best score displays on statistics');

    // Create another finished Module attempt with worse score 50%.
    /* @see \Drupal\opigno_module\Entity\UserModuleStatus */
    $this->createAndFinishAttempt($training, $module, $this->groupCreator, 50);

    $this->drupalGet('/group/' . $training->id());
    $this->assertSession()->pageTextContains('100%');
    $this->drupalGet('/achievements');
    $content = $this->getSession()->getPage()->find('css', '.lp_step_summary_score', 'Best score still displays on achievements when user get a worse result');
    $this->assertEquals('Score: 100%', $content->getText());
    $this->drupalGet('/statistics/training/' . $training->id());
    $content = $this->getSession()->getPage()->find('css', '.training-content-list');
    $this->assertEquals(1, substr_count($content->getText(), '100%'), 'Best score still displays on statistics when user get a worse result');

    // Change keep score option to show only newest (last) score.
    $module->set('keep_results', $this->getKeepResultsOption('newest'));
    $module->save();

    // Create another finished Module attempt with worse score 50%
    // because statistics only updates
    // when user finish module after keep_results option was changed.
    /* @see \Drupal\opigno_module\Entity\UserModuleStatus */
    $this->createAndFinishAttempt($training, $module, $this->groupCreator, 50);

    $this->drupalGet('/group/' . $training->id());
    $this->assertSession()->pageTextContains('50%');
    $this->drupalGet('/achievements');
    $content = $this->getSession()->getPage()->find('css', '.lp_step_summary_score');
    $this->assertEquals('Score: 50%', $content->getText(), 'Newest score displays on achievements');
    $this->drupalGet('/statistics/training/' . $training->id());
    $content = $this->getSession()->getPage()->find('css', '.training-content-list');
    $this->assertEquals(1, substr_count($content->getText(), '50%'), 'Newest score displays on statistics');
  }

  /**
   * Get keep option by key.
   *
   * @param string $name
   *   Option name.
   *
   * @return int|null
   *   Option.
   *
   * @see OpignoModule::getKeepResultsOption()
   */
  private function getKeepResultsOption($name) {

    $options = [
      'best' => 0,
      'newest' => 1,
      'all' => 2,
    ];

    return in_array($name, $options) ? $options[$name] : NULL;
  }

  /**
   * Creates and finishes attempt.
   */
  private function createAndFinishAttempt($training, $module, $user, $score) {
    $attempt = UserModuleStatus::create([]);
    $attempt->setModule($module);
    $attempt->setScore($score);
    $attempt->setEvaluated(1);
    $attempt->setFinished(time());
    $attempt->save();
    // Reset all static variables.
    drupal_static_reset();
    // Save all achievements.
    $step = opigno_learning_path_get_module_step($training->id(), $user->id(), $module);
    opigno_learning_path_save_step_achievements($training->id(), $user->id(), $step);
    opigno_learning_path_save_achievements($training->id(), $user->id());

    return $attempt;
  }

}
