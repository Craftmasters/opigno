/**
 * @file
 * Open popup with requested settings.
 */

(function ($) {

  'use strict';

  /**
   * Behaviors.
   */
  Drupal.behaviors.fieldGroup = {
    attach: function (context, settings) {

      $('.' + settings.popupFieldGroup.linkCssClass, context).once('popup-field-group').each(function () {
        var link = $(this);
        var targetId = link.data('target');

        if (typeof settings.popupFieldGroup.popups[targetId] !== 'undefined') {
          var popupContent = $('#' + targetId);
          var popupSettings = settings.popupFieldGroup.popups[targetId];

          if (popupContent.length > 0) {
            // Ensure form elements are not moved outside the form.
            popupSettings.dialog.appendTo = link.parent();

            var dialog = Drupal.dialog(popupContent, popupSettings.dialog);

            link.click(function () {
              if (popupSettings.modal) {
                dialog.showModal();
              }
              else {
                dialog.show();
              }
            });

          }
        }

      });
    }
  };

})(jQuery);
