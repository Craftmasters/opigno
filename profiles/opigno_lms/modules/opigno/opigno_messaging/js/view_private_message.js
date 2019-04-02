;(function ($, Drupal) {
  Drupal.behaviors.opignoViewPrivateMessage = {
    attach: function (context, settings) {
      var $rows = $('.view-private-message .views-row', context);

      // Redirects to thread if user clicks on thread block.
      $rows.once('click').click(function(e) {
        e.preventDefault();

        var $thread = $(this).find('.private-message-thread');

        if (!$thread.length) {
          return false;
        }

        var id = $thread.attr('data-thread-id');
        window.location = '/private_messages/' + id;

        return false;
      });
    },
  };

  // Fixes multiselect issue 2123241.
  if (Drupal.behaviors.multiSelect
      && !Drupal.behaviors.multiSelect.detach
  ) {
    Drupal.behaviors.multiSelect.detach = function (context, settings, trigger) {
      if (trigger === 'serialize') {
        $('select.multiselect-selected').selectAll();
      }
    };
  }
}(jQuery, Drupal));
