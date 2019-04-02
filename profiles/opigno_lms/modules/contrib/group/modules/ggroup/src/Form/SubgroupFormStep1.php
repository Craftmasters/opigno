<?php

namespace Drupal\ggroup\Form;

use Drupal\group\Entity\Form\GroupForm;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a creating a group without it being saved yet.
 */
class SubgroupFormStep1 extends GroupForm {

  /**
   * The private temporary store factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Constructs a SubgroupFormStep2 object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The temporary store factory.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityManagerInterface $entity_manager) {
    parent::__construct($temp_store_factory, $entity_manager);
    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions['submit'] = [
      '#type' => 'submit',
      '#value' => $form_state->get('wizard') ? $this->t('Continue to final step') : $this->t('Create subgroup'),
      '#submit' => ['::submitForm', '::saveTemporary'],
    ];

    $actions['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#submit' => ['::cancel'],
      '#limit_validation_errors' => [],
    ];

    return $actions;
  }

  /**
   * Saves a temporary group and continues to step 2 of subgroup creation.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\ggroup\Controller\SubgroupWizardController::add()
   * @see \Drupal\ggroup\Form\SubgroupFormStep2
   */
  public function saveTemporary(array &$form, FormStateInterface $form_state) {
    $storage_id = $form_state->get('storage_id');

    $store = $this->tempStoreFactory->get('ggroup_add_temp');
    $store->set("$storage_id:group", $this->entity);
    $store->set("$storage_id:step", 2);

    // Disable any URL-based redirect until the final step.
    $request = $this->getRequest();
    $form_state->setRedirectUrl(Url::fromRoute('<current>', [], ['query' => $request->query->all()]));
    $request->query->remove('destination');
  }

  /**
   * Cancels the group creation by emptying the temp store.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\ggroup\Controller\SubgroupWizardController::add()
   */
  public function cancel(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\group\Entity\GroupInterface $group */
    $group = $form_state->get('group');

    $storage_id = $form_state->get('storage_id');
    $store = $this->tempStoreFactory->get('ggroup_add_temp');
    $store->delete("$storage_id:group");

    // Redirect to the group page if no destination was set in the URL.
    $form_state->setRedirect('entity.group.canonical', ['group' => $group->id()]);
  }

}
