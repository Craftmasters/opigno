(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.opignoLearningPathTrainingContent = {
    attach: function (context, settings) {
      var $step_show = $('.lp_step_show', context);
      var $step_hide = $('.lp_step_hide', context);

      $step_show.once('click').click(function (e) {
        e.preventDefault();

        var $parent = $(this).parent('.lp_step');

        if (!$parent) {
          return false;
        }

        $parent.find('.lp_step_details_wrapper').show();
        $parent.find('.lp_step_show').hide();
        $parent.find('.lp_step_hide').show();

        return false;
      });

      $step_hide.once('click').click(function (e) {
        e.preventDefault();

        var $parent = $(this).parent('.lp_step');

        if (!$parent) {
          return false;
        }

        $parent.find('.lp_step_details_wrapper').hide();
        $parent.find('.lp_step_show').show();
        $parent.find('.lp_step_hide').hide();

        return false;
      });

      // Workspace should be visible for a correct Moxtra load.
      const $workspace = $('#collaborative-workspace', context);
      const $workspace_container = $('#collaborative_workspace_container', $workspace);
      if ($workspace_container.length) {
        const workspace = $workspace.get(0);
        // Make workspace visible outside the screen.
        workspace.style.display = 'block';
        workspace.style.position = 'fixed';
        workspace.style.top = '100%';

        $workspace.on('moxtra_loaded', '#collaborative_workspace_container', function (e) {
          // Unset inline styles on the workspace.
          workspace.removeAttribute('style');
        });
      }
    },
  };
}(jQuery, Drupal, drupalSettings));
