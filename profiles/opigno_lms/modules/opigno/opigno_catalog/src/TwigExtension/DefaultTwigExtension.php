<?php

namespace Drupal\opigno_catalog\TwigExtension;

use Drupal\Core\Render\Markup;
use Drupal\group\Entity\Group;

/**
 * Class DefaultTwigExtension.
 */
class DefaultTwigExtension extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction(
        'opigno_catalog_get_style',
        [$this, 'get_row_style']
      ),
      new \Twig_SimpleFunction(
        'opigno_catalog_is_member',
        [$this, 'is_member']
      ),
      new \Twig_SimpleFunction(
        'opigno_catalog_is_started',
        [$this, 'is_started']
      ),
      new \Twig_SimpleFunction(
        'opigno_catalog_get_default_image',
        [$this, 'get_default_image']
      ),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'opigno_catalog.twig.extension';
  }

  /**
   * Gets row style.
   */
  public function get_row_style() {
    $style = \Drupal::service('opigno_catalog.get_style')->getStyle();

    return ($style == 'line') ? 'style-line' : 'style-block';
  }

  /**
   * Checks if user is a member of group.
   */
  public function is_member($group_id) {
    $group = Group::load($group_id);
    $account = \Drupal::currentUser();

    return (bool) $group->getMember($account);
  }

  /**
   * Checks if training started.
   */
  public function is_started($group_id) {
    $group = Group::load($group_id);
    $account = \Drupal::currentUser();

    return (bool) opigno_learning_path_started($group, $account);
  }

  /**
   * Returns default image.
   */
  public function get_default_image($type) {
    $request = \Drupal::request();
    $path = \Drupal::service('module_handler')
      ->getModule('opigno_catalog')
      ->getPath();
    switch ($type) {
      case 'course':
        $img = '<img src="' . $request->getBasePath() . '/' . $path . '/img/img_course.png" alt="">';
        break;

      case 'module':
        $img = '<img src="' . $request->getBasePath() . '/' . $path . '/img/img_module.png" alt="">';
        break;

      case 'learning_path':
        $img = '<img src="' . $request->getBasePath() . '/' . $path . '/img/img_training.png" alt="">';
        break;

      default:
        $img = NULL;
        break;
    }

    return Markup::create($img);
  }

}
