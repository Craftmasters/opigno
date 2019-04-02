/* eslint func-names: ["error", "never"] */

import 'bootstrap';
import 'iframe-resizer';

/**
 * All Drupal Platon JavaScript APIs are contained in this namespace.
 *
 * @namespace
 */
(function ($, Drupal, drupalSettings) {
  Drupal.platon = {
    settings: drupalSettings.platon || {
      homepageSlideSpeed: 2000,
    },

    getViewport() {
      return {
        width: Math.max(document.documentElement.clientWidth, window.innerWidth || 0),
        height: Math.max(document.documentElement.clientHeight, window.innerHeight || 0),
      };
    },

    frontpageSlider(context) {
      if ($('body.anonymous-slider', context).length) {
        let nbSlides = 0;
        let currentSlide = 0;

        $('.anonymous-slider .slider-item').each(function () {
          $(this).attr('data-id', nbSlides);
          if (nbSlides) {
            $(this)
              .addClass('hide')
              .fadeOut();
          }
          nbSlides += 1;
        });

        setInterval(() => {
          if (currentSlide < nbSlides - 1) {
            currentSlide += 1;
          }
          else {
            currentSlide = 0;
          }

          $(`.anonymous-slider .slider-item:not([data-id="${currentSlide}"])`)
            .addClass('hide')
            .fadeOut();
          $(`.anonymous-slider .slider-item[data-id="${currentSlide}"]`)
            .removeClass('hide')
            .fadeIn();
        }, Drupal.platon.settings.homepageSlideSpeed);
      }
    },

    anonymousUserForms(context) {
      if (!$('body.anonymous-slider').length) {
        return;
      }

      $('#user-sidebar a', context).once().click(function (e) {
        const href = $(this).attr('href');
        if ($(`.form-wrapper[data-target="${href}"]`).length) {
          e.preventDefault();
          $('.form-wrapper[data-target]').hide();
          $(`.form-wrapper[data-target="${href}"]`).show();
        }
      });
    },

    searchBar(context) {
      $('.search-trigger a', context).once().click(function (e) {
        e.preventDefault();

        if ($(this).hasClass('open')) {
          $(this).removeClass('open');
          $('#search-form', context).hide();
        }
        else {
          $(this).addClass('open');
          $('#search-form', context).show();
          $('#search-form form input[type="search"]', context).focus();
        }
      });
    },

    trainingCatalog(context) {
      $('body.page-catalogue .views-exposed-form fieldset#edit-sort-by--wrapper legend', context).once().click(function () {
        if ($(this).hasClass('active')) {
          $(this).removeClass('active');
        }
        else {
          $(this).addClass('active');
        }
      });
    },

    privateMessageRecipients(context) {
      const pmc = $('.private-message-recipients', context);

      pmc.each(function () {
        const lh = parseInt($(this).css('line-height'), 10);
        $(this).css('max-height', lh);

        if ($(this).find('.content').height() > lh) {
          $(this).css('max-height', lh).addClass('short').append('<a href="#" class="expander">...</a>');
          $(this).find('.expander').on('click', function () {
            $(this).closest('.private-message-recipients').css('max-height', 'none').removeClass('short');
            $(this).hide();
            return false;
          });
        }
      });
    },

    stepsVisibility(context) {
      if (!$('div#block-lp-steps-block', context).length) {
        return;
      }

      const defaultMainClass = $('#content', context).attr('class');
      $('#sidebar-first', context).hide();
      $('#content', context).addClass('col-lg-12');

      // Add trigger
      $('#main div#edit-actions', context).prepend('<a href="#" id="lp-steps-trigger" class="btn btn-link mr-auto">show</a>');

      // Handle trigger clicks
      $('a#lp-steps-trigger', context).once().click((e) => {
        e.preventDefault();

        if ($('a#lp-steps-trigger', context).hasClass('open')) {
          $('a#lp-steps-trigger', context).removeClass('open');
          $('#sidebar-first', context).hide();
          $('#content', context).addClass('col-lg-12');
        }
        else {
          $('a#lp-steps-trigger', context).addClass('open');
          $('#sidebar-first', context).show();
          $('#content', context).attr('class', defaultMainClass);
        }
      });
    },

    formatTFTOperations(context) {
      const $td = $('div#documents-library table tbody tr > td:last-child', context);

      $td.each(function () {
        if ($(this).hasClass('js-formatted')) {
          return;
        }

        $(this).addClass('js-formatted');

        let html = '<div class="btn-group operations">';
        html += '<button class="btn btn-light dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown"></button>';
        html += '<div class="dropdown-menu dropdown-menu-right">';
        $(this).find('a').each(function () {
          $(this).removeClass('ops-link');
          html += $(this)[0].outerHTML;
        });
        html += '</div>';
        html += '</div>';

        $(this).html(html);
      });
    },

    mobileMenu(context) {
      const $toggler = $('button.navbar-toggler', context);
      const $nav = $('div#menu-wrapper', context);

      $toggler.once().click(() => {
        if ($toggler.hasClass('open')) {
          $toggler.removeClass('open');
          $nav.removeClass('show');
        }
        else {
          $toggler.addClass('open');
          $nav.addClass('show');
        }
      });
    },

    fileWidget(context) {
      $('.opigno-file-widget-wrapper', context).each(function () {
        if ($(this).find('input[type="hidden"] + span.file').length) {
          $(this).addClass('not-empty');
        }
        else {
          $(this).removeClass('not-empty');
        }
      });
    },

    packageFileInput(context) {
      $(document, context).on('change', 'input[name="files[package]"]', function (e) {
        const $label = $(this).next('.description');
        const fileName = e.target.value.split('\\').pop();

        if (!$label.data('default-value')) {
          $label.data('default-value', $label.text());
        }

        if (fileName) {
          $label.text(fileName);
        }
        else {
          $label.text($label.data('default-value'));
        }
      });
    },

    viewOpignoActivitiesBank(context) {
      if ($('.view-opigno-activities-bank-lp-interface', context).length) {
        let $pager = $('nav[aria-labelledby="pagination-heading"]', context);
        $pager.children('ul.pager').css('margin-bottom', '-1rem');
        $('form#views-form-opigno-activities-bank-lp-interface-default > table', context).after($pager);
      }
    },
  };

  Drupal.behaviors.platon = {
    attach(context) {
      Drupal.platon.frontpageSlider(context);
      Drupal.platon.anonymousUserForms(context);
      Drupal.platon.searchBar(context);
      Drupal.platon.trainingCatalog(context);
      Drupal.platon.privateMessageRecipients(context);
      Drupal.platon.stepsVisibility(context);
      Drupal.platon.mobileMenu(context);
      Drupal.platon.fileWidget(context);
      Drupal.platon.packageFileInput(context);
      Drupal.platon.viewOpignoActivitiesBank(context);

      $('iframe').iFrameResize();

      $('a[href="#documents-library"]', context).once().click(() => {
        Drupal.platon.formatTFTOperations(context);
      });

      $(document).ajaxSuccess(() => {
        Drupal.platon.formatTFTOperations(context);
      });
    },
  };
}(window.jQuery, window.Drupal, window.drupalSettings));
