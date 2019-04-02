<?php

namespace Drupal\opigno_learning_path\EventSubscriber;

use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class RedirectOnAccessDeniedSubscriber.
 */
class RedirectOnAccessDeniedSubscriber implements EventSubscriberInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * Constructs a new ResponseSubscriber instance.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(AccountInterface $current_user) {
    $this->user = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user')
    );
  }

  /**
   * Redirect if 403 and node an event.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The route building event.
   */
  public function redirectOn403(FilterResponseEvent $event) {
    $route_name = \Drupal::routeMatch()->getRouteName();
    $status_code = $event->getResponse()->getStatusCode();
    $is_anonymous = $this->user->isAnonymous();

    // Do not redirect if there is REST request.
    if (strpos($route_name, 'rest.') !== FALSE) {
      return;
    }
    // Do not redirect if there is a token authorization.
    $auth_header = $event->getRequest()->headers->get('Authorization');
    if ($is_anonymous && preg_match('/^Bearer (.*)/', $auth_header)) {
      return;
    }

    if ($is_anonymous && $status_code == 403) {
      $current_path = \Drupal::service('path.current')->getPath();
      $response = new RedirectResponse("/user/login/?prev_path={$current_path}");

      $event->setResponse($response);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['redirectOn403'];
    return $events;
  }

}
