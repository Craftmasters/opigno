<?php

namespace Drupal\private_message\Service;

use Drupal\private_message\Entity\PrivateMessageInterface;
use Drupal\private_message\Entity\PrivateMessageThreadInterface;

/**
 * Interface for the Private Message notification service.
 */
interface PrivateMessageNotifierInterface {

  /**
   * Send a private message notification email.
   *
   * @param \Drupal\private_message\Entity\PrivateMessageInterface $message
   *   The message.
   * @param \Drupal\private_message\Entity\PrivateMessageThreadInterface $thread
   *   The message thread.
   * @param \Drupal\user\UserInterface[] $members
   *   The message members.
   */
  public function notify(PrivateMessageInterface $message, PrivateMessageThreadInterface $thread, array $members = []);

}
