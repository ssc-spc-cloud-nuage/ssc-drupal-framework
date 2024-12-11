/**
 * @file
 * Addional Site theme-specific interactivity.
 */

(function ($, Drupal, once) {
  "use strict";

  Drupal.behaviors.sscCommonSite = {};
  Drupal.behaviors.sscCommonSite.attach = function (context, settings) {
    once('ssc-common-site', document.documentElement, context).forEach(function () {
      // Add IDs for TOC H2/H3
      let level1 = 0;
      let level2 = 0;
      $('h2, h3', '.toc-section').each(function() {
        // Only add ID when there is no parent element with the .toc-ignore class
        if ($(this).parents('.toc-ignore').length === 0) {
          const id = $(this).attr('id');

          if ($(this).prop('tagName') === 'H2') {
            level1++;
            level2 = 0;
          }
          else {
            level2++;
          }

          if (id) {
            $(this).attr('id', `${id}-01`);
          }
          else {
            $(this).attr('id', `section-${level1}-${level2}`);
          }
        }
      });

      // Set <details> elements to automatically open on print if their <summary> contains a heading
      $('details', '.section-main').each(function () {
        const detailsEl = this;
        const hasHeading = $('summary', detailsEl).find('h2, h3, h4, h5, h6').length > 0;

        if (hasHeading) {
          // Open <details> when the print modal shows up
          $(window).on('beforeprint', function () {
            $(detailsEl).attr('open', '');
          });

          // Close <details> when the print modal is dismissed
          $(window).on('afterprint', function () {
            $(detailsEl).removeAttr('open');
          });
        }
      });

      // Delete empty IDs in Slick Slider (causing HTML validation errors)
      $(".slick-cloned").each(function () {
        this.removeAttribute("id");
      });

      // Fix ICS links on Event pages
      $("a.ics-link").each(function() {
        let href = $(this).attr('href');
        href = href.replace("text/calendar", "data:text/calendar");
        $(this).attr('href', href);
      });
    });
  }
})(jQuery, Drupal, once);
