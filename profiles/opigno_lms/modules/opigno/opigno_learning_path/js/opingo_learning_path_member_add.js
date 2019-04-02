(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.opignoLearningPathMemberAdd = {
    attach: function (context, settings) {
      var $training_users = $('#training_users', context);
      var $training_users_available = $('#training_users-available', context);
      var $training_users_autocomplete = $('#training_users_autocomplete', context);
      var $dropdown = $('.ui-widget.ui-widget-content');

      function hideDropdown() {
        $dropdown.addClass('invisible');
      }

      function setDropdownDefault() {
        $dropdown.removeClass('invisible');
      }

      $training_users_autocomplete
          .once('autocompleteselect')
          .on('autocompleteselect', function (e, ui) {
            // Get ids of the already selected options.
            var selected_ids = $('option', $training_users)
                .map(function () {
                  return $(this).val();
                }).get();

            // Replace available options list with the selected option.
            $training_users_available.empty();
            if (selected_ids.indexOf(ui.item.id) === -1) {
              var option_html = '<option value="' + ui.item.id + '">'
                  + ui.item.label
                  + '</option>';
              $training_users_available.append(option_html);
            }

            hideDropdown();
          });
      $training_users_autocomplete
          .once('autocompleteresponse')
          .on('autocompleteresponse', function (e, ui) {
            // Get ids of the already selected options.
            var selected_ids = $('option', $training_users)
                .map(function () {
                  return $(this).val();
                }).get();
            // Get available options without the already selected.
            var options = ui.content.filter(function (option) {
              return selected_ids.indexOf(option.id) === -1;
            });

            // Replace available options list with the available options.
            $training_users_available.empty();
            options.forEach(function (option) {
              var option_html = '<option value="' + option.id + '">'
                  + option.label
                  + '</option>';
              $training_users_available.append(option_html);

            });
            hideDropdown();
          });

      $training_users_autocomplete.on('blur', function() {
        setDropdownDefault();
      })
    },
  };

  // Fixes multiselect issue 2123241.
  if (Drupal.behaviors.multiSelect
      && !Drupal.behaviors.multiSelect.detach) {
    Drupal.behaviors.multiSelect.detach = function (context, settings, trigger) {
      if (trigger === 'serialize') {
        $('select.multiselect-selected').selectAll();
      }
    };
  }
}(jQuery, Drupal, drupalSettings));
