(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.opignoLearningPathTrainingMainPage = {
    attach: function (context, settings) {
      // Disable link to Training start if user has not access.
      var $pending_link = $('#group-content .approval-pending-link');
      if ($pending_link.length) {
        // $('.lp_progress_continue').once().css('pointer-events', 'none');
        $('.lp_progress_continue').once('preventClick').click(function (e) {
          e.stopPropagation();
          e.preventDefault();
        });
      }
    }
  };
}(jQuery, Drupal, drupalSettings));
