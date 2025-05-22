// Override Drupal's progress indicators.
(function ($, Drupal) {
  Drupal.behaviors.sscLoader = {
    attach: function (context, settings) {
      // Implement custom full-screen progress indicator
      Drupal.theme.ajaxProgressIndicatorFullscreen = function () {
        return $(`
          <div class="ssc-loader ssc-loader--fullscreen" aria-hidden="true">
            <svg viewBox="0 0 94.45 100">
              <clipPath id="ssc-loader-mask">
                <circle cx="48" cy="94" />
              </clipPath>

              <g clip-path="url(#ssc-loader-mask)">
                <path d="M92.83,33.96l-14.48,3.38-2.57-8.25-13.8,15.29,4.19-30.18-9.07,4.33L47.9,0l-8.53,19.08-10.42-4.33,4.06,29.91-13.8-15.29-2.17,7.58-15.43-3.25,4.6,17.05-6.22,2.84,9.61,9.47c21.65-19.49,54.53-19.62,76.32-.14l8.53-8.53-6.5-3.92,4.74-16.24.14-.27Z"/>
                <path d="M15.29,68.34l5.41,5.41c15.56-13.53,38.7-13.53,54.26,0l5.41-5.41c-18.54-16.51-46.55-16.37-65.09,0Z"/>
                <path d="M26.39,79.3l5.41,5.41c9.61-7.17,22.6-7.17,32.21,0l5.41-5.41c-12.45-10.42-30.72-10.42-43.17,0h.14Z"/>
              </g>

              <path d="M47.9,86.74c-3.65,0-6.63,2.98-6.63,6.63s2.98,6.63,6.63,6.63,6.63-2.98,6.63-6.63-2.98-6.63-6.63-6.63h0Z" fill="white" />
            </svg>
          </div>
        `);
      };

      // @TODO: Implement custom throbber
      // Drupal.theme.ajaxProgressIndicatorThrobber = function () {};
    },
  };
})(jQuery, Drupal);
