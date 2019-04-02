<?php

namespace Drupal\opigno_certificate;

use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a opigno_certificate entity.
 */
interface OpignoCertificateInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface, RevisionLogInterface, EntityPublishedInterface {

  /**
   * Denotes that the opigno_certificate is not published.
   */
  const NOT_PUBLISHED = 0;

  /**
   * Denotes that the opigno_certificate is published.
   */
  const PUBLISHED = 1;

  /**
   * Gets the opigno_certificate creation timestamp.
   *
   * @return int
   *   Creation timestamp of the opigno_certificate.
   */
  public function getCreatedTime();

  /**
   * Sets the opigno_certificate creation timestamp.
   *
   * @param int $timestamp
   *   The opigno_certificate creation timestamp.
   *
   * @return \Drupal\opigno_certificate\CertificateInterface
   *   The called opigno_certificate entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the opigno_certificate revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the opigno_certificate revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\opigno_certificate\CertificateInterface
   *   The called opigno_certificate  entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Returns the view mode selector field, if available.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface|null
   *   A field definition or NULL if no view mode selector field is defined.
   */
  public function getViewModeSelectorField();

}
