<?php

namespace Drupal\opigno_tincan_live_meeting\EventSubscriber;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\opigno_moxtra\Entity\Meeting;
use Drupal\opigno_moxtra\MoxtraServiceInterface;
use Drupal\opigno_tincan_api\OpignoTinCanApiStatements;
use Drupal\opigno_tincan_api\OpignoTincanApiTinCanActivityDefinitionTypes;
use Drupal\opigno_tincan_api\OpignoTincanApiTinCanVerbs;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use TinCan\Context;

/**
 * Class OpignoTincanLiveMeeting.
 */
class OpignoTincanLiveMeeting implements EventSubscriberInterface {

  /**
   * User.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * Route.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $route;

  /**
   * Moxtra Service Interface.
   *
   * @var \Drupal\opigno_moxtra\MoxtraServiceInterface
   */
  protected $moxtraService;

  /**
   * EventSubscriber constructor.
   */
  public function __construct(
    AccountInterface $current_user,
    RouteMatchInterface $route,
    MoxtraServiceInterface $moxtra_service
  ) {
    $this->user = $current_user;
    $this->route = $route;
    $this->moxtraService = $moxtra_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('current_route_match'),
      $container->get('opigno_moxtra.moxtra_api')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['tincanLiveMeeting'];
    return $events;
  }

  /**
   * Called whenever the tincanLiveMeeting event is dispatched.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   Event.
   *
   * @throws \Exception
   */
  public function tincanLiveMeeting(Event $event) {
    // Check if Tincan PHP library is installed.
    $has_library = opigno_tincan_api_tincanphp_is_installed();
    if (!$has_library) {
      \Drupal::logger('opigno_tincan')
        ->notice('Tincan statements can not be send. Tincan PHP library is not installed.');
      return;
    };

    $route_name = $this->route->getRouteName();
    // Statement must be send only from this route.
    if ($route_name != 'opigno_moxtra.meeting') {
      return;
    }

    /** @var \Drupal\opigno_moxtra\MeetingInterface[] $meetings */
    $meeting = $this->route->getParameter('opigno_moxtra_meeting');

    // Check if statements was already sent.
    $was_send = $this->wasStatementSend($meeting);
    if ($was_send) {
      return;
    };

    // Get response about meeting from moxtra.
    $owner_id = $meeting->getOwnerId();
    $session_key = $meeting->getSessionKey();

    $info = $this->moxtraService->getMeetingInfo($owner_id, $session_key);
    $status = $info['data']['status'];

    // Live meeting is ended.
    if ($status == 'SESSION_ENDED') {
      // User must be a participant of meeting.
      $participants = $info['data']['participants'];
      $is_participant = FALSE;
      foreach ($participants as $participant) {
        if ($participant['unique_id'] == $this->user->id()) {
          $is_participant = TRUE;
        }
      }
      if ($is_participant) {
        $this->createAndSendTincanStatementsForMeeting($meeting, $info);
      };
    }

  }

  /**
   * Sends tincan statements for Live meeting.
   *
   * @param \Drupal\opigno_moxtra\Entity\Meeting $meeting
   *   Meeting.
   * @param array $response_data
   *   Response data.
   *
   * @throws \Exception
   */
  protected function createAndSendTincanStatementsForMeeting(Meeting $meeting, array $response_data) {
    /****
     * - When user attended a live meeting
     * Actor: user
     * Verb: xAPI/attended
     * Object: xAPI/meeting
     * Context: Training
     */

    // Statement creation.
    $statement = OpignoTinCanApiStatements::statementBaseCreation(
      OpignoTincanApiTinCanVerbs::$attended,
      OpignoTincanApiTinCanActivityDefinitionTypes::$meeting,
      $meeting
    );

    if ($statement === FALSE) {
      return;
    }

    // Context creation.
    $context = new Context();

    // Get group.
    $group = $meeting->getTraining();
    $parent_ids = [$group->id()];
    OpignoTinCanApiStatements::contextSetGrouping($context, $parent_ids);

    // Set language in context.
    OpignoTinCanApiStatements::contextSetLanguage(
      $context,
      $meeting->language()->getId()
    );

    // Get duration.
    $start_date = $response_data['data']['starts'];
    $end_date = $response_data['data']['ends'];
    $duration_s = strtotime($end_date) - strtotime($start_date);

    // Set result.
    OpignoTinCanApiStatements::setResult(
      $statement,
      NULL,
      NULL,
      NULL,
      NULL,
      NULL,
      abs($duration_s)
    );
    // Set statement context.
    $statement->setContext($context);

    // Sending statement.
    $result = OpignoTinCanApiStatements::sendStatement($statement);

    if ($result) {
      // Save data about statement sending in database.
      $this->saveSendingStatement($meeting);
    };
  }

  /**
   * Returns sent statement flag.
   *
   * @param \Drupal\opigno_moxtra\Entity\Meeting $meeting
   *   Meeting.
   *
   * @return bool
   *   sent statement flag.
   */
  protected function wasStatementSend(Meeting $meeting) {
    /* @var $db_connection \Drupal\Core\Database\Connection */
    $db_connection = \Drupal::service('database');
    $user = $this->user;
    $query = $db_connection->select('opigno_tincan_live_meeting', 'otl')
      ->fields('otl')
      ->condition('otl.uid', $user->id())
      ->condition('otl.meeting_id', $meeting->id());
    $result = $query->execute()->fetchObject();
    if ($result) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Saves statement.
   *
   * @param \Drupal\opigno_moxtra\Entity\Meeting $meeting
   *   Meeting.
   *
   * @throws \Exception
   */
  protected function saveSendingStatement(Meeting $meeting) {
    /* @var $db_connection \Drupal\Core\Database\Connection */
    $db_connection = \Drupal::service('database');
    $user = $this->user;
    $query = $db_connection->insert('opigno_tincan_live_meeting')
      ->fields([
        'uid' => $user->id(),
        'meeting_id' => $meeting->id(),
      ]);
    $query->execute();
  }

}
