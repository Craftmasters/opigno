<?php

namespace Drupal\opigno_module;

use Drupal\Core\Plugin\PluginBase;
use Drupal\opigno_module\Entity\OpignoActivityInterface;
use Drupal\opigno_module\Entity\OpignoAnswerInterface;

/**
 * Class ActivityAnswerPluginBase.
 */
abstract class ActivityAnswerPluginBase extends PluginBase implements ActivityAnswerPluginInterface {

  /**
   * ActivityAnswerPluginBase constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->pluginDefinition['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getActivityType() {
    return $this->pluginDefinition['activityTypeBundle'];
  }

  /**
   * {@inheritdoc}
   */
  public function evaluatedOnSave(OpignoActivityInterface $activity) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getScore(OpignoAnswerInterface $answer) {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function answeringForm(array &$form) {}

}
