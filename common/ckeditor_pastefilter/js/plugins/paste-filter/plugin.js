/**
 * @file
 * Paste Filter CKEditor plugin.
 *
 * Plugin for filtering elements pasted into the CKEditor editing area.
 */
(function(Drupal) {

  'use strict';

  CKEDITOR.on('instanceReady', function(ev) {
    var blockTags = ['div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'pre', 'li', 'blockquote', 'ul', 'ol',
      'table', 'thead', 'tbody', 'tfoot', 'td', 'th',];

    for (var i = 0; i < blockTags.length; i++) {
      ev.editor.dataProcessor.writer.setRules(blockTags[i], {
        indent: false,
        breakBeforeOpen: true,
        breakAfterOpen: false,
        breakBeforeClose: false,
        breakAfterClose: true,
      });
    }
  });

  CKEDITOR.plugins.add('ckeditor_pastefilter', {

    init: function(editor) {

      // Paste from clipboard:
      editor.on('paste', function(e) {
        var data = e.data,
          html = (data.html || (data.type && data.type == 'html' && data.dataValue));
        if (!html)
          return;

        // add extra Config rule
        var rules = e.editor.config.ckeditor_pastefilter;
        for (var key in rules) {
          if (rules.hasOwnProperty(key)) {
          html = html.replace(new RegExp(key, 'gimsu'), rules[key]);      
          }
        }

        if (e.data.html)
          e.data.html = html;
        else
          e.data.dataValue = html;
      });
    } //Init
  });
}(Drupal));