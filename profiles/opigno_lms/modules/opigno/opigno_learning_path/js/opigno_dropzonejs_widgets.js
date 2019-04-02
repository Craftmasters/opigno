(function ($, Drupal) {
  Drupal.behaviors.opignoDropzonejsWidgets = {
    attach: function (context, settings) {

      // Make form auto submission.
      var targetNode = document.querySelector("form.entity-browser-form #edit-actions .button");
      // Options for the observer (which mutations to observe)
      var config = {attributes: true, subtree: true};
      // Create an observer instance
      var observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
          if (mutation.attributeName === 'disabled') {
            // Check if file is loaded.
            var value = $("input[name='upload[uploaded_files]']").attr('value');
            if (value.length) {
              // Trigger click on submit button.
              $("form.entity-browser-form").find("#edit-actions .button").click();
            }
          }
        });
      });

      // Start observing the target node for configured mutations
      observer.observe(targetNode, config);

      // observer.disconnect();

    }
  }
}(jQuery, Drupal));
