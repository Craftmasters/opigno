<?php

namespace Drupal\opigno_learning_path\TwigExtension;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\group\Entity\Group;
use Drupal\opigno_learning_path\Controller\LearningPathController;
use Drupal\opigno_learning_path\LearningPathAccess;

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
        'is_group_member',
        [$this, 'is_group_member']
      ),
      new \Twig_SimpleFunction(
        'get_join_group_link',
        [$this, 'get_join_group_link']
      ),
      new \Twig_SimpleFunction(
        'get_start_link',
        [$this, 'get_start_link']
      ),
      new \Twig_SimpleFunction(
        'get_progress',
        [$this, 'get_progress']
      ),
      new \Twig_SimpleFunction(
        'get_training_content',
        [$this, 'get_training_content']
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
    return 'opigno_learning_path.twig.extension';
  }

  /**
   * Tests if user is member of a group.
   *
   * @param mixed $group
   *   Group.
   * @param mixed $account
   *   User account.
   *
   * @return bool
   *   Member flag.
   */
  public function is_group_member($group = NULL, $account = NULL) {
    if (!$group) {
      $group = \Drupal::routeMatch()->getParameter('group');
    }

    if (empty($group)) {
      return FALSE;
    }

    if (!$account) {
      $account = \Drupal::currentUser();
    }

    return $group->getMember($account) !== FALSE;
  }

  /**
   * Returns join group link.
   *
   * @param mixed $group
   *   Group.
   * @param mixed $account
   *   User account.
   * @param array $attributes
   *   Attributes.
   *
   * @return mixed|null|string
   *   Join group link or empty.
   */
  public function get_join_group_link($group = NULL, $account = NULL, array $attributes = []) {
    $route = \Drupal::routeMatch();

    if (!isset($group)) {
      $group = $route->getParameter('group');
    }

    if (!isset($account)) {
      $account = \Drupal::currentUser();
    }

    $route_name = $route->getRouteName();
    $access = isset($group) && $group->access('view', $account) && $group->hasPermission('join group', $account);
    if ($route_name == 'entity.group.canonical' && $access) {
      $link = NULL;
      $visibility = $group->field_learning_path_visibility->value;
      $validation = $group->field_requires_validation->value;
      $is_member = $group->getMember($account) !== FALSE;
      $is_anonymous = $account->id() === 0;
      $module_commerce_enabled = \Drupal::moduleHandler()->moduleExists('opigno_commerce');

      // If training is paid.
      if ($module_commerce_enabled
        && $group->hasField('field_lp_price')
        && $group->get('field_lp_price')->value != 0
        && !$is_member) {

        return '';
      }

      if ($visibility == 'semiprivate' && $validation) {
        $joinLabel = t('Request group membership');
      }
      else {
        $joinLabel = t('Subscribe to training');
      }

      if ($is_anonymous) {
        if ($visibility === 'public') {
          $link = [
            'title' => t('Start'),
            'route' => 'opigno_learning_path.steps.start',
            'args' => ['group' => $group->id()],
          ];
          $attributes['class'][] = 'use-ajax';
        }
        else {
          $url = Url::fromRoute('entity.group.canonical', ['group' => $group->id()]);
          $link = [
            'title' => $joinLabel,
            'route' => 'user.login',
            'args' => ['destination' => render($url)->toString()],
          ];
        }
      }
      elseif (!$is_member) {
        $link = [
          'title' => $joinLabel,
          'route' => 'entity.group.join',
          'args' => ['group' => $group->id()],
        ];
      }

      if ($link) {
        $url = Url::fromRoute($link['route'], $link['args'], ['attributes' => $attributes]);
        $l = Link::fromTextAndUrl($link['title'], $url)->toRenderable();

        return render($l);
      }
    }

    return '';
  }

  /**
   * Returns group start link.
   *
   * @param mixed $group
   *   Group.
   * @param array $attributes
   *   Attributes.
   *
   * @return array|mixed|null
   *   Group start link or empty.
   */
  public function get_start_link($group = NULL, array $attributes = []) {
    $user = \Drupal::currentUser();
    if ($user->isAnonymous()) {
      return [];
    }

    if (!$group) {
      $group = \Drupal::routeMatch()->getParameter('group');
    }

    if (filter_var($group, FILTER_VALIDATE_INT) !== FALSE) {
      $group = Group::load($group);
    }

    if (empty($group)) {
      return '';
    }

    $current_route = \Drupal::routeMatch()->getRouteName();
    $visibility = $group->field_learning_path_visibility->value;
    $validation = $group->field_requires_validation->value;
    $account = \Drupal::currentUser();
    $is_anonymous = $account->id() === 0;
    $member_pending = $visibility === 'semiprivate' && $validation
      && !LearningPathAccess::statusGroupValidation($group, $account);
    $module_commerce_enabled = \Drupal::moduleHandler()->moduleExists('opigno_commerce');
    $required_trainings = LearningPathAccess::hasUncompletedRequiredTrainings($group, $account);

    if (
      $module_commerce_enabled
      && $group->hasField('field_lp_price')
      && $group->get('field_lp_price')->value != 0
      && !$group->getMember($account)) {
      // Get currency code.
      $cs = \Drupal::service('commerce_store.current_store');
      $store_default = $cs->getStore();
      $default_currency = $store_default ? $store_default->getDefaultCurrencyCode() : '';

      $text = t('Add to cart') . ' / ' . $group->get('field_lp_price')->value . ' ' . $default_currency;
      $route = 'opigno_commerce.subscribe_with_payment';
    }
    elseif ($visibility === 'public' && $is_anonymous) {
      $text = t('Start');
      $route = 'opigno_learning_path.steps.start';
      $attributes['class'][] = 'use-ajax';
      $attributes['class'][] = 'start-link';
    }
    elseif (!$group->getMember($account)) {
      if ($group->hasPermission('join group', $account)) {
        $text = ($current_route == 'entity.group.canonical') ? t('Subscribe to training') : t('Learn more');
        $route = ($current_route == 'entity.group.canonical') ? 'entity.group.join' : 'entity.group.canonical';
        if ($current_route == 'entity.group.canonical') {
          $attributes['class'][] = 'join-link';
        }
      }
      else {
        return '';
      }
    }
    elseif ($member_pending || $required_trainings) {
      $text = $required_trainings ? t('Prerequisites Pending') : t('Approval Pending');
      $route = 'entity.group.canonical';
      $attributes['class'][] = 'approval-pending-link';
    }
    else {
      $text = opigno_learning_path_started($group, $account) ? t('Continue training') : t('Start');
      $route = 'opigno_learning_path.steps.start';
      $attributes['class'][] = 'use-ajax';

      if (opigno_learning_path_started($group, $account)) {
        $attributes['class'][] = 'continue-link';
      }
      else {
        $attributes['class'][] = 'start-link';
      }
    }

    $args = ['group' => $group->id()];
    $url = Url::fromRoute($route, $args, ['attributes' => $attributes]);
    $l = Link::fromTextAndUrl($text, $url)->toRenderable();

    return render($l);
  }

  /**
   * Returns current user progress.
   *
   * @return array|mixed|null
   *   Current user progress.
   */
  public function get_progress() {
    $user = \Drupal::currentUser();
    if ($user->isAnonymous()) {
      return [];
    }
    $controller = new LearningPathController();
    $content = $controller->progress();
    return render($content);
  }

  /**
   * Returns training content.
   *
   * @return mixed|null
   *   Training content.
   */
  public function get_training_content() {
    $controller = new LearningPathController();
    $content = $controller->trainingContent();
    return render($content);
  }

}
