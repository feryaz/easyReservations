( function () {
	var el = wp.element.createElement;
	var circle = el( 'circle', { cx: 10, cy: 10, r: 10, fill: 'red', stroke: 'blue', strokeWidth: '10' } );
	var svgIcon = el( wp.components.SVG, { width: 20, height: 20, viewBox: '0 0 20 20' }, circle );
	wp.blocks.updateCategory( 'easy-reservations', { icon: svgIcon } );

	wp.blocks.registerBlockType( 'easy-reservations/form', {
		title: wp.i18n.__( 'Form', 'easyReservations' ),
		icon: 'format-aside',
		category: 'easy-reservations',

		attributes: {
			content: { type: 'string' },
			color: { type: 'string' },
			form_template: { type: 'string' },
			redirect: { type: 'string' },
			//direct_checkout: {type: 'bool'},
			price: { type: 'bool' },
			inline: { type: 'bool' }
		},

		edit: function ( props ) {

			return el(
				"div",
				{ className: "components-placeholder" },
				el(
					"div",
					{ className: "components-placeholder__label" },
					wp.i18n.__( 'easyReservations Form', 'easyReservations' )
				),
				el(
					"div",
					{ className: "components-placeholder__fieldset" },
					el(
						"div",
						null,
						wp.i18n.__( 'The form is used to add reservations to the shopping cart.', 'easyReservations' )
					),
					el(
						"div",
						{ className: "easyreservations-block-list" },
						el(
							"div",
							{ className: "easyreservations-block-list-element" },
							el(
								"div",
								{ className: "components-base-control" },
								el( wp.components.SelectControl, {
									label: wp.i18n.__( 'Form template', 'easyReservations' ),
									className: "components-text-control__input",
									value: props.attributes.form_template,
									onChange: function ( value ) {
										props.setAttributes( { form_template: value } )
									},
									options: easy_data.form_templates
								} )
							)
						),
						el(
							"div",
							{ className: "easyreservations-block-list-element" },
							el( wp.components.SelectControl, {
								label: wp.i18n.__( 'After submit redirect to', 'easyReservations' ),
								className: "components-text-control__input",
								value: props.attributes.redirect,
								onChange: function ( value ) {
									props.setAttributes( { redirect: value } )
								},
								options: easy_data.pages.slice( 1 )
							} )
						),
						el(
							"div",
							{ className: "easyreservations-block-list-element" },
							/*el(wp.components.CheckboxControl, {
								label: wp.i18n.__('Direct checkout', 'easyReservations'),
								checked: props.attributes.direct_checkout,
								onChange: function (val) {
									if (val) {
										props.setAttributes({direct_checkout: true})
									} else {
										props.setAttributes({direct_checkout: false})
									}
								}
							}),*/
							el( wp.components.CheckboxControl, {
								label: wp.i18n.__( 'Inline style', 'easyReservations' ),
								checked: props.attributes.inline,
								onChange: function ( val ) {
									if ( val ) {
										props.setAttributes( { inline: true } )
									} else {
										props.setAttributes( { inline: false } )
									}
								}
							} ),
							el( wp.components.CheckboxControl, {
								label: wp.i18n.__( 'Display price', 'easyReservations' ),
								checked: props.attributes.price,
								onChange: function ( val ) {
									if ( val ) {
										props.setAttributes( { price: true } )
									} else {
										props.setAttributes( { price: false } )
									}
								}
							} )
						)
					)
				)
			);
		},
		save: function ( props ) {
			var shortcode = '[easy_form';

			if( props.attributes && props.attributes.form_template !== 'undefined' && props.attributes.form_template !== undefined && props.attributes.form_template) {
				shortcode += ' ' + props.attributes.form_template;
			}

			if ( props.attributes.inline ) {
				shortcode += ' inline="1"';
			}

			//if(props.attributes.direct_checkout){
			//    shortcode += ' direct_checkout="1"';
			//}

			if ( props.attributes.price ) {
				shortcode += ' price="1"';
			}

			if ( props.attributes.redirect ) {
				shortcode += ' redirect="' + props.attributes.redirect + '"';
			}

			return shortcode + ']';
		}
	} );

} )();

function easy_data_preparation() {
	this.form_templates = function () {
		let opts = [];
		jQuery.each( er_blocks_params.form_templates, function ( k, v ) {
			opts.push( { value: k, label: v } );
		} );
		easy_data.form_templates = opts;
	};

	this.pages = function () {
		let opts = [ { value: 'res', label: wp.i18n.__( 'Resources page', 'easyReservations' ) } ];

		jQuery.each( er_blocks_params.pages, function ( k, v ) {
			opts.push( { value: k, label: v } );
		} );

		easy_data.pages = opts;
	};

	this.pages();
	this.form_templates();
}

const easy_data = { form_templates: [], pages: [] };
easy_data_preparation();
