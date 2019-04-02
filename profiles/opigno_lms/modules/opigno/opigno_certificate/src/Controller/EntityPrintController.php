<?php

namespace Drupal\opigno_certificate\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\entity_print\Plugin\ExportTypeManagerInterface;
use Drupal\entity_print\Plugin\EntityPrintPluginManagerInterface;
use Drupal\entity_print\PrintBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Print controller.
 */
class EntityPrintController extends ControllerBase {

  /**
   * The plugin manager for our Print engines.
   *
   * @var \Drupal\entity_print\Plugin\EntityPrintPluginManagerInterface
   */
  protected $pluginManager;

  /**
   * The export type manager.
   *
   * @var \Drupal\entity_print\Plugin\ExportTypeManagerInterface
   */
  protected $exportTypeManager;

  /**
   * The Print builder.
   *
   * @var \Drupal\entity_print\PrintBuilderInterface
   */
  protected $printBuilder;

  /**
   * The Entity Type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityPrintPluginManagerInterface $plugin_manager, ExportTypeManagerInterface $export_type_manager, PrintBuilderInterface $print_builder, EntityTypeManagerInterface $entity_type_manager) {
    $this->pluginManager = $plugin_manager;
    $this->exportTypeManager = $export_type_manager;
    $this->printBuilder = $print_builder;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity_print.print_engine'),
      $container->get('plugin.manager.entity_print.export_type'),
      $container->get('entity_print.print_builder'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Print an entity to the selected format.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $entity_id
   *   The entity ID.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object on error otherwise the Print is sent.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function viewPrint($entity_type, $entity_id) {
    $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);
    $opigno_certificate = $entity->field_certificate->entity;

    // We're going to render the opigno_certificate,
    // but the opigno_certificate will need pull
    // information from the entity that references it. So set the
    // 'referencing_entity' computed field to the entity being displayed.
    $opigno_certificate->set('referencing_entity', $entity);

    // @todo: check opigno_certificate access before rendering the opigno_certificate.
    // @todo: implement entity access to check that user has completed learning
    // path. This will need to be a custom access operation other than 'view'.
    // Create the Print engine plugin.
    $config = $this->config('entity_print.settings');

    $print_engine = $this->pluginManager->createSelectedInstance('pdf');
    return (new StreamedResponse(function () use ($opigno_certificate, $print_engine, $config) {
      // The Print is sent straight to the browser.
      $this->printBuilder->deliverPrintable([$opigno_certificate], $print_engine, $config->get('force_download'), $config->get('default_css'));
    }))->send();
  }

  /**
   * A debug callback for styling up the Print.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $entity_id
   *   The entity ID.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function viewPrintDebug($entity_type, $entity_id) {
    $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);
    $opigno_certificate = $entity->field_opigno_certificate->entity;

    // We're going to render the opigno_certificate,
    // but the opigno_certificate will need pull
    // information from the entity that references it. So set the
    // 'referencing_entity' computed field to the entity being displayed.
    $opigno_certificate->set('referencing_entity', $entity);
    $use_default_css = $this->config('entity_print.settings')->get('default_css');
    return new Response($this->printBuilder->printHtml($opigno_certificate, $use_default_css, FALSE));
  }

}
