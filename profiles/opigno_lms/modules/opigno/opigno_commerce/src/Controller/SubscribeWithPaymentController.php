<?php

namespace Drupal\opigno_commerce\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\group\Entity\Group;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_cart\CartManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Controller routines for products routes.
 */
class SubscribeWithPaymentController extends ControllerBase {

  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * SubscribeWithPaymentController constructor.
   *
   * @param \Drupal\commerce_cart\CartManagerInterface $cart_manager
   *   Cart manager.
   * @param \Drupal\commerce_cart\CartProviderInterface $cart_provider
   *   Cart provider.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(CartManagerInterface $cart_manager, CartProviderInterface $cart_provider, EntityTypeManagerInterface $entity_type_manager) {
    $this->cartManager = $cart_manager;
    $this->cartProvider = $cart_provider;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_cart.cart_manager'),
      $container->get('commerce_cart.cart_provider'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Subscribe With Payment.
   *
   * @param \Drupal\group\Entity\Group $group
   *   Group entity.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function subscribeWithPayment(Group $group) {
    // Check if training is already bought.
    $user = $this->currentUser();
    $is_bought = opingo_commerce_check_if_training_bought($group->id(), $user->id());
    $response = new RedirectResponse(
      Url::fromRoute(
        'entity.group.canonical',
        ['group' => $group->id()]
      )->toString()
    );
    if ($is_bought) {
      $this->messenger()
        ->addMessage($this->t('Training @training is already bought. Check your orders.', [
          '@training' => $group->label(),
        ]));
      return $response;
    }
    // Add product to cart.
    $storage = $this->entityTypeManager->getStorage('commerce_product');
    $productObj = $storage->loadByProperties(['field_learning_path_id' => $group->id()]);
    $productObj = reset($productObj);

    $product_variation_id = $productObj->get('variations')
      ->getValue()[0]['target_id'];
    $storeId = $productObj->get('stores')->getValue()[0]['target_id'];
    $variationobj = $this->entityTypeManager
      ->getStorage('commerce_product_variation')
      ->load($product_variation_id);
    $store = $this->entityTypeManager
      ->getStorage('commerce_store')
      ->load($storeId);

    $cart = $this->cartProvider->getCart('default', $store);

    if (!$cart) {
      $cart = $this->cartProvider->createCart('default', $store);
    }

    // Check if item is already in cart.
    $cart_items = $cart->getItems();
    if ($cart_items) {
      foreach ($cart_items as $item) {
        /* @var Drupal\commerce_product\Entity\ProductVariation $product_variation */
        $item_variation = $item->getPurchasedEntity();
        if ($item_variation->id() == $product_variation_id) {
          $this->messenger()
            ->addMessage($this->t('Training @training is already added to @cart', [
              '@training' => $group->label(),
              '@cart' => Link::createFromRoute('your cart', 'commerce_cart.page')
                ->toString(),
            ]));
          return $response;
        }
      }
    }

    // Process to place order programatically.
    $this->cartManager->addEntity($cart, $variationobj);

    return $response;
  }

  /**
   * Page title callback.
   *
   * @param \Drupal\group\Entity\Group $group
   *   Group entity.
   *
   * @return string
   *   Training entity label.
   */
  public function formTitleCallback(Group $group) {
    // Return entity label.
    return 'Buy access to training - ' . $group->label();
  }

}
