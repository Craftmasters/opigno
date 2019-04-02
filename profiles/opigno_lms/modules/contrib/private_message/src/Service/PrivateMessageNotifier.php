<?php

namespace Drupal\private_message\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\message_notify\MessageNotifier;
use Drupal\private_message\Entity\PrivateMessageInterface;
use Drupal\private_message\Entity\PrivateMessageThreadInterface;
use Drupal\user\UserDataInterface;

/**
 * A service class for sending notifications of private messages.
 */
class PrivateMessageNotifier implements PrivateMessageNotifierInterface {

  /**
   * The private message service.
   *
   * @var \Drupal\private_message\Service\PrivateMessageServiceInterface
   */
  protected $privateMessageService;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * The configuration factory service.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The message manager.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $messageManager;

  /**
   * The message notification service.
   *
   * @var \Drupal\message_notify\MessageNotifier
   */
  protected $messageNotifier;

  /**
   * Constructs a new PrivateMessageNotifier object.
   *
   * @param \Drupal\private_message\Service\PrivateMessageServiceInterface $privateMessageService
   *   The private message service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\user\UserDataInterface $userData
   *   The user data service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\message_notify\MessageNotifier $messageNotifier
   *   The message notification service.
   */
  public function __construct(
    PrivateMessageServiceInterface $privateMessageService,
    AccountProxyInterface $currentUser,
    UserDataInterface $userData,
    ConfigFactoryInterface $configFactory,
    EntityTypeManagerInterface $entityTypeManager,
    MessageNotifier $messageNotifier
  ) {
    $this->privateMessageService = $privateMessageService;
    $this->currentUser = $currentUser;
    $this->userData = $userData;
    $this->config = $configFactory->get('private_message.settings');
    $this->messageManager = $entityTypeManager->getStorage('message');
    $this->messageNotifier = $messageNotifier;
  }

  /**
   * {@inheritdoc}
   */
  public function notify(PrivateMessageInterface $message, PrivateMessageThreadInterface $thread, array $members = []) {
    $params = [
      'private_message' => $message,
      'private_message_thread' => $thread,
    ];

    foreach ($members as $member) {
      if ($member->id() != $this->currentUser->id()) {
        $params['member'] = $member;

        // Check if the notification should be sent.
        if ($this->shouldSend($member, $message, $thread)) {
          // Send the notification.
          // This is done through integration with the Message module, by
          // creating a new Message entity.
          $notification = $this->messageManager->create([
            'template' => 'private_message_notification',
            'uid' => $member->id(),
          ]);
          $notification->set('field_message_private_message', $message);
          $notification->set('field_message_pm_thread', $thread);
          $notification->save();

          $this->messageNotifier->send($notification);
        }
      }
    }
  }

  /**
   * Determines if the message should be sent.
   *
   * Checks individual user preferences as well as system defaults.
   *
   * @param \Drupal\Core\Session\AccountInterface $recipient
   *   The potential recipient.
   * @param \Drupal\private_message\Entity\PrivateMessageInterface $message
   *   The private message for which a notification is being sent.
   * @param \Drupal\private_message\Entity\PrivateMessageThreadInterface $thread
   *   The private message thread.
   *
   * @return bool
   *   A boolean indicating whether or not the message should be sent.
   */
  private function shouldSend(AccountInterface $recipient, PrivateMessageInterface $message, PrivateMessageThreadInterface $thread) {

    // Don't notify the user by default.
    $notify = FALSE;

    // Check if notifications have been enabled.
    if ($this->config->get('enable_notifications')) {

      // Eligibility to receive notifications will be checked.
      $eligible_to_receive = FALSE;

      // Get the user default.
      $user_default = $this->userData->get('private_message', $recipient->id(), 'receive_notification');
      // Check if the user default is to notify.
      if ($user_default) {
        $eligible_to_receive = TRUE;
      }
      // Check if the user has not made any selection, and the system default is
      // to send:
      elseif (is_null($user_default) && $this->config->get('notify_by_default')) {
        $eligible_to_receive = TRUE;
      }

      // If the user is elegible to receive notfication, user and system
      // settings are  used to determine whether or not the notification should
      // be sent.
      if ($eligible_to_receive) {

        // Determine whether a user should always be notified of every message,
        // or if they should only be notified when they aren't viewing a thread.
        $notify_when_using = $this->userData->get('private_message', $recipient->id(), 'notify_when_using');
        // Check if the user has not yet set a value.
        if (is_null($notify_when_using)) {
          // The user has not yet set a value, so use the system default.
          $notify_when_using = $this->config->get('notify_when_using');
        }

        // Get the number of seconds a user has set in their profile, after
        // which they should be considered 'away' from the thread.
        $away_time = $this->userData->get('private_message', $recipient->id(), 'number_of_seconds_considered_away');
        // Check if the user has not yet set a value.
        if (is_null($away_time)) {
          // The user has not yet set a value, so use the system default.
          $away_time = $this->config->get('number_of_seconds_considered_away');
        }

        // Check if users should always be notified.
        if ($notify_when_using == 'yes') {
          $notify = TRUE;
        }
        // Check if users have been away for long enough to be considered away:
        elseif (($message->getCreatedTime() - $thread->getLastAccessTimestamp($recipient)) > $away_time) {
          $notify = TRUE;
        }
      }
    }

    return $notify;
  }

}
