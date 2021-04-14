jQuery( function( $ ) {
	// Run tipTip
	function runTipTip() {
		// Remove any lingering tooltips
		$( '#tiptip_holder' ).removeAttr( 'style' );
		$( '#tiptip_arrow' ).removeAttr( 'style' );
		$( '.tips' ).tipTip( {
			'attribute': 'data-tip',
			'fadeIn': 50,
			'fadeOut': 50,
			'delay': 200,
		} );
	}

	runTipTip();

	// Tabbed Panels
	$( document.body ).on( 'er-init-tabbed-panels', function() {
		$( 'ul.er-tabs' ).show();
		$( 'ul.er-tabs a' ).on( 'click', function( e ) {
			e.preventDefault();
			const panelWrap = $( this ).closest( 'div.panel-wrap' );
			$( 'ul.er-tabs li', panelWrap ).removeClass( 'active' );
			$( this ).parent().addClass( 'active' );
			$( 'div.panel', panelWrap ).hide();
			$( $( this ).attr( 'href' ) ).show();
		} );
		$( 'div.panel-wrap' ).each( function() {
			$( this ).find( 'ul.er-tabs li' ).eq( 0 ).find( 'a' ).trigger( 'click' );
		} );
	} ).trigger( 'er-init-tabbed-panels' );
} );
