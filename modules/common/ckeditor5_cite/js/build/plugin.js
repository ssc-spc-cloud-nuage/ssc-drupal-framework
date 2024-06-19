/**
 * @file
 * Plugin to insert cite elements.
 *
 * Created out of the CKEditor Plugin SDK:
 * http://docs.ckeditor.com/#!/guide/plugin_sdk_sample_1
 */

(function ($, Drupal, CKEDITOR) {
  /**
   * Gets the required attributes for cites in the current element.
   *
   * @param  {CKEDITOR.dom.element} element - The CKEditor selected cite element.
   *
   * @return {object} - The list of attributes.
   */
  function parseAttributes(element) {
    var parsedAttributes = {};
    var domElement = element.$;
    var attribute, attributeName;

    for (var attrIndex = 0; attrIndex < domElement.attributes.length; attrIndex++) {
      attribute = domElement.attributes.item(attrIndex);
      attributeName = attribute.nodeName.toLowerCase();

      // data-cke-* attributes are automatically added by CKEditor. Ignore them.
      if (attributeName.indexOf('data-cke-') === 0) {
        continue;
      }

      // Only store the raw attribute if there isn't already a cke-saved- version of it.
      parsedAttributes[attributeName] = element.data('cke-saved-' + attributeName) || attribute.nodeValue;
    }

    // Remove all cke_* classes.
    if (parsedAttributes.class) {
      parsedAttributes.class = CKEDITOR.tools.trim(parsedAttributes.class.replace(/cke_\S+/, ''));
    }

    // Set the "text" attribute.
    parsedAttributes.text = domElement.innerText;

    return parsedAttributes;
  }

  /**
   * Gets the currently selected cite element in the CKEditor.
   *
   * @param {CKEDITOR.editor} editor - The CKEditor object.
   *
   * @return {CKEDITOR.dom.element|null} - The CKEditor selected cite element.
   */
  function getSelectedCite(editor) {
    var selection = editor.getSelection();
    var selectedElement = selection.getSelectedElement();
    if (selectedElement && selectedElement.is('cite')) {
      return selectedElement;
    }

    var range = selection.getRanges(true)[0];

    if (range) {
      range.shrink(CKEDITOR.SHRINK_TEXT);
      return editor.elementPath(range.getCommonAncestor()).contains('cite', 1);
    }
    return null;
  }


  // Register the plugin within the editor.
  CKEDITOR.plugins.add('cite', {
    lang: 'en,fr',

    // Register the icons.
    icons: 'cite',

    // The plugin initialization logic goes inside this method.
    init: function (editor) {
      var lang = editor.lang.cite;

      // Define an editor command that opens our dialog.
      editor.addCommand('cite', {
        // Allow cite tag with optional lang.
        allowedContent: 'cite[lang]',
        // Require cite tag to be allowed to work.
        requiredContent: 'cite',
        contentForms: [
          'cite',
          'acronym'
        ],
        exec(editor) {
          // Get existing values if an cite element is currently selected.
          var citeElement = getSelectedCite(editor);
          var existingValues = citeElement && citeElement.$
            ? parseAttributes(citeElement)
            : {text: editor.getSelection().getSelectedText()};

          /**
           * Saves the dialog submission,
           * inserting the information into the CKEditor DOM.
           *
           * @param {object} returnedValues - The returned values from the Drupal form.
           */
          var saveCallback = function(returnedValues) {
            // If there isn't an existing cite element, create it.
            if (!citeElement && returnedValues.attributes.text) {
              var selection = editor.getSelection();
              var range = selection.getRanges(1)[0];

              if (range.collapsed) {
                var text = new CKEDITOR.dom.text(
                  returnedValues.attributes.text,
                  editor.document,
                )

                range.insertNode(text);
                range.selectNodeContents(text);
              }

              delete returnedValues.attributes.text;

              var style = new CKEDITOR.style({
                element: 'cite',
                attributes: returnedValues.attributes,
              });
              style.type = CKEDITOR.STYLE_INLINE;
              style.applyToRange(range);
              range.select();

              citeElement = getSelectedCite(editor);
            } else if (citeElement) {
              if (returnedValues.attributes.text) {
                citeElement.$.innerText = returnedValues.attributes.text;
              } else {
                citeElement.$.replaceWith(citeElement.$.innerText);
              }

              delete returnedValues.attributes.text;

              Object.keys(returnedValues.attributes || {}).forEach(attrName => {
                if (returnedValues.attributes[attrName].length > 0) {
                  var value = returnedValues.attributes[attrName];

                  citeElement.data('cke-saved-' + attrName, value);
                  citeElement.setAttribute(attrName, value);
                } else {
                  citeElement.removeAttribute(attrName);
                }
              });
            }
          }

          var dialogSettings = {
            // Since CKEditor loads the JS file, Drupal.t() will not work.
            // The config in the plugin settings can be translated server-side.
            title: citeElement
              ? lang.menuItemTitle
              : lang.buttonTitle,
            dialogClass: 'ckeditor-cite-dialog',
          };

          // Use the "Drupal way" of opening a dialog.
          Drupal.ckeditor.openDialog(
            editor,
            Drupal.url('ckeditor-cite/dialog/cite/' + editor.config.drupal.format),
            existingValues,
            saveCallback,
            dialogSettings,
          );
        }
      });

      // Create a toolbar button that executes the above command.
      editor.ui.addButton('cite', {

        // The text part of the button (if available) and tooptip.
        label: lang.buttonTitle,

        // The command to execute on click.
        command: 'cite',

        // The button placement in the toolbar (toolbar group name).
        toolbar: 'insert',

        // The path to the icon.
        icon: this.path + 'icons/cite.png'
      });

      if (editor.contextMenu) {
        editor.addMenuGroup('citeGroup');
        editor.addMenuItem('citeItem', {
          label: lang.menuItemTitle,
          icon: this.path + 'icons/cite.png',
          command: 'cite',
          group: 'citeGroup'
        });

        editor.contextMenu.addListener(function (element) {
          if (element.getAscendant('cite', true)) {
            return { citeItem: CKEDITOR.TRISTATE_OFF };
          }
        });
      }
    }
  });
})(jQuery, Drupal, CKEDITOR);
