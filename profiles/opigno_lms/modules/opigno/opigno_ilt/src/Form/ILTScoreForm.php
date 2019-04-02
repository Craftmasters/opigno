<?php

namespace Drupal\opigno_ilt\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\opigno_ilt\Entity\ILTResult;
use Drupal\opigno_ilt\ILTInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for scoring a opigno_ilt entity.
 */
class ILTScoreForm extends FormBase {

  /**
   * The Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Opigno ILT.
   *
   * @var \Drupal\opigno_ilt\ILTInterface
   */
  protected $opigno_ilt;

  /**
   * Creates a ILTScoreForm object.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'opigno_ilt_score_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    ILTInterface $opigno_ilt = NULL
  ) {
    $this->opigno_ilt = $opigno_ilt;
    $training = $opigno_ilt->getTraining();
    if (!isset($training)) {
      $message = $this->t('The Instructor-Led Training should has related training to save the presences.');
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

    // Load the Instructor-Led Training members
    // or the related training members, if there is no member restriction.
    $users = $opigno_ilt->getMembers();
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

    // Load the existing Instructor-Led Training results.
    /** @var \Drupal\opigno_ilt\ILTResultInterface[] $results */
    $results = $this->entityTypeManager
      ->getStorage('opigno_ilt_result')
      ->loadByProperties(['opigno_ilt' => $opigno_ilt->id()]);
    // Reindex results by the user ID.
    $results_by_user = [];
    array_walk($results, function ($result) use (&$results_by_user) {
      /** @var \Drupal\opigno_ilt\ILTResultInterface $result */
      $results_by_user[$result->getUserId()] = $result;
    });

    foreach ($users as $user) {
      $id = $user->id();
      if (isset($results_by_user[$id])) {
        // If result for this Instructor-Led Training
        // and user is exists, use it.
        /** @var \Drupal\opigno_ilt\ILTResultInterface $result */
        $result = $results_by_user[$id];
        $attendance = $result->getStatus();
        $score = $result->getScore();
      }
      else {
        // Else get the default values.
        $attendance = 1;
        $score = 100;
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
    $entity = $this->opigno_ilt;
    $scores = $form_state->getValue('submit_scores');
    foreach ($scores as $user_id => $values) {
      $status = $values['attendance'];
      $score = $values['score'];

      // Try load existing result.
      /** @var \Drupal\opigno_ilt\ILTResultInterface[] $results */
      $results = $this->entityTypeManager
        ->getStorage('opigno_ilt_result')
        ->loadByProperties([
          'opigno_ilt' => $entity->id(),
          'user_id' => $user_id,
        ]);
      $result = current($results);
      if ($result === FALSE) {
        // Create new result.
        $result = ILTResult::create();
        $result->setILT($entity);
        $result->setUserId($user_id);
      }

      // Update values.
      $result->setStatus($status);
      $result->setScore($score);
      $result->save();

      // Update user achievements.
      $gid = $entity->getTrainingId();
      if (isset($gid)) {
        $step = opigno_learning_path_get_ilt_step(
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
