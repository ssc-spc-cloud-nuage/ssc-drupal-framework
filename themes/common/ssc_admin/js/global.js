/**
 * @file
 * Addional global interactivity.
 */

(function ($, Drupal, once) {
  "use strict";

  Drupal.behaviors.sscAdmin = {};
  Drupal.behaviors.sscAdmin.attach = function (context, settings) {
    // Improve Session fieldset labels on Event edit form - NOTE: needs to re-run after Ajax
    $('[id^="edit-field-sessions"] > [data-drupal-selector^="edit-field-sessions-widget"]').each((function () {
      const date = $(this).find('input.form-date').attr('value');
      const lang = $(this).find('div.field--name-field-session-language select option:selected').text();
      $(this).find('summary').first().html(date + " - " + lang);
    }));

    once('ssc-admin', document.documentElement, context).forEach(function () {
      // Hide Country selector on custom address for Session - painful to do with CSS
      $('#node-event-form label:contains("Country")').parent().hide();

      // Add form-required to Campaign Date
      $('.field--name-field-campaign-date .fieldset__label').addClass('form-required');
      $('.field--name-field-campaign-date h4.form-item__label:contains("Start")').addClass('form-required');
      
      // Move Node form buttons to bottom of Meta group
      $('form.node-form #edit-meta').append($('#edit-actions'));
    });
  }
})(jQuery, Drupal, once);
