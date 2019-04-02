<?php

namespace Drupal\private_message\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use DRupal\private_message\PluginManager\PrivateMessageConfigFormManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the configuration form for the private message module.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * The private message config form plugin manager.
   *
   * @var \Drupal\private_message\PluginManager\PrivateMessageConfigFormManager
   */
  protected $privateMessageConfigFormManager;

  /**
   * Constructs a PrivateMessageForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory service.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager service.
   * @param \Drupal\private_message\PluginManager\PrivateMessageConfigFormManager $privateMessageConfigFormManager
   *   The private message config form plugin manager.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    EntityManagerInterface $entityManager,
    PrivateMessageConfigFormManager $privateMessageConfigFormManager
  ) {
    parent::__construct($configFactory, $entityManager);

    $this->privateMessageConfigFormManager = $privateMessageConfigFormManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity.manager'),
      $container->get('private_message.private_message_config_form_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'private_message_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'private_message.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $formState) {
    $config = $this->config('private_message.settings');

    $form['pm_core'] = [
      '#type' => 'details',
      '#title' => $this->t('Private message core'),
      '#open' => TRUE,
    ];

    $form['pm_core']['notifications'] = [
      '#type' => 'details',
      '#title' => $this->t('Notifications'),
      '#open' => TRUE,
    ];

    $form['pm_core']['notifications']['enable_notifications'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable notifications'),
      '#default_value' => $config->get('enable_notifications'),
    ];

    $form['pm_core']['notifications']['notify_by_default'] = [
      '#type' => 'radios',
      '#title' => $this->t('Default action'),
      '#options' => [
        $this->t('Do not send notifications (users can opt-in)'),
        $this->t('Send notifications (users can opt-out)'),
      ],
      '#default_value' => (int) $config->get('notify_by_default'),
      '#states' => [
        'visible' => [
          ':input[name="enable_notifications"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['pm_core']['notifications']['notify_when_using'] = [
      '#type' => 'radios',
      '#title' => $this->t('Send notifications of new messages in a thread'),
      '#options' => [
        'yes' => $this->t('For every private message'),
        'no' => $this->t('Only when the user is not viewing the thread'),
      ],
      '#default_value' => $config->get('notify_when_using'),
      '#description' => $this->t("Whether or not notifications should be sent when the user is viewing a given thread. Users will be able to override this value on their profile settings page."),
      '#states' => [
        'visible' => [
          ':input[name="enable_notifications"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['pm_core']['notifications']['number_of_seconds_considered_away'] = [
      '#type' => 'number',
      '#title' => $this->t('The number of seconds after which a user should be considered as not viewing a thread'),
      '#default_value' => $config->get('number_of_seconds_considered_away'),
      '#description' => $this->t('When users have a private message thread open, calls to the server update the last time they have accessed the thread. This setting determines how many seconds after they have closed the thread, they should be considred as not accessing the thread anymore. Users will be able to override this value on their profile settings page.'),
      '#states' => [
        'visible' => [
          ':input[name="enable_notifications"]' => ['checked' => TRUE],
          ':input[name="notify_when_using"]' => ['value' => 'no'],
        ],
      ],
    ];

    $form['pm_core']['hide_form_filter_tips'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide filter tips'),
      '#description' => $this->t('If this box is checked, the text formats description on the private message form will be removed'),
      '#default_value' => (int) $config->get('hide_form_filter_tips'),
    ];

    $form['pm_core']['hide_recipient_field_when_prefilled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide recipient field when recipient is in the URL'),
      '#description' => $this->t('Links can be created to the private message page, passing the recipient in the URL. If this box is checked, the recipient field will be hidden when the recipient is passed in the URL.'),
      '#default_value' => (int) $config->get('hide_recipient_field_when_prefilled'),
    ];

    $definitions = $this->privateMessageConfigFormManager->getDefinitions();
    foreach ($definitions as $definition) {
      $instance = $this->privateMessageConfigFormManager->createInstance($definition['id']);
      $form[$instance->getId()] = [
        '#type' => 'details',
        '#title' => $instance->getName(),
        '#tree' => TRUE,
        '#open' => TRUE,
      ];
      foreach ($instance->buildForm($formState) as $key => $element) {
        $form[$instance->getId()][$key] = $element;
      }
    }

    return parent::buildForm($form, $formState);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $formState) {
    $definitions = $this->privateMessageConfigFormManager->getDefinitions();
    foreach ($definitions as $definition) {
      $instance = $this->privateMessageConfigFormManager->createInstance($definition['id']);
      $instance->validateForm($form, $formState);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $formState) {

    $this->config('private_message.settings')
      ->set('enable_notifications', (bool) $formState->getValue('enable_notifications'))
      ->set('notify_by_default', (bool) $formState->getValue('notify_by_default'))
      ->set('notify_when_using', (string) $formState->getValue('notify_when_using'))
      ->set('number_of_seconds_considered_away', (int) $formState->getValue('number_of_seconds_considered_away'))
      ->set('hide_form_filter_tips', (bool) $formState->getValue('hide_form_filter_tips'))
      ->set('hide_recipient_field_when_prefilled', (bool) $formState->getValue('hide_recipient_field_when_prefilled'))
      ->save();

    $definitions = $this->privateMessageConfigFormManager->getDefinitions();
    foreach ($definitions as $definition) {
      $instance = $this->privateMessageConfigFormManager->createInstance($definition['id']);
      $instance->submitForm($formState->getValue($instance->getId()));
    }

    parent::submitForm($form, $formState);
  }

}
