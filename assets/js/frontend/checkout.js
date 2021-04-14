( function( $, data ) {
	$( function() {
		$( '.er-selection-box li:first-child input[name=deposit_type]' ).prop( 'checked', true );

		var er_checkout_form = {
			updateTimer: false,
			selectedPaymentMethod: false,
			$order_review: $( '#order_review' ),
			$checkout_form: $( 'form.checkout' ),
			init: function() {
				$( document.body ).on( 'update_checkout', this.update_checkout );
				$( document.body ).on( 'init_checkout', this.init_checkout );

				if ( $( document.body ).hasClass( 'easyreservations-order-pay' ) ) {
					this.$order_review.on( 'click', 'input[name="payment_method"]', this.payment_method_selected );
					this.$order_review.attr( 'novalidate', 'novalidate' );
				}

				// Payment methods
				this.$checkout_form.on( 'click', 'input[name="payment_method"]', this.payment_method_selected );
				this.$checkout_form.on( 'change', '.validate', this.update_checkout );
				this.$checkout_form.on( 'change', 'input[name="deposit_type"]', this.update_checkout );

				this.init_payment_methods();

				// Update on page load
				if ( er_checkout_params.is_checkout === '1' ) {
					$( document.body ).trigger( 'init_checkout' );
				}
				if ( er_checkout_params.option_guest_checkout === 'yes' ) {
					$( 'input#createaccount' ).on( 'change', this.toggle_create_account ).trigger( 'change' );
				}
			},
			init_payment_methods: function() {
				var $payment_methods = $( '.easyreservations-checkout' ).find( 'input[name="payment_method"]' );

				// If there is one method, we can hide the radio input
				if ( 1 === $payment_methods.length ) {
					$payment_methods.eq( 0 ).hide();
				}

				// If there was a previously selected method, check that one.
				if ( er_checkout_form.selectedPaymentMethod ) {
					$( '#' + er_checkout_form.selectedPaymentMethod ).prop( 'checked', true );
				}

				// If there are none selected, select the first.
				if ( 0 === $payment_methods.filter( ':checked' ).length ) {
					$payment_methods.eq( 0 ).prop( 'checked', true );
				}

				// Get name of new selected method.
				var checkedPaymentMethod = $payment_methods.filter( ':checked' ).eq( 0 ).prop( 'id' );

				if ( $payment_methods.length > 1 ) {
					// Hide open descriptions.
					$( 'div.payment_box:not(".' + checkedPaymentMethod + '")' ).filter( ':visible' ).slideUp( 0 );
				}

				// Trigger click event for selected method
				$payment_methods.filter( ':checked' ).eq( 0 ).trigger( 'click' );
			},
			get_payment_method: function() {
				return er_checkout_form.$checkout_form.find( 'input[name="payment_method"]:checked' ).val();
			},
			payment_method_selected: function( e ) {
				e.stopPropagation();

				if ( $( '.payment_methods input.input-radio' ).length > 1 ) {
					var target_payment_box = $( 'div.payment_box.' + $( this ).attr( 'ID' ) ),
						is_checked = $( this ).is( ':checked' );

					if ( is_checked && ! target_payment_box.is( ':visible' ) ) {
						$( 'div.payment_box' ).filter( ':visible' ).slideUp( 230 );

						if ( is_checked ) {
							target_payment_box.slideDown( 230 );
						}
					}
				} else {
					$( 'div.payment_box' ).show();
				}

				if ( $( this ).data( 'order_button_text' ) ) {
					$( '#place_order' ).text( $( this ).data( 'order_button_text' ) );
				} else {
					$( '#place_order' ).text( $( '#place_order' ).data( 'value' ) );
				}

				var selectedPaymentMethod = $( '.easyreservations-checkout input[name="payment_method"]:checked' ).attr( 'id' );

				if ( selectedPaymentMethod !== er_checkout_form.selectedPaymentMethod ) {
					$( document.body ).trigger( 'payment_method_selected' );
				}

				er_checkout_form.selectedPaymentMethod = selectedPaymentMethod;
			},
			toggle_create_account: function() {
				$( 'div.create-account' ).hide();

				if ( $( this ).is( ':checked' ) ) {
					// Ensure password is not pre-populated.
					$( '#account_password' ).val( '' ).trigger( 'change' );
					$( 'div.create-account' ).slideDown();
				}
			},
			init_checkout: function() {
				// Fire updated_checkout event after existing ready event handlers.
				$( document.body ).trigger( 'updated_checkout' );
			},
			update_checkout: function( event, args ) {
				// Small timeout to prevent multiple requests when several fields update at the same time
				er_checkout_form.reset_update_checkout_timer();
				er_checkout_form.updateTimer = setTimeout( er_checkout_form.update_checkout_action, '5', args );
			},
			reset_update_checkout_timer: function() {
				clearTimeout( er_checkout_form.updateTimer );
			},
			update_checkout_action: function( args ) {
				if ( er_checkout_form.xhr ) {
					er_checkout_form.xhr.abort();
				}

				if ( $( 'form.checkout' ).length === 0 ) {
					return;
				}

				var data = $( 'form.checkout' ).serializeObject(),
					country = $( '#country' ).val(),
					state = $( '#state' ).val(),
					postcode = $( ':input#postcode' ).val(),
					city = $( '#city' ).val(),
					address = $( ':input#address_1' ).val(),
					address_2 = $( ':input#address_2' ).val(),
					$required_inputs = $( er_checkout_form.$checkout_form ).find( '.address-field.validate-required:visible' ),
					has_full_address = true;

				if ( $required_inputs.length ) {
					$required_inputs.each( function() {
						if ( $( this ).find( ':input' ).val() === '' ) {
							has_full_address = false;
						}
					} );
				}

				data.security = er_checkout_params.update_order_review_nonce;
				data.payment_method = er_checkout_form.get_payment_method();
				data.country = country;
				data.state = state;
				data.postcode = postcode;
				data.city = city;
				data.address = address;
				data.address_2 = address_2;
				data.has_full_address = has_full_address;

				$( '.easyreservations-checkout-payment, .easyreservations-checkout-review-order-table, .easyreservations-checkout-deposit' ).block( {
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				} );

				er_checkout_form.xhr = $.ajax( {
					type: 'POST',
					url: er_checkout_params.er_ajax_url.toString().replace( '%%endpoint%%', 'update_order_review' ),
					data: data,
					success: function( data ) {

						// Reload the page if requested
						if ( data && true === data.reload ) {
							window.location.reload();
							return;
						}

						// Remove any notices added previously
						$( '.easyreservations-NoticeGroup-updateOrderReview' ).remove();

						const termsCheckBoxChecked = $( '#terms' ).prop( 'checked' );

						// Save payment details to a temporary object
						const paymentDetails = {};
						$( '.payment_box :input' ).each( function() {
							var ID = $( this ).attr( 'id' );

							if ( ID ) {
								if ( $.inArray( $( this ).attr( 'type' ), [ 'checkbox', 'radio' ] ) !== -1 ) {
									paymentDetails[ ID ] = $( this ).prop( 'checked' );
								} else {
									paymentDetails[ ID ] = $( this ).val();
								}
							}
						} );

						// Always update the fragments
						if ( data && data.fragments ) {
							$.each( data.fragments, function( key, value ) {
								if ( ! er_checkout_form.fragments || er_checkout_form.fragments[ key ] !== value ) {
									$( key ).replaceWith( value );
								}
								$( key ).unblock();
							} );
							er_checkout_form.fragments = data.fragments;
						}

						// Recheck the terms and conditions box, if needed
						if ( termsCheckBoxChecked ) {
							$( '#terms' ).prop( 'checked', true );
						}

						// Fill in the payment details if possible without overwriting data if set.
						if ( ! $.isEmptyObject( paymentDetails ) ) {
							$( '.payment_box :input' ).each( function() {
								const ID = $( this ).attr( 'id' );

								if ( ID ) {
									if ( $.inArray( $( this ).attr( 'type' ), [ 'checkbox', 'radio' ] ) !== -1 ) {
										$( this ).prop( 'checked', paymentDetails[ ID ] ).trigger( 'change' );
									} else if ( $.inArray( $( this ).attr( 'type' ), [ 'select' ] ) !== -1 ) {
										$( this ).val( paymentDetails[ ID ] ).trigger( 'change' );
									} else if ( null !== $( this ).val() && 0 === $( this ).val().length ) {
										$( this ).val( paymentDetails[ ID ] ).trigger( 'change' );
									}
								}
							} );
						}

						// Check for error
						if ( data && 'failure' === data.result ) {
							const $form = $( 'form.checkout' );

							// Remove notices from all sources
							$( '.easyreservations-error, .easyreservations-message' ).remove();

							// Add new errors returned by this event
							if ( data.messages ) {
								$form.prepend( '<div class="easyreservations-NoticeGroup easyreservations-NoticeGroup-updateOrderReview">' + data.messages + '</div>' );
							} else {
								$form.prepend( data );
							}

							// Lose focus for all fields
							$form.find( '.input-text, select, input:checkbox' ).trigger( 'validate' ).trigger( 'blur' );

							er_checkout_form.scroll_to_notices();
						}

						// Re-init methods
						er_checkout_form.init_payment_methods();

						// Fire updated_checkout event.
						$( document.body ).trigger( 'updated_checkout', [ data ] );
					},
				} );
			},
		};

		var er_checkout_coupons = {
			init: function() {
				$( document.body ).on( 'click', 'a.showcoupon', this.show_coupon_form );
				$( document.body ).on( 'click', '.easyreservations-remove-coupon', this.remove_coupon );
				$( 'form.checkout_coupon' ).hide().on( 'submit', this.submit );
			},
			show_coupon_form: function() {
				$( '.checkout_coupon' ).slideToggle( 400, function() {
					$( '.checkout_coupon' ).find( ':input:eq(0)' ).focus();
				} );
				return false;
			},
			submit: function() {
				var $form = $( this );

				if ( $form.is( '.processing' ) ) {
					return false;
				}

				$form.addClass( 'processing' ).block( {
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				} );

				var data = {
					security: er_checkout_params.apply_coupon_nonce,
					coupon_code: $form.find( 'input[name="coupon_code"]' ).val()
				};

				$.ajax( {
					type: 'POST',
					url: er_checkout_params.er_ajax_url.toString().replace( '%%endpoint%%', 'apply_coupon' ),
					data: data,
					success: function( code ) {
						$( '.easyreservations-error, .easyreservations-message' ).remove();
						$form.removeClass( 'processing' ).unblock();

						if ( code ) {
							$form.before( code );
							$form.slideUp()

							$( document.body ).trigger( 'applied_coupon_in_checkout', [ data.coupon_code ] );
							$( document.body ).trigger( 'update_checkout', {} );
						}
					},
					dataType: 'html',
				} );

				return false;
			},
			remove_coupon: function( e ) {
				e.preventDefault();

				var container = $( this ).parents( '.easyreservations-checkout-review-order' ),
					coupon = $( this ).data( 'coupon' );

				container.addClass( 'processing' ).block( {
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6,
					},
				} );

				var data = {
					security: er_checkout_params.remove_coupon_nonce,
					coupon: coupon,
				};

				$.ajax( {
					type: 'POST',
					url: er_checkout_params.er_ajax_url.toString().replace( '%%endpoint%%', 'remove_coupon' ),
					data: {
						security: er_checkout_params.remove_coupon_nonce,
						coupon: coupon,
					},
					success: function( code ) {
						$( '.easyreservations-error, .easyreservations-message' ).remove();
						container.removeClass( 'processing' ).unblock();

						if ( code ) {
							$( 'form.easyreservations-checkout' ).before( code );

							$( document.body ).trigger( 'removed_coupon_in_checkout', [ data.coupon ] );
							$( document.body ).trigger( 'update_checkout', {} );

							// Remove coupon code from coupon field
							$( 'form.checkout_coupon' ).find( 'input[name="coupon_code"]' ).val( '' );
						}
					},
					error: function( jqXHR ) {
						if ( er_checkout_params.debug_mode ) {
							/* jshint devel: true */
							console.log( jqXHR.responseText );
						}
					},
					dataType: 'html',
				} );
			}
		};

		var er_checkout_login_form = {
			init: function() {
				$( document.body ).on( 'click', 'a.showlogin', this.show_login_form );
			},
			show_login_form: function() {
				$( 'form.login, form.easyreservations-form--login' ).slideToggle();
				return false;
			}
		};

		var er_terms_toggle = {
			init: function() {
				$( document.body ).on( 'click', 'a.easyreservations-terms-and-conditions-link', this.toggle_terms );
			},

			toggle_terms: function() {
				if ( $( '.easyreservations-terms-and-conditions' ).length ) {
					$( '.easyreservations-terms-and-conditions' ).slideToggle( function() {
						var link_toggle = $( '.easyreservations-terms-and-conditions-link' );

						if ( $( '.easyreservations-terms-and-conditions' ).is( ':visible' ) ) {
							link_toggle.addClass( 'easyreservations-terms-and-conditions-link--open' );
							link_toggle.removeClass( 'easyreservations-terms-and-conditions-link--closed' );
						} else {
							link_toggle.removeClass( 'easyreservations-terms-and-conditions-link--open' );
							link_toggle.addClass( 'easyreservations-terms-and-conditions-link--closed' );
						}
					} );

					return false;
				}
			}
		};

		er_checkout_form.init();
		er_checkout_coupons.init();
		er_checkout_login_form.init();
		er_terms_toggle.init();

		$( 'form.checkout > label' ).each( function() {
			$( this ).next( 'div.content' ).addBack().wrapAll( '<p class="form-row"/>' );
		} );

	} );
} )
( jQuery, er_checkout_params );
