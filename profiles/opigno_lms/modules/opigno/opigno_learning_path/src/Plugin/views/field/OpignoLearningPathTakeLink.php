<?php

namespace Drupal\opigno_learning_path\Plugin\views\field;

use Drupal\Core\Link;
use Drupal\opigno_learning_path\Entity\LPResult;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to output boolean indication of current user membership.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("opigno_learning_path_take_link")
 */
class OpignoLearningPathTakeLink extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $account = \Drupal::currentUser();
    // Get an entity object.
    $learnign_path = $values->_entity;
    $bundle = $learnign_path->getGroupType()->id();
    $group_progress = LPResult::learningPathUserProgress($learnign_path, $account->id());
    if ($group_progress > 0) {
      $link_text = $this->t('Continue');
    }
    else {
      $link_text = $this->t('Start');
    }
    // Take the bundle and build the take link.
    if ($bundle == 'learning_path') {
      return Link::createFromRoute(
        $link_text,
        'opigno_learning_path.steps.start',
        ['group' => $learnign_path->id()],
        [
          'attributes' => [
            'class' => ['use-ajax'],
          ],
        ]
      )->toString();
    }
    else {
      return '';
    }
  }

}
