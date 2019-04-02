<?php

namespace Drupal\opigno_ilt;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;

/**
 * Provides a list controller for opigno_ilt entity.
 */
class ILTListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['name'] = $this->t('Name');
    $header['training'] = $this->t('Related training');
    $header['date'] = $this->t('Date');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\opigno_ilt\ILTInterface $entity */
    $row['id'] = $entity->id();
    $row['name'] = $entity->toLink();

    $training = $entity->getTraining();
    $row['training'] = isset($training) ? $entity->getTraining()->toLink() : '-';

    $date = $entity->getStartDate();
    $row['date'] = !empty($date) ? $date : '-';

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    $operations['score'] = [
      'title' => $this->t('Score'),
      'weight' => 10,
      'url' => Url::fromRoute('opigno_ilt.score', ['opigno_ilt' => $entity->id()],
        [
          'query' => ['destination' => 'admin/content/ilt'],
          'absolute' => TRUE,
        ]),
    ];

    return $operations;
  }

}
