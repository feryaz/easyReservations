var tag_before = '',
	tag_edit = false,
	savedSelection = false,
	insert_began = [];

function bindFormtag() {
	jQuery( 'formtag' ).off( 'click' ).on( 'click', function() {
		tag_before = this;
		var text = jQuery( this ).html().replace( '[', '' ).replace( ']', '' );
		var tag_final = {},
			n = 0,
			pattern,
			match;
		pattern = /(\w+)\s*=\s*"([^"]*)"(?:\s|$)|(\w+)\s*=\s*\'([^\']*)\'(?:\s|$)|(\w+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/g;
		text = text.replace( /[\u00a0\u200b]/g, ' ' );
		while ( ( match = pattern.exec( text ) ) ) {
			if ( match[ 1 ] ) {
				tag_final[ match[ 1 ] ] = match[ 2 ];
			} else if ( match[ 3 ] ) {
				tag_final[ match[ 3 ] ] = match[ 4 ];
			} else if ( match[ 5 ] ) {
				tag_final[ match[ 5 ] ] = match[ 6 ];
			} else if ( match[ 7 ] ) {
				tag_final[ n ] = match[ 7 ];
				n++;
			} else if ( match[ 8 ] ) {
				tag_final[ n ] = match[ 8 ];
				n++;
			}
		}
		tag_edit = true;
		generateTagEdit( tag_final[ 0 ], tag_final );
	} );
}

bindFormtag();

jQuery( 'table.formtable tbody tr' )
	.on( 'click', function() {
		if ( jQuery( this ).attr( 'attr' ) ) {
			generateTagEdit( jQuery( this ).attr( 'attr' ) );
		} else if ( jQuery( this ).attr( 'bttr' ) ) {
			var list = {
					b: { 0: "<strong>", 1: "</strong>" },
					i: { 0: "<i>", 1: "</i>" },
					label: { 0: "<label>", 1: "</label>" },
					small: { 0: '<small>', 1: "</small>" },
					row: { 0: '<div class="row">', 1: "</div>" },
					div: { 0: '<div class="content">', 1: "</div>" },
					h1: { 0: '<h1>', 1: "</h1>" },
					h2: { 0: '<h2>', 1: "</h2>" }
				},
				type = jQuery( this ).attr( 'bttr' );
			if ( list[ type ] ) {
				var end = false;
				if ( list[ type ][ 1 ] ) {
					end = list[ type ][ 1 ];
				}
				insertTag( type, list[ type ][ 0 ], end )
			}
		}
	} ).on( 'mouseenter mouseleave', function() {
	var type = jQuery( this ).attr( 'attr' );
	jQuery( 'formtag[attr="' + type + '"]' ).toggleClass( 'taghover' );
} );

jQuery( '#form_container' )
	.on( 'click', function() {
		savedSelection = saveSelection();
	} )
	.on( "keydown", function( e ) {
		if ( e.which === 13 ) {
			if ( window.getSelection ) {
				var selection = window.getSelection(),
					range = selection.getRangeAt( 0 ),
					br = document.createElement( "br" ),
					n = document.createTextNode( "\n" );
				range.insertNode( br );
				range.insertNode( n );
				range.setStartAfter( br );
				range.setEndAfter( br );
				range.collapse( false );
				selection.removeAllRanges();
				selection.addRange( range );
			}
			return false;
		}
	} );

function generateTagEdit( type, tag ) {
	if ( fields[ type ] ) {
		var title = 'Add';
		if ( tag ) {
			title = 'Edit';
		}
		jQuery( '*[name=deltag]' ).remove();
		var value = '<h3 name="deltag">' + title + ' ' + fields[ type ][ 'name' ] + ' field</h3><div name="deltag"><input type="hidden" name="0" value="' + type + '">';
		value += '<p class="desc">' + fields[ type ][ 'desc' ] + '</p>';
		var options = fields[ type ][ 'options' ];
		if ( typeof options == 'function' ) {
			value += options( tag );
		} else {
			jQuery.each( options, function( k, v ) {
				if ( v[ 'title' ] && v[ 'input' ] != 'check' ) {
					value += '<h4>' + v[ 'title' ] + '</h4>';
				}
				value += '<p>';
				var sel = false,
					hasclass = '';
				if ( v[ 'class' ] ) {
					hasclass = ' class="' + v[ 'class' ] + '"';
				}
				if ( tag && tag[ k ] ) {
					sel = tag[ k ];
				} else if ( v[ 'default' ] ) {
					sel = v[ 'default' ];
				} else {
					sel = '';
				}
				if ( typeof v[ 'input' ] == 'function' ) {
					value += v[ 'input' ]( tag );
				} else {
					if ( v[ 'input' ] == 'text' ) {
						value += '<input type="text" name="' + k + '" value="' + sel + '"' + hasclass + '>';
					} else if ( v[ 'input' ] == 'amount' ) {
						value += '<input type="number" name="' + k + '" value="' + sel + '"' + hasclass + '>';
					} else if ( v[ 'input' ] == 'textarea' ) {
						value += '<textarea name="' + k + '"' + hasclass + '>' + sel + '</textarea>';
					} else if ( v[ 'input' ] == 'check' ) {
						if ( tag && tag[ k ] || ( ! tag && v[ 'checked' ] ) ) {
							sel = 'checked="checked" ';
						} else {
							sel = '';
						}
						value += '<label class="wrapper"><input type="checkbox" name="' + k + '" value="' + v[ 'default' ] + '" ' + sel + hasclass + '>' + v[ 'title' ] + '</label>';
					} else if ( v[ 'input' ] == 'select' ) {
						value += '<select name="' + k + '"' + hasclass + '>';
						value += generateOptions( v[ 'options' ], sel );
						value += '</select>';
					}
				}
				value += '</p>';
			} );
		}
		value += '<div class="easy-ui" style="margin-top: 5px">';
		value += '<a href="javascript:" class="button-primary" onclick="submitTag();">' + title + '</a>&nbsp;';
		value += '<a href="javascript:" class="button" onclick="deactivateTag();">Cancel</a>';
		value += '</div>';
		value += '</div>';
		jQuery( '#accordion' ).prepend( value ).accordion( "destroy" ).accordion( {
			heightStyle: "content",
			autoHeight: false,
			icons: {
				"header": "ui-icon-plus",
				"activeHeader": "ui-icon-minus"
			}
		} );
	}
}

