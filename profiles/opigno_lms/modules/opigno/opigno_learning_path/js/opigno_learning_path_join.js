/* eslint-disable func-names */

(function ($, Drupal) {
  Drupal.behaviors.opignoLearningPathJoin = {
    attach: function (context, settings) {
      $('.opigno-quiz-app-course-button.join-link', context).mousedown(function (e) {
        e.preventDefault();
        $('#join-group-form-overlay').fadeIn(200);
      });

      $('#join-group-form-overlay button.close-overlay', context).click(function () {
        $('#join-group-form-overlay').fadeOut(200);
      });
    },
  };
}(jQuery, Drupal));
