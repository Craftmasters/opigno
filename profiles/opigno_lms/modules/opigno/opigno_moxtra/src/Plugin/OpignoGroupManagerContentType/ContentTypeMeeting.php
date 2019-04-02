<?php

namespace Drupal\opigno_moxtra\Plugin\OpignoGroupManagerContentType;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Url;
use Drupal\opigno_group_manager\ContentTypeBase;
use Drupal\opigno_group_manager\OpignoGroupContent;
use Drupal\opigno_moxtra\Entity\Meeting;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ContentTypeMeeting.
 *
 * @OpignoGroupManagerContentType(
 *   id = "ContentTypeMeeting",
 *   entity_type = "opigno_moxtra_meeting",
 *   readable_name = "Live Meeting",
 *   description = "Contains the Live Meetings",
 *   allowed_group_types = {
 *     "learning_path"
 *   },
 *   group_content_plugin_id = "opigno_moxtra_meeting_group"
 * )
 */
class ContentTypeMeeting extends ContentTypeBase {

  /**
   * {@inheritdoc}
   */
  public function shouldShowNext() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserScore($user_id, $entity_id) {
    /** @var \Drupal\opigno_moxtra\MeetingResultInterface[] $results */
    $results = \Drupal::entityTypeManager()
      ->getStorage('opigno_moxtra_meeting_result')
      ->loadByProperties([
        'user_id' => $user_id,
        'meeting' => $entity_id,
      ]);

    $best_score = 0;
    foreach ($results as $result) {
      $score = $result->getScore();
      if ($score > $best_score) {
        $best_score = $score;
      }
    }

    return $best_score / 100;
  }

  /**
   * {@inheritdoc}
   */
  public function getViewContentUrl($entity_id) {
    return Url::fromRoute('entity.opigno_moxtra_meeting.canonical', [
      'opigno_moxtra_meeting' => $entity_id,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getStartContentUrl($content_id, $group_id = NULL) {
    return Url::fromRoute('opigno_moxtra.meeting', [
      'opigno_moxtra_meeting' => $content_id,
    ]);
  }

  /**
   * {@inheritdoc}
   *
   * @param int|\Drupal\opigno_moxtra\Entity\Meeting $meeting
   *   The entity ID or entity instance.
   */
  public function getContent($meeting) {
    // If the value is the meeting ID, load the meeting.
    if (is_numeric($meeting)) {
      /** @var \Drupal\opigno_moxtra\MeetingInterface $meeting */
      $meeting = Meeting::load($meeting);
    }

    if ($meeting === NULL || $meeting === FALSE) {
      return FALSE;
    }

    return new OpignoGroupContent(
      $this->getPluginId(),
      $this->getEntityType(),
      $meeting->id(),
      $meeting->label(),
      $this->getDefaultModuleImageUrl(),
      t('Default image')
    );
  }

  /**
   * Returns default image url.
   */
  public function getDefaultModuleImageUrl() {
    $request = \Drupal::request();
    $path = \Drupal::service('module_handler')->getModule('opigno_module')->getPath();
    return $request->getBasePath() . '/' . $path . '/img/img_module.svg';
  }

  /**
   * {@inheritdoc}
   */
  public function getContentFromRequest(Request $request) {
    $meeting = $request->get('opigno_moxtra_meeting');
    if ($meeting === NULL || $meeting === FALSE) {
      return FALSE;
    }

    return $this->getContent($meeting);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormObject($entity_id = NULL) {
    if (empty($entity_id)) {
      $form = \Drupal::entityTypeManager()->getFormObject($this->getEntityType(), 'add');
      $entity = Meeting::create();
    }
    else {
      $form = \Drupal::entityTypeManager()->getFormObject($this->getEntityType(), 'edit');
      $entity = Meeting::load($entity_id);
    }

    $form->setEntity($entity);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllContents() {
    try {
      /** @var \Drupal\opigno_moxtra\Entity\Meeting[] $meetings */
      $meetings = \Drupal::entityTypeManager()
        ->getStorage('opigno_moxtra_meeting')
        ->loadMultiple();
    }
    catch (InvalidPluginDefinitionException $e) {
      // TODO: Log the error.
      return FALSE;
    }

    $contents = [];
    foreach ($meetings as $meeting) {
      $contents[] = $this->getContent($meeting);
    }
    return $contents;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContents() {
    return $this->getAllContents();
  }

}
