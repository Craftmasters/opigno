<?php

namespace Drupal\opigno_certificate;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;

/**
 * Provides routes for Certificates.
 */
class CertificateRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getCanonicalRoute(EntityTypeInterface $entity_type) {
    $route = parent::getCanonicalRoute($entity_type);

    $defaults = $route->getDefaults();
    unset($defaults['_entity_view']);
    $defaults['_controller'] = '\Drupal\opigno_certificate\Controller\CertificateController::view';
    $route->setDefaults($defaults);

    return $route;
  }

}
