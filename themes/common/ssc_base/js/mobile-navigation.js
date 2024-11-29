/**
 * @file
 * Addional interactivity for the mobile navigation.
 */

(function ($, Drupal, once) {
  "use strict";

  Drupal.behaviors.mobileNavigation = {};
  Drupal.behaviors.mobileNavigation.attach = function (context, settings) {
    once('mobile-navigation', document.documentElement, context).forEach(function () {
      // Set unique IDs for main menu items in the mobile dock
      $(".mobile-dock .gcweb-menu [id]").each(function () {
        const currentId = this.getAttribute("id");
        this.setAttribute("id", `${currentId}-mobile`);
      });

      // Set aria-controls attributes to match mobile IDs
      $(".mobile-dock .gcweb-menu [aria-controls]").each(function () {
        const currentAriaControls = this.getAttribute("aria-controls");
        this.setAttribute("aria-controls", `${currentAriaControls}-mobile`);
      });

      // Set unique IDs for exposed forms in the mobile dock
      $('.mobile-dock [id^="views-exposed-form"]').each(function () {
        const currentId = this.getAttribute("id");
        this.setAttribute("id", `${currentId}-mobile`);
      });

      // Hide/show mobile navbar on scroll event
      let lastScrollTop = 0;
      $(window).scroll(function (event) {
        const st = $(this).scrollTop();
        const mobileNavbarHeight = 80;
        const navbarTop = $(".navbar-top", "#navbar");

        if (window.visualViewport.pageTop > 0) {
          navbarTop.addClass("navbar-top--shadow");
        } else {
          navbarTop.removeClass("navbar-top--shadow");
        }

        if (
          st > lastScrollTop &&
          window.visualViewport.pageTop > mobileNavbarHeight
        ) {
          // Scrolling down
          navbarTop.addClass("navbar-top--hidden");
        } else {
          // Scrolling up
          navbarTop.removeClass("navbar-top--hidden");
        }
        lastScrollTop = st;
      });

      // Set all GCWeb menu tabs other than the current tab to dynamically collapse
      $(".main-menu-tab > a", ".mobile-dock").click(function () {
        var clickedTab = $(this);
        $(".main-menu-tab > a", ".mobile-dock").each(function () {
          var currentTab = $(this);
          if (currentTab[0] !== clickedTab[0]) {
            currentTab.attr("aria-expanded", "false");
          }
        });
      });
    });
  };
})(jQuery, Drupal, once);
