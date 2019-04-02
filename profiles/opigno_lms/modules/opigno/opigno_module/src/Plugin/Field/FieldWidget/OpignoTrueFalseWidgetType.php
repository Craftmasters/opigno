<?php

namespace Drupal\opigno_module\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'opigno_true_false_widget_type' widget.
 *
 * @FieldWidget(
 *   id = "opigno_true_false_widget_type",
 *   label = @Translation("True or False widget type"),
 *   field_types = {
 *     "opigno_true_false_field"
 *   }
 * )
 */
class OpignoTrueFalseWidgetType extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $options = [
      1 => $this->t('True'),
      0 => $this->t('False'),
    ];
    $element['value'] = $element + [
      '#type' => 'radios',
      '#options' => $options,
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#title' => 'Answer',
      '#weight' => 1,
    ];

    return $element;
  }

}
