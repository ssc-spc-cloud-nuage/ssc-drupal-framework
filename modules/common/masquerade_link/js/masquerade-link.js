(function ($, Drupal) {
  Drupal.behaviors.appendMasqueradeIconAndHandleAction = {
    attach: function (context, settings) {
      once('appendMasqueradeIcon', 'a.masquerade-link, .masquerade-link > a', context).forEach((element) => {
        // Determine the language prefix (e.g., 'fr' in /fr/user/123).
        const pathParts = window.location.pathname.split('/');
        let langPrefix = '';
        if (drupalSettings.path.prefix) {
          langPrefix = drupalSettings.path.prefix; // Typically includes trailing slash
        } else if (pathParts.length > 1 && pathParts[1].length === 2) {
          langPrefix = '/' + pathParts[1] + '/';
        } else {
          langPrefix = '/';
        }

        var uid = $(element).attr('href').split('/')[3];
        if (uid) {
          var icon = '<i class="fas fa-user-secret masquerade-fa-icon"></i>';
          var masqueradeLink = $('<a href="#" class="masquerade-icon" data-uid="' + uid + '">' + icon + '</a>');
          $(element).after(masqueradeLink);

          masqueradeLink.on('click', function (e) {
            e.preventDefault();
            var uid = $(this).data('uid');

            // 🔄 Show spinner (you can customize this)
            const $spinner = $('<div class="masquerade-spinner">Switching accounts...</div>').appendTo('body');
            $spinner.css({
              position: 'fixed',
              top: '50%',
              left: '50%',
              transform: 'translate(-50%, -50%)',
              padding: '1em 2em',
              background: '#fff',
              border: '2px solid #ccc',
              borderRadius: '8px',
              zIndex: 9999,
            });

            // Fetch CSRF Token first
            fetch(Drupal.url('masquerade-token/' + uid))
              .then(response => response.json()) // Parse response as JSON
              .then(data => {
                var token = data.token;
                var masqueradeUrl = Drupal.url('user/' + uid + '/masquerade?token=' + token);

                // Perform the masquerade action with the token in the URL
                return fetch(masqueradeUrl, {
                  method: 'POST',
                  credentials: 'same-origin'
                });
              })
              .then(response => {
                if (response.ok) {
                  window.location.href = langPrefix + 'user/' + uid;
                } else {
                  alert('Masquerade failed. Please check permissions and ensure the action is configured correctly.');
                }
              })
              .catch(error => {
                console.error('Masquerade request failed:', error);
                alert('Masquerade request failed. See console for details.');
              });
          });
        }
      });
    }
  };
})(jQuery, Drupal);

