(function ($, Drupal, cookies) {
  Drupal.behaviors.ssc_common = {};
  var init = false;

  Drupal.behaviors.ssc_common.attach = function (context, settings) {
    // Improve Session fieldset labels on Event edit form - NOTE: needs to run after Ajax "remove" button
    $('[id^="edit-field-sessions"] > [data-drupal-selector^="edit-field-sessions-widget"]').each((function() {
      var date = $(this).find('input.form-date').attr('value');
      var lang = $(this).find('div.field--name-field-session-language select option:selected').text();
      $(this).find('summary').first().html(date + " - " + lang);
    }));

    if (init) {
      return;
    }
    init = true;

    // Add IDs for TOC H2/H3
    var level1 = 0;
    var level2 = 0;
    $('h2, h3', '.toc-section').each(function() {
      // Only add ID when there is no parent element with the .toc-ignore class
      if ($(this).parents('.toc-ignore').length === 0) {
        var id = $(this).attr('id');

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

    function setDateFilter(date) {
      $('input#edit-date-min--2').val(date);
      $('input#edit-date-max--2').val(date);
      $('.landing-page .views-exposed-form button[id^="edit-submit"]').click();
    }

    // Hack to fix Reset button not clearing filters on Events page.
    $('.landing-page .views-exposed-form button[id^="edit-reset"]').click(function() {
      setDateFilter(null);
      $('.landing-page .views-exposed-form input.form-checkbox').prop('checked', false);
      $('.landing-page .views-exposed-form input.form-text').prop('value', '');
      $('.landing-page .views-exposed-form select').prop('value', 'All');

      // Clear Query on lang switcher
      $("#user-profile-block-switcher a")[0].search = "";
    });

    $("a.ics-link").each(function() {
      var href = $(this).attr('href');
      href = href.replace("text/calendar", "data:text/calendar");
      $(this).attr('href', href);
    });

    // Hide Country selector on Custom address for Session - painful to do with CSS
    $('#node-event-form label:contains("Country")').parent().hide();
  };

  // Click outside menu closes menu
  $(document).mouseup(function(e) {
    var container = $("nav.gcweb-menu");
    // if the target of the click isn't the container nor a descendant of the container
    if (!container.is(e.target) && container.has(e.target).length === 0) {
      $("nav.gcweb-menu button").attr('aria-expanded', 'false');
    }
  });

  // Set last dashboard tab
  $('li.tabs__tab').click(function() {
    cookies.set('dashboard_tab', $(this).find('a').attr('id'));
  });

  // Move Node form buttons to bottom of Meta group
  $('form.node-form #edit-meta').append($('#edit-actions'));

  // Add form-required to Campaign Date
  $('.field--name-field-campaign-date .fieldset__label').addClass('form-required');
  $('.field--name-field-campaign-date h4.form-item__label:contains("Start")').addClass('form-required');

  // Override jQuery UI dialog string to become translatable
  $.widget("ui.dialog", $.ui.dialog, {
    options: {
      closeText: Drupal.t("Close")
    }
  });

})(jQuery, Drupal, window.Cookies);
