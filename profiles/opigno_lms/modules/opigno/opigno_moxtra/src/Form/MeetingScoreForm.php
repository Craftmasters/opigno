<?php

namespace Drupal\opigno_moxtra\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\opigno_moxtra\Entity\MeetingResult;
use Drupal\opigno_moxtra\MoxtraServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a form for scoring a opigno_moxtra_meeting entity.
 */
class MeetingScoreForm extends FormBase {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Moxtra service.
   *
   * @var \Drupal\opigno_moxtra\MoxtraServiceInterface
   */
  protected $moxtraService;

  /**
   * Creates a WorkspaceForm object.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    MoxtraServiceInterface $moxtra_service
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moxtraService = $moxtra_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('opigno_moxtra.moxtra_api')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'opigno_moxtra_score_meeting_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\opigno_moxtra\MeetingInterface $entity */
    $entity = $this->getRequest()->get('opigno_moxtra_meeting');
    if (!isset($entity)) {
      throw new NotFoundHttpException();
    }

    $training = $entity->getTraining();
    if (!isset($training)) {
      $message = $this->t('The meeting should has related training to save the presences.');
      $this->messenger()->addError($message);
      return $form;
    }

    $owner_id = $entity->getOwnerId();
    $session_key = $entity->getSessionKey();
    $info = $this->moxtraService->getMeetingInfo($owner_id, $session_key);
    $status = $info['data']['status'];
    if ($status !== 'SESSION_ENDED') {
      $message = $this->t('The meeting has to be ended in order to save the presences.');
      $this->messenger()->addError($message);
      return $form;
    }

    $form['submit_scores'] = [
      '#type' => 'table',
      '#title' => $this->t('Participants for @training', [
        '@training' => $training->label(),
      ]),
      '#header' => [
        $this->t('Name'),
        $this->t('Attendance'),
        $this->t('Score'),
      ],
    ];
    $scores = &$form['submit_scores'];

    // Load the meeting members
    // or the related training members, if there is no member restriction.
    $users = $entity->getMembers();
    if (empty($users)) {
      $members = $training->getMembers();
      $users = array_map(function ($member) {
        /** @var \Drupal\group\GroupMembership $member */
        return $member->getUser();
      }, $members);
    }
    uasort($users, function ($user1, $user2) {
      /** @var \Drupal\user\Entity\User $user1 */
      /** @var \Drupal\user\Entity\User $user2 */
      return strcasecmp($user1->getDisplayName(), $user2->getDisplayName());
    });

    // Load the existing meeting results.
    /** @var \Drupal\opigno_moxtra\MeetingResultInterface[] $results */
    $results = $this->entityTypeManager
      ->getStorage('opigno_moxtra_meeting_result')
      ->loadByProperties(['meeting' => $entity->id()]);
    // Reindex results by the user ID.
    $results_by_user = [];
    array_walk($results, function ($result) use (&$results_by_user) {
      /** @var \Drupal\opigno_moxtra\MeetingResultInterface $result */
      $results_by_user[$result->getUserId()] = $result;
    });

    // Get the user IDs of the actual participants of the meeting.
    $participants = array_map(function ($participant) {
      return $participant['unique_id'];
    }, $info['data']['participants']);

    foreach ($users as $user) {
      $id = $user->id();

      if (isset($results_by_user[$id])) {
        // If result for this meeting and user is exists, use it.
        /** @var \Drupal\opigno_moxtra\MeetingResultInterface $result */
        $result = $results_by_user[$id];
        $attendance = $result->getStatus();
        $score = $result->getScore();
      }
      else {
        // Else get the default values.
        if (in_array($id, $participants)) {
          $attendance = 1;
          $score = 100;
        }
        else {
          $attendance = 0;
          $score = 0;
        }
      }

      $scores[$id]['name'] = $user->toLink()->toRenderable();
      $scores[$id]['attendance'] = [
        '#type' => 'select',
        '#options' => [
          0 => $this->t('Absent'),
          1 => $this->t('Attended'),
        ],
        '#default_value' => $attendance,
      ];
      $scores[$id]['score'] = [
        '#type' => 'number',
        '#min' => 0,
        '#max' => 100,
        '#step' => 1,
        '#default_value' => $score,
      ];
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save attendances'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\opigno_moxtra\MeetingInterface $entity */
    $entity = $this->getRequest()->get('opigno_moxtra_meeting');
    $scores = $form_state->getValue('submit_scores');
    foreach ($scores as $user_id => $values) {
      $status = $values['attendance'];
      $score = $values['score'];

      // Try load existing result.
      /** @var \Drupal\opigno_moxtra\MeetingResultInterface[] $results */
      $results = $this->entityTypeManager
        ->getStorage('opigno_moxtra_meeting_result')
        ->loadByProperties([
          'meeting' => $entity->id(),
          'user_id' => $user_id,
        ]);
      $result = current($results);
      if ($result === FALSE) {
        // Create new result.
        $result = MeetingResult::create();
        $result->setMeeting($entity);
        $result->setUserId($user_id);
      }

      // Update values.
      $result->setStatus($status);
      $result->setScore($score);
      $result->save();

      // Update user achievements.
      $gid = $entity->getTrainingId();
      if (isset($gid)) {
        $step = opigno_learning_path_get_meeting_step(
          $gid,
          $user_id,
          $entity
        );
        opigno_learning_path_save_step_achievements($gid, $user_id, $step, 0);
        opigno_learning_path_save_achievements($gid, $user_id);
      }
    }
  }

}
