(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.opignoModuleActivitiesBank = {
    attach: function (context, settings) {
      var view = $('.view-opigno-activities-bank-lp-interface tbody');
      var group_page = false;

      if (drupalSettings.activities_bank.page !== 'undefined' && drupalSettings.activities_bank.page === 'group') {
        group_page = true;
      }

      var arrayLength, checked_array, activity_input, i;
      if (group_page) {
        if (drupalSettings.activities_bank.checkboxes_ids !== undefined) {
          arrayLength = drupalSettings.activities_bank.checkboxes_ids.length;
          if (arrayLength) {
            checked_array = drupalSettings.activities_bank.checkboxes_ids;
            for (i = 0; i < arrayLength; i++) {
              activity_input = view.find('input[value="' + checked_array[i] + '"]');
              activity_input.prop('checked', 'checked');
            }
          }
        }
      }
      else if (typeof $.cookie('activities_bank_checked') !== 'undefined') {
        arrayLength = $.cookie('activities_bank_checked').length;
        if (arrayLength) {
          checked_array = $.cookie('activities_bank_checked').split(/,/);
          for (i = 0; i < arrayLength; i++) {
            activity_input = view.find('input[value="' + checked_array[i] + '"]');
            activity_input.prop('checked', 'checked');
          }
        }
      }

      view.find('input[type="checkbox"]').click(function () {
        var href = $(this).parents('td').next().find('a').attr('href');
        var hrefArray = href.split('/');
        var activityID = hrefArray[hrefArray.length - 1];

        if (group_page) {
          var data;
          if ($(this).is(':checked')) {
            data = {
              checked: $(this).val(),
              activityID: activityID
            }
          }
          else {
            data = {
              unchecked: $(this).val(),
              activityID: activityID
            }
          }
          $.ajax({
            url: '/ajax/activities-bank-lpm/checked-activities',
            data: {data: JSON.stringify(data)},
            dataType: 'json',
            type: 'post'
          });
        }
        else {
          if (typeof $.cookie('activities_bank_checked') !== 'undefined') {
            var checkboxes_ids = $.cookie('activities_bank_checked').split(/,/);
          }
          else {
            checkboxes_ids = [];
          }

          if (typeof $.cookie('activities_bank_activities') !== 'undefined') {
            var activities_ids = $.cookie('activities_bank_activities').split(/,/);
          }
          else {
            activities_ids = [];
          }

          if ($(this).is(':checked')) {
            checkboxes_ids.push($(this).val());
            activities_ids.push(activityID);
          }
          else {
            checkboxes_ids.splice($.inArray($(this).val(), checkboxes_ids), 1);
            activities_ids.splice($.inArray(activityID, activities_ids), 1);
          }

          if (checkboxes_ids.length && activities_ids.length) {
            $.cookie('activities_bank_checked', checkboxes_ids);
            $.cookie('activities_bank_activities', activities_ids);
          }
          else {
            $.cookie('activities_bank_checked', []);
            $.cookie('activities_bank_activities', []);
          }
        }
      });
    }
  };
}(jQuery, Drupal, drupalSettings));
