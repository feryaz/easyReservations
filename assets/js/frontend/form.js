jQuery( function( $ ) {
	$.fn.serializeObject = function() {
		var o = {};
		$.each( this.serializeArray(), function() {
			if ( o[ this.name ] ) {
				if ( this.name.slice( -2 ) === '[]' ) {
					if ( ! o[ this.name ].push ) {
						o[ this.name ] = [ o[ this.name ] ];
					}
					o[ this.name ].push( this.value || '' );
				} else {
					o[ this.name ] = this.value || '';
				}
			} else {
				o[ this.name ] = this.value || '';
			}
		} );

		return o;
	};

	var er_form = {
		$form: $( 'form[rel=js-easy-form]' ),
		init: function() {
			$( document.body ).on( 'submit', 'form[rel=js-easy-form]', this.submit );

			$( 'form[rel=js-easy-form] > div > label, .easyreservations-additional-fields > label' ).each( function() {
				$( this ).next( 'div.content, div.easy-date-selection' ).addBack().wrapAll( '<div class="form-row"/>' );
			} );

			er_form.$form.attr( 'novalidate', 'novalidate' );

			if ( this.$form.attr( 'name' ) !== 'checkout' ) {
				this.$form.on( 'change', '.validate', this.validate );
			}

			er_form.$form.on( 'input validate change', '.input-text, select, input:checkbox, input:text, textarea', this.validate_field );
		},

		ajaxRequest: function( form, submit ) {
			if ( form.is( '.processing' ) ) {
				return false;
			}

			form.addClass( 'processing' );

			$( document.body ).trigger( 'easyreservations_validate_form' );

			if ( submit ) {
				form.block( {
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6,
					},
				} );
			}

			form.find( '.easy-price' ).block( {
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6,
				},
			} );

			const data = form.serializeObject();

			data.action = 'easyreservations_form';

			if ( submit ) {
				data.submit = 'yes';
				$( document.body ).trigger( 'adding_to_cart' );
			}

			$.ajax( {
				type: 'POST',
				url: er_both_params.ajaxurl,
				data: data,
				dataType: 'json',
				success: function( result ) {
					er_form.detachUnloadEventsOnSubmit();
					try {
						if ( 'success' === result.result ) {
							if ( result.redirect ) {
								if ( -1 === result.redirect.indexOf( 'https://' ) || -1 === result.redirect.indexOf( 'http://' ) ) {
									window.location = result.redirect;
								} else {
									window.location = decodeURI( result.redirect );
								}
							} else {
								if ( result.price ) {
									form.find( '.easy-price' ).unblock().css( 'display', 'block' );
									form.find( '.easy-price-display' ).html( result.price_formatted );

									$( document.body ).trigger( 'easyreservations_price_has_changed', [ result.label, result.price ] );
								} else {
									form.find( '.easy-price' ).css( 'display', 'none' );
								}

								if ( result.order_review ) {
									form.find( '.easyreservations-checkout-review-order' ).html( result.order_review );
								}

								$( '.easyreservations-NoticeGroup-checkout, .easyreservations-error, .easyreservations-message' ).remove();

								if ( result.messages ) {
									form.html( '<div class="easyreservations-message">' + result.messages + '</div>' );
									er_form.scroll_to_notices();
								} else {
									form.find( '.input-text, select, input:checkbox, input:text, textarea' ).trigger( 'validate' ).trigger( 'blur' );
								}

								if ( result.added_to_cart ) {
									$( document.body ).trigger( 'added_to_cart' ).trigger( 'updated_er_div' );
								}

								form.removeClass( 'processing' ).unblock();
							}
						} else if ( 'failure' === result.result ) {
							throw 'Result failure';
						} else {
							throw 'Invalid response';
						}
					} catch ( err ) {
						// Reload page
						if ( true === result.reload ) {
							window.location.reload();
							return;
						}

						form.find( '.easy-price' ).unblock().css( 'display', 'none' );

						// Trigger update in case we need a fresh nonce
						if ( true === result.refresh ) {
							$( document.body ).trigger( 'update_checkout' );
						}

						// Add new errors
						if ( result.messages ) {
							er_form.submit_error( form, result.messages );
						} else {
							form.removeClass( 'processing' ).unblock();
						}
					}
				},
				error: function( jqXHR, textStatus, errorThrown ) {
					er_form.submit_error( form, '<div class="easyreservations-error">' + errorThrown + '</div>' );
				},
			} );
		},

		validate_field: function( e ) {
			var $this = $( this ),
				$parent = $this.closest( '.form-row' ),
				validated = true,
				validate_required = $parent.is( '.validate-required' ),
				validate_email = $parent.is( '.validate-email' ),
				event_type = e.type;

			if ( $this.hasClass( 'do-not-validate' ) ) {
				return;
			}

			if ( 'input' === event_type ) {
				$parent.removeClass( 'easyreservations-invalid easyreservations-invalid-required-field easyreservations-invalid-email easyreservations-validated' );
			}

			if ( 'validate' === event_type || 'change' === event_type ) {
				if ( validate_required ) {
					if ( 'checkbox' === $this.attr( 'type' ) && ! $this.is( ':checked' ) ) {
						$parent.removeClass( 'easyreservations-validated' ).addClass( 'easyreservations-invalid easyreservations-invalid-required-field' );
						validated = false;
					} else if ( $this.val() === '' ) {
						$parent.removeClass( 'easyreservations-validated' ).addClass( 'easyreservations-invalid easyreservations-invalid-required-field' );
						validated = false;
					}
				}

				if ( validate_email ) {
					if ( $this.val() ) {
						/* https://stackoverflow.com/questions/2855865/jquery-validate-e-mail-address-regex */
						var pattern = new RegExp( /^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i );

						if ( ! pattern.test( $this.val() ) ) {
							$parent.removeClass( 'easyreservations-validated' ).addClass( 'easyreservations-invalid easyreservations-invalid-email' );
							validated = false;
						}
					}
				}

				if ( validated ) {
					if ( $( '.er-error-type[data-type="' + $this.attr( 'name' ) + '"]' ).length > 0 ) {
						$parent.removeClass( 'easyreservations-validated' ).addClass( 'easyreservations-invalid' );
						validated = false;
					}
				}

				if ( validated ) {
					$parent.removeClass( 'easyreservations-invalid easyreservations-invalid-required-field easyreservations-invalid-email' ).addClass( 'easyreservations-validated' );
				}
			}
		},

		submit_error: function( form, error_message ) {
			$( '.easyreservations-NoticeGroup-checkout, .easyreservations-error, .easyreservations-message' ).remove();
			form.prepend( '<div class="easyreservations-NoticeGroup easyreservations-NoticeGroup-checkout">' + error_message + '</div>' );
			form.removeClass( 'processing' ).unblock();
			form.find( '.input-text, select, input:checkbox, input:text, textarea' ).trigger( 'validate' ).trigger( 'blur' );
			er_form.scroll_to_notices();

			$( document.body ).trigger( 'form_error' );
		},

		scroll_to_notices: function() {
			var scrollElement = $( '.easyreservations-NoticeGroup-updateOrderReview, .easyreservations-NoticeGroup-checkout' );

			if ( ! scrollElement.length ) {
				scrollElement = $( 'form[rel=js-easy-form]' );
			}

			if ( scrollElement.length ) {
				$( 'html, body' ).animate( {
					scrollTop: ( scrollElement.offset().top - 100 ),
				}, 1000 );
			}
		},

		submit: function( e ) {
			e.preventDefault();
			if ( er_form.$form.triggerHandler( 'checkout_place_order' ) !== false && er_form.$form.triggerHandler( 'checkout_place_order_' + er_form.get_payment_method() ) !== false ) {
				er_form.ajaxRequest( $( this ).closest( 'form' ), true );
			}
		},
		get_payment_method: function() {
			return er_form.$form.find( 'input[name="payment_method"]:checked' ).val();
		},

		validate: function() {
			er_form.ajaxRequest( $( this ).closest( 'form' ), false );
		},

		handleUnloadEvent: function( e ) {
			// Modern browsers have their own standard generic messages that they will display.
			// Confirm, alert, prompt or custom message are not allowed during the unload event
			// Browsers will display their own standard messages

			// Check if the browser is Internet Explorer
			if ( ( navigator.userAgent.indexOf( 'MSIE' ) !== -1 ) || ( !! document.documentMode ) ) {
				// IE handles unload events differently than modern browsers
				e.preventDefault();
				return undefined;
			}

			return true;
		},
		attachUnloadEventsOnSubmit: function() {
			$( window ).on( 'beforeunload', this.handleUnloadEvent );
		},
		detachUnloadEventsOnSubmit: function() {
			$( window ).off( 'beforeunload', this.handleUnloadEvent );
		},
	};

	er_form.init();
} );