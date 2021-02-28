<?php
defined( 'ABSPATH' ) || exit;

/**
 * ER_Settings_General.
 */
class ER_Settings_Tax extends ER_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'tax';
		$this->label = __( 'Tax', 'easyReservations' );

		parent::__construct();
	}

	/**
	 * Output tax settings
	 */
	public function output() {
		$settings = $this->get_settings();

		ER_Admin::output_settings( $settings );

		wp_localize_script(
			'er-settings-tax', 'htmlSettingsTaxLocalizeScript', array(
				'rates'        => array_values( ER_Tax::get_tax_rates() ),
				'page'         => ! empty( $_GET['p'] ) ? absint( $_GET['p'] ) : 1,
				'limit'        => 100,
				'base_url'     => admin_url(
					add_query_arg(
						array(
							'page' => 'er-settings',
							'tab'  => 'tax',
						), 'admin.php'
					)
				),
				'default_rate' => array(
					'id'       => 0,
					'rate'     => '',
					'name'     => '',
					'priority' => 1,
					'compound' => 0,
					'flat'     => 0,
					'apply'    => 'all',
					'order'    => null,
				),
				'strings'      => array(
					'no_rows_selected'        => __( 'No row(s) selected', 'easyReservations' ),
					'unload_confirmation_msg' => __( 'Your changed data will be lost if you leave this page without saving.', 'easyReservations' ),
					'csv_data_cols'           => array(
						__( 'Tax name', 'easyReservations' ),
						__( 'Rate %', 'easyReservations' ),
						__( 'Priority', 'easyReservations' ),
						__( 'Flat', 'easyReservations' ),
						__( 'Compound', 'easyReservations' ),
						__( 'Apply', 'easyReservations' ),
					),
				),
			)
		);
		wp_enqueue_script( 'er-settings-tax' );

		include __DIR__ . '/views/html-admin-settings-tax.php';
	}

	/**
	 * Get tax settings
	 *
	 * @return array
	 */
	public function get_settings() {
		$settings = apply_filters(
			'easyreservations_general_settings',
			array(
				array(
					'title' => __( 'Tax options', 'easyReservations' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'tax_options',
				),

				array(
					'title'    => __( 'Prices entered with tax', 'easyReservations' ),
					'id'       => 'reservations_prices_include_tax',
					'option'   => 'reservations_prices_include_tax',
					'type'     => 'radio',
					'desc_tip' => __( 'This option is important as it will affect how you input prices. Changing it will not update existing resources.', 'easyReservations' ),
					'options'  => array(
						'yes' => __( 'Yes, I will enter prices inclusive of tax', 'easyReservations' ),
						'no'  => __( 'No, I will enter prices exclusive of tax', 'easyReservations' ),
					),
				),

				array(
					'title'  => __( 'Rounding', 'easyReservations' ),
					'desc'   => __( 'Round tax at subtotal level, instead of rounding per line', 'easyReservations' ),
					'id'     => 'reservations_tax_round_at_subtotal',
					'option' => 'reservations_tax_round_at_subtotal',
					'type'   => 'checkbox',
				),

				array(
					'title'   => __( 'Display prices in frontend', 'easyReservations' ),
					'id'      => 'reservations_tax_display_shop',
					'option'  => 'reservations_tax_display_shop',
					'default' => 'excl',
					'type'    => 'select',
					'class'   => 'er-enhanced-select',
					'options' => array(
						'incl' => __( 'Including tax', 'easyReservations' ),
						'excl' => __( 'Excluding tax', 'easyReservations' ),
					),
				),

				array(
					'title'   => __( 'Display prices during cart and checkout', 'easyReservations' ),
					'id'      => 'reservations_tax_display_cart',
					'option'  => 'reservations_tax_display_cart',
					'default' => 'excl',
					'type'    => 'select',
					'class'   => 'er-enhanced-select',
					'options' => array(
						'incl' => __( 'Including tax', 'easyReservations' ),
						'excl' => __( 'Excluding tax', 'easyReservations' ),
					),
				),

				array(
					'title'       => __( 'Price display suffix', 'easyReservations' ),
					'id'          => 'reservations_price_display_suffix',
					'option'      => 'reservations_price_display_suffix',
					'default'     => '',
					'placeholder' => __( 'N/A', 'easyReservations' ),
					'type'        => 'text',
					'desc_tip'    => __( 'Define text to show after your resources prices. This could be, for example, "inc. Vat" to explain your pricing. You can also have prices substituted here using one of the following: {price_including_tax}, {price_excluding_tax}.', 'easyReservations' ),
				),

				array(
					'title'   => __( 'Display tax totals', 'easyReservations' ),
					'id'      => 'reservations_tax_total_display',
					'option'  => 'reservations_tax_total_display',
					'default' => 'itemized',
					'type'    => 'select',
					'class'   => 'er-enhanced-select',
					'options' => array(
						'single'   => __( 'As a single total', 'easyReservations' ),
						'itemized' => __( 'Itemized', 'easyReservations' ),
					)
				),

				array(
					'type' => 'sectionend',
					'id'   => 'general_options',
				),

			)
		);

		return apply_filters( 'easyreservations_get_settings_' . $this->id, $settings );
	}

	/**
	 * Save settings.
	 */
	public function save() {
		$settings = $this->get_settings();

		ER_Admin_Settings::save_fields( $settings );

		$rates = array();
		$new_id = 1;

		foreach ( $_POST['tax_rate_name'] as $i => $name ) {
			$id      = sanitize_key( $i );
			$new_rate = array(
				'apply'    => sanitize_key( $_POST['tax_rate_apply'][ $id ] ),
				'title'    => sanitize_text_field( $_POST['tax_rate_name'][ $id ] ),
				'rate'     => sanitize_text_field( $_POST['tax_rate'][ $id ] ),
				'flat'     => isset( $_POST['tax_rate_flat'][ $id ] ) ? 1 : 0,
				'compound' => isset( $_POST['tax_rate_compound'][ $id ] ) ? 1 : 0,
				'priority' => intval( $_POST['tax_rate_priority'][ $id ] ),
			);

			if ( ! is_numeric( $id ) ) {
				$id = $new_id;
			}

			$new_rate['id'] = $id;

			$rates[] = $new_rate;
			$new_id++;
		}

		array_multisort( array_column( $rates, 'priority' ), $rates );

		update_option( 'reservations_tax_rates', $rates );
	}
}

return new ER_Settings_Tax();
