<?php

namespace Drupal\opigno_messaging\TwigExtension;

/**
 * Class DefaultTwigExtension.
 */
class DefaultTwigExtension extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getTokenParsers() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getNodeVisitors() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getTests() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction(
        'get_unread_thread_count',
        [$this, 'get_unread_thread_count']
      ),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getOperators() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'opigno_messaging.twig.extension';
  }

  /**
   * Returns unread thread count.
   */
  public function get_unread_thread_count() {
    return \Drupal::service('private_message.service')->getUnreadThreadCount();
  }

}
