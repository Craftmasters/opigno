<?php

namespace Drupal\opigno_module;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Class ActivityAnswerManager.
 */
class ActivityAnswerManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/ActivityAnswer',
      $namespaces,
      $module_handler,
      'Drupal\opigno_module\ActivityAnswerPluginInterface',
      'Drupal\opigno_module\Annotation\ActivityAnswer'
    );
    $this->alterInfo('activity_answer_info');
    $this->setCacheBackend($cache_backend, 'activity_answer');
    $this->factory = new DefaultFactory($this->getDiscovery());
  }

}
