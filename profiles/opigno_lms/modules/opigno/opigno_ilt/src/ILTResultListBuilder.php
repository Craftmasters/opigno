<?php

namespace Drupal\opigno_ilt;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for opigno_ilt_result entity.
 */
class ILTResultListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['ilt'] = $this->t('Instructor-Led Training');
    $header['user'] = $this->t('User');
    $header['status'] = $this->t('Status');
    $header['score'] = $this->t('Score');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\opigno_ilt\ILTResultInterface $entity */
    $row['id'] = $entity->id();
    $row['ilt'] = $entity->getILT()->toLink();
    $row['user'] = $entity->getUser()->toLink();
    $row['status'] = $entity->getStatusString();
    $row['score'] = $entity->getScore();
    return $row + parent::buildRow($entity);
  }

}
