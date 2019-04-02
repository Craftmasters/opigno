<?php

namespace Drupal\opigno_module;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;
use Drupal\opigno_module\Entity\OpignoModule;

/**
 * Defines a class to build a listing of Module entities.
 *
 * @ingroup opigno_module
 */
class OpignoModuleListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * Returns module list.
   */
  protected function getEntities() {
    $query = $this->getStorage()->getQuery()
      ->sort($this->entityType->getKey('id'));
    $ids = $query->execute();

    // Filter entities that user has edit access.
    $entities = OpignoModule::loadMultiple($ids);
    $entities = array_filter($entities, function ($entity) {
      return $entity->access('update');
    });
    return $entities;
  }

  /**
   * Returns modules count.
   */
  protected function getTotalCount() {
    return count($this->getEntities());
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $ids = array_keys($this->getEntities());

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $page = \Drupal::request()->query->get('page', 0);
      $limit = $this->limit;
      $start = $limit * $page;
      $ids = array_slice($ids, $start, $limit);
    }

    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    pager_default_initialize($this->getTotalCount(), $this->limit);

    $build = parent::render();
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Module ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\opigno_module\Entity\OpignoModule */
    $row['id'] = $entity->id();
    $row['name'] = $this->l(
      $entity->label(),
      new Url(
        'opigno_module.edit', [
          'opigno_module' => $entity->id(),
        ]
      )
    );

    // Change default edit link.
    $ops = parent::buildRow($entity);
    $destination = $_SERVER['REQUEST_URI'];
    $ops['operations']['data']['#links']['edit']['url'] = new Url(
      'opigno_module.edit', [
        'opigno_module' => $entity->id(),
      ], [
        'query' => ['destination' => $destination],
      ]
    );

    return $row + $ops;
  }

}
