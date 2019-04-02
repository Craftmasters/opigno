<?php

namespace Drupal\ggroup\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Render\RendererInterface;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\GroupContentEnablerManagerInterface;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Returns responses for 'subgroup' GroupContent routes.
 */
class SubgroupWizardController extends ControllerBase {

  /**
   * The private store for temporary subgroups.
   *
   * @var \Drupal\user\PrivateTempStore
   */
  protected $privateTempStore;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * The group content plugin manager.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface
   */
  protected $pluginManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new SubgroupWizardController.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The factory for the temp store object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   * @param \Drupal\group\Plugin\GroupContentEnablerManagerInterface $plugin_manager
   *   The group content plugin manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $entity_type_manager, EntityFormBuilderInterface $entity_form_builder, GroupContentEnablerManagerInterface $plugin_manager, RendererInterface $renderer) {
    $this->privateTempStore = $temp_store_factory->get('ggroup_add_temp');
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFormBuilder = $entity_form_builder;
    $this->pluginManager = $plugin_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder'),
      $container->get('plugin.manager.group_content_enabler'),
      $container->get('renderer')
    );
  }

  /**
   * Provides the form for creating a subgroup in a group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to create a subgroup in.
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The subgroup type to create.
   *
   * @return array
   *   The form array for either step 1 or 2 of the subgroup creation wizard.
   */
  public function addForm(GroupInterface $group, GroupTypeInterface $group_type) {
    $plugin_id = 'subgroup:' . $group_type->id();
    $storage_id = $plugin_id . ':' . $group->id();
    $creation_wizard = $group->getGroupType()->getContentPlugin($plugin_id)->getConfiguration()['use_creation_wizard'];
    // If we are on step one, we need to build a group form.
    if ($this->privateTempStore->get("$storage_id:step") !== 2) {
      $this->privateTempStore->set("$storage_id:step", 1);

      // Only create a new group if we have nothing stored.
      if (!$entity = $this->privateTempStore->get("$storage_id:group")) {
        $entity = Group::create(['type' => $group_type->id()]);
      }
    }
    // If we are on step two, we need to build a group content form.
    else {
      /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
      $plugin = $group->getGroupType()->getContentPlugin($plugin_id);
      $entity = GroupContent::create([
        'type' => $plugin->getContentTypeConfigId(),
        'gid' => $group->id(),
      ]);
      if (!$creation_wizard && $entity = $this->privateTempStore->get("$storage_id:group")) {
         $entity->save();
         $group->addContent($entity, $plugin_id);

          // We also clear the private store so we can start fresh next time around.
          $this->privateTempStore->delete("$storage_id:step");
          $this->privateTempStore->delete("$storage_id:group");

         return $this->redirect('entity.group.canonical', ['group' => $entity ->id()]);
      }
    }

    // Return the form with the group and storage ID added to the form state.
    $extra = ['group' => $group, 'storage_id' => $storage_id, 'wizard' => $creation_wizard];
    return $this->entityFormBuilder()->getForm($entity, 'ggroup-form', $extra);
  }

  /**
   * The _title_callback for the add group form route.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to create a group in.
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The group type to create.
   *
   * @return string
   *   The page title.
   */
  public function addFormTitle(GroupInterface $group, GroupTypeInterface $group_type) {
    return $this->t('Create %type in %label', ['%type' => $group_type->label(), '%label' => $group->label()]);
  }

  /**
   * Provides the subgroup creation overview page.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to add the subgroup to.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   The subgroup creation overview page or a redirect to the create form if
   *   we only have 1 bundle.
   */
  public function addPage(GroupInterface $group) {
    // We do not set the "entity_add_list" template's "#add_bundle_message" key
    // because we deny access to the page if no bundle is available.
    $build = ['#theme' => 'entity_add_list', '#bundles' => []];
    $add_form_route = 'entity.group_content.subgroup_add_form';

    // Retrieve all subgroup plugins for the group's type.
    $plugin_ids = $this->pluginManager->getInstalledIds($group->getGroupType());
    foreach ($plugin_ids as $key => $plugin_id) {
      if (strpos($plugin_id, 'subgroup:') !== 0) {
        unset($plugin_ids[$key]);
      }
    }

    $storage = $this->entityTypeManager->getStorage('group_content_type');
    $properties = [
      'group_type' => $group->bundle(),
      'content_plugin' => $plugin_ids,
    ];
    /** @var \Drupal\group\Entity\GroupContentTypeInterface[] $bundles */
    $bundles = $storage->loadByProperties($properties);

    // Filter out the bundles the user doesn't have access to.
    $access_control_handler = $this->entityTypeManager->getAccessControlHandler('group_content');
    foreach (array_keys($bundles) as $bundle) {
      // Check for access and add it as a cacheable dependency.
      $access = $access_control_handler->createAccess($bundle, NULL, ['group' => $group], TRUE);
      $this->renderer->addCacheableDependency($build, $access);

      // Remove inaccessible bundles from the list.
      if (!$access->isAllowed()) {
        unset($bundles[$bundle]);
      }
    }

    // Redirect if there's only one bundle available.
    if (count($bundles) == 1) {
      $group_content_type = reset($bundles);
      $plugin = $group_content_type->getContentPlugin();
      $route_params = ['group' => $group->id(), 'group_type' => $plugin->getEntityBundle()];
      $url = Url::fromRoute($add_form_route, $route_params, ['absolute' => TRUE]);
      return new RedirectResponse($url->toString());
    }

    // Get the subgroup type storage handler.
    $storage_handler = $this->entityTypeManager->getStorage('group_type');

    // Set the info for all of the remaining bundles.
    foreach ($bundles as $bundle => $group_content_type) {
      $plugin = $group_content_type->getContentPlugin();
      $bundle_label = $storage_handler->load($plugin->getEntityBundle())->label();
      $route_params = ['group' => $group->id(), 'group_type' => $plugin->getEntityBundle()];

      $build['#bundles'][$bundle] = [
        'label' => $bundle_label,
        'description' => $this->t('Create a subgroup of type %group_type for the group.', ['%group_type' => $bundle_label]),
        'add_link' => Link::createFromRoute($bundle_label, $add_form_route, $route_params),
      ];
    }

    // Add the list cache tags for the GroupContentType entity type.
    $bundle_entity_type = $this->entityTypeManager->getDefinition('group_content_type');
    $build['#cache']['tags'] = $bundle_entity_type->getListCacheTags();

    return $build;
  }

}
