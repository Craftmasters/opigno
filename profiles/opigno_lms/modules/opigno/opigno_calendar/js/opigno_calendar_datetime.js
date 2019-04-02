(function ($, Drupal) {

  Drupal.behaviors.opignoCalendarDateTime = {

    attach: function (context, settings) {
      $('.daterange-date input', context).datepicker({
        constrainInput: true,
        firstDay: 1,
      });
    },

  };

}(jQuery, Drupal));
