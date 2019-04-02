<?php

namespace Drupal\opigno_learning_path\EventSubscriber;

use Drupal\Core\Url;
use Drupal\opigno_learning_path\LearningPathContentTypesManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class LearningPathEventSubscriber.
 */
class LearningPathEventSubscriber implements EventSubscriberInterface {

  private $content_types_manager;

  /**
   * LearningPathEventSubscriber constructor.
   */
  public function __construct(LearningPathContentTypesManager $content_types_manager) {
    $this->content_types_manager = $content_types_manager;
  }

  /**
   * Returns an array of event names this subscriber wants to listen to.
   *
   * The array keys are event names and the value can be:
   *
   *  * The method name to call (priority defaults to 0)
   *  * An array composed of the method name to call and the priority
   *  * An array of arrays composed of the method names to call and respective
   *    priorities, or 0 if unset
   *
   * For instance:
   *
   *  * array('eventName' => 'methodName')
   *  * array('eventName' => array('methodName', $priority))
   *  * array('eventName' => array(array('methodName1', $priority),
   *  * array('methodName2')))
   *
   * @return array
   *   The event names to listen to.
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => ['onKernelRequest'],
      KernelEvents::RESPONSE => ['moduleRedirect'],
    ];
  }

  /**
   * Event called when a request is sent.
   */
  public function onKernelRequest(GetResponseEvent $event) {
  }

  /**
   * Redirect from canonical module path to module edit.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The route building event.
   */
  public function moduleRedirect(FilterResponseEvent $event) {
    $route = \Drupal::routeMatch();
    $route_name = $route->getRouteName();
    if ($route_name == 'entity.opigno_module.canonical') {
      // Get module from url.
      $module = $route->getParameter('opigno_module');
      // Get target path.
      $url = Url::fromRoute('opigno_module.edit', [
        'opigno_module' => $module->id(),
      ])->toString();
      // Set redirect response.
      $response = new RedirectResponse($url);
      $event->setResponse($response);
    };
  }

}
