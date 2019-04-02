<?php

namespace Drupal\private_message\Plugin\Field\FieldFormatter;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the private message member field formatter.
 *
 * @FieldFormatter(
 *   id = "private_message_thread_member_formatter",
 *   label = @Translation("Private Message Thread Members"),
 *   field_types = {
 *     "entity_reference"
 *   },
 * )
 */
class PrivateMessageThreadMemberFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Construct a PrivateMessageThreadFormatter object.
   *
   * @param string $plugin_id
   *   The ID of the plugin.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $settings
   *   The field settings.
   * @param mixed $label
   *   The label of the field.
   * @param string $view_mode
   *   The current view mode.
   * @param array $third_party_settings
   *   The third party settings.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager service.
   * @param |Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    EntityManagerInterface $entityManager,
    AccountProxyInterface $currentUser
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->entityManager = $entityManager;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity.manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return ($field_definition->getFieldStorageDefinition()->getTargetEntityTypeId() == 'private_message_thread' && $field_definition->getFieldStorageDefinition()->getSetting('target_type') == 'user');
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    if ($this->getSetting('display_type') == 'label') {
      $format = $this->t('Displays members using their username, linked to the user account if the viewer has permission to access user profiles');
    }
    elseif ($this->getSetting('display_type') == 'entity') {
      $format = $this->t('Displays members using the %display_mode display mode of the user entity', ['%display_mode' => $this->getSetting('entity_display_mode')]);
    }

    $summary['format'] = $format;

    $members_prefix = $this->getSetting('members_prefix');
    if (empty($members_prefix)) {
      $summary['field_prefix'] = $this->t('The members list is shown without a prefix');
    }
    else {
      $summary['field_prefix'] = $this->t('The members list is prefixed with the text: %members_prefix.', ['%members_prefix' => $members_prefix]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'display_type' => 'label',
      'entity_display_mode' => 'private_message_author',
      'members_prefix' => t('You and'),
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['display_type'] = [
      '#title' => $this->t('Display Type'),
      '#type' => 'select',
      '#options' => [
        'label' => $this->t('Label'),
        'entity' => $this->t('Entity'),
      ],
      '#default_value' => $this->getSetting('display_type'),
      '#ajax' => [
        'wrapper' => 'private_message_thread_member_formatter_settings_wrapper',
        'callback' => [$this, 'ajaxCallback'],
      ],
    ];

    $element['entity_display_mode'] = [
      '#prefix' => '<div id="private_message_thread_member_formatter_settings_wrapper">',
      '#suffix' => '</div>',
    ];

    foreach ($this->entityManager->getViewModes('user') as $display_mode_id => $display_mode) {
      $options[$display_mode_id] = $display_mode['label'];
    }

    $setting_key = 'display_type';
    if ($value = $form_state->getValue(
      [
        'fields',
        $this->getFieldName(),
        'settings_edit_form',
        'settings',
        $setting_key,
      ])
    ) {
      $display_type = $value;
    }
    else {
      $display_type = $this->getSetting('display_type');
    }

    if ($display_type == 'entity') {
      $element['entity_display_mode']['#type'] = 'select';
      $element['entity_display_mode']['#title'] = $this->t('View mode');
      $element['entity_display_mode']['#options'] = $options;
      $element['entity_display_mode']['#default_value'] = $this->getSetting('entity_display_mode');
    }
    else {
      $element['entity_display_mode']['#markup'] = '';
    }

    $element['members_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field prefix'),
      '#default_value' => $this->getSetting('members_prefix'),
    ];

    return $element;
  }

  /**
   * Ajax callback for settings form.
   */
  public function ajaxCallback(array $form, FormStateInterface $form_state) {
    return $form['fields'][$this->getFieldName()]['plugin']['settings_edit_form']['settings']['entity_display_mode'];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    $access_profiles = $this->currentUser->hasPermission('access user profiles');
    $users = [];

    $view_builder = $this->entityManager->getViewBuilder('user');
    foreach ($items as $delta => $item) {
      $user = $item->entity;
      if ($user) {
        if ($user->id() != $this->currentUser->id()) {
          if ($this->getSetting('display_type') == 'label') {
            if ($access_profiles) {
              $url = Url::fromRoute('entity.user.canonical', ['user' => $user->id()]);
              $users[$user->id()] = new FormattableMarkup('<a href=":link">@username</a>', [':link' => $url->toString(), '@username' => $user->getDisplayName()]);
            }
            else {
              $users[$user->id()] = $user->getDisplayName();
            }
          }
          elseif ($this->getSetting('display_type') == 'entity') {
            $renderable = $view_builder->view($user, $this->getSetting('entity_display_mode'));
            $users[$user->id()] = render($renderable);
          }
        }
      }
      else {
        $users['Missing-' . $delta] = '<em>' . $this->t('User Deleted') . '</em>';
      }
    }

    $element = [
      '#prefix' => '<div class="private-message-recipients">',
      '#suffix' => '</div>',
      '#markup' => '',
    ];

    $members_prefix = $this->getSetting('members_prefix');
    if (strlen($members_prefix)) {
      $element['#markup'] .= '<span>' . $members_prefix . ' </span>';
    }

    $separator = $this->getSetting('display_type') == 'label' ? ', ' : '';
    $element['#markup'] .= implode($separator, $users);

    return $element;
  }

  /**
   * Retrieve the name of the field.
   */
  protected function getFieldName() {
    return $this->fieldDefinition->getItemDefinition()->getFieldDefinition()->getName();
  }

}
