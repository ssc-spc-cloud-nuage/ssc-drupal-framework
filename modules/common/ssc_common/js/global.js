(function ($, Drupal, once, cookies) {
  "use strict";

  Drupal.behaviors.sscCommon = {};
  Drupal.behaviors.sscCommon.attach = function (context, settings) {
    once('ssc-common', document.documentElement, context).forEach(function () {
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
