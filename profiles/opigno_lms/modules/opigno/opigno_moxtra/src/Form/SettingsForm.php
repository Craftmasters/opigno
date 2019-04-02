<?php

namespace Drupal\opigno_moxtra\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\opigno_moxtra\OpignoServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements the Opigno Moxtra settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Opigno service.
   *
   * @var \Drupal\opigno_moxtra\OpignoServiceInterface
   */
  protected $opignoService;

  /**
   * Constructs a SettingsForm object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TimeInterface $time,
    OpignoServiceInterface $opigno_service
  ) {
    parent::__construct($config_factory);
    $this->time = $time;
    $this->opignoService = $opigno_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('datetime.time'),
      $container->get('opigno_moxtra.opigno_api')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'opigno_moxtra_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'opigno_moxtra.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('opigno_moxtra.settings');
    $org_id = $config->get('org_id');

    $form['content'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['row'],
      ],
    ];

    $form['content']['left'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['col-4'],
      ],
    ];

    $form['content']['right'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['col-8'],
      ],
    ];

    if (empty($org_id)) {
      $form['content']['left'][] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => [
          'class' => ['amount'],
        ],
        '#value' => $this->t('12$'),
      ];
      $form['content']['left'][] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => [
          'class' => ['unit'],
        ],
        '#value' => $this->t('/user /month'),
      ];

      $form['content']['left'][] = [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#items' => [
          $this->t('File/Document Sharing and Viewing'),
          $this->t('Multiparty Messaging'),
          $this->t('Share Screen from any device'),
          $this->t('Whiteboard with all Meeting Members'),
          $this->t('Ability to Record and Share Meetings'),
          $this->t('Ability to save annotated files'),
        ],
      ];

      $form['content']['left'][] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => [
          'class' => ['trial'],
        ],
        '#value' => $this->t('30 days free trial'),
      ];

      $submit = [
        '#type' => 'submit',
        '#name' => 'create_organization',
        '#attributes' => [
          'class' => ['try'],
        ],
        '#value' => $this->t('Start trial'),
        '#button_type' => 'primary',
      ];

      $form['content']['left']['submit'] = $submit;

      $form['content']['right'][] = [
        '#type' => 'html_tag',
        '#attributes' => [
          'class' => ['trial-title'],
        ],
        '#tag' => 'h2',
        '#value' => $this->t('Enhance the interactions between your learners'),
      ];

      $form['content']['right'][] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['row'],
        ],
        [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['col-8'],
          ],
          [
            '#markup' => $this->t('Opigno offers a great collaborative workspace feature to facilitate collaboration between your learners and teachers. This feature enables multi-partite chat with multi-layered document and content interactions. Improve your participation / engagement rate on our platform thanks the tools of our collaborative solution : chat, document sharing, annotations, to-do lists, live meetings.'),
          ],
        ],
        [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['col-4'],
          ],
          'submit' => $submit,
        ],
      ];
    }
    else {
      $form['content']['right']['title'] = [
        '#type' => 'html_tag',
        '#attributes' => [
          'class' => ['title'],
        ],
        '#tag' => 'h2',
        '#value' => $this->t('Informations'),
      ];

      $info = $this->opignoService->getOrganizationInfo();

      // Update configuration.
      if (empty($info['opigno_error_message'])) {
        $this->configFactory
          ->getEditable('opigno_moxtra.settings')
          ->set('status', (int) $info['active'] === 1)
          ->save();
      }

      // Print organization info.
      $active = $config->get('status')
        ? $this->t('Active')
        : $this->t('Inactive');

      $trial = (int) $info['max_total_users'] === 0;
      if ($trial) {
        $type = $this->t('Trial');
        $quota = $this->t('There is no user quota during trial');
      }
      else {
        $type = $this->t('Paid');
        $quota = $this->t('@curr/@max user(s).', [
          '@curr' => $info['current_total_users'],
          '@max' => $info['max_total_users'],
        ]);
      }

      $date_diff = $info['valid_until'] - $this->time->getRequestTime();
      $date_diff_days = round($date_diff / (60 * 60 * 24));
      if ($date_diff_days < 0) {
        $contact_text = $trial
          ? $this->t('Because the trial has expired users might be out of sync, please contact us after renewing the plan')
          : $this->t('Because your plan has expired users might be out of sync, please contact us after renewing the plan');
        $contact_uri = 'https://www.opigno.org/en#contact';
        $contact_url = Url::fromUri($contact_uri);
        $contact_link = Link::fromTextAndUrl($contact_text, $contact_url)
          ->toRenderable();
        $form['content']['right']['link'] = $contact_link;
        $form['content']['right']['link']['#attributes']['class'][] = 'contact-link';
        $form['content']['right']['link']['#attributes']['target'] = '_blank';

        $validity_text = $trial
          ? $this->t('<span class="days">0</span><br />Your trial period has ended')
          : $this->t('<span class="days">0</span><br />Your plan has expired');
      }
      else {
        $args = [
          '@days' => $date_diff_days,
        ];
        $validity_text = $trial
          ? $this->t('<span class="days">@days</span> days before the end of the trial', $args)
          : $this->t('<span class="days">@days</span> days remaining in your plan', $args);
      }

      $form['content']['left']['text'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['timeout'],
        ],
        'text' => [
          '#markup' => $validity_text,
        ],
      ];

      $renew_title = $this->t('Buy');
      $renew_uri = 'https://opigno.org/opigno-moxtra/commerce-moxtra/renew/'
        . $org_id;
      $renew_url = Url::fromUri($renew_uri);

      $want_more_url = clone $renew_url;
      $want_more = Link::fromTextAndUrl($this->t('Want more?'), $want_more_url)
        ->toRenderable();
      $want_more['#attributes']['class'][] = 'want-more';

      $form['content']['right']['info'] = [
        '#type' => 'table',
        '#attributes' => [
          'class' => 'col-8',
        ],
        '#rows' => [
          [
            [
              'class' => ['label'],
              'data' => [
                '#markup' => $this->t('Your organization ID on Opigno.org is:'),
              ],
            ],
            $org_id,
          ],
          [
            [
              'class' => ['label'],
              'data' => [
                '#markup' => $this->t('Your organization is:'),
              ],
            ],
            $active,
          ],
          [
            [
              'class' => ['label'],
              'data' => [
                '#markup' => $this->t('Your organization type is:'),
              ],
            ],
            $type,
          ],
          [
            [
              'class' => ['label'],
              'data' => [
                '#markup' => $this->t('Your organization contact is:'),
              ],
            ],
            $info['email'],
          ],
          [
            [
              'class' => ['label'],
              'data' => [
                '#markup' => $this->t('Your instance user quota:'),
              ],
            ],
            [
              'data' => [
                '#type' => 'container',
                [
                  '#type' => 'html_tag',
                  '#tag' => 'span',
                  '#attributes' => [
                    'class' => 'quota',
                  ],
                  '#value' => $quota,
                ],
                $want_more,
              ],
            ],
          ],
        ],
      ];

      $renew = Link::fromTextAndUrl($renew_title, $renew_url)
        ->toRenderable();
      $renew['#attributes']['class'][] = 'renew';
      $renew['#attributes']['target'] = '_blank';
      $form['content']['left']['renew'] = $renew;
    }

    $form['#attached']['library'][] = 'opigno_moxtra/settings_form';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    if (isset($element['#name'])
      && $element['#name'] === 'create_organization') {
      $response = $this->opignoService->createOrganization();
      if (isset($response['opigno_error_message'])) {
        $this->messenger()->addMessage($this->t('An error occurred while creating the organization. Try again, check the logs or contact an opigno.org administrator.'));
      }
      else {
        $this->messenger()->addMessage($this->t('Organization created successfully. You can now use the Moxtra.'));
      }
    }
  }

}
