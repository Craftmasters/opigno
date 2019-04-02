(function ($) {
  Drupal.behaviors.opignoBadges = {
    attach: function () {
      var badgeActive = $('#edit-badge-active-value'),
          badgeSettings = $('#edit-opigno-badges-settings-group');

      if (badgeActive.is(':checked')) {
        badgeSettings.show();
      }
      else {
        badgeSettings.hide();
      }

      badgeActive.unbind('change');
      badgeActive.on('change',function () {
        badgeSettings.toggle();
      });

    }
  };
}(jQuery));
