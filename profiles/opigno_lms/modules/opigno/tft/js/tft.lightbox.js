;(function ($, Drupal) {
  Drupal.behaviors.tftLightbox = {
    attach: function (context, settings) {
      if (context != document) return;
      var that = this;
      var dataSrc;

      $(document, context).on('click', 'a[data-src]', function(e) {
        e.preventDefault();
        dataSrc = $(this).attr('data-src');
        that.loadModal(dataSrc);
      });

      $(document, context).on('click', '#modalOverlay button.closeModal', function(e) {
        $(this).closest('#modalOverlay').remove();
        dataSrc = null;
      });

      $(document, context).on('click', '#modalOverlay .nav-wrapper button.next', function(e) {
        dataSrc = that.findNextImage(dataSrc);
      });

      $(document, context).on('click', '#modalOverlay .nav-wrapper button.prev', function(e) {
        dataSrc = that.findPrevImage(dataSrc);
      });
    },

    loadModal: function (dataSrc) {
      var html = '<div class="wrapper">' +
                    '<div class="text-right mb-1"><button class="closeModal">×</button></div>' +
                    '<div class="image-wrapper">' +
                      '<img src="' + dataSrc + '" alt="" />' +
                    '</div>' +
                    '<div class="nav-wrapper d-flex mt-1">' +
                      '<button class="prev">&#8249;</button>' +
                      '<button class="next ml-auto">&#8250;</button>' +
                    '</div>' +
                 '</div>';

      $('body').append('<div id="modalOverlay">' + html + '</div>');
    },

    updateModal: function (dataSrc) {
      $('#modalOverlay .image-wrapper').html('<img src="' + dataSrc + '" alt="" />');
    },

    findNextImage: function (dataSrc) {
      var $x = $('a[data-src]');
      var $next = $x.eq($x.index($('a[data-src="' + dataSrc + '"]')) + 1);

      if ($next.length) {
        this.updateModal($next.attr('data-src'));
        return $next.attr('data-src');
      } else {
        return dataSrc;
      }
    },

    findPrevImage: function (dataSrc) {
      var $x = $('a[data-src]');
      var i = $x.index($('a[data-src="' + dataSrc + '"]'));
      var $prev = $x.eq(i - 1);

      if (i > 0) {
        this.updateModal($prev.attr('data-src'));
        return $prev.attr('data-src');
      } else {
        return dataSrc;
      }
    }
  }
} (window.jQuery, window.Drupal, window.drupalSettings));
