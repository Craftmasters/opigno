<?php

namespace Drupal\opigno_moxtra;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\user\Entity\User;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements Moxtra REST API.
 */
class MoxtraService implements MoxtraServiceInterface {

  use StringTranslationTrait;

  const MOXTRA_API = 'https://api.moxtra.com/v1';

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $logger;

  /**
   * Messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Opigno service.
   *
   * @var \Drupal\opigno_moxtra\OpignoServiceInterface
   */
  protected $opignoService;

  /**
   * Creates a MoxtraService instance.
   */
  public function __construct(
    TranslationInterface $translation,
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory,
    MessengerInterface $messenger,
    ClientInterface $http_client,
    OpignoServiceInterface $opigno_service
  ) {
    $this->setStringTranslation($translation);
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('opigno_moxtra');
    $this->messenger = $messenger;
    $this->httpClient = $http_client;
    $this->opignoService = $opigno_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('string_translation'),
      $container->get('config.factory'),
      $container->get('logger.factory'),
      $container->get('messenger'),
      $container->get('http_client'),
      $container->get('opigno_moxtra.opigno_api')
    );
  }

  /**
   * Returns URL to list the binders.
   *
   * @param int $owner_id
   *   User ID.
   *
   * @return string
   *   URL.
   */
  protected function getBinderListUrl($owner_id) {
    $access_token = $this->opignoService->getToken($owner_id);
    return self::MOXTRA_API . "/me/binders?access_token=$access_token";
  }

  /**
   * Returns URL to create the binder.
   *
   * @param int $owner_id
   *   User ID.
   *
   * @return string
   *   URL.
   */
  protected function getCreateBinderUrl($owner_id) {
    $access_token = $this->opignoService->getToken($owner_id);
    return self::MOXTRA_API . "/me/binders?access_token=$access_token";
  }

  /**
   * Returns URL to update the binder.
   *
   * @param int $owner_id
   *   User ID.
   * @param string $binder_id
   *   Binder ID.
   *
   * @return string
   *   URL.
   */
  protected function getUpdateBinderUrl($owner_id, $binder_id) {
    $access_token = $this->opignoService->getToken($owner_id);
    return self::MOXTRA_API . "/$binder_id?access_token=$access_token";
  }

  /**
   * Returns URL to delete the binder.
   *
   * @param int $owner_id
   *   User ID.
   * @param string $binder_id
   *   Binder ID.
   *
   * @return string
   *   URL.
   */
  protected function getDeleteBinderUrl($owner_id, $binder_id) {
    $access_token = $this->opignoService->getToken($owner_id);
    return self::MOXTRA_API . "/$binder_id?access_token=$access_token";
  }

  /**
   * Returns URL to send a message to the the binder.
   *
   * @param int $owner_id
   *   User ID.
   * @param string $binder_id
   *   Binder ID.
   *
   * @return string
   *   URL.
   */
  protected function getSendMessageUrl($owner_id, $binder_id) {
    $access_token = $this->opignoService->getToken($owner_id);
    return self::MOXTRA_API . "/$binder_id/comments?access_token=$access_token";
  }

  /**
   * Returns URL to add the users to the binder.
   *
   * @param int $owner_id
   *   User ID.
   * @param string $binder_id
   *   Binder ID.
   *
   * @return string
   *   URL.
   */
  protected function getAddUsersUrl($owner_id, $binder_id) {
    $access_token = $this->opignoService->getToken($owner_id);
    return self::MOXTRA_API . "/$binder_id/addorguser?access_token=$access_token";
  }

  /**
   * Returns URL to remove the user from the binder.
   *
   * @param int $owner_id
   *   User ID.
   * @param string $binder_id
   *   Binder ID.
   *
   * @return string
   *   URL.
   */
  protected function getRemoveUserUrl($owner_id, $binder_id) {
    $access_token = $this->opignoService->getToken($owner_id);
    return self::MOXTRA_API . "/$binder_id/removeuser?access_token=$access_token";
  }

  /**
   * Returns URL to get the meeting info.
   *
   * @param int $owner_id
   *   User ID.
   * @param string $session_key
   *   Session key of the Live Meeting.
   *
   * @return string
   *   URL.
   */
  protected function getMeetingInfoUrl($owner_id, $session_key) {
    $access_token = $this->opignoService->getToken($owner_id);
    return self::MOXTRA_API . "/meets/$session_key?access_token=$access_token";
  }

  /**
   * Returns URL to schedule a meeting.
   *
   * @param int $owner_id
   *   User ID.
   *
   * @return string
   *   URL.
   */
  protected function getCreateMeetingUrl($owner_id) {
    $access_token = $this->opignoService->getToken($owner_id);
    return self::MOXTRA_API . "/meets/schedule?access_token=$access_token";
  }

  /**
   * Returns URL to update the meeting.
   *
   * @param int $owner_id
   *   User ID.
   * @param string $session_key
   *   Session key of the Live Meeting.
   *
   * @return string
   *   URL.
   */
  protected function getUpdateMeetingUrl($owner_id, $session_key) {
    $access_token = $this->opignoService->getToken($owner_id);
    return self::MOXTRA_API . "/meets/$session_key?access_token=$access_token";
  }

  /**
   * Returns URL to delete a meeting.
   *
   * @param int $owner_id
   *   User ID.
   * @param string $session_key
   *   Session key of the Live Meeting.
   *
   * @return string
   *   URL.
   */
  protected function getDeleteMeetingUrl($owner_id, $session_key) {
    $access_token = $this->opignoService->getToken($owner_id);
    return self::MOXTRA_API . "/meets/$session_key?access_token=$access_token";
  }

  /**
   * Returns URL to get a meeting files list.
   *
   * @param int $owner_id
   *   User ID.
   * @param string $binder_id
   *   Binder ID of the Binder related to the Live Meeting.
   *
   * @return string
   *   URL.
   */
  protected function getMeetingFilesListUrl($owner_id, $binder_id) {
    $access_token = $this->opignoService->getToken($owner_id);
    return self::MOXTRA_API . "/$binder_id/files?access_token=$access_token";
  }

  /**
   * Returns URL to get a meeting file info.
   *
   * @param int $owner_id
   *   User ID.
   * @param string $binder_id
   *   Binder ID of the Binder related to the Live Meeting.
   * @param string $file_id
   *   File ID.
   *
   * @return string
   *   URL.
   */
  protected function getMeetingFileInfoUrl($owner_id, $binder_id, $file_id) {
    $access_token = $this->opignoService->getToken($owner_id);
    return self::MOXTRA_API . "/$binder_id/files/$file_id?access_token=$access_token";
  }

  /**
   * Returns URL to get a meeting recording info.
   *
   * @param int $owner_id
   *   User ID.
   * @param string $binder_id
   *   Binder ID of the Binder related to the Live Meeting.
   *
   * @return string
   *   URL.
   */
  protected function getMeetingRecordingInfoUrl($owner_id, $binder_id) {
    $access_token = $this->opignoService->getToken($owner_id);
    return self::MOXTRA_API . "/meets/recordings/$binder_id?access_token=$access_token";
  }

  /**
   * Helper function to send a request with JSON data to the Moxtra API.
   *
   * @param string $method
   *   HTTP method.
   * @param string $url
   *   Request URL.
   * @param array $request_data
   *   Request data.
   *
   * @return array
   *   Response data.
   */
  protected function request($method, $url, array $request_data) {
    $data = [];

    try {
      $response = $this->httpClient->request($method, $url, [
        'json' => $request_data,
      ]);
    }
    catch (ClientException $exception) {
      $this->logger->error($exception);
      $response = $exception->getResponse();
    }
    catch (\Exception $exception) {
      $this->logger->error($exception);
    }

    if (isset($response)) {
      $data['http_code'] = $response->getStatusCode();
      $response_body = $response->getBody()->getContents();
      if (!empty($response_body) && $response_body !== 'null') {
        $json_data = Json::decode($response_body);
        if (is_array($json_data) && !empty($json_data)) {
          $data = array_merge($data, $json_data);
        }
      }

      if ($data['http_code'] == 400) {
        if (isset($data['message'])
          && $data['message'] == 'cann\'t expel owner') {
          // Ignore 'cann't expel owner' error.
          $data['http_code'] = 200;
        }
      }

      if ($data['http_code'] == 404) {
        // Ignore 'User not found in member list.' error.
        $data['http_code'] = 200;
      }

      if ($data['http_code'] == 409) {
        // Ignore 'all invitees are already members' error.
        $data['http_code'] = 200;
      }

      if ($data['http_code'] != 200) {
        $this->logger->error($this->t('Error while contacting the Moxtra server.<br/><pre>Response: @response</pre>', [
          '@response' => print_r($data, TRUE),
        ]));

        $this->messenger->addError($this->t('Error while contacting the Moxtra server. Try again or contact the administrator.'));
      }
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function createWorkspace($owner_id, $name) {
    $data = [
      'name' => $name,
      'restricted' => TRUE,
      'conversation' => TRUE,
    ];

    $url = $this->getCreateBinderUrl($owner_id);
    return $this->request('POST', $url, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function updateWorkspace($owner_id, $binder_id, $name) {
    $data = [
      'name' => $name,
    ];

    $url = $this->getUpdateBinderUrl($owner_id, $binder_id);
    return $this->request('POST', $url, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteWorkspace($owner_id, $binder_id) {
    $url = $this->getDeleteBinderUrl($owner_id, $binder_id);
    return $this->request('DELETE', $url, []);
  }

  /**
   * {@inheritdoc}
   */
  public function sendMessage($owner_id, $binder_id, $message) {
    $data = [
      'text' => $message,
    ];

    $url = $this->getSendMessageUrl($owner_id, $binder_id);
    return $this->request('POST', $url, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function addUsersToWorkspace($owner_id, $binder_id, $users_ids) {
    $users = array_map(function ($id) {
      return [
        'user' => [
          'unique_id' => $id,
        ],
      ];
    }, $users_ids);

    $data = [
      'users' => $users,
      'suppress_feed' => TRUE,
    ];

    $url = $this->getAddUsersUrl($owner_id, $binder_id);
    $response = $this->request('POST', $url, $data);

    if (!empty($response) && $response['http_code'] == 200) {
      $owner = User::load($owner_id);
      /** @var \Drupal\user\Entity\User[] $users */
      $users = User::loadMultiple($users_ids);
      foreach ($users as $user) {
        $message = $this->t('@owner invited @user to join this conversation.', [
          '@owner' => $owner->getDisplayName(),
          '@user' => $user->getDisplayName(),
        ]);
        $this->sendMessage($owner_id, $binder_id, $message);
      }
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function removeUserFromWorkspace($owner_id, $binder_id, $user_id) {
    $data = [
      'unique_id' => $user_id,
      'suppress_feed' => TRUE,
    ];

    $url = $this->getRemoveUserUrl($owner_id, $binder_id);
    $response = $this->request('POST', $url, $data);
    if (!empty($response) && $response['http_code'] == 200) {
      $owner = User::load($owner_id);
      /** @var \Drupal\user\Entity\User $user */
      $user = User::load($user_id);
      $message = $this->t('@owner removed @user from this conversation.', [
        '@owner' => $owner->getDisplayName(),
        '@user' => $user->getDisplayName(),
      ]);
      $this->sendMessage($owner_id, $binder_id, $message);
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function getMeetingInfo($owner_id, $session_key) {
    $url = $this->getMeetingInfoUrl($owner_id, $session_key);
    return $this->request('GET', $url, []);
  }

  /**
   * {@inheritdoc}
   */
  public function createMeeting($owner_id, $title, $starts, $ends) {
    $data = [
      'name' => $title,
      'starts' => $starts,
      'ends' => $ends,
    ];

    $url = $this->getCreateMeetingUrl($owner_id);
    return $this->request('POST', $url, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function updateMeeting($owner_id, $session_key, $title, $starts, $ends = NULL) {
    $data = [
      'name' => $title,
      'starts' => $starts,
    ];

    if (isset($ends)) {
      $data['ends'] = $ends;
    }

    $url = $this->getUpdateMeetingUrl($owner_id, $session_key);
    return $this->request('POST', $url, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMeeting($owner_id, $session_key) {
    $url = $this->getDeleteMeetingUrl($owner_id, $session_key);
    return $this->request('DELETE', $url, []);
  }

  /**
   * {@inheritdoc}
   */
  public function getMeetingFilesList($owner_id, $binder_id) {
    $url = $this->getMeetingFilesListUrl($owner_id, $binder_id);
    return $this->request('GET', $url, []);
  }

  /**
   * {@inheritdoc}
   */
  public function getMeetingFileInfo($owner_id, $binder_id, $file_id) {
    $url = $this->getMeetingFileInfoUrl($owner_id, $binder_id, $file_id);
    return $this->request('GET', $url, []);
  }

  /**
   * {@inheritdoc}
   */
  public function getMeetingRecordingInfo($owner_id, $binder_id) {
    $url = $this->getMeetingRecordingInfoUrl($owner_id, $binder_id);
    return $this->request('GET', $url, []);
  }

}
