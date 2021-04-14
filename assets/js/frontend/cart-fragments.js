/* global er_cart_fragments_params, Cookies */
jQuery( function( $ ) {
	// er_cart_fragments_params is required to continue, ensure the object exists
	if ( typeof er_cart_fragments_params === 'undefined' ) {
		return false;
	}

	/* Storage Handling */
	var $supports_html5_storage = true,
		cart_hash_key = er_cart_fragments_params.cart_hash_key;

	try {
		$supports_html5_storage = ( 'sessionStorage' in window && window.sessionStorage !== null );
		window.sessionStorage.setItem( 'er', 'test' );
		window.sessionStorage.removeItem( 'er' );
		window.localStorage.setItem( 'er', 'test' );
		window.localStorage.removeItem( 'er' );
	} catch ( err ) {
		$supports_html5_storage = false;
	}

	/* Cart session creation time to base expiration on */
	function set_cart_creation_timestamp() {
		if ( $supports_html5_storage ) {
			sessionStorage.setItem( 'er_cart_created', ( new Date() ).getTime() );
		}
	}

	/** Set the cart hash in both session and local storage */
	function set_cart_hash( cart_hash ) {
		if ( $supports_html5_storage ) {
			localStorage.setItem( cart_hash_key, cart_hash );
			sessionStorage.setItem( cart_hash_key, cart_hash );
		}
	}

	var $fragment_refresh = {
		url: er_cart_fragments_params.er_ajax_url.toString().replace( '%%endpoint%%', 'get_refreshed_fragments' ),
		type: 'POST',
		data: {
			time: new Date().getTime(),
		},
		timeout: er_cart_fragments_params.request_timeout,
		success: function( data ) {
			if ( data && data.fragments ) {

				$.each( data.fragments, function( key, value ) {
					$( key ).replaceWith( value );
				} );

				if ( $supports_html5_storage ) {
					sessionStorage.setItem( er_cart_fragments_params.fragment_name, JSON.stringify( data.fragments ) );
					set_cart_hash( data.cart_hash );

					if ( data.cart_hash ) {
						set_cart_creation_timestamp();
					}
				}

				$( document.body ).trigger( 'er_fragments_refreshed' );
			}
		},
		error: function() {
			$( document.body ).trigger( 'er_fragments_ajax_error' );
		},
	};

	/* Named callback for refreshing cart fragment */
	function refresh_cart_fragment() {
		$.ajax( $fragment_refresh );
	}

	/* Cart Handling */
	if ( $supports_html5_storage ) {

		let cartTimeout = null;

		$( document.body ).on( 'er_fragment_refresh updated_er_div', function() {
			refresh_cart_fragment();
		} );

		$( document.body ).on( 'added_to_cart removed_from_cart', function( event, fragments, cart_hash ) {
			const prevCartHash = sessionStorage.getItem( cart_hash_key );

			if ( prevCartHash === null || prevCartHash === undefined || prevCartHash === '' ) {
				set_cart_creation_timestamp();
			}

			sessionStorage.setItem( er_cart_fragments_params.fragment_name, JSON.stringify( fragments ) );
			set_cart_hash( cart_hash );
		} );

		$( document.body ).on( 'er_fragments_refreshed', function() {
			clearTimeout( cartTimeout );
			cartTimeout = setTimeout( refresh_cart_fragment, 24 * 60 * 60 * 1000 );
		} );

		// Refresh when storage changes in another tab
		$( window ).on( 'storage onstorage', function( e ) {
			if (
				cart_hash_key === e.originalEvent.key && localStorage.getItem( cart_hash_key ) !== sessionStorage.getItem( cart_hash_key )
			) {
				refresh_cart_fragment();
			}
		} );

		// Refresh when page is shown after back button (safari)
		$( window ).on( 'pageshow', function( e ) {
			if ( e.originalEvent.persisted ) {
				$( '.widget_shopping_cart_content' ).empty();
				$( document.body ).trigger( 'er_fragment_refresh' );
			}
		} );

		try {
			var er_fragments = JSON.parse( sessionStorage.getItem( er_cart_fragments_params.fragment_name ) ),
				cart_hash = sessionStorage.getItem( cart_hash_key ),
				cookie_hash = Cookies.get( 'woocommerce_cart_hash' ),
				cart_created = sessionStorage.getItem( 'er_cart_created' );

			if ( cart_hash === null || cart_hash === undefined || cart_hash === '' ) {
				cart_hash = '';
			}

			if ( cookie_hash === null || cookie_hash === undefined || cookie_hash === '' ) {
				cookie_hash = '';
			}

			if ( cart_hash && ( cart_created === null || cart_created === undefined || cart_created === '' ) ) {
				throw 'No cart_created';
			}

			if ( cart_created ) {
				var cart_expiration = ( ( 1 * cart_created ) + day_in_ms ),
					timestamp_now = ( new Date() ).getTime();
				if ( cart_expiration < timestamp_now ) {
					throw 'Fragment expired';
				}
				cartTimeout = setTimeout( refresh_cart_fragment, ( cart_expiration - timestamp_now ) );
			}

			if ( er_fragments && er_fragments[ 'div.widget_shopping_cart_content' ] && cart_hash === cookie_hash ) {

				$.each( er_fragments, function( key, value ) {
					$( key ).replaceWith( value );
				} );

				$( document.body ).trigger( 'er_fragments_loaded' );
			} else {
				throw 'No fragment';
			}

		} catch ( err ) {
			refresh_cart_fragment();
		}

	} else {
		refresh_cart_fragment();
	}

	/* Cart Hiding */
	if ( Cookies.get( 'woocommerce_items_in_cart' ) > 0 ) {
		$( '.hide_cart_widget_if_empty' ).closest( '.widget_shopping_cart' ).show();
	} else {
		$( '.hide_cart_widget_if_empty' ).closest( '.widget_shopping_cart' ).hide();
	}

	$( document.body ).on( 'adding_to_cart', function() {
		$( '.hide_cart_widget_if_empty' ).closest( '.widget_shopping_cart' ).show();
	} );

	// Customiser support.
	var hasSelectiveRefresh = (
		'undefined' !== typeof wp &&
		wp.customize &&
		wp.customize.selectiveRefresh &&
		wp.customize.widgetsPreview &&
		wp.customize.widgetsPreview.WidgetPartial
	);
	if ( hasSelectiveRefresh ) {
		wp.customize.selectiveRefresh.on( 'partial-content-rendered', function() {
			refresh_cart_fragment();
		} );
	}
} );
