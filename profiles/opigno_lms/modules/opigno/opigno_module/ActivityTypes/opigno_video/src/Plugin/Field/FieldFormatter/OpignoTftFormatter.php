<?php

namespace Drupal\opigno_video\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\file\Entity\File;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;

/**
 * Plugin implementation of the 'opigno_tft_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "opigno_tft_formatter",
 *   label = @Translation("Tft formatter"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class OpignoTftFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'width' => 220,
      'height' => 220,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return parent::settingsForm($form, $form_state) + [
      'width' => [
        '#type' => 'number',
        '#title' => $this->t('Width'),
        '#default_value' => $this->getSetting('width'),
        '#size' => 5,
        '#maxlength' => 5,
        '#field_suffix' => $this->t('pixels'),
        '#min' => 0,
        '#required' => TRUE,
      ],
      'height' => [
        '#type' => 'number',
        '#title' => $this->t('Height'),
        '#default_value' => $this->getSetting('height'),
        '#size' => 5,
        '#maxlength' => 5,
        '#field_suffix' => $this->t('pixels'),
        '#min' => 0,
        '#required' => TRUE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements['#attached']['library'][] = 'core/drupal.dialog.ajax';

    foreach ($items as $delta => $item) {
      $elements[$delta] = [$this->viewValue($item)];
    }

    if (empty($items->list)) {
      $media = $items->getEntity();
      if (!empty($media) && $media->hasField('opigno_moxtra_recording_link') && !empty($link = $media->get('opigno_moxtra_recording_link')->getValue())) {
        // Moxtra recording file.
        // Get the filefield icon.
        $icon_class = file_icon_class('video/mp4');

        $elements[][] = [
          '#type' => 'link',
          '#title' => $media->getName(),
          '#url' => Url::fromUri("internal:/tft/download/file/{$media->id()}"),
          '#attributes' => [
            'class' => "file $icon_class",
            'target' => '_blank',
          ],
          '#prefix' => '<div>',
          '#suffix' => '</div>',
        ];
      }
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    $fid = $item->getValue();
    $file = File::load($fid['target_id']);
    $ext = explode('/', $file->getMimeType());
    $entity = $item->getEntity();

    $output[] = [
      '#type' => 'link',
      '#title' => $entity->label(),
      '#url' => Url::fromUri("internal:/tft/download/file/{$entity->id()}"),
      '#attributes' => [
        'target' => '_blank',
      ],
      '#prefix' => '<div>',
      '#suffix' => '</div>',
    ];

    switch ($ext[0]) {
      case 'video':
        $source_attributes = new Attribute();
        $source_attributes
          ->setAttribute('src', file_url_transform_relative(file_create_url($file->getFileUri())))
          ->setAttribute('type', $file->getMimeType());
        $source_file = [
          'file' => $file,
          'source_attributes' => $source_attributes,
        ];

        $video_attributes = new Attribute();
        $video_attributes->setAttribute('controls', 'controls')
          ->setAttribute('width', $this->getSetting('width'))
          ->setAttribute('height', $this->getSetting('height'))
          ->setAttribute('controlsList', 'nodownload');

        array_unshift($output, [
          '#theme' => 'file_video',
          '#attributes' => $video_attributes,
          '#files' => [$source_file],
        ]);
        break;

      case 'image':
        $image = [
          '#theme' => 'image_style',
          '#style_name' => 'medium',
          '#uri' => $file->getFileUri(),
          '#width' => $this->getSetting('width'),
          '#height' => $this->getSetting('height'),
        ];
        array_unshift($output, [
          '#type' => 'link',
          '#title' => $image,
          '#url' => URL::fromRoute('opigno_video.image_popup_render', ['fid' => $fid['target_id']]),
          '#options' => [
            'attributes' => [
              'class' => ['use-ajax', 'ops-link'],
              'data-dialog-type' => 'modal',
              'data-dialog-options' => Json::encode([
                'width' => 500,
              ]),
            ],
          ],
        ]);
        break;
    }

    return $output;
  }

}
