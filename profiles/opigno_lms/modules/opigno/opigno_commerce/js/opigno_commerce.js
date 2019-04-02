(function ($, Drupal) {
  Drupal.behaviors.opignoCommerce = {
    attach: function (context, settings) {
      $(".form-item-payment-method-billing-information-address-0-address-address-line2 label")
          .once('removeLabelClass').removeClass("visually-hidden");
      $(".form-item-payment-information-add-payment-method-billing-information-address-0-address-address-line2 label")
          .once('removeLabelClass').removeClass("visually-hidden");
      $(".form-item-payment-information-billing-information-address-0-address-address-line2 label")
          .once('removeLabelClass').removeClass("visually-hidden");
    }
  };
}(jQuery, Drupal));
