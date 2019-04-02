(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.opignoLearningPathMemberOverview = {
    attach: function (context, settings) {
      var gid = drupalSettings.opigno_learning_path.gid;

      $('#learning-path-members-form').submit(function (e) {
        e.preventDefault();
        return false;
      });

      $('.class_hide', context).hide();
      $('.class_members_row', context).hide();

      function showClass($el) {
        var $parent = $el.closest('.class');

        if (!$parent.length) {
          return;
        }

        $parent.find('.class_hide').show();
        $parent.find('.class_show').hide();
        $parent.find('.class_members_row').show();
      }

      function hideClass($el) {
        var $parent = $el.closest('.class');

        if (!$parent.length) {
          return;
        }

        $parent.find('.class_hide').hide();
        $parent.find('.class_show').show();
        $parent.find('.class_members_row').hide();
      }

      $('.class_hide', context).once('click').click(function () {
        hideClass($(this));
      });

      $('.class_show', context).once('click').click(function () {
        showClass($(this));
      });

      $('.class_delete').once('click').click(function (e) {
        e.preventDefault();

        var $this = $(this);

        $.ajax({
          url: '/group/' + gid + '/learning-path/members/delete-class',
          data: {
            class_id: this.id.match(/(\d+)$/)[1],
          },
        })
            .done(function (data) {
              $this.parent().hide();
            });

        return false;
      });

      $('.class_member_since_pending').once('click').click(function (e) {
        e.preventDefault();

        var $this = $(this);

        $.ajax({
          url: '/group/' + gid + '/learning-path/members/validate',
          data: {
            user_id: this.id.match(/(\d+)$/)[1],
          },
        }).done(function (e) {
          this.classList.remove('class_member_since_pending');

          var date = new Date();
          var day = date.getDate();
          var month = date.getMonth() + 1;
          var year = date.getFullYear();
          this.textContent =
              (day < 10 ? '0' : '') + day + '/'
              + (month < 10 ? '0' : '') + month + '/'
              + year;
        }.bind(this));

        return false;
      });

      $('.class_member_toggle_sm').once('click').click(function (e) {
        e.preventDefault();

        var $this = $(this);

        $.ajax({
          url: '/group/' + gid + '/learning-path/members/toggle-role',
          data: {
            uid: this.id.match(/(\d+)$/)[1],
            role: drupalSettings.opigno_learning_path.student_manager_role,
          },
        })
            .done(function (data) {
              $this.toggleClass('class_member_toggle_sm_active');
            });

        return false;
      });

      $('.class_member_toggle_cm').once('click').click(function (e) {
        e.preventDefault();

        var $this = $(this);

        $.ajax({
          url: '/group/' + gid + '/learning-path/members/toggle-role',
          data: {
            uid: this.id.match(/(\d+)$/)[1],
            role: drupalSettings.opigno_learning_path.content_manager_role,
          },
        })
            .done(function (data) {
              $this.toggleClass('class_member_toggle_cm_active');
            });

        return false;
      });

      $('.class_member_toggle_class_manager').once('click').click(function (e) {
        e.preventDefault();

        var $this = $(this);

        $.ajax({
          url: '/group/' + gid + '/learning-path/members/toggle-role',
          data: {
            uid: this.id.match(/(\d+)$/)[1],
            role: drupalSettings.opigno_learning_path.class_manager_role,
          },
        })
          .done(function (data) {
            $this.toggleClass('class_member_toggle_class_manager_active');
          });

        return false;
      });

      $('#class_members_search').once('autocompleteselect').on('autocompleteselect', function (e, ui) {
        e.preventDefault();

        if (ui.item) {
          var id = 'student_' + ui.item.id;
          var $row = $('#' + id);

          if ($row.length) {
            showClass($row);

            window.location.hash = id;
            window.scrollBy(0, -100);
          } else {
            var id = 'individual_' + ui.item.id;
            var $row = $('#' + id);

            if ($row.length) {
              showClass($row);

              window.location.hash = id;
              window.scrollBy(0, -100);
            }
          }
        }

        return false;
      });

      $('#individual_members_search').once('autocompleteselect').on('autocompleteselect', function (e, ui) {
        e.preventDefault();

        if (ui.item) {
          var id = 'individual_' + ui.item.id;
          var $row = $('#' + id);

          if ($row.length) {
            showClass($row);

            window.location.hash = id;
            window.scrollBy(0, -100);
          }
        }

        return false;
      });

      $('.class_member_delete').once('click').click(function (e) {
        e.preventDefault();

        var $this = $(this);

        $.ajax({
          url: '/group/' + gid + '/learning-path/members/delete-user',
          data: {
            user_id: this.id.match(/(\d+)$/)[1],
          },
        })
            .done(function (data) {
              $this.parents('.class_members_row').hide();
            });

        return false;
      });
    },
  };
}(jQuery, Drupal, drupalSettings));
