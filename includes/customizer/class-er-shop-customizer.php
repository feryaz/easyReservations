<?php
/**
 * Adds options to the customizer for easyReservations.
 *
 * @package easyReservations
 */

defined( 'ABSPATH' ) || exit;

/**
 * ER_Shop_Customizer class.
 */
class ER_Shop_Customizer {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'customize_register', array( $this, 'add_sections' ) );
		add_action( 'customize_controls_print_styles', array( $this, 'add_styles' ) );
		add_action( 'customize_controls_print_scripts', array( $this, 'add_scripts' ), 30 );
	}

	/**
	 * Add settings to the customizer.
	 *
	 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
	 */
	public function add_sections( $wp_customize ) {
		$wp_customize->add_panel( 'easyreservations', array(
			'priority'       => 200,
			'capability'     => 'manage_easyreservations',
			'theme_supports' => '',
			'title'          => 'easyReservations',
		) );

		$this->add_resource_page_section( $wp_customize );
		$this->add_resource_catalog_section( $wp_customize );
		$this->add_resource_images_section( $wp_customize );
		$this->add_checkout_section( $wp_customize );
	}

	/**
	 * CSS styles to improve our form.
	 */
	public function add_styles() {
		?>
        <style type="text/css">
            .easyreservations-cropping-control {
                margin: 0 40px 1em 0;
                padding: 0;
                display: inline-block;
                vertical-align: top;
            }

            .easyreservations-cropping-control input[type=radio] {
                margin-top: 1px;
            }

            .easyreservations-cropping-control span.easyreservations-cropping-control-aspect-ratio {
                margin-top: .5em;
                display: block;
            }

            .easyreservations-cropping-control span.easyreservations-cropping-control-aspect-ratio input {
                width: auto;
                display: inline-block;
            }
        </style>
		<?php
	}

	/**
	 * Scripts to improve our form.
	 */
	public function add_scripts() {
		$min_rows    = er_get_theme_support( 'resource_grid::min_rows', 1 );
		$max_rows    = er_get_theme_support( 'resource_grid::max_rows', '' );
		$min_columns = er_get_theme_support( 'resource_grid::min_columns', 1 );
		$max_columns = er_get_theme_support( 'resource_grid::max_columns', '' );

		/* translators: %d: Setting value */
		$min_notice = __( 'The minimum allowed setting is %d', 'easyReservations' );
		/* translators: %d: Setting value */
		$max_notice = __( 'The maximum allowed setting is %d', 'easyReservations' );
		?>
        <script type="text/javascript">
			jQuery( function( $ ) {
				$( document.body ).on( 'change', '.easyreservations-cropping-control input[type="radio"]', function() {
					var $wrapper = $( this ).closest( '.easyreservations-cropping-control' ),
						value    = $wrapper.find( 'input:checked' ).val();

					if ( 'custom' === value ) {
						$wrapper.find( '.easyreservations-cropping-control-aspect-ratio' ).slideDown( 200 );
					} else {
						$wrapper.find( '.easyreservations-cropping-control-aspect-ratio' ).hide();
					}

					return false;
				} );

				wp.customize.bind( 'ready', function() { // Ready?
					$( '.easyreservations-cropping-control' ).find( 'input:checked' ).trigger( 'change' );
				} );

				wp.customize.section( 'reservations_resource_catalog', function( section ) {
					section.expanded.bind( function( isExpanded ) {
						if ( isExpanded ) {
							wp.customize.previewer.previewUrl.set( '<?php echo esc_js( er_get_page_permalink( 'shop' ) ); ?>' );
						}
					} );
				} );

				wp.customize.section( 'reservations_resource_images', function( section ) {
					section.expanded.bind( function( isExpanded ) {
						if ( isExpanded ) {
							wp.customize.previewer.previewUrl.set( '<?php echo esc_js( er_get_page_permalink( 'shop' ) ); ?>' );
						}
					} );
				} );

				wp.customize.section( 'reservations_checkout', function( section ) {
					section.expanded.bind( function( isExpanded ) {
						if ( isExpanded ) {
							wp.customize.previewer.previewUrl.set( '<?php echo esc_js( er_get_page_permalink( 'checkout' ) ); ?>' );
						}
					} );
				} );

				wp.customize( 'reservations_catalog_columns', function( setting ) {
					setting.bind( function( value ) {
						var min = parseInt( '<?php echo esc_js( $min_columns ); ?>', 10 );
						var max = parseInt( '<?php echo esc_js( $max_columns ); ?>', 10 );

						value = parseInt( value, 10 );

						if ( max && value > max ) {
							setting.notifications.add( 'max_columns_error', new wp.customize.Notification(
								'max_columns_error',
								{
									type:    'error',
									message: '<?php echo esc_js( sprintf( $max_notice, $max_columns ) ); ?>'
								}
							) );
						} else {
							setting.notifications.remove( 'max_columns_error' );
						}

						if ( min && value < min ) {
							setting.notifications.add( 'min_columns_error', new wp.customize.Notification(
								'min_columns_error',
								{
									type:    'error',
									message: '<?php echo esc_js( sprintf( $min_notice, $min_columns ) ); ?>'
								}
							) );
						} else {
							setting.notifications.remove( 'min_columns_error' );
						}
					} );
				} );

				wp.customize( 'reservations_catalog_rows', function( setting ) {
					setting.bind( function( value ) {
						var min = parseInt( '<?php echo esc_js( $min_rows ); ?>', 10 );
						var max = parseInt( '<?php echo esc_js( $max_rows ); ?>', 10 );

						value = parseInt( value, 10 );

						if ( max && value > max ) {
							setting.notifications.add( 'max_rows_error', new wp.customize.Notification(
								'max_rows_error',
								{
									type:    'error',
									message: '<?php echo esc_js( sprintf( $max_notice, $max_rows ) ); ?>'
								}
							) );
						} else {
							setting.notifications.remove( 'max_rows_error' );
						}

						if ( min && value < min ) {
							setting.notifications.add( 'min_rows_error', new wp.customize.Notification(
								'min_rows_error',
								{
									type:    'error',
									message: '<?php echo esc_js( sprintf( $min_notice, $min_rows ) ); ?>'
								}
							) );
						} else {
							setting.notifications.remove( 'min_rows_error' );
						}
					} );
				} );
			} );
        </script>
		<?php
	}

	public function sanitize_default_catalog_orderby( $value ) {
		$options = apply_filters( 'easyreservations_default_catalog_orderby_options', array(
			'menu_order' => __( 'Default sorting (custom ordering + name)', 'easyReservations' ),
			'date'       => __( 'Sort by most recent', 'easyReservations' ),
			'price'      => __( 'Sort by price (asc)', 'easyReservations' ),
			'price-desc' => __( 'Sort by price (desc)', 'easyReservations' ),
		) );

		return array_key_exists( $value, $options ) ? $value : 'menu_order';
	}

	/**
	 * Resource page section.
	 *
	 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
	 */
	public function add_resource_page_section( $wp_customize ) {
		$wp_customize->add_section(
			'reservations_resource_page',
			array(
				'title'    => __( 'Resource Page', 'easyreservations' ),
				'priority' => 10,
				'panel'    => 'easyreservations',
			)
		);

		$wp_customize->add_setting(
			'reservations_resource_page_redirect',
			array(
				'default'    => '',
				'type'       => 'option',
				'capability' => 'manage_easyreservations',
			)
		);

		$pages        = get_pages( array(
			'post_type'   => 'page',
			'post_status' => 'publish,private,draft',
			'child_of'    => 0,
			'parent'      => - 1,
			'sort_order'  => 'asc',
			'sort_column' => 'post_title',
		) );
		$page_choices = array( '' => __( 'No page set', 'easyReservations' ) ) + array_combine( array_map( 'strval', wp_list_pluck( $pages, 'ID' ) ), wp_list_pluck( $pages, 'post_title' ) );

		$wp_customize->add_control(
			'reservations_resource_page_redirect',
			array(
				'label'       => __( 'Continue', 'easyReservations' ),
				'description' => __( 'Select to which page the guest should continue on after adding the resource to cart.', 'easyReservations' ),
				'section'     => 'reservations_resource_page',
				'settings'    => 'reservations_resource_page_redirect',
				'type'        => 'select',
				'choices'     => apply_filters( 'easyreservations_resource_page_redirect_options', $page_choices ),
			)
		);

		$wp_customize->add_setting(
			'reservations_resource_page_display_price',
			array(
				'default'              => 'yes',
				'type'                 => 'option',
				'capability'           => 'manage_easyreservations',
				'sanitize_callback'    => 'er_bool_to_string',
				'sanitize_js_callback' => 'er_string_to_bool',
			)
		);

		$wp_customize->add_control(
			'reservations_resource_page_display_price',
			array(
				'label'    => __( 'Display base price', 'easyReservations' ),
				'section'  => 'reservations_resource_page',
				'settings' => 'reservations_resource_page_display_price',
				'type'     => 'checkbox',
			)
		);
	}

	/**
	 * Resource catalog section.
	 *
	 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
	 */
	public function add_resource_catalog_section( $wp_customize ) {
		$wp_customize->add_section(
			'reservations_resource_catalog',
			array(
				'title'    => __( 'Resource Catalog', 'easyreservations' ),
				'priority' => 10,
				'panel'    => 'easyreservations',
			)
		);

		$wp_customize->add_setting(
			'reservations_default_catalog_orderby',
			array(
				'default'           => 'menu_order',
				'type'              => 'option',
				'capability'        => 'manage_easyreservations',
				'sanitize_callback' => array( $this, 'sanitize_default_catalog_orderby' ),
			)
		);

		$wp_customize->add_control(
			'reservations_default_catalog_orderby',
			array(
				'label'       => __( 'Default resource sorting', 'easyReservations' ),
				'description' => __( 'How should resources be sorted in the catalog by default?', 'easyReservations' ),
				'section'     => 'reservations_resource_catalog',
				'settings'    => 'reservations_default_catalog_orderby',
				'type'        => 'select',
				'choices'     => apply_filters( 'easyreservations_default_catalog_orderby_options', array(
					'menu_order' => __( 'Default sorting (custom ordering + name)', 'easyReservations' ),
					'date'       => __( 'Sort by most recent', 'easyReservations' ),
					'price'      => __( 'Sort by price (asc)', 'easyReservations' ),
					'price-desc' => __( 'Sort by price (desc)', 'easyReservations' ),
				) ),
			)
		);

		$wp_customize->add_setting(
			'reservations_catalog_columns',
			array(
				'default'              => 4,
				'type'                 => 'option',
				'capability'           => 'manage_easyreservations',
				'sanitize_callback'    => 'absint',
				'sanitize_js_callback' => 'absint',
			)
		);

		$wp_customize->add_control(
			'reservations_catalog_columns',
			array(
				'label'       => __( 'Resources per row', 'easyReservations' ),
				'description' => __( 'How many resources should be shown per row?', 'easyReservations' ),
				'section'     => 'reservations_resource_catalog',
				'settings'    => 'reservations_catalog_columns',
				'type'        => 'number',
				'input_attrs' => array(
					'min'  => er_get_theme_support( 'resource_grid::min_columns', 1 ),
					'max'  => er_get_theme_support( 'resource_grid::max_columns', '' ),
					'step' => 1,
				),
			)
		);

		// Only add this setting if something else isn't managing the number of resources per page.
		if ( ! has_filter( 'loop_shop_per_page' ) ) {
			$wp_customize->add_setting(
				'reservations_catalog_rows',
				array(
					'default'              => 4,
					'type'                 => 'option',
					'capability'           => 'manage_easyreservations',
					'sanitize_callback'    => 'absint',
					'sanitize_js_callback' => 'absint',
				)
			);
		}

		$wp_customize->add_control(
			'reservations_catalog_rows',
			array(
				'label'       => __( 'Rows per page', 'easyReservations' ),
				'description' => __( 'How many rows of resources should be shown per page?', 'easyReservations' ),
				'section'     => 'reservations_resource_catalog',
				'settings'    => 'reservations_catalog_rows',
				'type'        => 'number',
				'input_attrs' => array(
					'min'  => er_get_theme_support( 'resource_grid::min_rows', 1 ),
					'max'  => er_get_theme_support( 'resource_grid::max_rows', '' ),
					'step' => 1,
				),
			)
		);
	}

	/**
	 * Resource images section.
	 *
	 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
	 */
	private function add_resource_images_section( $wp_customize ) {
		if ( class_exists( 'Jetpack' ) && Jetpack::is_module_active( 'photon' ) ) {
			$regen_description = ''; // Nothing to report; Jetpack will handle magically.
		} elseif ( apply_filters( 'easyreservations_background_image_regeneration', true ) && ! is_multisite() ) {
			$regen_description = __( 'After publishing your changes, new image sizes will be generated automatically.', 'easyReservations' );
		} elseif ( apply_filters( 'easyreservations_background_image_regeneration', true ) && is_multisite() ) {
			/* translators: 1: tools URL 2: regen thumbs url */
			$regen_description = sprintf( __( 'After publishing your changes, new image sizes may not be shown until you regenerate thumbnails. You can do this from the <a href="%1$s" target="_blank">tools section in easyReservations</a> or by using a plugin such as <a href="%2$s" target="_blank">Regenerate Thumbnails</a>.', 'easyReservations' ), admin_url( 'admin.php?page=er-settings&tab=status&section=tools' ), 'https://en-gb.wordpress.org/plugins/regenerate-thumbnails/' );
		} else {
			/* translators: %s: regen thumbs url */
			$regen_description = sprintf( __( 'After publishing your changes, new image sizes may not be shown until you <a href="%s" target="_blank">Regenerate Thumbnails</a>.', 'easyReservations' ), 'https://en-gb.wordpress.org/plugins/regenerate-thumbnails/' );
		}

		$wp_customize->add_section(
			'reservations_resource_images',
			array(
				'title'       => __( 'Resource Images', 'easyReservations' ),
				'description' => $regen_description,
				'priority'    => 20,
				'panel'       => 'easyreservations',
			)
		);

		$wp_customize->add_setting(
			'reservations_placeholder_image',
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_easyreservations',
				'sanitize_callback' => array( $this, 'image_url_to_id' ),
			)
		);

		$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'reservations_placeholder_image_control', array(
			'label'    => __( 'Placeholder Image', 'easyReservations' ),
			'section'  => 'reservations_resource_images',
			'settings' => 'reservations_placeholder_image',
		) ) );

		if ( ! er_get_theme_support( 'single_image_width' ) ) {
			$wp_customize->add_setting(
				'reservations_single_image_width',
				array(
					'default'              => 600,
					'type'                 => 'option',
					'capability'           => 'manage_easyreservations',
					'sanitize_callback'    => 'absint',
					'sanitize_js_callback' => 'absint',
				)
			);

			$wp_customize->add_control(
				'reservations_single_image_width',
				array(
					'label'       => __( 'Main image width', 'easyReservations' ),
					'description' => __( 'Image size used for the main image on single resource pages. These images will remain uncropped.', 'easyReservations' ),
					'section'     => 'reservations_resource_images',
					'settings'    => 'reservations_single_image_width',
					'type'        => 'number',
					'input_attrs' => array(
						'min'  => 0,
						'step' => 1,
					),
				)
			);
		}

		if ( ! er_get_theme_support( 'thumbnail_image_width' ) ) {
			$wp_customize->add_setting(
				'reservations_thumbnail_image_width',
				array(
					'default'              => 300,
					'type'                 => 'option',
					'capability'           => 'manage_easyreservations',
					'sanitize_callback'    => 'absint',
					'sanitize_js_callback' => 'absint',
				)
			);

			$wp_customize->add_control(
				'reservations_thumbnail_image_width',
				array(
					'label'       => __( 'Thumbnail width', 'easyReservations' ),
					'description' => __( 'Image size used for resources in the catalog.', 'easyReservations' ),
					'section'     => 'reservations_resource_images',
					'settings'    => 'reservations_thumbnail_image_width',
					'type'        => 'number',
					'input_attrs' => array(
						'min'  => 0,
						'step' => 1,
					),
				)
			);
		}

		include_once RESERVATIONS_ABSPATH . 'includes/customizer/class-er-customizer-control-cropping.php';

		$wp_customize->add_setting(
			'reservations_thumbnail_cropping',
			array(
				'default'           => '1:1',
				'type'              => 'option',
				'capability'        => 'manage_easyreservations',
				'sanitize_callback' => 'er_clean',
			)
		);

		$wp_customize->add_setting(
			'reservations_thumbnail_cropping_custom_width',
			array(
				'default'              => '4',
				'type'                 => 'option',
				'capability'           => 'manage_easyreservations',
				'sanitize_callback'    => 'absint',
				'sanitize_js_callback' => 'absint',
			)
		);

		$wp_customize->add_setting(
			'reservations_thumbnail_cropping_custom_height',
			array(
				'default'              => '3',
				'type'                 => 'option',
				'capability'           => 'manage_easyreservations',
				'sanitize_callback'    => 'absint',
				'sanitize_js_callback' => 'absint',
			)
		);

		$wp_customize->add_control(
			new ER_Customizer_Control_Cropping(
				$wp_customize,
				'reservations_thumbnail_cropping',
				array(
					'section'  => 'reservations_resource_images',
					'settings' => array(
						'cropping'      => 'reservations_thumbnail_cropping',
						'custom_width'  => 'reservations_thumbnail_cropping_custom_width',
						'custom_height' => 'reservations_thumbnail_cropping_custom_height',
					),
					'label'    => __( 'Thumbnail cropping', 'easyReservations' ),
					'choices'  => array(
						'1:1'       => array(
							'label'       => __( '1:1', 'easyReservations' ),
							'description' => __( 'Images will be cropped into a square', 'easyReservations' ),
						),
						'custom'    => array(
							'label'       => __( 'Custom', 'easyReservations' ),
							'description' => __( 'Images will be cropped to a custom aspect ratio', 'easyReservations' ),
						),
						'uncropped' => array(
							'label'       => __( 'Uncropped', 'easyReservations' ),
							'description' => __( 'Images will display using the aspect ratio in which they were uploaded', 'easyReservations' ),
						),
					),
				)
			)
		);
	}

	/**
	 * Checkout section.
	 *
	 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
	 */
	public function add_checkout_section( $wp_customize ) {
		$wp_customize->add_section(
			'reservations_checkout',
			array(
				'title'       => __( 'Checkout', 'easyReservations' ),
				'priority'    => 20,
				'panel'       => 'easyreservations',
				'description' => __( 'These options let you change the appearance of the easyReservations checkout.', 'easyReservations' ),
			)
		);

		$wp_customize->add_setting(
			'reservations_checkout_address_field',
			array(
				'default'           => 'required',
				'type'              => 'option',
				'capability'        => 'manage_easyreservations',
				'sanitize_callback' => array( $this, 'sanitize_checkout_field_display' ),
			)
		);

		$wp_customize->add_control(
			'easyreservations_checkout_address_field',
			array(
				/* Translators: %s field name. */
				'label'    => sprintf( __( '%s fields', 'easyReservations' ), __( 'Address', 'easyReservations' ) ),
				'section'  => 'reservations_checkout',
				'settings' => 'reservations_checkout_address_field',
				'type'     => 'select',
				'choices'  => array(
					'hidden'   => __( 'Hidden', 'easyReservations' ),
					'required' => __( 'Required', 'easyReservations' ),
				),
			)
		);

		// Checkout field controls.
		$fields = array(
			'company'   => __( 'Company name', 'easyReservations' ),
			'address_2' => __( 'Address line 2', 'easyReservations' ),
			'phone'     => __( 'Phone', 'easyReservations' ),
		);

		foreach ( $fields as $field => $label ) {
			$wp_customize->add_setting(
				'reservations_checkout_' . $field . '_field',
				array(
					'default'           => 'phone' === $field ? 'required' : 'optional',
					'type'              => 'option',
					'capability'        => 'manage_easyreservations',
					'sanitize_callback' => array( $this, 'sanitize_checkout_field_display' ),
				)
			);
			$wp_customize->add_control(
				'easyreservations_checkout_' . $field . '_field',
				array(
					/* Translators: %s field name. */
					'label'    => sprintf( __( '%s field', 'easyReservations' ), $label ),
					'section'  => 'reservations_checkout',
					'settings' => 'reservations_checkout_' . $field . '_field',
					'type'     => 'select',
					'choices'  => array(
						'hidden'   => __( 'Hidden', 'easyReservations' ),
						'optional' => __( 'Optional', 'easyReservations' ),
						'required' => __( 'Required', 'easyReservations' ),
					),
				)
			);
		}

		// Register settings.
		$wp_customize->add_setting(
			'reservations_checkout_highlight_required_fields',
			array(
				'default'              => 'yes',
				'type'                 => 'option',
				'capability'           => 'manage_easyreservations',
				'sanitize_callback'    => 'er_bool_to_string',
				'sanitize_js_callback' => 'er_string_to_bool',
			)
		);

		$wp_customize->add_setting(
			'reservations_checkout_terms_and_conditions_checkbox_text',
			array(
				/* translators: %s terms and conditions page name and link */
				'default'           => sprintf( __( 'I have read and agree to the website %s', 'easyReservations' ), '[terms]' ),
				'type'              => 'option',
				'capability'        => 'manage_easyreservations',
				'sanitize_callback' => 'wp_kses_post',
				'transport'         => 'postMessage',
			)
		);

		$wp_customize->add_setting(
			'reservations_checkout_privacy_policy_text',
			array(
				/* translators: %s privacy policy page name and link */
				'default'           => sprintf( __( 'Your personal data will be used to process your order, support your experience throughout this website, and for other purposes described in our %s.', 'easyReservations' ), '[privacy_policy]' ),
				'type'              => 'option',
				'capability'        => 'manage_easyreservations',
				'sanitize_callback' => 'wp_kses_post',
				'transport'         => 'postMessage',
			)
		);

		// Register controls.
		$wp_customize->add_control(
			'reservations_checkout_highlight_required_fields',
			array(
				'label'    => __( 'Highlight required fields with an asterisk', 'easyReservations' ),
				'section'  => 'reservations_checkout',
				'settings' => 'reservations_checkout_highlight_required_fields',
				'type'     => 'checkbox',
			)
		);

		if ( current_user_can( 'manage_privacy_options' ) ) {
			$choose_pages = array(
				'wp_page_for_privacy_policy' => __( 'Privacy policy', 'easyReservations' ),
				'reservations_terms_page_id'  => __( 'Terms and conditions', 'easyReservations' ),
			);
		} else {
			$choose_pages = array(
				'reservations_terms_page_id' => __( 'Terms and conditions', 'easyReservations' ),
			);
		}

		$pages        = get_pages( array(
			'post_type'   => 'page',
			'post_status' => 'publish,private,draft',
			'child_of'    => 0,
			'parent'      => - 1,
			'exclude'     => array(
				er_get_page_id( 'cart' ),
				er_get_page_id( 'checkout' ),
				er_get_page_id( 'myaccount' ),
			),
			'sort_order'  => 'asc',
			'sort_column' => 'post_title',
		) );
		$page_choices = array( '' => __( 'No page set', 'easyReservations' ) ) + array_combine( array_map( 'strval', wp_list_pluck( $pages, 'ID' ) ), wp_list_pluck( $pages, 'post_title' ) );

		foreach ( $choose_pages as $id => $name ) {
			$wp_customize->add_setting(
				$id,
				array(
					'default'    => '',
					'type'       => 'option',
					'capability' => 'manage_easyreservations',
				)
			);
			$wp_customize->add_control(
				$id,
				array(
					/* Translators: %s: page name. */
					'label'    => sprintf( __( '%s page', 'easyReservations' ), $name ),
					'section'  => 'reservations_checkout',
					'settings' => $id,
					'type'     => 'select',
					'choices'  => $page_choices,
				)
			);
		}

		$wp_customize->add_control(
			'reservations_checkout_privacy_policy_text',
			array(
				'label'           => __( 'Privacy policy', 'easyReservations' ),
				'description'     => __( 'Optionally add some text about your store privacy policy to show during checkout.', 'easyReservations' ),
				'section'         => 'reservations_checkout',
				'settings'        => 'reservations_checkout_privacy_policy_text',
				'active_callback' => array( $this, 'has_privacy_policy_page_id' ),
				'type'            => 'textarea',
			)
		);

		$wp_customize->add_control(
			'reservations_checkout_terms_and_conditions_checkbox_text',
			array(
				'label'           => __( 'Terms and conditions', 'easyReservations' ),
				'description'     => __( 'Optionally add some text for the terms checkbox that customers must accept.', 'easyReservations' ),
				'section'         => 'reservations_checkout',
				'settings'        => 'reservations_checkout_terms_and_conditions_checkbox_text',
				'active_callback' => array( $this, 'has_terms_and_conditions_page_id' ),
				'type'            => 'text',
			)
		);

		if ( isset( $wp_customize->selective_refresh ) ) {
			$wp_customize->selective_refresh->add_partial(
				'reservations_checkout_privacy_policy_text', array(
					'selector'            => '.easyreservations-privacy-policy-text',
					'container_inclusive' => true,
					'render_callback'     => 'er_checkout_privacy_policy_text',
				)
			);
			$wp_customize->selective_refresh->add_partial(
				'reservations_checkout_terms_and_conditions_checkbox_text', array(
					'selector'            => '.easyreservations-terms-and-conditions-checkbox-text',
					'container_inclusive' => false,
					'render_callback'     => 'er_terms_and_conditions_checkbox_text',
				)
			);
		}
	}

	/**
	 * Sanitize placeholder image setting.
	 *
	 * @param string $value URL of image.
	 *
	 * @return string
	 */
	public function image_url_to_id( $value ) {
		$id = attachment_url_to_postid( $value );

		return $id ? $id : '';
	}

	/**
	 * Sanitize field display.
	 *
	 * @param string $value '', 'subcategories', or 'both'.
	 *
	 * @return string
	 */
	public function sanitize_checkout_field_display( $value ) {
		$options = array( 'hidden', 'optional', 'required' );

		return in_array( $value, $options, true ) ? $value : '';
	}

	/**
	 * Whether or not a page has been chose for the privacy policy.
	 *
	 * @return bool
	 */
	public function has_privacy_policy_page_id() {
		return er_privacy_policy_page_id() > 0;
	}

	/**
	 * Whether or not a page has been chose for the terms and conditions.
	 *
	 * @return bool
	 */
	public function has_terms_and_conditions_page_id() {
		return er_terms_and_conditions_page_id() > 0;
	}
}

new ER_Shop_Customizer();
