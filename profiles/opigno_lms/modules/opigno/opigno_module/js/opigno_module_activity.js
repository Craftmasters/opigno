(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.opignoModuleActivity = {
    attach: function (context, settings) {
      var that = this;

      $('.fullscreen-link a', context).click(function(e) {
        e.preventDefault();

        if ($('body').hasClass('fullscreen')) {
          $('body', context).removeClass('fullscreen');
          that.goOutFullscreen();
        }
        else {
          $('body', context).addClass('fullscreen');
          that.goInFullscreen(document.querySelector('html'));
        }
      });

      var activityDeleteForm = $('form.opigno-activity-with-answers');
      if (activityDeleteForm.length) {
        activityDeleteForm.submit();
      }
    },

    goInFullscreen: function (element) {
      if (element.requestFullscreen) {
        element.requestFullscreen();
      }
      else if (element.mozRequestFullScreen) {
        element.mozRequestFullScreen();
      }
      else if (element.webkitRequestFullscreen) {
        element.webkitRequestFullscreen();
      }
      else if (element.msRequestFullscreen) {
        element.msRequestFullscreen();
      }
    },

    goOutFullscreen: function () {
      if (document.exitFullscreen) {
        document.exitFullscreen();
      }
      else if (document.mozCancelFullScreen) {
        document.mozCancelFullScreen();
      }
      else if (document.webkitExitFullscreen) {
        document.webkitExitFullscreen();
      }
      else if (document.msExitFullscreen) {
        document.msExitFullscreen();
      }
    },
  };
}(jQuery, Drupal, drupalSettings));
