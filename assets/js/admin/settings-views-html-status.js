/* global easyreservations_admin_system_status, erSetClipboard, erClearClipboard */
jQuery( function( $ ) {
	/**
	 * Users country and state fields
	 */
	const erSystemStatus = {
		init: function() {
			$( document.body )
				.on( 'click', 'a.easyreservations-help-tip', this.preventTipTipClick )
				.on( 'click', 'a.debug-report', this.generateReport )
				.on( 'click', '#copy-for-support', this.copyReport )
				.on( 'aftercopy', '#copy-for-support', this.copySuccess )
				.on( 'aftercopyfailure', '#copy-for-support', this.copyFail );
		},

		/**
		 * Prevent anchor behavior when click on TipTip.
		 *
		 * @return {boolean} bool
		 */
		preventTipTipClick: function() {
			return false;
		},

		/**
		 * Generate system status report.
		 *
		 * @return {boolean} bool
		 */
		generateReport: function() {
			let report = '';

			$( '.er_status_table thead, .er_status_table tbody' ).each( function() {
				if ( $( this ).is( 'thead' ) ) {
					const label = $( this ).find( 'th:eq(0)' ).data( 'export-label' ) || $( this ).text();
					report = report + '\n### ' + label.trim() + ' ###\n\n';
				} else {
					$( 'tr', $( this ) ).each( function() {
						const label = $( this ).find( 'td:eq(0)' ).data( 'export-label' ) || $( this ).find( 'td:eq(0)' ).text();
						const theName = label.trim().replace( /(<([^>]+)>)/ig, '' ); // Remove HTML.

						// Find value
						const valueHTML = $( this ).find( 'td:eq(2)' ).clone();
						valueHTML.find( '.private' ).remove();
						valueHTML.find( '.dashicons-yes' ).replaceWith( '&#10004;' );
						valueHTML.find( '.dashicons-no-alt, .dashicons-warning' ).replaceWith( '&#10060;' );

						// Format value
						let theValue = valueHTML.text().trim();
						const valueArray = theValue.split( ', ' );

						if ( valueArray.length > 1 ) {
							// If value have a list of plugins ','.
							// Split to add new line.
							let tempLine = '';
							$.each( valueArray, function( key, line ) {
								tempLine = tempLine + line + '\n';
							} );

							theValue = tempLine;
						}

						report = report + '' + theName + ': ' + theValue + '\n';
					} );
				}
			} );

			try {
				$( '#debug-report' ).slideDown();
				$( '#debug-report' ).find( 'textarea' ).val( '`' + report + '`' ).focus().select();
				$( this ).fadeOut();
				return false;
			} catch ( e ) {
				/* jshint devel: true */
				console.log( e );
			}

			return false;
		},

		/**
		 * Copy for report.
		 *
		 * @param {Object} evt Copy event.
		 */
		copyReport: function( evt ) {
			erClearClipboard();
			erSetClipboard( $( '#debug-report' ).find( 'textarea' ).val(), $( this ) );
			evt.preventDefault();
		},

		/**
		 * Display a "Copied!" tip when success copying
		 */
		copySuccess: function() {
			$( '#copy-for-support' ).tipTip( {
				'attribute': 'data-tip',
				'activation': 'focus',
				'fadeIn': 50,
				'fadeOut': 50,
				'delay': 0,
			} ).focus();
		},

		/**
		 * Displays the copy error message when failure copying.
		 */
		copyFail: function() {
			$( '.copy-error' ).removeClass( 'hidden' );
			$( '#debug-report' ).find( 'textarea' ).focus().select();
		},
	};

	erSystemStatus.init();

	$( '.er_status_table' ).on( 'click', '.run-tool .button', function( evt ) {
		evt.stopImmediatePropagation();
		return window.confirm( easyreservations_admin_system_status.run_tool_confirmation );
	} );

	$( '#log-viewer-select' ).on( 'click', 'h2 a.page-title-action', function( evt ) {
		evt.stopImmediatePropagation();
		return window.confirm( easyreservations_admin_system_status.delete_log_confirmation );
	} );
} );
