<?php

namespace Drupal\opigno_learning_path\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\group\Entity\Group;
use Drupal\opigno_module\Entity\UserModuleStatus;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LearningPathAdminSettingsForm.
 */
class DeleteAchievementsForm extends ConfirmFormBase {

  /**
   * Group.
   *
   * @var \Drupal\group\Entity\Group
   */
  protected $group;

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * DeleteAchievementsForm constructor.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('database'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'opigno_learning_path_delete_achievements';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $group = NULL) {
    $form = parent::buildForm($form, $form_state);

    if (isset($group)) {
      $this->group = $group;
      $form['group'] = [
        '#type' => 'hidden',
        '#value' => $group->id(),
      ];
    }

    $form['actions']['submit']['#name'] = 'submit';

    $form['actions']['cancel']['#type'] = 'submit';
    $form['actions']['cancel']['#name'] = 'cancel';
    $form['actions']['cancel']['#value'] = $this->getCancelText();

    $is_ajax = $this->getRequest()->isXmlHttpRequest();
    if ($is_ajax) {
      $form['actions']['cancel']['#ajax'] = [
        'callback' => [$this, 'closeModal'],
        'event' => 'click',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('The contents of that training has changed. If you start again that training, your previous achievements for it will be deleted. Do you want to continue?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    if (isset($this->group)) {
      return $this->group->toUrl();
    }
    else {
      return Url::fromRoute('<front>');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Yes');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('No');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    if (isset($element['#name'])) {
      if ($element['#name'] === 'submit') {
        $uid = $this->currentUser()->id();
        $gid = $form_state->getValue('group');
        if (isset($gid)) {
          $this->database
            ->delete('opigno_learning_path_step_achievements')
            ->condition('uid', $uid)
            ->condition('gid', $gid)
            ->execute();

          $this->database
            ->delete('opigno_learning_path_achievements')
            ->condition('uid', $uid)
            ->condition('gid', $gid)
            ->execute();

          $group = Group::load($gid);
          if (isset($group)) {
            $modules = $group->getContentEntities('opigno_module_group');
            $module = reset($modules);
            if (isset($module)) {
              // Create new unfinished user module attempt on the first module
              // of training to disable the training resume.
              $attempt = UserModuleStatus::create([]);
              $attempt->setModule($module);
              $attempt->setFinished(0);
              $attempt->save();
            }
          }

          $form_state->setRedirect('opigno_learning_path.steps.start', [
            'group' => $gid,
          ]);
        }
      }
      elseif ($element['#name'] === 'cancel') {
        $form_state->setRedirectUrl($this->getCancelUrl());
      }
    }
  }

  /**
   * Returns ajax response.
   */
  public function closeModal(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    return $response;
  }

}
