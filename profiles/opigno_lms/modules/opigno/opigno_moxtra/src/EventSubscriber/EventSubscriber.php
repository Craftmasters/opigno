<?php

namespace Drupal\opigno_moxtra\EventSubscriber;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\media\Entity\Media;
use Drupal\opigno_moxtra\MoxtraServiceInterface;
use Drupal\opigno_moxtra\OpignoServiceInterface;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class EventSubscriber.
 */
class EventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * User.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * Route.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface*/
  protected $route;

  /**
   * Time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface*/
  protected $time;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Opigno service.
   *
   * @var \Drupal\opigno_moxtra\OpignoServiceInterface
   */
  protected $opignoService;

  /**
   * Moxtra service.
   *
   * @var \Drupal\opigno_moxtra\MoxtraServiceInterface
   */
  protected $moxtraService;

  /**
   * EventSubscriber constructor.
   */
  public function __construct(
    TranslationInterface $translation,
    AccountInterface $current_user,
    RouteMatchInterface $route,
    TimeInterface $time,
    EntityTypeManagerInterface $entity_type_manager,
    OpignoServiceInterface $opigno_service,
    MoxtraServiceInterface $moxtra_service
  ) {
    $this->setStringTranslation($translation);
    $this->user = $current_user;
    $this->route = $route;
    $this->time = $time;
    $this->entityTypeManager = $entity_type_manager;
    $this->opignoService = $opigno_service;
    $this->moxtraService = $moxtra_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('string_translation'),
      $container->get('current_user'),
      $container->get('current_route_match'),
      $container->get('datetime.time'),
      $container->get('entity_type.manager'),
      $container->get('opigno_moxtra.opigno_api'),
      $container->get('opigno_moxtra.moxtra_api')
    );
  }

  /**
   * Helper function that returns tid of the live meetings recordings folder.
   *
   * Creates folder if it is not exists.
   *
   * @param int $group_id
   *   Group ID.
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   Taxonomy term ID of the recordings folder.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function getRecordingsFolder($group_id) {
    $results = &drupal_static(__FUNCTION__);
    if (isset($results[$group_id])) {
      return $results[$group_id];
    }

    // Get the tid of the folder of the group.
    $group_folder_tid = _tft_get_group_tid($group_id);
    if ($group_folder_tid === NULL) {
      return NULL;
    }

    $recording_folder_name = $this->t('Recorded Live Meetings');

    // Try get folder for the live meetings recordings.
    $recording_folder = NULL;
    /** @var \Drupal\taxonomy\TermStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term');
    $children = $storage->loadChildren($group_folder_tid);
    foreach ($children as $child) {
      if ($child->label() === (string) $recording_folder_name) {
        $recording_folder = $child;
        break;
      }
    }

    // Create folder for the live meetings recordings if it is not exists.
    if (!isset($recording_folder)) {
      $recording_folder = Term::create([
        'vid' => 'tft_tree',
        'name' => $recording_folder_name,
        'parent' => $group_folder_tid,
      ]);
      $recording_folder->save();
    }

    return $results[$group_id] = $recording_folder;
  }

  /**
   * Saves recordings of the ended live meetings.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   Event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function request(GetResponseEvent $event) {
    $route_name = $this->route->getRouteName();
    $routes = [
      // Routes where the TFT 'Documents library' appears.
      'entity.group.canonical',
    ];
    if (!in_array($route_name, $routes)) {
      return;
    }

    // Get group from the route.
    /** @var \Drupal\group\Entity\GroupInterface $group */
    $group = $this->route->getParameter('group');
    if (!isset($group)) {
      return;
    }

    // Check group type.
    if ($group->getGroupType()->id() !== 'learning_path') {
      return;
    }

    /** @var \Drupal\opigno_moxtra\MeetingInterface[] $meetings */
    $meetings = $group->getContentEntities('opigno_moxtra_meeting_group');
    foreach ($meetings as $meeting) {
      $owner_id = $meeting->getOwnerId();
      // Check the live meeting status.
      $session_key = $meeting->getSessionKey();
      if (empty($session_key)) {
        continue;
      }

      $info = $this->moxtraService->getMeetingInfo($owner_id, $session_key);
      $status = $info['data']['status'];
      if ($status !== 'SESSION_ENDED') {
        continue;
      }

      // Check live meeting has recordings.
      $info = $this->moxtraService->getMeetingRecordingInfo($owner_id, $session_key);
      if (((int) $info['data']['count']) === 0) {
        continue;
      }
      $recordings = array_map(function ($recording) {
        return $recording['download_url'];
      }, $info['data']['recordings']);

      // Get the recordings folder.
      $group_id = $group->id();
      $folder = $this->getRecordingsFolder($group_id);
      if (!isset($folder)) {
        continue;
      }

      // Get the files.
      $fids = \Drupal::entityQuery('media')
        ->condition('bundle', 'tft_file')
        ->condition('tft_folder.target_id', $folder->id())
        ->execute();

      /** @var \Drupal\media\MediaInterface[] $files */
      $files = Media::loadMultiple($fids);

      foreach ($recordings as $recording) {
        // Check that file for this live meeting recording
        // is not already exists.
        $exists = FALSE;
        foreach ($files as $file) {
          if (!$file->hasField('opigno_moxtra_recording_link')) {
            continue;
          }

          $link = $file->get('opigno_moxtra_recording_link')->getValue();
          if (!empty($link)) {
            $url = $link[0]['uri'];
            if ($url === $recording) {
              $exists = TRUE;
              break;
            }
          }
        }

        // Save the live meeting recording.
        if (!$exists) {
          $members = $meeting->getMembersIds();
          if (empty($members)) {
            $training = $meeting->getTraining();
            if (isset($training)) {
              $members = array_map(function ($membership) {
                /** @var \Drupal\group\GroupMembership $membership */
                return $membership->getUser()->id();
              }, $training->getMembers());
            }
          }

          $file = Media::create([
            'bundle' => 'tft_file',
            'name' => $meeting->label(),
            'uid' => $owner_id,
            'opigno_moxtra_recording_link' => [
              'uri' => $recording,
            ],
            'tft_folder' => [
              'target_id' => $folder->id(),
            ],
            'tft_members' => $members,
          ]);
          $file->save();
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['request'];
    return $events;
  }

}
