<?php

namespace Drupal\opigno_notification\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\opigno_notification\Entity\OpignoNotification;
use Drupal\opigno_notification\OpignoNotificationInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides the controller for OpignoNotification entity pages.
 *
 * @ingroup opigno_notification
 */
class OpignoNotificationController extends ControllerBase {

  /**
   * Ajax callback. Returns unread notifications count.
   */
  public function count() {
    $count = OpignoNotification::unreadCount();

    return new JsonResponse([
      'count' => $count,
    ]);
  }

  /**
   * Ajax callback. Marks the notification as read.
   */
  public function markRead(OpignoNotificationInterface $opigno_notification = NULL) {
    if ($opigno_notification === NULL) {
      throw new NotFoundHttpException();
    }

    $uid = \Drupal::currentUser()->id();

    if ($opigno_notification->getUser() !== $uid) {
      throw new AccessDeniedHttpException();
    }

    $opigno_notification->setHasRead(TRUE);
    $opigno_notification->save();

    return new JsonResponse([]);
  }

  /**
   * Ajax callback. Marks all current user notifications as read.
   */
  public function markReadAll() {
    $uid = \Drupal::currentUser()->id();

    $query = \Drupal::entityQuery('opigno_notification');
    $query->condition('uid', $uid);
    $query->condition('has_read', FALSE);
    $ids = $query->execute();

    /* @var OpignoNotificationInterface[] $notifications */
    $notifications = OpignoNotification::loadMultiple($ids);

    foreach ($notifications as $notification) {
      $notification->setHasRead(TRUE);
      $notification->save();
    }

    return new JsonResponse([]);
  }

}
