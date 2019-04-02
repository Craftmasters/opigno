(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.h5pContentResonse = {
    attach: function (context, settings) {
      if (H5P && H5P.externalDispatcher) {
        H5P.externalDispatcher.on('xAPI', function (event) {
          const $back_btn = $('#opigno-answer-opigno-h5p-form #edit-back');
          // Check that 'back' button is initially disabled.
          const is_back_btn_disabled = $back_btn.prop('disabled');
          if (is_back_btn_disabled) {
            // Enable 'back' button.
            $back_btn.prop('disabled', false);
          }

          const $next_btn = $('#opigno-answer-opigno-h5p-form #edit-submit');
          const statement = event.data.statement;
          if (statement.verb
              && statement.verb.id === 'http://adlnet.gov/expapi/verbs/attempted'
              && statement.context
              && statement.context.contextActivities.category.length) {
            const category = statement.context.contextActivities.category[0].id;
            if (category.indexOf('H5P.CoursePresentation') !== -1) {
              // Disable the 'back' button if user is not on the first page
              // and use it to pilot H5P navigation.
              $back_btn.click(function (event) {
                const $h5p_div = $('.h5p-content');
                const $current_page = $('.h5p-footer-slide-count-current', $h5p_div);
                const current_page = parseInt($current_page.text());
                if (current_page !== 1) {
                  // Simulate click on the H5P back button.
                  const $h5p_back_btn = $('.h5p-footer-previous-slide', $h5p_div);
                  $h5p_back_btn.click();

                  // Prevent default click action.
                  event.preventDefault();
                  return false;
                }
              });

              // Disable the 'next' button if user is not on the last page
              // and use it to pilot H5P navigation.
              $next_btn.click(function (event) {
                const $h5p_div = $('.h5p-content');
                const $current_page = $('.h5p-footer-slide-count-current', $h5p_div);
                const current_page = parseInt($current_page.text());
                const $total_pages = $('.h5p-footer-slide-count-max', $h5p_div);
                const total_pages = parseInt($total_pages.text());
                if (!isNaN(current_page) && !isNaN(total_pages) && current_page !== total_pages) {
                  // Simulate click on the H5P next button.
                  const $h5p_next_btn = $('.h5p-footer-next-slide', $h5p_div);
                  $h5p_next_btn.click();

                  // Prevent default click action.
                  event.preventDefault();
                  return false;
                }
              });
            }
            else if (category.indexOf('H5P.QuestionSet') !== -1) {
              // Disable the 'back' button if user is not on the first page
              // and use it to pilot H5P navigation.
              $back_btn.click(function (event) {
                const $h5p_div = $('.h5p-content');
                const $results_page = $('.questionset-results', $h5p_div);
                const is_on_results_page = $results_page.length > 0
                    && $results_page.css('display') !== 'none';
                if (is_on_results_page) {
                  // Simulate click on the retry button.
                  $('.qs-retrybutton', $h5p_div).click();

                  // Prevent default click action.
                  event.preventDefault();
                  return false;
                }

                const $first_progress_dot = $('.progress-dot', $h5p_div).first();
                const is_on_first_page = $first_progress_dot.hasClass('current');
                if (!is_on_first_page) {
                  // Back button is an anchor link so use native DOM click.
                  const $h5p_back_btn = $('.h5p-question-prev', $h5p_div);
                  if ($h5p_back_btn.length) {
                    $h5p_back_btn.get(0).click();
                  }

                  // Prevent default click action.
                  event.preventDefault();
                  return false;
                }
              });

              // Disable the 'next' button if user is not on the result page
              // and use it to pilot H5P navigation.
              $next_btn.click(function (event) {
                const $h5p_div = $('.h5p-content');
                const $results_page = $('.questionset-results', $h5p_div);
                const is_on_results_page = $results_page.length > 0
                    && $results_page.css('display') !== 'none';
                if (!is_on_results_page) {
                  // Next button is an anchor link so use native DOM click.
                  const $h5p_next_btn = $('.h5p-question-next', $h5p_div);
                  if ($h5p_next_btn.length) {
                    $h5p_next_btn.get(0).click();
                  }

                  // Prevent default click action.
                  event.preventDefault();
                  return false;
                }
              });
            }
            else if (category.indexOf('H5P.SingleChoiceSet') !== -1) {
              // Disable the 'back' button if user is not on the first page
              // and use it to pilot H5P navigation.
              $back_btn.click(function (event) {
                const $h5p_div = $('.h5p-content');
                const $results = $('.h5p-sc-set-results', $h5p_div);
                if ($results.length) {
                  // Simulate click on the H5P retry button.
                  const $back_btn = $('.h5p-question-try-again', $h5p_div);
                  $back_btn.click();

                  // Prevent default click action.
                  event.preventDefault();
                  return false;
                }
              });

              // Disable the 'next' button if user is not on the result page
              // and use it to pilot H5P navigation.
              $next_btn.click(function (event) {
                const $h5p_div = $('.h5p-content');
                const $results = $('.h5p-sc-set-results.h5p-sc-current-slide', $h5p_div);
                if (!$results.length) {
                  // If user is not on a results page.
                  // Skip current slide by triggering a first wrong answer.
                  $('.h5p-sc-current-slide .h5p-sc-is-wrong', $h5p_div).get(0).click();

                  // Prevent default click action.
                  event.preventDefault();
                  return false;
                }
              });
            }
          }

          $back_btn.click(function (event) {
            if (is_back_btn_disabled) {
              // If 'back' button was initially disabled.
              event.preventDefault();
              return false;
            }
          });

          var score = event.getScore();
          var maxScore = event.getMaxScore();

          if (score === undefined || score === null) {
            var contentId = event.getVerifiedStatementValue([
              'object',
              'definition',
              'extensions',
              'http://h5p.org/x-api/h5p-local-content-id'
            ]);

            for (var i = 0; i < H5P.instances.length; i++) {
              if (H5P.instances[i].contentId === contentId) {
                if (typeof H5P.instances[i].getScore === 'function') {
                  score = H5P.instances[i].getScore();
                  maxScore = H5P.instances[i].getMaxScore();
                }
                break;
              }
            }
          }

          if (score !== undefined && score !== null) {
            var key = maxScore > 0 ? score / maxScore : 0;
            key = (key + 32.17) * 1.234;
            $('#activity-h5p-result').val(key);
          }

          // Store correct answer patterns.
          var object = statement.object;

          if (object) {
            $('#activity-h5p-correct-response').val(object.definition.correctResponsesPattern);
          }

          // Store user answer.
          var result = statement.result;

          if (result) {
            $('#activity-h5p-response').val(result.response);
          }



          // Store results on back, next, skip and finish
          $($next_btn).click(function () {
            storeXAPIData(getH5PInstance());
          });
          $($back_btn).click(function () {
            storeXAPIData(getH5PInstance());
          });

          if (H5P && H5P.externalDispatcher) {
            // Get xAPI data initially
            H5P.externalDispatcher.once('domChanged', function () {
              storeXAPIData(getH5PInstance(this.contentId));
            });

            // Get xAPI data every time it changes
            H5P.externalDispatcher.on('xAPI', function (event) {
              storeXAPIData(getH5PInstance(this.contentId), event);
            });
          }
        });
      }


      /**
       * Finds a H5P library instance in an array based on the content ID
       *
       * @param  {Array} instances
       * @param  {number} contentId
       * @returns {Object} Content instance
       */
      function findInstanceInArray(instances, contentId) {
        if (instances !== undefined && contentId !== undefined) {
          for (var i = 0; i < instances.length; i++) {
            if (instances[i].contentId === contentId) {
              return instances[i];
            }
          }
        }
      }

      /**
       * Finds the global instance from content id by looking through the DOM
       *
       * @param {number} [contentId] Content identifier
       * @returns {Object} Content instance
       */
      function getH5PInstance(contentId) {
        var iframes, instance = null; // returning null means no instance is found

        // No content id given, search for instance
        if (!contentId) {
          instance = H5P.instances[0];
          if (!instance) {
            iframes = document.getElementsByClassName('h5p-iframe');
            // Assume first iframe
            instance = iframes[0].contentWindow.H5P.instances[0];
          }
        }
        else {
          // Try this documents instances
          instance = findInstanceInArray(H5P.instances, contentId);
          if (!instance) {
            // Locate iframes
            iframes = document.getElementsByClassName('h5p-iframe');
            for (var i = 0; i < iframes.length; i++) {
              // Search through each iframe for content
              instance = findInstanceInArray(iframes[i].contentWindow.H5P.instances, contentId);
              if (instance) {
                break;
              }
            }
          }
        }

        return instance;
      }

      /**
       * Get xAPI data for content type and put them in a form ready for storage.
       *
       * @param {Object} instance Content type instance
       */
      function storeXAPIData(instance, event) {
        var xAPIData;

        if (instance) {
          if (instance.getXAPIData) {
            // Get data from the H5P Content Type
            xAPIData = instance.getXAPIData();
            $('#activity-h5p-xapi-data').val(JSON.stringify(xAPIData, null));
          }
        }
      }


    }
  };
}(jQuery, Drupal, drupalSettings));
