<?php

namespace Drupal\opigno_moxtra;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheBackendInterface;
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
 * Implements Opigno API.
 */
class OpignoService implements OpignoServiceInterface {

  use StringTranslationTrait;

  const OPIGNO_API = 'https://www.opigno.org/en/opigno_moxtra/opigno_moxtra';
  const OPIGNO_API_GET_TOKEN = OpignoService::OPIGNO_API . '/token_get.json';
  const OPIGNO_API_GET_ORG_INFO = OpignoService::OPIGNO_API . '/organization_info_get.json';
  const OPIGNO_API_CREATE_ORG = OpignoService::OPIGNO_API . '/organization_create.json';
  const OPIGNO_API_CREATE_USERS = OpignoService::OPIGNO_API . '/users_create.json';
  const OPIGNO_API_UPDATE_USER = OpignoService::OPIGNO_API . '/user_update.json';
  const OPIGNO_API_ENABLE_USER = OpignoService::OPIGNO_API . '/user_enable.json';
  const OPIGNO_API_DISABLE_USER = OpignoService::OPIGNO_API . '/user_disable.json';
  const OPIGNO_API_DELETE_USER = OpignoService::OPIGNO_API . '/user_delete.json';

  const ERROR_CODE_ORG_NOT_CREATED = 4000;

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
   * Cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

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
   * Constructs a OpignoService object.
   */
  public function __construct(
    TranslationInterface $translation,
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory,
    CacheBackendInterface $cache,
    TimeInterface $time,
    MessengerInterface $messenger,
    ClientInterface $http_client
  ) {
    $this->setStringTranslation($translation);
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('opigno_moxtra');
    $this->cache = $cache;
    $this->time = $time;
    $this->messenger = $messenger;
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('string_translation'),
      $container->get('config.factory'),
      $container->get('logger.factory'),
      $container->get('cache.default'),
      $container->get('datetime.time'),
      $container->get('messenger'),
      $container->get('http_client')
    );
  }

  /**
   * Returns error message by the Opigno API error code.
   *
   * @param string $error_code
   *   Error code.
   *
   * @return string
   *   Message.
   */
  protected function getErrorMessage($error_code) {
    $error_codes = &drupal_static(__FUNCTION__ . ':error_codes');
    if (!isset($error_codes)) {
      $error_codes = [
        4000 => $this->t('This Opigno instance is not registered in Opigno.org for using the Moxtra App. Use the Administration menu to manually register the app.'),
        'error_create_org_moxtra_servers' => $this->t('Error while creating the organization.'),
        'error_update_org_moxtra_servers' => $this->t('There was an error while updating the organization on Moxtra servers.'),
        'org_not_exists' => $this->t('This organisation does not exist in Opigno database.'),
        'org_not_valid' => $this->t('Organization not valid.'),
        'error_create_users_moxtra_servers' => $this->t('Error while creating the users on Moxtra servers.'),
        'error_create_users_opigno_servers' => $this->t('Error while creating the users on Opigno servers.'),
        'error_create_users_maximum_reached' => $this->t('You have reached your total number of authorized users for collaborative workspaces.'),
        'error_update_users_moxtra_servers' => $this->t('Error while updating the users on Moxtra servers. Manage your subscription to add more users.'),
        'error_update_users_opigno_servers' => $this->t('Error while updating the users on Opigno servers. Maybe you have reached your maximum number of users for collaborative workspaces. Manage your subscription to add more users.'),
        'error_get_users_moxtra_servers' => $this->t('Error while getting the user information from Moxtra servers.'),
        'error_disable_user_moxtra_servers' => $this->t('Error while disabling the user on Moxtra servers.'),
        'error_disable_user_opigno_servers' => $this->t('Error while disabling the user on Opigno servers.'),
        'error_enable_user_moxtra_servers' => $this->t('Error while enabling the user on Moxtra servers.'),
        'error_enable_user_opigno_servers' => $this->t('Error while enabling the user on Opigno servers.'),
        'error_allow_user_create_meet_opigno_servers' => $this->t('Error while updating the Opigno database.'),
        'error_allow_user_create_meet_maximum_reached' => $this->t('You have reached the maximum number of users who can create meetings. Manage your subscription to add more users.'),
        'error_allow_user_create_meet_moxtra_servers' => $this->t('An error occurred on Moxtra server while allowing the user to create meeting.'),
        'error_deny_user_create_meet_opigno_servers' => $this->t('Error while updating the Opigno database.'),
        'error_deny_user_create_meet_moxtra_servers' => $this->t('An error occurred on Moxtra server while denying the user to create meeting.'),
        'error_get_token_moxtra_servers' => $this->t('Error while getting the access token from Moxtra servers.'),
      ];
    }

    if (isset($error_codes[$error_code])) {
      return $error_codes[$error_code];
    }

    return $this->t('Moxtra error unknown. Please contact the administrator or check the log.');
  }

  /**
   * Returns error response array by the Opigno API error code.
   *
   * @param string $error_code
   *   Error code.
   *
   * @return array
   *   Message.
   */
  protected function getErrorResponse($error_code) {
    return [
      'opigno_error_code' => $error_code,
      'opigno_error_message' => $this->getErrorMessage($error_code),
    ];
  }

  /**
   * Helper function to send a POST request with JSON data to the Opigno API.
   *
   * @param string $url
   *   Request URL.
   * @param array $request_data
   *   Request data.
   *
   * @return array
   *   Response data.
   */
  protected function postJson($url, array $request_data) {
    $data = [];

    try {
      $response = $this->httpClient->post($url, [
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
    }

    $error_code = isset($data['error_code'])
      ? $data['error_code']
      : NULL;

    if ($data['http_code'] !== 200 || $error_code !== NULL) {
      $data = $this->getErrorResponse($error_code);
      $this->logger->error($data['opigno_error_message']);
      $this->messenger->addError($data['opigno_error_message']);
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function createOrganization() {
    global $base_url;

    $users = array_filter(User::loadMultiple(), function ($user) {
      /** @var \Drupal\user\Entity\User $user */
      return $user->id() !== 0
        && $user->isActive()
        && $user->hasRole(OPIGNO_MOXTRA_COLLABORATIVE_FEATURES_RID);
    });

    $site_config = $this->configFactory->get('system.site');
    $site_name = $site_config->get('name');
    $site_mail = $site_config->get('mail');

    $users_formatted = array_map(function ($user) {
      /** @var \Drupal\user\Entity\User $user */
      return [
        'uid' => $user->id(),
        'name' => $user->getDisplayName(),
        'timezone' => $user->getTimeZone(),
      ];
    }, $users);

    $data = [
      'site_name' => $site_name,
      'base_url' => $base_url,
      'email' => $site_mail,
      'users' => $users_formatted,
    ];

    $response = $this->postJson(static::OPIGNO_API_CREATE_ORG, $data);
    if (!empty($response['org_id']) && !empty($response['client_id'])) {
      $this->configFactory
        ->getEditable('opigno_moxtra.settings')
        ->set('status', TRUE)
        ->set('org_id', $response['org_id'])
        ->set('client_id', $response['client_id'])
        ->save();
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrganizationInfo() {
    $config = $this->configFactory->get('opigno_moxtra.settings');
    $org_id = $config->get('org_id');
    if (empty($org_id)) {
      return $this->getErrorResponse(static::ERROR_CODE_ORG_NOT_CREATED);
    }

    $data = ['org_id' => $org_id];
    return $this->postJson(static::OPIGNO_API_GET_ORG_INFO, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function getMaxTotalUsers() {
    $info = $this->getOrganizationInfo();
    return $info['max_total_users'];
  }

  /**
   * {@inheritdoc}
   */
  public function createUsers($users) {
    $config = $this->configFactory->get('opigno_moxtra.settings');
    $org_id = $config->get('org_id');
    if (empty($org_id)) {
      return $this->getErrorResponse(static::ERROR_CODE_ORG_NOT_CREATED);
    }

    $user_data = array_map(function ($user) {
      /** @var \Drupal\user\UserInterface $user */
      return [
        'uid' => $user->id(),
        'name' => $user->getDisplayName(),
        'timezone' => $user->getTimeZone(),
      ];
    }, $users);

    $data = [
      'org_id' => $org_id,
      'users' => $user_data,
    ];

    return $this->postJson(static::OPIGNO_API_CREATE_USERS, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function updateUser($user) {
    $config = $this->configFactory->get('opigno_moxtra.settings');
    $org_id = $config->get('org_id');
    if (empty($org_id)) {
      return $this->getErrorResponse(static::ERROR_CODE_ORG_NOT_CREATED);
    }

    $user_data = [
      'uid' => $user->id(),
      'name' => $user->getDisplayName(),
      'timezone' => $user->getTimeZone(),
    ];

    $data = [
      'org_id' => $org_id,
      'user' => $user_data,
    ];

    return $this->postJson(static::OPIGNO_API_UPDATE_USER, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function enableUser($user) {
    $config = $this->configFactory->get('opigno_moxtra.settings');
    $org_id = $config->get('org_id');
    if (empty($org_id)) {
      return $this->getErrorResponse(static::ERROR_CODE_ORG_NOT_CREATED);
    }

    $user_data = [
      'uid' => $user->id(),
      'name' => $user->getDisplayName(),
      'timezone' => $user->getTimeZone(),
    ];

    $data = [
      'org_id' => $org_id,
      'user' => $user_data,
    ];

    return $this->postJson(static::OPIGNO_API_ENABLE_USER, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function disableUser($user_id) {
    $config = $this->configFactory->get('opigno_moxtra.settings');
    $org_id = $config->get('org_id');
    if (empty($org_id)) {
      return $this->getErrorResponse(static::ERROR_CODE_ORG_NOT_CREATED);
    }

    $data = [
      'org_id' => $org_id,
      'user_id' => $user_id,
    ];

    return $this->postJson(static::OPIGNO_API_DISABLE_USER, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteUser($user_id) {
    $config = $this->configFactory->get('opigno_moxtra.settings');
    $org_id = $config->get('org_id');
    if (empty($org_id)) {
      return $this->getErrorResponse(static::ERROR_CODE_ORG_NOT_CREATED);
    }

    $data = [
      'org_id' => $org_id,
      'user_id' => $user_id,
    ];

    return $this->postJson(static::OPIGNO_API_DELETE_USER, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function getToken($user_id, $use_cache = TRUE) {
    $key = __FUNCTION__ . ':' . $user_id;
    if ($use_cache) {
      $cache_entry = $this->cache->get($key);
      if ($cache_entry !== FALSE) {
        $access_token = $cache_entry->data;
        if ($access_token !== NULL) {
          return $access_token;
        }
      }
    }

    $config = $this->configFactory->get('opigno_moxtra.settings');
    $org_id = $config->get('org_id');
    if (empty($org_id)) {
      return $this->getErrorResponse(static::ERROR_CODE_ORG_NOT_CREATED);
    }

    $data = [
      'org_id' => $org_id,
      'user_id' => $user_id,
    ];

    $response = $this->postJson(static::OPIGNO_API_GET_TOKEN, $data);
    if (isset($response['opigno_error_message'])
      || !isset($response['access_token'])) {
      return FALSE;
    }

    $expire = $this->time->getRequestTime() + $response['expires_in'];
    $this->cache->set($key, $response['access_token'], $expire);

    return $response['access_token'];
  }

}
