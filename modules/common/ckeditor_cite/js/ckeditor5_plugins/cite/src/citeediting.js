import { Plugin } from 'ckeditor5/src/core';
import CiteCommand from './citecommand';
import RemoveCiteCommand from './removecitecommand';

export default class CiteEditing extends Plugin {
	init() {
		this._defineSchema();
		this._defineConverters();

		this.editor.commands.add(
			'addCite', new CiteCommand( this.editor )
		);
    this.editor.commands.add(
      'removeCite', new RemoveCiteCommand( this.editor )
    );
	}
	_defineSchema() {
		const schema = this.editor.model.schema;

    	// Extend the text node's schema to accept the cite attribute.
		schema.extend( '$text', {
			allowAttributes: [ 'cite' ]
		} );
	}
	_defineConverters() {
		const conversion = this.editor.conversion;

        // Conversion from a model attribute to a view element
		conversion.for( 'downcast' ).attributeToElement( {
			model: 'cite',

            // Callback function provides access to the model attribute value
			// and the DowncastWriter
			view: ( modelAttributeValue, conversionApi ) => {
				const { writer } = conversionApi;
        let titleAttribute = (modelAttributeValue)
          ? { title: modelAttributeValue }
          : null;
        return writer.createAttributeElement('cite', titleAttribute);
			}
		} );

		// Conversion from a view element to a model attribute
		conversion.for( 'upcast' ).elementToAttribute( {
			view: {
				name: 'cite',
				attributes: [ 'title' ]
			},
			model: {
				key: 'cite',

                // Callback function provides access to the view element
				value: viewElement => {
					const title = viewElement.getAttribute( 'title' );
					return title;
				}
			}
		} );
	}
}
