;(function ($, Drupal) {
  Drupal.behaviors.opignoNotificationView = {
    attach: function (context, settings) {
      var $readAll = $('#read-all-notifications', context);
      var $notifications = $('.notification', context);
      var $unreadCount = $('#site-header #header-right .user-notifications a .unread');
      var $viewNotifications = $('header#site-header .user-notifications .view-opigno-notifications .views-row');
      // var $markReadTrigger = $('header#site-header .user-notifications #read-all-notifications');

      // Mark all notifications as read.
      $readAll.once('click').click(function(e) {
        e.preventDefault();

        $('.user-notifications')
          .removeClass('show')
          .children('.dropdown-menu')
          .removeClass('show');

        $.ajax({
          url: '/ajax/notifications/mark-read-all',
          success: function() {
            $unreadCount.text(0);
            $viewNotifications.remove();
            // $markReadTrigger.remove();
          },
        });

        return false;
      });

      // Mark a notification as read on click.
      $notifications.once('click').click(function(e) {
        e.preventDefault();

        var id = $(this).attr('data-id');
        $(this).closest('.views-row').remove();

        $.ajax({
          url: '/ajax/notifications/mark-read/' + id,
          success: function() {
            if ($unreadCount.length && $unreadCount.text() !== 0) {
              $unreadCount.text($unreadCount.text() - 1);
            }
          }
        });

        return false;
      });
    },
  };
}(jQuery, Drupal));
