(function ($, Drupal) {
  Drupal.behaviors.opignoStatisticsUser = {
    attach: function (context) {
      const $training_details_link = $('.trainings-list .training a.details', context);
      $training_details_link.once('click').click(function (e) {
        e.preventDefault();

        const $this = $(this);
        const user_id = $this.attr('data-user');
        const training_id = $this.attr('data-training');

        const training_selector = '.training[data-training="' + training_id + '"]';
        const active_selector = '.training-active[data-training="' + training_id + '"]';
        const details_selector = '.training-details[data-training="' + training_id + '"]';

        const $table = $this.parents('.trainings-list');
        const $training = $table.find(training_selector);
        const $training_active = $table.find(active_selector);
        const $training_details = $table.find(details_selector);
        if ($training_details.length === 0) {
          if (!$this.attr('data-ajax-loading')) {
            $this.attr('data-ajax-loading', true);
            Drupal.ajax({
              url: `/ajax/statistics/user/${user_id}/training/${training_id}`,
            }).execute()
                .done(() => {
                  const $training_active = $table.find(active_selector);
                  const $training_details = $table.find(details_selector);

                  // Remove div wrapper around the AJAX-loaded content.
                  const $parent = $training_details.parent();
                  if ($parent.prop('tagName') === 'DIV') {
                    $parent.replaceWith($parent.contents());
                  }

                  $training.hide();
                  $training_active.show();
                  $training_details.show();
                })
                .always(() => {
                  $this.removeAttr('data-ajax-loading');
                });
          }
        }
        else {
          $training.hide();
          $training_active.show();
          $training_details.show();
        }

        return false;
      });

      const $trainings_list = $('.trainings-list', context);
      $trainings_list.once('click_training_close').on('click', '.training-active .training-close', function (e) {
        e.preventDefault();

        const $this = $(this);
        const training_id = $this.attr('data-training');

        const training_selector = '.training[data-training="' + training_id + '"]';
        const active_selector = '.training-active[data-training="' + training_id + '"]';
        const details_selector = '.training-details[data-training="' + training_id + '"]';

        const $table = $this.parents('.trainings-list');
        const $training = $table.find(training_selector);
        const $training_active = $table.find(active_selector);
        const $training_details = $table.find(details_selector);

        $training.show();
        $training_active.hide();
        $training_details.hide();

        return false;
      });

      $trainings_list.once('click_course_open').on('click', '.course .course-details-open', function (e) {
        e.preventDefault();

        const $this = $(this);
        const user_id = $this.attr('data-user');
        const training_id = $this.attr('data-training');
        const course_id = $this.attr('data-id');

        const course_selector = '.course[data-training="' + training_id + '"][data-id="' + course_id + '"]';
        const active_selector = '.course-active[data-training="' + training_id + '"][data-id="' + course_id + '"]';
        const details_selector = '.course-details[data-training="' + training_id + '"][data-id="' + course_id + '"]';

        const $table = $this.parents('.training-modules-list');
        const $course = $table.find(course_selector);
        const $course_active = $table.find(active_selector);
        const $course_details = $table.find(details_selector);
        if ($course_details.length === 0) {
          if (!$this.attr('data-ajax-loading')) {
            $this.attr('data-ajax-loading', true);
            Drupal.ajax({
              url: `/ajax/statistics/user/${user_id}/training/${training_id}/course/${course_id}`,
            }).execute()
                .done(() => {
                  const $course_active = $table.find(active_selector);
                  const $course_details = $table.find(details_selector);

                  // Remove div wrapper around the AJAX-loaded content.
                  const $parent = $course_details.parent();
                  if ($parent.prop('tagName') === 'DIV') {
                    $parent.replaceWith($parent.contents());
                  }

                  $course.hide();
                  $course_active.show();
                  $course_details.show();
                })
                .always(() => {
                  $this.removeAttr('data-ajax-loading');
                });
          }
        }
        else {
          $course.hide();
          $course_active.show();
          $course_details.show();
        }

        return false;
      });

      $trainings_list.once('click_course_close').on('click', '.course-active .course-close', function (e) {
        e.preventDefault();

        const $this = $(this);
        const training_id = $this.attr('data-training');
        const course_id = $this.attr('data-id');

        const course_selector = '.course[data-training="' + training_id + '"][data-id="' + course_id + '"]';
        const active_selector = '.course-active[data-training="' + training_id + '"][data-id="' + course_id + '"]';
        const details_selector = '.course-details[data-training="' + training_id + '"][data-id="' + course_id + '"]';

        const $table = $this.parents('.training-modules-list');
        const $course = $table.find(course_selector);
        const $course_active = $table.find(active_selector);
        const $course_details = $table.find(details_selector);

        $course.show();
        $course_active.hide();
        $course_details.hide();

        return false;
      });

      $trainings_list.once('click_training_module_open').on('click', '.training-module-details-open', function (e) {
        e.preventDefault();

        const $panels = $('.module_panel', context);
        $panels.hide();

        const $this = $(this);
        const $wrapper = $this.parents('td');
        const user = $this.attr('data-user');
        const training = $this.attr('data-training');
        const module = $this.attr('data-id');
        const panelSelector = `#module_panel_${training}_${module}[data-ajax-loaded]`;
        const $panel = $wrapper.find(panelSelector);
        if ($panel.length === 0) {
          if (!$this.attr('data-ajax-loading')) {
            $this.attr('data-ajax-loading', true);
            Drupal.ajax({
              url: `/ajax/statistics/user/${user}/training/${training}/module/${module}`,
            }).execute()
                .done(() => {
                  $wrapper.find(panelSelector).show();
                })
                .always(() => {
                  $this.removeAttr('data-ajax-loading');
                });
          }
        }
        else {
          $panel.show();
        }

        return false;
      });

      $trainings_list.once('click_course_module_open').on('click', '.course-module-details-open', function (e) {
        e.preventDefault();

        const $panels = $('.module_panel', context);
        $panels.hide();

        const $this = $(this);
        const $wrapper = $this.parents('td');
        const user = $this.attr('data-user');
        const training = $this.attr('data-training');
        const course = $this.attr('data-course');
        const module = $this.attr('data-id');
        const panelSelector = `#module_panel_${training}_${course}_${module}[data-ajax-loaded]`;
        const $panel = $wrapper.find(panelSelector);
        if ($panel.length === 0) {
          if (!$this.attr('data-ajax-loading')) {
            $this.attr('data-ajax-loading', true);
            Drupal.ajax({
              url: `/ajax/statistics/user/${user}/training/${training}/course/${course}/module/${module}`,
            }).execute()
                .done(() => {
                  $wrapper.find(panelSelector).show();
                })
                .always(() => {
                  $this.removeAttr('data-ajax-loading');
                });
          }
        }
        else {
          $panel.show();
        }

        return false;
      });

      $trainings_list.once('click_module_close').on('click', '.module_panel_close', function (e) {
        e.preventDefault();

        const $panels = $('.module_panel', context);
        $panels.hide();

        return false;
      });
    },
  };
}(jQuery, Drupal, drupalSettings));
