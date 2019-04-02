<?php

namespace Drupal\Tests\opigno_moxtra\Functional;

use Drupal\Core\Form\FormState;
use Drupal\opigno_moxtra\Entity\Meeting;

/**
 * Common tests for Opigno Moxtra.
 *
 * @group opigno_moxtra
 */
class OpignoMoxtraTest extends OpignoMoxtraBrowserTestBase {

  /**
   * Tests Opigno Moxtra Meeting access.
   */
  public function testOpignoMoxtraMeetingAccess() {
    // Create test training.
    $training = $this->createGroup(['field_workspace' => 1]);
    // Create one student with role - Collaborative features.
    $student_collaborative_features = $this->drupalCreateUser();
    $student_collaborative_features->addRole('collaborative_features');
    $student_collaborative_features->save();
    // Create a student without any roles.
    $student_1 = $this->drupalCreateUser();
    // Add students to training.
    $training->addMember($student_collaborative_features);
    $training->addMember($student_1);
    // Training members count.
    $this->assertEquals(3, count($training->getMembers()), 'Training members - group admin and two students.');

    // Create Meeting entity.
    $meeting = Meeting::create([
      'user_id' => $this->groupCreator->id(),
      'title' => $this->randomString(),
      'training' => $training->id(),
    ]);
    $meeting->save();

    $form_state_values = [
      'title' => $meeting->getTitle(),
      'members' => [],
    ];
    $this->saveMeetingForm($meeting, $form_state_values);

    // Check Meeting members without restriction.
    $meeting = Meeting::load($meeting->id());
    $members = $meeting->getMembersIds();
    $notified = $meeting->getNotifiedMembersIds();
    $this->assertEquals(1, count($members), 'Only user with role Collaborative features was added to a Meeting.');
    $this->assertEquals(1, count($notified), 'Only user with role Collaborative features was notified about Meeting.');

    // Check Meeting members with restriction.
    $form_state_values['members'] = ['user_' . $student_1->id()];
    $this->saveMeetingForm($meeting, $form_state_values);

    $meeting = Meeting::load($meeting->id());
    $members = $meeting->getMembersIds();
    $notified = $meeting->getNotifiedMembersIds();
    $this->assertEquals(0, count($members), 'User without role Collaborative features can not be added to a Meeting.');
    $this->assertEquals(0, count($notified), 'Not users to notify about Meeting.');

    $form_state_values['members'] = [
      'user_' . $student_collaborative_features->id(),
      'user_' . $student_1->id(),
    ];
    $this->saveMeetingForm($meeting, $form_state_values);
    $meeting = Meeting::load($meeting->id());
    $members = $meeting->getMembersIds();
    $notified = $meeting->getNotifiedMembersIds();
    $this->assertEquals(1, count($members), 'User with Collaborative features was added to a Meeting.');
    $this->assertEquals(1, count($notified), 'User with Collaborative features was notified about Meeting.');

  }

  /**
   * Programmatically execute method save for MeetingForm object.
   *
   * @param \Drupal\opigno_moxtra\Entity\Meeting $meeting
   *   Meeting entity.
   * @param array $form_state_values
   *   Values for FormState object.
   */
  protected function saveMeetingForm(Meeting $meeting, array $form_state_values) {
    $form_object = \Drupal::entityTypeManager()->getFormObject('opigno_moxtra_meeting', 'edit');
    $form_object->setEntity($meeting);
    $form_state = new FormState();
    $form_state->setFormObject($form_object);
    $form_state->setValues($form_state_values);
    $form = $form_object->buildForm([], $form_state);
    $form_object->save($form, $form_state);
  }

}
