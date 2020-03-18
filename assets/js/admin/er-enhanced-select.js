/*global er_enhanced_select_params */
jQuery( function( $ ) {
	function getEnhancedSelectFormatString() {
		return {
			language: {
				errorLoading: function() {
					// Workaround for https://github.com/select2/select2/issues/4355 instead of i18n_ajax_error.
					return er_enhanced_select_params.i18n_searching;
				},
				inputTooLong: function( args ) {
					const overChars = args.input.length - args.maximum;

					if ( 1 === overChars ) {
						return er_enhanced_select_params.i18n_input_too_long_1;
					}

					return er_enhanced_select_params.i18n_input_too_long_n.replace( '%qty%', overChars );
				},
				inputTooShort: function( args ) {
					const remainingChars = args.minimum - args.input.length;

					if ( 1 === remainingChars ) {
						return er_enhanced_select_params.i18n_input_too_short_1;
					}

					return er_enhanced_select_params.i18n_input_too_short_n.replace( '%qty%', remainingChars );
				},
				loadingMore: function() {
					return er_enhanced_select_params.i18n_load_more;
				},
				maximumSelected: function( args ) {
					if ( args.maximum === 1 ) {
						return er_enhanced_select_params.i18n_selection_too_long_1;
					}

					return er_enhanced_select_params.i18n_selection_too_long_n.replace( '%qty%', args.maximum );
				},
				noResults: function() {
					return er_enhanced_select_params.i18n_no_matches;
				},
				searching: function() {
					return er_enhanced_select_params.i18n_searching;
				},
			},
		};
	}

	try {
		$( document.body )

			.on( 'er-enhanced-select-init', function() {
				// Regular select boxes
				$( ':input.er-enhanced-select, :input.chosen_select' ).filter( ':not(.enhanced)' ).each( function() {
					const args = $.extend( {
						minimumResultsForSearch: 10,
						allowClear: $( this ).data( 'allow_clear' ) ? true : false,
						placeholder: $( this ).data( 'placeholder' ),
					}, getEnhancedSelectFormatString() );

					$( this ).selectWoo( args ).addClass( 'enhanced' );
				} );

				$( ':input.er-enhanced-select-nostd, :input.chosen_select_nostd' ).filter( ':not(.enhanced)' ).each( function() {
					const args = $.extend( {
						minimumResultsForSearch: 10,
						allowClear: true,
						placeholder: $( this ).data( 'placeholder' ),
					}, getEnhancedSelectFormatString() );

					$( this ).selectWoo( args ).addClass( 'enhanced' );
				} );

				// Ajax product search box
				$( ':input.er-resource-search' ).filter( ':not(.enhanced)' ).each( function() {
					const args = $.extend( {
						allowClear: $( this ).data( 'allow_clear' ) ? true : false,
						placeholder: $( this ).data( 'placeholder' ),
						minimumInputLength: $( this ).data( 'minimum_input_length' ) ? $( this ).data( 'minimum_input_length' ) : '3',
						escapeMarkup: function( m ) {
							return m;
						},
						ajax: {
							url: er_enhanced_select_params.ajax_url,
							dataType: 'json',
							delay: 250,
							data: function( params ) {
								return {
									term: params.term,
									action: $( this ).data( 'action' ) || 'woocommerce_json_search_products_and_variations',
									security: er_enhanced_select_params.search_resources_nonce,
									exclude: $( this ).data( 'exclude' ),
									exclude_type: $( this ).data( 'exclude_type' ),
									include: $( this ).data( 'include' ),
									limit: $( this ).data( 'limit' ),
									display_stock: $( this ).data( 'display_stock' ),
								};
							},
							processResults: function( data ) {
								const terms = [];
								if ( data ) {
									$.each( data, function( id, text ) {
										terms.push( { id: id, text: text } );
									} );
								}
								return {
									results: terms,
								};
							},
							cache: true,
						},
					}, getEnhancedSelectFormatString() );

					$( this ).selectWoo( args ).addClass( 'enhanced' );

					if ( $( this ).data( 'sortable' ) ) {
						const $select = $( this ),
							$list = $select.next( '.select2-container' ).find( 'ul.select2-selection__rendered' );

						$list.sortable( {
							placeholder: 'ui-state-highlight select2-selection__choice',
							forcePlaceholderSize: true,
							items: 'li:not(.select2-search__field)',
							tolerance: 'pointer',
							stop: function() {
								$( $list.find( '.select2-selection__choice' ).get().reverse() ).each( function() {
									const option = $select.find( 'option[value="' + $( this ).data( 'data' ).id + '"]' )[ 0 ];
									$select.prepend( option );
								} );
							},
						} );
						// Keep multiselects ordered alphabetically if they are not sortable.
					} else if ( $( this ).prop( 'multiple' ) ) {
						$( this ).on( 'change', function() {
							const $children = $( this ).children();
							$children.sort( function( a, b ) {
								const atext = a.text.toLowerCase();
								const btext = b.text.toLowerCase();

								if ( atext > btext ) {
									return 1;
								}
								if ( atext < btext ) {
									return -1;
								}
								return 0;
							} );
							$( this ).html( $children );
						} );
					}
				} );

				// Ajax customer search boxes
				$( ':input.er-customer-search' ).filter( ':not(.enhanced)' ).each( function() {
					const args = $.extend( {
						allowClear: $( this ).data( 'allow_clear' ) ? true : false,
						placeholder: $( this ).data( 'placeholder' ),
						minimumInputLength: $( this ).data( 'minimum_input_length' ) ? $( this ).data( 'minimum_input_length' ) : '1',
						escapeMarkup: function( m ) {
							return m;
						},
						ajax: {
							url: er_enhanced_select_params.ajax_url,
							dataType: 'json',
							delay: 1000,
							data: function( params ) {
								return {
									term: params.term,
									action: 'easyreservations_json_search_customers',
									security: er_enhanced_select_params.search_customers_nonce,
									exclude: $( this ).data( 'exclude' ),
								};
							},
							processResults: function( data ) {
								const terms = [];
								if ( data ) {
									$.each( data, function( id, text ) {
										terms.push( {
											id: id,
											text: text,
										} );
									} );
								}
								return {
									results: terms,
								};
							},
							cache: true,
						},
					}, getEnhancedSelectFormatString() );

					$( this ).selectWoo( args ).addClass( 'enhanced' );

					if ( $( this ).data( 'sortable' ) ) {
						const $select = $( this ),
							$list = $select.next( '.select2-container' ).find( 'ul.select2-selection__rendered' );

						$list.sortable( {
							placeholder: 'ui-state-highlight select2-selection__choice',
							forcePlaceholderSize: true,
							items: 'li:not(.select2-search__field)',
							tolerance: 'pointer',
							stop: function() {
								$( $list.find( '.select2-selection__choice' ).get().reverse() ).each( function() {
									const option = $select.find( 'option[value="' + $( this ).data( 'data' ).id + '"]' )[ 0 ];
									$select.prepend( option );
								} );
							},
						} );
					}
				} );

				// Ajax order search boxes
				$( ':input.er-order-search' ).filter( ':not(.enhanced)' ).each( function() {
					const args = $.extend( {
						allowClear: $( this ).data( 'allow_clear' ) ? true : false,
						placeholder: $( this ).data( 'placeholder' ),
						minimumInputLength: $( this ).data( 'minimum_input_length' ) ? $( this ).data( 'minimum_input_length' ) : 1,
						escapeMarkup: function( m ) {
							return m;
						},
						ajax: {
							url: er_enhanced_select_params.ajax_url,
							dataType: 'json',
							delay: 250,
							data: function( params ) {
								return {
									term: params.term,
									action: 'easyreservations_json_search_order',
									security: er_enhanced_select_params.search_order_nonce,
								};
							},
							processResults: function( data ) {
								const terms = [];
								if ( data ) {
									$.each( data, function( id, text ) {
										terms.push( {
											id: id,
											text: text,
										} );
									} );
								}
								return {
									results: terms,
								};
							},
							cache: true,
						},
					}, getEnhancedSelectFormatString() );

					$( this ).selectWoo( args ).addClass( 'enhanced' );
				} );
			} )

			// easyReservations Backbone Modal
			.on( 'er_backbone_modal_before_remove', function() {
				$( '.er-enhanced-select, :input.er-resource-search, :input.er-customer-search' ).filter( '.select2-hidden-accessible' ).selectWoo( 'close' );
			} )

			.trigger( 'er-enhanced-select-init' );

		$( 'html' ).on( 'click', function( event ) {
			if ( this === event.target ) {
				$( '.er-enhanced-select, :input.er-resource-search, :input.er-customer-search' ).filter( '.select2-hidden-accessible' ).selectWoo( 'close' );
			}
		} );
	} catch ( err ) {
		// If select2 failed (conflict?) log the error but don't stop other scripts breaking.
		window.console.log( err );
	}
} );
