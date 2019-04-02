<?php

namespace Drupal\opigno_file_upload\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'opigno_evaluation_method_widget' widget.
 *
 * @FieldWidget(
 *   id = "opigno_evaluation_method_widget",
 *   label = @Translation("Evaluation method widget"),
 *   field_types = {
 *     "opigno_evaluation_method"
 *   }
 * )
 */
class OpignoEvaluationMethodWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $options = [
      0 => $this->t('Automatic'),
      1 => $this->t('Manual'),
    ];
    $element['value'] = $element + [
      '#type' => 'radios',
      '#options' => $options,
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#title' => 'Evaluation method',
      '#weight' => 1,
    ];

    return $element;
  }

}
