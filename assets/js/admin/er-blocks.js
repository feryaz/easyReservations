/* global er_blocks_params */

( function() {
	const el = wp.element.createElement,
		iconEl = el( 'svg', { width: 24, height: 24 },
			el( 'path', { d: 'M7.42 10.05c-.18-.16-.46-.23-.84-.23H6l.02 2.44.04 2.45.56-.02c.41 0 .63-.07.83-.26.24-.24.26-.36.26-2.2 0-1.91-.02-1.96-.29-2.18zM0 4.94v14.12h24V4.94H0zM8.56 15.3c-.44.58-1.06.77-2.53.77H4.71V8.53h1.4c1.67 0 2.16.18 2.6.9.27.43.29.6.32 2.57.05 2.23-.02 2.73-.47 3.3zm5.09-5.47h-2.47v1.77h1.52v1.28l-.72.04-.75.03v1.77l1.22.03 1.2.04v1.28h-1.6c-1.53 0-1.6-.01-1.87-.3l-.3-.28v-3.16c0-3.02.01-3.18.25-3.48.23-.31.25-.31 1.88-.31h1.64v1.3zm4.68 5.45c-.17.43-.64.79-1 .79-.18 0-.45-.15-.67-.39-.32-.32-.45-.63-.82-2.08l-.9-3.39-.45-1.67h.76c.4 0 .75.02.75.05 0 .06 1.16 4.54 1.26 4.83.04.15.32-.7.73-2.3l.66-2.52.74-.04c.4-.02.73 0 .73.04 0 .14-1.67 6.38-1.8 6.68z' } )
		);

	wp.blocks.updateCategory( 'easy-reservations', { icon: iconEl } );

	wp.blocks.registerBlockType( 'easy-reservations/form', {
		title: wp.i18n.__( 'Form', 'easyReservations' ),
		icon: 'format-aside',
		category: 'easy-reservations',

		attributes: {
			content: { type: 'string' },
			color: { type: 'string' },
			form_template: { type: 'string' },
			redirect: { type: 'string' },
			direct_checkout: {type: 'bool'},
			price: { type: 'bool' },
			inline: { type: 'bool' },
		},

		edit: function( props ) {
			return el(
				'div',
				{ className: 'components-placeholder' },
				el(
					'div',
					{ className: 'components-placeholder__label' },
					wp.i18n.__( 'easyReservations Form', 'easyReservations' )
				),
				el(
					'div',
					{ className: 'components-placeholder__fieldset' },
					el(
						'div',
						null,
						wp.i18n.__( 'The form is used to add reservations to the shopping cart.', 'easyReservations' )
					),
					el(
						'div',
						{ className: 'easyreservations-block-list' },
						el(
							'div',
							{ className: 'easyreservations-block-list-element' },
							el(
								'div',
								{ className: 'components-base-control' },
								el( wp.components.SelectControl, {
									label: wp.i18n.__( 'Form template', 'easyReservations' ),
									style: { 'display': 'block' },
									className: 'components-text-control__input',
									value: props.attributes.form_template,
									onChange: function( value ) {
										props.setAttributes( { form_template: value } );
									},
									options: easyData.form_templates,
								} )
							)
						),
						el(
							'div',
							{ className: 'easyreservations-block-list-element' },
							el( wp.components.SelectControl, {
								label: wp.i18n.__( 'After submit redirect to', 'easyReservations' ),
								style: { 'display': 'block' },
								className: 'components-text-control__input',
								value: props.attributes.redirect,
								onChange: function( value ) {
									props.setAttributes( { redirect: value } );
								},
								options: easyData.pages.slice( 1 ),
							} )
						),
						el(
							'div',
							{ className: 'easyreservations-block-list-element' },
							el( wp.components.CheckboxControl, {
								label: wp.i18n.__( 'Direct checkout', 'easyReservations' ),
								checked: props.attributes.direct_checkout,
								onChange: function( val ) {
									if ( val ) {
										props.setAttributes( { direct_checkout: true } );
									} else {
										props.setAttributes( { direct_checkout: false } );
									}
								},
							} ),
							el( wp.components.CheckboxControl, {
								label: wp.i18n.__( 'Inline style', 'easyReservations' ),
								checked: props.attributes.inline,
								onChange: function( val ) {
									if ( val ) {
										props.setAttributes( { inline: true } );
									} else {
										props.setAttributes( { inline: false } );
									}
								},
							} ),
							el( wp.components.CheckboxControl, {
								label: wp.i18n.__( 'Display price', 'easyReservations' ),
								checked: props.attributes.price,
								onChange: function( val ) {
									if ( val ) {
										props.setAttributes( { price: true } );
									} else {
										props.setAttributes( { price: false } );
									}
								},
							} )
						)
					)
				)
			);
		},
		save: function( props ) {
			let shortcode = '[easy_form';

			if ( props.attributes && props.attributes.form_template !== 'undefined' && props.attributes.form_template !== undefined && props.attributes.form_template ) {
				shortcode += ' ' + props.attributes.form_template;
			}

			if ( props.attributes.inline ) {
				shortcode += ' inline="1"';
			}

			if ( props.attributes.direct_checkout ) {
				shortcode += ' direct_checkout="1"';
			}

			if ( props.attributes.price ) {
				shortcode += ' price="1"';
			}

			if ( props.attributes.redirect ) {
				shortcode += ' redirect="' + props.attributes.redirect + '"';
			}

			return shortcode + ']';
		},
	} );
}() );

function easyDataPreparation() {
	this.form_templates = function() {
		const opts = [];
		jQuery.each( er_blocks_params.form_templates, function( k, v ) {
			opts.push( { value: k, label: v } );
		} );
		easyData.form_templates = opts;
	};

	this.pages = function() {
		const opts = [ { value: 'res', label: wp.i18n.__( 'Resources page', 'easyReservations' ) } ];

		jQuery.each( er_blocks_params.pages, function( k, v ) {
			opts.push( { value: k, label: v } );
		} );

		easyData.pages = opts;
	};

	this.pages();
	this.form_templates();
}

const easyData = { form_templates: [], pages: [] };
easyDataPreparation();
