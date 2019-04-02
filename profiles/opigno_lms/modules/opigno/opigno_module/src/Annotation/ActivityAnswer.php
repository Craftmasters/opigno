<?php

namespace Drupal\opigno_module\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Annotation for ActivityType plugin.
 *
 * @Annotation
 */
class ActivityAnswer extends Plugin {

  /**
   * Plugin id.
   *
   * @var string
   */
  public $id;

  /**
   * Plugin field activity type bundle.
   *
   * @var string
   */
  public $activityTypeBundle;

}
