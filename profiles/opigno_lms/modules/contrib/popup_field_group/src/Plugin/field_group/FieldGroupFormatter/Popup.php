<?php

namespace Drupal\popup_field_group\Plugin\field_group\FieldGroupFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\field_group\FieldGroupFormatterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Plugin implementation of the 'popup' formatter.
 *
 * @FieldGroupFormatter(
 *   id = "popup",
 *   label = @Translation("Popup"),
 *   description = @Translation("This fieldgroup renders the inner content in a simple (position-fixed) popup."),
 *   supported_contexts = {
 *     "form",
 *     "view",
 *   }
 * )
 */
class Popup extends FieldGroupFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Current popup identifier.
   *
   * @var string
   */
  protected $elementId;

  /**
   * CSS class which should be available to open popup.
   *
   * @var string
   */
  protected $openPopupCssClass = 'popup-field-group-open-popup';

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a Popup object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param $group
   *   The group object.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label.
   * @param RequestStack $request_stack
   *   The request stack.
   */
  public function __construct($plugin_id, $plugin_definition, $group, array $settings, $label, RequestStack $request_stack) {
    parent::__construct($plugin_id, $plugin_definition, $group, $settings, $label);
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['group'],
      $configuration['settings'],
      $configuration['label'],
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultContextSettings($context) {
    $defaults = [
      'popup_link' => [
        'show' => 1,
        'text' => 'Show popup',
        'classes' => '',
      ],
      'popup_labels' => [
        'title' => '',
        'close_text' => '',
      ],
      'popup_settings' => [
        'modal' => 1,
        'dialog_class' => '',
        'close_on_escape' => 1,
        'height' => 'auto',
        'min_height' => '',
        'max_height' => '',
        'width' => 'auto',
        'min_width' => '',
        'max_width' => '',
        'position_horizontal' => 'center',
        'position_vertical' => 'center',
      ],
      'extra_css' => '',
    ] + parent::defaultSettings($context);

    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {
    $form = parent::settingsForm();

    $form['popup_link'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Display popup link settings:'),
    ];
    $form['popup_link']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display open popup link.'),
      '#default_value' => $this->getSettingValue('popup_link', 'show'),
      '#attributes' => ['class' => ['popup-link-show-link']],
    ];
    $form['popup_link']['text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link text'),
      '#default_value' => $this->getSettingValue('popup_link', 'text'),
      '#states' => [
        'invisible' => [
          'input.popup-link-show-link' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['popup_link']['classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link classes'),
      '#description' => $this->t('Multiple classes may be space-separated.'),
      '#default_value' => $this->getSettingValue('popup_link', 'classes'),
      '#element_validate' => ['field_group_validate_css_class'],
      '#states' => [
        'invisible' => [
          'input.popup-link-show-link' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['popup_link']['link_suggestion'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['messages', 'messages--status']],
      '#states' => [
        'invisible' => [
          'input.popup-link-show-link' => ['checked' => TRUE],
        ],
      ],
      'message' => [
        '#markup' => $this->t(
          'To open this popup use: @link<br>WARNING: On usage in content lists (f.e.: view with nodes) "--[NUMBER]" will be added (to the "data-target") to prevent IDs duplication.',
          [
            '@link' => '<a href="#open-popup" class="' . $this->openPopupCssClass . '" data-target="' . $this->getGroupId() . '">' . $this->t('Open popup') . '</a>',
          ]
        ),
      ],
    ];

    $form['popup_labels'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Popup labels:'),
    ];
    $form['popup_labels']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $this->getSettingValue('popup_labels', 'title'),
    ];
    $form['popup_labels']['close_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Close button text'),
      '#default_value' => $this->getSettingValue('popup_labels', 'close_text'),
    ];

    $form['popup_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Popup settings:'),
    ];
    $form['popup_settings']['modal'] = [
      '#type' => 'select',
      '#title' => $this->t('Modal'),
      '#options' => [
        1 => $this->t('Yes'),
        0 => $this->t('No'),
      ],
      '#default_value' => $this->getSettingValue('popup_settings', 'modal'),
    ];
    $form['popup_settings']['dialog_class'] = [
      '#type' => 'textfield',
      '#size' => 20,
      '#title' => $this->t('Dialog holder CSS class'),
      '#default_value' => $this->getSettingValue('popup_settings', 'dialog_class'),
    ];
    $form['popup_settings']['close_on_escape'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Close dialog on press Escape keyboard button.'),
      '#default_value' => $this->getSettingValue('popup_settings', 'close_on_escape'),
    ];
    $form['popup_settings']['height'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#title' => $this->t('Height'),
      '#default_value' => $this->getSettingValue('popup_settings', 'height'),
    ];
    $form['popup_settings']['min_height'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#title' => $this->t('minHeight'),
      '#default_value' => $this->getSettingValue('popup_settings', 'min_height'),
    ];
    $form['popup_settings']['max_height'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#title' => $this->t('maxHeight'),
      '#default_value' => $this->getSettingValue('popup_settings', 'max_height'),
    ];
    $form['popup_settings']['width'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#title' => $this->t('Width'),
      '#default_value' => $this->getSettingValue('popup_settings', 'width'),
    ];
    $form['popup_settings']['min_width'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#title' => $this->t('minWidth'),
      '#default_value' => $this->getSettingValue('popup_settings', 'min_width'),
    ];
    $form['popup_settings']['max_width'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#title' => $this->t('maxWidth'),
      '#default_value' => $this->getSettingValue('popup_settings', 'max_width'),
    ];
    $form['popup_settings']['position_horizontal'] = [
      '#type' => 'textfield',
      '#size' => 15,
      '#title' => $this->t('Position horizontal'),
      '#required' => TRUE,
      '#description' => $this->t('left, center, right'),
      '#default_value' => $this->getSettingValue('popup_settings', 'position_horizontal'),
    ];
    $form['popup_settings']['position_vertical'] = [
      '#type' => 'textfield',
      '#size' => 15,
      '#title' => $this->t('Position vertical'),
      '#required' => TRUE,
      '#description' => $this->t('top, center, bottom'),
      '#default_value' => $this->getSettingValue('popup_settings', 'position_vertical'),
    ];

    if (\Drupal::moduleHandler()->moduleExists('system_stream_wrapper')) {
      $form['extra_css'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Include custom CSS files'),
        '#description' => $this->t('Each line is a path to a CSS file in any module, theme, profile or library (see <a href="http://drupal.org/project/system_stream_wrapper">System stream wrapper</a>) or indeed any <strong>local</strong> path provided by any stream wrapper.'),
        '#element_validate' => [[get_class($this), 'validateCssFiles']],
        '#default_value' => $this->getSetting('extra_css'),
      ];
    }
    else {
      $form['install_system_stream_wrappers'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['messages', 'messages--status']],
        'message' => [
          '#markup' => $this->t('Install the <a target="_blank" href="https://www.drupal.org/project/system_stream_wrapper">System stream wrapper</a> module to include custom CSS files.'),
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $summary[] = $this->t('Display open popup link: @act', [
      '@act' => $this->getSettingValue('popup_link', 'show') ? $this->t('Yes') : $this->t('No'),
    ]);
    $summary[] = $this->t('Modal popup: @act', [
      '@act' => $this->getSettingValue('popup_settings', 'modal') ? $this->t('Yes') : $this->t('No'),
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$element, $rendering_object) {
    parent::preRender($element, $rendering_object);

    $element = [
      '#weight' => isset($element['#weight']) ? $element['#weight'] : 0,
      'popup' => $this->appendPopupWrapper($element),
    ];

    if ($this->getSettingValue('popup_link', 'show')) {
      $element['link'] = $this->generateOpenPopupLink();
    }

    $element['#attached']['library'][] = 'popup_field_group/core';
    $element['#attached']['drupalSettings']['popupFieldGroup']['linkCssClass'] = $this->openPopupCssClass;
    $element['#attached']['drupalSettings']['popupFieldGroup']['popups'][$this->getGroupId()] = $this->getPopupJsSettings();

    // Add arbitrary extra CSS files from our config.
    if ($this->getSetting('extra_css')) {
      foreach ($this->expandFileNames($this->getSetting('extra_css')) as $uri) {
        $element['#attached']['css'][$uri] = ['group' => JS_THEME];
      }
    }
  }

  /**
   * Form element validation handler for extra_css element.
   */
  public static function validateCssFiles(&$element, FormStateInterface $form_state) {
    $value = trim($element['#value']);

    if ($value) {
      $paths = static::expandFileNames($value);
      $invalid_paths = [];

      foreach ($paths as $path) {
        $real_path = \Drupal::service('file_system')->realpath($path);

        if (!$real_path || !is_file($real_path)) {
          $invalid_paths[] = $path;
        }
        else {
          $wrapper = \Drupal::service('stream_wrapper_manager')->getViaUri($path);

          if ($wrapper instanceof StreamWrapperInterface) {
            // @TODO: Check stream wrapper type to prevent using Private and not Local files.
          }
          else {
            $invalid_paths[] = $path;
          }
        }
      }

      if ($invalid_paths) {
        $form_state->setError($element, t('The following paths are invalid, or do not exist on the server: @paths', ['@paths' => implode(', ', $invalid_paths)]));
      }
    }
  }

  /**
   * Return a newline-separated list of files properly split into an array.
   *
   * @param string $value
   *   Files list string.
   *
   * @return array
   *   Files list (1 item 1 file).
   */
  protected static function expandFileNames($value) {
    $value = preg_split('/(\r\n?|\n)/', trim($value));
    return array_filter(array_map('trim', $value));
  }

  /**
   * Return popup JS settings.
   *
   * @return array
   *   Popup JS settings.
   */
  protected function getPopupJsSettings() {

    $settings = [
      'modal' => (bool) $this->getSettingValue('popup_settings', 'modal'),
      'dialog' => [
        'closeOnEscape' => (bool) $this->getSettingValue('popup_settings', 'close_on_escape'),
        'position' => [
          'at' => $this->getSettingValue('popup_settings', 'position_horizontal') . ' ' . $this->getSettingValue('popup_settings', 'position_vertical'),
        ],
      ],
    ];

    if ($close_text = $this->getSettingValue('popup_labels', 'close_text')) {
      $settings['dialog']['closeText'] = $close_text;
    }
    if ($dialog_class = $this->getSettingValue('popup_settings', 'dialog_class')) {
      $settings['dialog']['dialogClass'] = $dialog_class;
    }
    if ($height = $this->getSettingValue('popup_settings', 'height')) {
      $settings['dialog']['height'] = $height;
    }
    if ($min_height = $this->getSettingValue('popup_settings', 'min_height')) {
      $settings['dialog']['minHeight'] = $min_height;
    }
    if ($max_height = $this->getSettingValue('popup_settings', 'max_height')) {
      $settings['dialog']['maxHeight'] = $max_height;
    }
    if ($width = $this->getSettingValue('popup_settings', 'width')) {
      $settings['dialog']['width'] = $width;
    }
    if ($min_width = $this->getSettingValue('popup_settings', 'min_width')) {
      $settings['dialog']['minWidth'] = $min_width;
    }
    if ($max_width = $this->getSettingValue('popup_settings', 'max_width')) {
      $settings['dialog']['maxWidth'] = $max_width;
    }

    return $settings;
  }

  /**
   * Return current group ID.
   *
   * @return string
   *   Current group ID.
   */
  protected function getGroupId() {
    if (empty($this->elementId)) {

      if ($this->getSetting('id')) {
        $this->elementId = $this->getSetting('id');
      }
      else {
        $this->elementId = 'popup_field_' . $this->group->group_name;
      }

      Html::setIsAjax(FALSE);
      $this->elementId = Html::getUniqueId($this->elementId);
    }

    return $this->elementId;
  }

  /**
   * Return nested setting value.
   *
   * @param string $setting
   *   Setting name.
   * @param string $key
   *   Data key.
   * @param mixed $default
   *   Default value (if key not exists).
   *
   * @return mixed
   *   Requested item value.
   */
  protected function getSettingValue($setting, $key, $default = NULL) {
    $storage = $this->getSetting($setting);
    return isset($storage[$key]) ? $storage[$key] : $default;
  }

  /**
   * Wrap popup element.
   *
   * @param array $element
   *   Element to wrap.
   *
   * @return array
   *   Wrapped popup element render array.
   */
  protected function appendPopupWrapper(array $element) {
    $element['#theme_wrappers']['container']['#attributes'] = new Attribute([
      'id' => $this->getGroupId(),
      'title' => $this->getSettingValue('popup_labels', 'title'),
      'style' => 'display:none;',
      'class' => array_merge(explode(
        ' ',
        $this->getSetting('classes')),
        ['popup-field-group']
      ),
    ]);

    return $element;
  }

  /**
   * Generate link for open popup.
   *
   * @return array
   *   Link for open popup render array.
   */
  protected function generateOpenPopupLink() {
    return [
      '#type' => 'link',
      '#title' => $this->getSettingValue('popup_link', 'text'),
      '#url' => Url::fromRoute('<current>', [], [
        'fragment' => 'open-popup',
        'query' => $this->requestStack->getCurrentRequest()->query->all(),
        'attributes' => [
          'data-target' => $this->getGroupId(),
          'class' => array_merge(
            [$this->openPopupCssClass],
            explode(
              ' ',
              $this->getSettingValue('popup_link', 'classes')
            )
          ),
        ],
      ]),
    ];
  }

}
