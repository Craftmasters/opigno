<?php

namespace Drupal\opigno_h5p\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\h5p\H5PDrupal\H5PDrupal;
use Drupal\h5p\Entity\H5PContent;

/**
 * Plugin implementation of the 'h5p_default' formatter.
 *
 * @FieldFormatter(
 *   id = "h5p_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "h5p"
 *   },
 *   quickedit = {
 *     "editor" = "disabled"
 *   }
 * )
 */
class H5PDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = $this->t('Displays interactive H5P content.');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $moduleHandler = \Drupal::service('module_handler');

    foreach ($items as $delta => $item) {
      $value = $item->getValue();

      // Load H5P Content entity.
      $h5p_content = H5PContent::load($value['h5p_content_id']);
      if (empty($h5p_content)) {
        continue;
      }

      // Grab generic integration settings.
      $h5p_integration = H5PDrupal::getGenericH5PIntegrationSettings();

      // Add content specific settings.
      $content_id_string = 'cid-' . $h5p_content->id();
      $h5p_integration['contents'][$content_id_string] = $h5p_content->getH5PIntegrationSettings($item->getEntity()->access('update'));

      $core = H5PDrupal::getInstance('core');
      $preloaded_dependencies = $core->loadContentDependencies($h5p_content->id(), 'preloaded');

      // Load dependencies.
      $files = $core->getDependenciesFiles($preloaded_dependencies, H5PDrupal::getRelativeH5PPath());

      $loadpackages = [
        'h5p/h5p.content',
      ];

      // Load dependencies.
      foreach ($preloaded_dependencies as $dependency) {
        $loadpackages[] = 'h5p/' . _h5p_library_machine_to_id($dependency);
      }

      // Add alter hooks.
      $moduleHandler->alter('h5p_scripts', $files['scripts'], $loadpackages, $h5p_content->getLibrary()->embed_types);
      $moduleHandler->alter('h5p_styles', $files['styles'], $loadpackages, $h5p_content->getLibrary()->embed_types);

      // Render always in Div.
      $html = '<div class="h5p-content" data-content-id="' . $h5p_content->id() . '"></div>';

      // Render each element as markup.
      $element[$delta] = [
        '#type' => 'markup',
        '#markup' => $html,
        '#allowed_tags' => ['div', 'iframe'],
        '#attached' => [
          'drupalSettings' => [
            'h5p' => [
              'H5PIntegration' => $h5p_integration,
            ],
          ],
          'library' => $loadpackages,
        ],
        '#cache' => [
          'tags' => [
            'h5p_content:' . $h5p_content->id(),
            'h5p_content',
          ],
        ],
      ];
    }

    return $element;
  }

}
