;(function ($, Drupal) {
  Drupal.behaviors.opignoStatisticsPopover = {
    attach: function (context) {
      $('.popover-help:not(.show)', context).click(function (e) {
        e.preventDefault();
        if ($(this).find('.popover').length) return;

        var content = $(this).attr('data-content');
        $(this).append('<div class="popover bs-popover-right"><div class="arrow"></div><div class="popover-header clearfix"><button class="close">x</button></div><div class="popover-body">' + content + '</div></div>');
      });

      $(document).on('click', '.popover-help button.close', function (e) {
        e.stopPropagation();
        $(this).closest('.popover').remove();
      });
    }
  }
}(jQuery, Drupal, drupalSettings));
