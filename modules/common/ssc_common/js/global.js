/**
 * @file
 * Addional global interactivity via the SSC Common module.
 */

(function ($, Drupal, once) {
  "use strict";

  Drupal.behaviors.sscCommonGlobal = {};
  Drupal.behaviors.sscCommonGlobal.attach = function (context, settings) {
    once('ssc-common-global', document.documentElement, context).forEach(function () {
      // Override jQuery UI dialog string to become translatable
      $.widget("ui.dialog", $.ui.dialog, {
        options: {
          closeText: Drupal.t("Close")
        }
      });

      // Fix Cancel button on VBO confirmation pages (required on Bootstrap only)
      $("form.views-bulk-operations-confirm-action button#edit-cancel").attr(
        "name",
        "op"
      );
    });
  }
})(jQuery, Drupal, once, window.Cookies);
