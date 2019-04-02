<?php

namespace Drupal\opigno_course\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\Group;

/**
 * Class CourseContentForm.
 */
class CourseContentForm extends FormBase {

  /**
   * A!!!!!!!!!!!!!!!!!!
   *
   * THIS WHOLE FILE WILL BE REPLACED BY A LEARNING PATH MANAGER LOOKALIKE TOOL.
   * // TODO: Create the course content manager
   * using the learning path manager app.
   *
   * !!!!!!!!!!!
   */

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'opigno_course_content_add';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * Check the access to this form.
   */
  public function access(Group $group) {
    if ($group->getGroupType()->id() == 'opigno_course') {
      return AccessResult::allowed();
    }
    return AccessResult::neutral();
  }

}
