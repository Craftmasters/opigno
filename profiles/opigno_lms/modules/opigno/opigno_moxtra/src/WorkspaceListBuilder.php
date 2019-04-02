<?php

namespace Drupal\opigno_moxtra;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for opigno_moxtra_workspace entity.
 */
class WorkspaceListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['name'] = $this->t('Name');
    $header['binder_id'] = $this->t('Binder ID');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\opigno_moxtra\WorkspaceInterface $entity */
    $row['id'] = $entity->id();
    $row['name'] = $entity->toLink(NULL, 'edit-form');
    $row['binder_id'] = $entity->getBinderId();
    return $row + parent::buildRow($entity);
  }

}
