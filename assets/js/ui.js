jQuery( document ).ready( function( $ ) {
	$( '.sbHolder' ).remove();

	var target = '.easy-ui .together',
		invert = ':not(' + target + ')',
		breakpoints = $( '.easy-ui > *' + invert + ',.easy-ui > div.content > *' + invert );

	breakpoints.each( function() {
		$( this ).nextUntil( invert ).wrapAll( '<span class="together-wrapper">' );
	} );

	breakpoints.first().prevUntil( invert ).wrapAll( '<span class="together-wrapper">' );

	$( 'select[name$="minute"]' ).each( function( k, v ) {
		$( '<span class="input-box"><span class="dashicons dashicons-clock"></span></span>' ).insertAfter( this );
		$( this ).add( $( this ).prev() ).add( $( this ).next() ).wrapAll( '<span class="input-wrapper">' );
	} );

	var isIE11 = !! window.MSInputMethodContext && !! document.documentMode;
	if ( window.CSS && window.CSS.supports && window.CSS.supports( '--a', 0 ) && ( isIE11 === undefined || ! isIE11 ) ) {
		$( '.input-wrapper select[name$="hour"]' ).each( function( k, v ) {
			var twelve_hours = false;
			var hideHoursInSelect = function( ele, test ) {
				var select = $( this );
				if ( test ) {
					select = ele;
				}
				select.find( 'option' ).each( function( k, t ) {
					if ( ! twelve_hours && ( t.text.indexOf( "AM" ) >= 0 || t.text.indexOf( "am" ) >= 0 || t.text.indexOf( "PM" ) >= 0 || t.text.indexOf( "pm" ) >= 0 ) ) {
						twelve_hours = true;
					}
					var explode = t.text.split( ":" );
					$( t ).attr( 'data-text', t.text );
					t.label = explode[ 0 ];
					t.text = explode[ 0 ];
				} );
				if ( ! test && twelve_hours ) {
					var label = 'PM';
					if ( select.find( 'option:selected' ).data( 'text' ).indexOf( "AM" ) >= 0 ) {
						label = 'AM';
					}
					while ( ! select.hasClass( 'input-box' ) ) {
						select = select.next();
						if ( select.hasClass( 'input-box' ) ) {
							select.children( 'span' ).removeClass( 'dashicons-clock' ).removeClass( 'dashicons' ).addClass( '' ).html( label )
						}
						if ( select.length < 1 ) {
							break;
						}
					}
				}
			};

			hideHoursInSelect( $( this ), 1 );

			$( this ).on( 'focusin click', function() {
				$( this ).find( 'option' ).each( function( k, t ) {
					var orig = $( t ).attr( 'data-text' );
					t.label = orig;
					t.text = orig;
				} );
			} ).on( 'blur change', hideHoursInSelect );
		} );
	}

	$( '.input-box.clickable' ).on( 'click', function( t ) {
		if ( $( this ).next().length > 0 ) {
			$( this ).next().focus()
		} else {
			$( this ).prev().focus()
		}
	} );

	function register_slider() {
		$( '.easy-slider-input:not(.generated)' ).each( function() {
			var form_field = $( this ),
				slider = $( '<div id="slider" class="easy-slider"><div id="custom-handle" class="ui-slider-handle"><label><span class="dashicons dashicons-arrow-left-alt2"></span><span class="text"></span><span class="dashicons dashicons-arrow-right-alt2"></label></div></div>' );

			form_field.after( slider ).addClass( 'generated' );

			var handle = slider.find( "span.text" ),
				min = parseFloat( form_field.attr( 'data-min' ) ),
				max = parseFloat( form_field.attr( 'data-max' ) ),
				step = parseFloat( form_field.attr( 'data-step' ) ),
				label = form_field.attr( 'data-label' );

			if ( min === undefined ) {
				min = 1;
			}
			if ( max === undefined ) {
				max = 100;
			}
			if ( step === undefined ) {
				step = 1;
			}
			if ( label === undefined ) {
				label = '';
			}

			slider.slider( {
				range: "min",
				min: min,
				max: max,
				step: step,
				value: form_field.val(),
				create: function() {
					handle.text( $( this ).slider( "value" ) + ' ' + label );
					form_field.val( $( this ).slider( "value" ) );
				},
				slide: function( event, ui ) {
					handle.text( ui.value + ' ' + label );
					form_field.val( ui.value );
				},
				stop: function( event, ui ) {
					form_field.val( ui.value ).trigger( 'change' );
				}
			} );
		} );
	}

	register_slider();
	$( document.body ).on( 'er_generated_custom_field', register_slider );

	$( document ).on( 'click', function( e ) {
		$( '.er-dropdown .dropdown-menu' ).hide();
	} );

	$( '.er-dropdown .dropdown-toggle' ).on( 'click', function( e ) {
		$( this ).parent().find( '.dropdown-menu' ).toggle();
		e.stopPropagation();
	} );

	$.fn.easyNavigation = function( options ) {
		var all_links = $( this ).find( 'a.nav-tab' );
		var current_target = options[ 'value' ];

		all_links.on( 'click', function( e ) {
			e.preventDefault();

			if ( ! $( this ).hasClass( 'active' ) ) {
				all_links.removeClass( 'nav-tab-active' );
				$( this ).addClass( 'nav-tab-active' );
				$( '#' + current_target ).addClass( 'hidden' );
				var target = $( this ).attr( 'target' );

				if ( target ) {
					var node = $( '#' + target );

					node.attr( 'id', '' );

					if ( options.hash ) {
						window.location.hash = target;
					}

					node.attr( 'id', target );

					current_target = target;
					$( '#' + target ).removeClass( 'hidden' );
				}
			}
		} );

		if ( options.hash && window.location.hash !== '' ) {
			$( 'a[target="' + window.location.hash.substring( 1 ) + '"]' ).trigger( 'click' );
		} else {
			//$('a[target="' + current_target + '"]').trigger( 'click' );
		}
	};
} );