function submitTag() {
	var tag_new = '';
	var type = false;
	jQuery( '*[name=deltag] :input:not(.not)' ).each( function( ui, child ) {
		if ( child.value != '' && ( child.type != 'checkbox' || child.checked == true ) ) {
			if ( ! type ) {
				type = child.value;
			}
			if ( child.name == "*" || ( ! isNaN( parseFloat( child.name ) ) && isFinite( child.name ) ) ) {
				if ( jQuery( this ).hasClass( 'quote' ) ) {
					tag_new += '"' + child.value + '" ';
				} else {
					tag_new += child.value + ' ';
				}
			} else {
				tag_new += child.name + '="' + child.value + '" ';
			}
		}
	} );
	if ( fields[ type ] && fields[ type ][ 'generate' ] ) {
		tag_new += fields[ type ][ 'generate' ]();
	}
	tag_new = tag_new.substr( 0, tag_new.length - 1 )
	tag_new = '[' + tag_new + ']';
	if ( tag_edit ) {
		jQuery( tag_before ).html( tag_new );
	} else {
		insertAtCaret( '<formtag attr="' + type + '">' + tag_new + '</formtag>' );
	}
	deactivateTag();
	bindFormtag();
}

function deactivateTag() {
	tag_edit = false;
	jQuery( '*[name=deltag]' ).fadeOut( "fast" );
	jQuery( '#accordion' ).accordion( "destroy" ).accordion( {
		heightStyle: "content",
		autoHeight: false,
		icons: {
			"header": "ui-icon-plus",
			"activeHeader": "ui-icon-minus"
		}
	} );
}

function insertAtCaret( text ) {
	if ( savedSelection ) {
		restoreSelection( text );
	} else {
		jQuery( '#form_container' ).prepend( text );
	}
}

if ( window.getSelection ) {
	saveSelection = function() {
		var sel = window.getSelection(),
			ranges = [];
		if ( sel.rangeCount ) {
			for ( var i = 0, len = sel.rangeCount; i < len; ++i ) {
				ranges.push( sel.getRangeAt( i ) );
			}
		}
		return ranges;
	};
	restoreSelection = function( text ) {
		var sel = window.getSelection();
		sel.removeAllRanges();
		for ( var i = 0, len = savedSelection.length; i < len; ++i ) {
			sel.addRange( savedSelection[ i ] );
		}
		var range = sel.getRangeAt( 0 );
		range.collapse( false );
		var el = document.createElement( "div" );
		el.innerHTML = text;
		var frag = document.createDocumentFragment(),
			node,
			lastNode;
		while ( ( node = el.firstChild ) ) {
			lastNode = frag.appendChild( node );
		}
		//document.execCommand("insertHTML", true, text);
		//range.deleteContents();
		range.insertNode( frag );
		sel.removeAllRanges();

	};
	insertTag = function( type, start, end ) {
		if ( savedSelection ) {
			var sel = window.getSelection();
			sel.removeAllRanges();
			for ( var i = 0, len = savedSelection.length; i < len; ++i ) {
				sel.addRange( savedSelection[ i ] );
			}
			if ( ! sel.type || sel.type == 'None' || sel.type == 'Caret' ) {
				if ( end && insert_began[ start ] ) {
					insert_began[ start ] = null;
					document.execCommand( "insertText", true, end );
					jQuery( 'tr[bttr="' + type + '"] tag' ).text( start );
				} else {
					if ( end ) {
						insert_began[ start ] = 1;
						jQuery( 'tr[bttr="' + type + '"] tag' ).text( end );
					}
					document.execCommand( "insertText", true, start );
				}
			} else {
				var text = window.getSelection();
				text = start + text;
				if ( end ) {
					text += end;
				}
				document.execCommand( "insertText", true, text );
			}
			savedSelection = saveSelection();
		} else {
			jQuery( '#form_container' ).prepend( start );
		}
	};
} else if ( document.selection && document.selection.createRange ) {
	saveSelection = function() {
		var sel = document.selection;
		return ( sel.type != "None" ) ? sel.createRange() : null;
	};
	restoreSelection = function( text ) {
		if ( savedSelection ) {
			savedSelection.select();
			document.selection.createRange().text = text;
		}
	};
	insertTag = function() {
		alert( 'Function not available in your browser' );
	};
}

function htmlForTextWithEmbeddedNewlines( text ) {
	var htmls = [];
	var lines = text.split( /\n/ );
	var tmpDiv = jQuery( document.createElement( 'div' ) );
	for ( var i = 0; i < lines.length; i++ ) {
		htmls.push( tmpDiv.text( lines[ i ] ).html() );
	}
	return htmls.join( "<br>\n" );
}

