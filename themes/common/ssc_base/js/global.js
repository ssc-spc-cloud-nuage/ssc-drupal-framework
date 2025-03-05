/**
 * @file
 * Addional global interactivity via the SSC Base theme.
 */

(function ($, Drupal, once) {
  "use strict";

  Drupal.behaviors.sscBase = {};
  Drupal.behaviors.sscBase.attach = function (context, settings) {
    once('ssc-base', document.documentElement, context).forEach(function () {
      // Add stretched link class to upcoming events teasers on the News landing page
      $(".upcoming-event a").addClass("stretched-link");
    });
  };
})(jQuery, Drupal, once);
