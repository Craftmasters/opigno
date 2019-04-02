<?php

namespace Drupal\opigno_certificate\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityDisplayRepository;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\view_mode_selector\Plugin\Field\FieldWidget\ViewModeSelectorSelect as ParentSelect;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ViewModeSelectorSelect.
 *
 * @FieldWidget(
 *  id = "opigno_certificate_view_mode_selector_select",
 *  label = @Translation("Select list (all view modes)"),
 *  field_types = {"view_mode_selector"}
 * )
 */
class ViewModeSelectorSelect extends ParentSelect implements ContainerFactoryPluginInterface {

  /**
   * Gather all available view modes.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityDisplayRepository $entity_display_repository) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    // Get all view modes without restricting them to enabled ones..
    // Workaround for static caching issue with display modes.
    $entity_type = $field_definition->getTargetEntityTypeId();
    $view_modes = $entity_display_repository->getViewModeOptions($entity_type);
    // Don't infinitely recurse.
    unset($view_modes['view_mode_selector']);
    $this->viewModes = $view_modes;
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
      $configuration['third_party_settings'],
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type' => 'select',
      '#options' => $this->viewModes,
      '#default_value' => $items[$delta]->value ?: current(array_keys($this->viewModes)),
    ];
    return $element;
  }

}
