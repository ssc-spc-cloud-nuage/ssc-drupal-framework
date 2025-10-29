/**
 * @file
 * Addional global interactivity via the SSC Base theme.
 */

(function ($, Drupal, once) {
  "use strict";

  Drupal.behaviors.sscBase = {
    attach: (context, settings) => {
      once("ssc-base", document.documentElement, context).forEach(function () {
        // Add stretched link class to upcoming events teasers on the News landing page
        $(".upcoming-event a").addClass("stretched-link");

        // Remove the scrollbar's width from the total width of `container-full-width`
        let scrollbarWidth = 0;
        $(document).ready(function () {
          const observer = new ResizeObserver((entries) => {
            const newScrollbarWidth =
              window.innerWidth - document.documentElement.clientWidth;

            if (newScrollbarWidth === scrollbarWidth) return;

            scrollbarWidth = newScrollbarWidth;

            document.documentElement.style.setProperty(
              "--size-scrollbar",
              `${scrollbarWidth}px`
            );
          });

          observer.observe(document.documentElement);
        });

        // Apply background image added via the `data-image-url` data attribute
        $(document).ready(function () {
          document.querySelectorAll(".bg-image").forEach((el) => {
            const imageUrl = el.getAttribute("data-image-url");

            if (imageUrl) {
              el.style.backgroundImage = `url('${imageUrl}')`;
            }
          });
        });
      });
    },
  };
})(jQuery, Drupal, once);
