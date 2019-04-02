<?php

namespace Drupal\opigno_scorm\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;

/**
 * Plugin implementation of the 'opigno_evaluation_method_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "opigno_scorm_field_formatter",
 *   label = @Translation("Opigno Scorm player"),
 *   field_types = {
 *     "opigno_scorm_package"
 *   }
 * )
 */
class OpignoScormFieldFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $scorm_service = \Drupal::service('opigno_scorm.scorm');
    $scorm_player = \Drupal::service('opigno_scorm.scorm_player');
    $first = TRUE;
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      if ($first) {
        $scorm = $scorm_service->scormLoadByFileEntity($file);
        $elements[$delta] = $scorm_player->toRendarableArray($scorm);
        $first = FALSE;
      }
      else {
        $elements[$delta] = [
          '#markup' => $this->t("As per <a href='!link' target='_blank'>SCORM.2004.3ED.ConfReq.v1.0</a>, only <em>only one SCO can be launched at a time.</em> To enforce this, only one SCORM package is loaded inside the player on this page at a time.", ['!link' => 'http://www.adlnet.gov/wp-content/uploads/2011/07/SCORM.2004.3ED.ConfReq.v1.0.pdf']),
        ];
      }
    }

    return $elements;
  }

}
