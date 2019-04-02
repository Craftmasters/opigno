/* eslint-disable func-names */

(function ($, Drupal) {
  Drupal.behaviors.opignoLearningPathDelete = {
    attach: function (context, settings) {
      $('a#edit-delete', context).click(function(e) {
        e.preventDefault();
        $('#delete-lp-form-overlay').fadeIn(200);
      });

      $('.close-overlay', context).click(function(e) {
        e.preventDefault();
        $('#delete-lp-form-overlay').fadeOut(200);
      });
    },
  };
}(jQuery, Drupal));
