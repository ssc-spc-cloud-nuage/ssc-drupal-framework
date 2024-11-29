/**
 * @file
 * Addional global interactivity.
 */

(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.sscBaseScript = {};
  Drupal.behaviors.sscBaseScript.attach = function (context, settings) {
    // Delete empty IDs in Slick Slider (causing HTML validation errors)
    $(".slick-cloned").each(function () {
      this.removeAttribute("id");
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

    // Add stretched link class to search teasers
    $(".search-result a").addClass("stretched-link");

    // Re-adds stretched link class after an AJAX search form submission
    $(document).ajaxComplete(function () {
      $(".search-result a").addClass("stretched-link");
    });

    // Add stretched link class to upcoming events teasers on the News landing page
    $(".upcoming-event a").addClass("stretched-link");

    // Fix Cancel button on VBO confirmation pages (required on Bootstrap only)
    $("form.views-bulk-operations-confirm-action button#edit-cancel").attr(
      "name",
      "op"
    );
  };
})(jQuery, Drupal);
