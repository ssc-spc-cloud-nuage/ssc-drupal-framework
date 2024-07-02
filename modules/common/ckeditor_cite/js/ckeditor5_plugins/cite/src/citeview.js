import {
	View,
	LabeledFieldView,
	createLabeledInputText,
	ButtonView,
	submitHandler,
	FocusCycler
} from 'ckeditor5/src/ui';
import { FocusTracker, KeystrokeHandler } from 'ckeditor5/src/utils';
import { icons } from 'ckeditor5/src/core';

export default class FormView extends View {
	constructor( locale ) {
		super( locale );

		this.focusTracker = new FocusTracker();
		this.keystrokes = new KeystrokeHandler();

		this.citeInputView = this._createInput( Drupal.t( 'Add cite' ));
		this.titleInputView = this._createInput( Drupal.t( 'Add title' ));

		this.saveButtonView = this._createButton( Drupal.t('Save'), icons.check, 'ck-button-save' );

		// Submit type of the button will trigger the submit event on entire form when clicked
		//(see submitHandler() in render() below).
		this.saveButtonView.type = 'submit';

		this.cancelButtonView = this._createButton( Drupal.t('Cancel'), icons.cancel, 'ck-button-cancel' );

		// Delegate ButtonView#execute to FormView#cancel.
		this.cancelButtonView.delegate( 'execute' ).to( this, 'cancel' );

    // Create a remove button to remove the cite tag.
    this.removeButtonView = this._createButton( 'Remove', icons.eraser, 'ck-button-remove' );
    this.removeButtonView.delegate( 'execute' ).to( this, 'remove' );

		this.childViews = this.createCollection( [
			this.citeInputView,
			this.titleInputView,
			this.saveButtonView,
			this.cancelButtonView,
      this.removeButtonView
		] );

		this._focusCycler = new FocusCycler( {
			focusables: this.childViews,
			focusTracker: this.focusTracker,
			keystrokeHandler: this.keystrokes,
			actions: {
				// Navigate form fields backwards using the Shift + Tab keystroke.
				focusPrevious: 'shift + tab',

				// Navigate form fields forwards using the Tab key.
				focusNext: 'tab'
			}
		} );

		this.setTemplate( {
			tag: 'form',
			attributes: {
				class: [ 'ck', 'ck-cite-form' ],
				tabindex: '-1'
			},
			children: this.childViews
		} );
	}

	render() {
		super.render();

		submitHandler( {
			view: this
		} );

		this.childViews._items.forEach( view => {
			// Register the view in the focus tracker.
			this.focusTracker.add( view.element );
		} );

		// Start listening for the keystrokes coming from #element.
		this.keystrokes.listenTo( this.element );
	}

	destroy() {
		super.destroy();

		this.focusTracker.destroy();
		this.keystrokes.destroy();
	}

	focus() {
		// If the cite text field is enabled, focus it straight away to allow the user to type.
		if ( this.citeInputView.isEnabled ) {
			this.citeInputView.focus();
		}
		// Focus the cite title field if the former is disabled.
		else {
			this.titleInputView.focus();
		}
	}

	_createInput( label ) {
		const labeledInput = new LabeledFieldView( this.locale, createLabeledInputText );

		labeledInput.label = label;

		return labeledInput;
	}

	_createButton( label, icon, className ) {
		const button = new ButtonView();

		button.set( {
			label,
			icon,
			tooltip: true,
			class: className
		} );

		return button;
	}
}
