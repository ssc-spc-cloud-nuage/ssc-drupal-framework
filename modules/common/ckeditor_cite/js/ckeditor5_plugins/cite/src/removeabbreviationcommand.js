import findAttributeRange from '@ckeditor/ckeditor5-typing/src/utils/findattributerange';
import CiteCommand from './citecommand';

export default class RemoveCiteCommand extends CiteCommand {
  refresh() {
    super.refresh();
    // The command is enabled when the "cite" attribute exists.
    this.isEnabled = !!this.value;
  }

  execute() {
    const model = this.editor.model;
    const selection = model.document.selection;

    model.change( writer => {
      // If the selection is collapsed and the caret is inside an cite, remove it.
      if ( selection.isCollapsed && selection.hasAttribute( 'cite' ) ) {
        // Find the entire range containing the cite under the caret position.
        const citeRange = findAttributeRange( selection.getFirstPosition(), 'cite', selection.getAttribute( 'cite' ), model );

        // Remove the cite.
        writer.removeAttribute( 'cite', citeRange );
      }
      // If the selection has non-collapsed ranges, remove the "cite" attribute from nodes inside those ranges
      // omitting nodes where the "cite" attribute is disallowed.
      else {
      	const ranges = model.schema.getValidRanges( selection.getRanges(), 'cite' );

      	for ( const range of ranges ) {
          writer.removeAttribute( 'cite', range );
        }
      }
    } );
  }
}
