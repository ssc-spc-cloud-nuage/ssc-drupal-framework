/**
 * @file
 * Plugin to insert cite elements.
 *
 * Created out of the CKEditor Plugin SDK:
 * http://docs.ckeditor.com/#!/guide/plugin_sdk_sample_1
 */

// eslint-disable-next-line func-names
(function ($, Drupal, CKEDITOR) {
  /**
   * Gets the required attributes for cites in the current element.
   *
   * @param  {CKEDITOR.dom.element} element - The CKEditor selected cite element.
   *
   * @return {object} - The list of attributes.
   */
  function parseAttributes(element) {
    const parsedAttributes = {};
    const domElement = element.$;
    let attribute;
    let attributeName;

    for (
      let attrIndex = 0;
      attrIndex < domElement.attributes.length;
      attrIndex++
    ) {
      attribute = domElement.attributes.item(attrIndex);
      attributeName = attribute.nodeName.toLowerCase();

      // data-cke-* attributes are automatically added by CKEditor. Ignore them.
      if (attributeName.indexOf('data-cke-') === 0) {
        continue; // eslint-disable-line no-continue
      }

      // Only store the raw attribute if there isn't already a cke-saved- version of it.
      parsedAttributes[attributeName] =
        element.data(`cke-saved-${attributeName}`) || attribute.nodeValue;
    }

    // Remove all cke_* classes.
    if (parsedAttributes.class) {
      parsedAttributes.class = CKEDITOR.tools.trim(
        parsedAttributes.class.replace(/cke_\S+/, ''),
      );
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
    const selection = editor.getSelection();
    const selectedElement = selection.getSelectedElement();
    if (selectedElement && selectedElement.is('cite')) {
      return selectedElement;
    }

    const range = selection.getRanges(true)[0];

    if (range) {
      range.shrink(CKEDITOR.SHRINK_TEXT);
      return editor.elementPath(range.getCommonAncestor()).contains('cite', 1);
    }
    return null;
  }

  // Register the plugin within the editor.
  CKEDITOR.plugins.add('cite', {
    lang: 'en,nl,de',

    // Register the icons.
    icons: 'cite',

    // The plugin initialization logic goes inside this method.
    init(editor) {
      const lang = editor.lang.cite;

      // Define an editor command that opens our dialog.
      editor.addCommand('cite', {
        // Allow cite tag with optional title.
        allowedContent: 'cite[title]',
        // Require cite tag to be allowed to work.
        requiredContent: 'cite',
        // Prefer cite over acronym. Transform acronyms into cites.
        contentForms: ['cite', 'acronym'],
        // eslint-disable-next-line no-shadow
        exec(editor) {
          // Get existing values if an cite element is currently selected.
          let citeElement = getSelectedCite(editor);
          const existingValues =
            citeElement && citeElement.$
              ? parseAttributes(citeElement)
              : { text: editor.getSelection().getSelectedText() };

          /**
           * Saves the dialog submission,
           * inserting the information into the CKEditor DOM.
           *
           * @param {object} returnedValues - The returned values from the Drupal form.
           */
          // eslint-disable-next-line func-names
          const saveCallback = function (returnedValues) {
            // If there isn't an existing cite element, create it.
            if (!citeElement && returnedValues.attributes.text) {
              const selection = editor.getSelection();
              const range = selection.getRanges(1)[0];

              if (range.collapsed) {
                const text = new CKEDITOR.dom.text( // eslint-disable-line new-cap
                  returnedValues.attributes.text,
                  editor.document,
                );

                range.insertNode(text);
                range.selectNodeContents(text);
              }

              delete returnedValues.attributes.text;

              // eslint-disable-next-line new-cap
              const style = new CKEDITOR.style({
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

              Object.keys(returnedValues.attributes || {}).forEach(
                (attrName) => {
                  if (returnedValues.attributes[attrName].length > 0) {
                    const value = returnedValues.attributes[attrName];

                    citeElement.data(`cke-saved-${attrName}`, value);
                    citeElement.setAttribute(attrName, value);
                  } else {
                    citeElement.removeAttribute(attrName);
                  }
                },
              );
            }
          };

          const dialogSettings = {
            // Since CKEditor loads the JS file, Drupal.t() will not work.
            // The config in the plugin settings can be translated server-side.
            title: citeElement ? lang.menuItemTitle : lang.buttonTitle,
            dialogClass: 'ckeditor-cite-dialog',
          };

          // Use the "Drupal way" of opening a dialog.
          Drupal.ckeditor.openDialog(
            editor,
            Drupal.url(
              `ckeditor-cite/dialog/cite/${editor.config.drupal.format}`,
            ),
            existingValues,
            saveCallback,
            dialogSettings,
          );
        },
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
        icon: `${this.path}icons/cite.png`,
      });

      if (editor.contextMenu) {
        editor.addMenuGroup('citeGroup');
        editor.addMenuItem('citeItem', {
          label: lang.menuItemTitle,
          icon: `${this.path}icons/cite.png`,
          command: 'cite',
          group: 'citeGroup',
        });

        // eslint-disable-next-line func-names
        editor.contextMenu.addListener(function (element) {
          if (element.getAscendant('cite', true)) {
            return { citeItem: CKEDITOR.TRISTATE_OFF };
          }
        });
      }
    },
  });
})(jQuery, Drupal, CKEDITOR); // eslint-disable-line no-undef
