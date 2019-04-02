(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.opignoILTForm = {
    attach: function (context, settings) {
      const $members = $('#members', context);
      const $members_available = $('#members-available', context);
      const $members_autocomplete = $('#members_autocomplete', context);
      $members_autocomplete
          .once('autocompleteselect')
          .on('autocompleteselect', function (e, ui) {
            // Get ids of the already selected options.
            const selected_ids = $('option', $members)
                .map(function () {
                  return $(this).val();
                }).get();

            // Replace available options list with the selected option.
            $members_available.empty();
            if (selected_ids.indexOf(ui.item.id) === -1) {
              const option_html = '<option value="' + ui.item.id + '">'
                  + ui.item.label
                  + '</option>';
              $members_available.append(option_html);
            }
          });
      $members_autocomplete
          .once('autocompleteresponse')
          .on('autocompleteresponse', function (e, ui) {
            // Get ids of the already selected options.
            const selected_ids = $('option', $members)
                .map(function () {
                  return $(this).val();
                }).get();
            // Get available options without the already selected.
            const options = ui.content.filter(function (option) {
              return selected_ids.indexOf(option.id) === -1;
            });

            // Replace available options list with the available options.
            $members_available.empty();
            options.forEach(function (option) {
              const option_html = '<option value="' + option.id + '">'
                  + option.label
                  + '</option>';
              $members_available.append(option_html);
            });
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
}(jQuery, Drupal, drupalSettings));
