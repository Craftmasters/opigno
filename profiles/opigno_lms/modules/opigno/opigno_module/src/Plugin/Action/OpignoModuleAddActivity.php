<?php

namespace Drupal\opigno_module\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\opigno_module\Entity\OpignoModuleInterface;

/**
 * Add activities to Module.
 *
 * @Action(
 *   id = "opigno_module_add_activity",
 *   label = @Translation("Add activities to Module"),
 *   type = "opigno_activity"
 * )
 */
class OpignoModuleAddActivity extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    // Get URL parameters.
    foreach (\Drupal::routeMatch()->getParameters() as $param) {
      if ($param instanceof OpignoModuleInterface) {
        $opigno_module = $param;
      }
    }
    if ($opigno_module) {
      $opigno_module_obj = \Drupal::service('opigno_module.opigno_module');
      $save_acitivities = $opigno_module_obj->activitiesToModule([$entity], $opigno_module);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = $object->access('update', $account, TRUE);

    return $return_as_object ? $result : $result->isAllowed();
  }

}
