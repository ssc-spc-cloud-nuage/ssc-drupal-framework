import { Command } from 'ckeditor5/src/core';
import findAttributeRange from '@ckeditor/ckeditor5-typing/src/utils/findattributerange';
import getRangeText from './utils.js';
import { toMap } from 'ckeditor5/src/utils';

export default class CiteCommand extends Command {
    refresh() {
		const model = this.editor.model;
		const selection = model.document.selection;
		const firstRange = selection.getFirstRange();

		// When the selection is collapsed, the command has a value if the caret is in an cite.
		if ( firstRange.isCollapsed ) {
			if ( selection.hasAttribute( 'cite' ) ) {
				const attributeValue = selection.getAttribute( 'cite' );

				// Find the entire range containing the cite under the caret position.
				const citeRange = findAttributeRange( selection.getFirstPosition(), 'cite', attributeValue, model );

				this.value = {
					cite: getRangeText( citeRange ),
					title: attributeValue,
					range: citeRange
				};
			} else {
				this.value = null;
			}
		}
		// When the selection is not collapsed, the command has a value if the selection contains a subset of a single cite
		// or an entire cite.
		else {
			if ( selection.hasAttribute( 'cite' ) ) {
				const attributeValue = selection.getAttribute( 'cite' );

				// Find the entire range containing the cite under the caret position.
				const citeRange = findAttributeRange( selection.getFirstPosition(), 'cite', attributeValue, model );

				if ( citeRange.containsRange( firstRange, true ) ) {
					this.value = {
						cite: getRangeText( firstRange ),
						title: attributeValue,
						range: firstRange
					};
				} else {
					this.value = null;
				}
			} else {
				this.value = null;
			}
		}

		// The command is enabled when the "cite" attribute can be set on the current model selection.
		this.isEnabled = model.schema.checkAttributeInSelection( selection, 'cite' );
	}

	execute( { cite, title } ) {
		const model = this.editor.model;
		const selection = model.document.selection;

		model.change( writer => {
			// If selection is collapsed then update the selected cite or insert a new one at the place of caret.
			if ( selection.isCollapsed ) {
				// When a collapsed selection is inside text with the "cite" attribute, update its text and title.
				if ( this.value ) {
					const { end: positionAfter } = model.insertContent(
						writer.createText( cite, { cite: title } ),
						this.value.range
					);
					// Put the selection at the end of the inserted cite.
					writer.setSelection( positionAfter );
				}
				// If the collapsed selection is not in an existing cite, insert a text node with the "cite" attribute
				// in place of the caret. Because the selection is collapsed, the attribute value will be used as a data for text.
				// If the cite is empty, do not do anything.
				else if ( cite !== '' ) {
					const firstPosition = selection.getFirstPosition();

					// Collect all attributes of the user selection (could be "bold", "italic", etc.)
					const attributes = toMap( selection.getAttributes() );

					// Put the new attribute to the map of attributes.
					attributes.set( 'cite', title );

					// Inject the new text node with the cite text with all selection attributes.
					const { end: positionAfter } = model.insertContent( writer.createText( cite, attributes ), firstPosition );

					// Put the selection at the end of the inserted cite. Using an end of a range returned from
					// insertContent() just in case nodes with the same attributes were merged.
					writer.setSelection( positionAfter );
				}

				// Remove the "cite" attribute attribute from the selection. It stops adding a new content into the cite
				// if the user starts to type.
				writer.removeSelectionAttribute( 'cite' );
			} else {
				// If the selection has non-collapsed ranges, change the attribute on nodes inside those ranges
				// omitting nodes where the "cite" attribute is disallowed.
				const ranges = model.schema.getValidRanges( selection.getRanges(), 'cite' );

				for ( const range of ranges ) {
					writer.setAttribute( 'cite', title, range );
				}
			}
		} );
	}
}
