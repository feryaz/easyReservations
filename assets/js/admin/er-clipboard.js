/* exported erSetClipboard, erClearClipboard */

/**
 * Simple text copy functions using native browser clipboard capabilities.
 */

/**
 * Set the user's clipboard contents.
 *
 * @param string data: Text to copy to clipboard.
 * @param object $el: jQuery element to trigger copy events on. (Default: document)
 */
function erSetClipboard( data, $el ) {
	if ( 'undefined' === typeof $el ) {
		$el = jQuery( document );
	}

	const tempInput = jQuery( '<textarea style="opacity:0">' );
	jQuery( 'body' ).append( tempInput );
	tempInput.val( data ).select();

	$el.trigger( 'beforecopy' );
	try {
		document.execCommand( 'copy' );
		$el.trigger( 'aftercopy' );
	} catch ( err ) {
		$el.trigger( 'aftercopyfailure' );
	}

	tempInput.remove();
}

/**
 * Clear the user's clipboard.
 */
function erClearClipboard() {
	erSetClipboard( '' );
}
