<?php

namespace Drupal\Tests\opigno_ilt\Functional;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\opigno_ilt\Entity\ILT;

/**
 * Common tests for Opigno ILT.
 *
 * @group opigno_ilt
 */
class OpignoILTTest extends OpignoILTBrowserTestBase {

  /**
   * Tests ILT access.
   */
  public function testOpignoILTAccess() {
    // Create test training.
    $training = $this->createGroup();
    // Create students.
    $student_1 = $this->drupalCreateUser();
    $student_2 = $this->drupalCreateUser();
    // Add students to training.
    $training->addMember($student_1);
    $training->addMember($student_2);
    $training->save();
    // Training members count.
    $this->assertEquals(3, count($training->getMembers()), 'Training members - Group admin and two students.');

    // Create ILT.
    $meeting = ILT::create([
      'title' => $this->randomString(),
      'training' => $training->id(),
      'place' => $this->randomString(),
      'date' => $this->createDummyDaterange(),
    ]);
    $meeting->save();

    // Students should automatically added to ILT.
    $this->drupalGet('/ilt/' . $meeting->id() . '/edit');
    $page = $this->getSession()->getPage();
    $page->pressButton('Save');

    $meeting = ILT::load($meeting->id());
    $members = $meeting->getMembersIds();
    $notified = $meeting->getNotifiedMembers();
    $this->assertEquals(3, count($members), 'Students without restriction were added to ILT.');
    $this->assertEquals(3, count($notified), 'Students without restriction were notified about ILT.');

    // Add only one student to ILT.
    $meeting = ILT::load($meeting->id());
    $meeting->setMembersIds([$student_1->id()]);
    $meeting->save();
    $this->drupalGet('/ilt/' . $meeting->id() . '/edit');
    $page = $this->getSession()->getPage();
    $page->pressButton('Save');
    $meeting = ILT::load($meeting->id());
    $members = $meeting->getMembersIds();
    $notified = $meeting->getNotifiedMembers();
    $this->assertEquals(1, count($members), 'Student with restriction was added to ILT.');
    $this->assertEquals(1, count($notified), 'Student with restriction was notified about ILT.');
  }

  /**
   * Create dummy date ranfge for ILT (interval +1 hour)
   *
   * @return array
   *   Array with Start date and End date.
   */
  protected function createDummyDateRange() {
    $display_format = 'm-d-Y H:i:s';
    $start_date = date($display_format, strtotime("1 hour"));
    $end_date = date($display_format, strtotime("2 hour"));
    $start_date_value = DrupalDateTime::createFromFormat($display_format, $start_date);
    $end_date_value = DrupalDateTime::createFromFormat($display_format, $end_date);
    $date_range = [
      'value' => $start_date_value->setTimezone(new \DateTimeZone(drupal_get_user_timezone()))
        ->format(DrupalDateTime::FORMAT),
      'end_value' => $end_date_value->setTimezone(new \DateTimeZone(drupal_get_user_timezone()))
        ->format(DrupalDateTime::FORMAT),
    ];
    return $date_range;
  }

}
