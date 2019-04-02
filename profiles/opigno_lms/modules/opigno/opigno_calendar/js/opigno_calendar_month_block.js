/* eslint-disable */

(function ($, Drupal) {

  Drupal.behaviors.opignoCalendarMonthBlock = {

    attach: function (context, settings) {
      var $container = $(context).find('.view-opigno-calendar[class*="month"] .view-content');
      this.initDayDisplay($container);
      $container.find('td.date-box.today a').click();
    },

    initDayDisplay: function ($container) {
      $container
        .find('td.date-box a')
        .click(function () {
          var
            activeClassName = 'single-day-active',
            $previousActive =  $container.find('.' + activeClassName),
            date = $(this).parents('td.date-box').attr('date-date'),
            $newActive = $container.find('td.single-day[date-date="' + date + '"]');

          if (!$newActive.is($previousActive)) {
            $newActive.addClass(activeClassName);
            $container.addClass(activeClassName);
          }
          else {
            $newActive.removeClass(activeClassName);
            $container.removeClass(activeClassName);
          }

          $previousActive.removeClass(activeClassName);

          return false;
        });
    }

  };

}(jQuery, Drupal));
