/* eslint-disable */

(function ($, Drupal) {
  Drupal.behaviors.opignoCatalog = {
    attach: function (context, settings) {
      this.handleViewStyle(context);
    },

    handleViewStyle: function (context) {
      var that = this;

      $('.view-style a', context).click(function (e) {
        e.preventDefault();
        if ($(this).hasClass('line')) {
          $(this).closest('.view').addClass('style-line');
          that.setStyle('line');
        } else {
          $(this).closest('.view').removeClass('style-line');
          that.setStyle('block');
        }
      });
    },

    setStyle: function (style) {
      $.ajax({
        type: 'get',
        url:'/opigno-catalog/set-style/'+ style,
        success: function (data) { },
        error: function (data) {
          console.error('The ajax request has encountered a problem');
        }
      });
    }
  };
}(jQuery, Drupal));
