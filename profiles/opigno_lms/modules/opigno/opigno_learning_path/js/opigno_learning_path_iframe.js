/* eslint-disable func-names */

(function ($, Drupal) {
  Drupal.behaviors.opignoLearningPathIframe = {
    attach: function (context, settings) {
      var self = this;

      $(document).once().ajaxComplete(function () {
        if (self.inIframe()) {
          parent.iframeFormValues = drupalSettings.formValues;
        }
      });
    },

    inIframe: function () {
      try {
        return window.self !== window.top;
      }
      catch (e) {
        return true;
      }
    },
  };
}(jQuery, Drupal));
