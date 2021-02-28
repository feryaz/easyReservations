<?php
defined( 'ABSPATH' ) || exit;

/**
 * ER_Settings_Advanced.
 */
class ER_Settings_Advanced extends ER_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'advanced';
		$this->label = __( 'Advanced', 'easyReservations' );

		parent::__construct();
	}

	/**
	 * Get sections.
	 *
	 * @return array
	 */
	public function get_sections() {
		global $current_section;

		if ( empty( $current_section ) ) {
			$current_section = 'page_setup';
		}

		return apply_filters( 'easyreservations_admin_advanced_sections', array(
			'page_setup' => __( 'Page setup', 'easyReservations' ),
			'easyreservations' => __( 'easyReservations.org', 'easyReservations' ),
		) );
	}

	/**
	 * Output sections
	 */
	public function get_settings() {
		global $current_section;

		if ( empty( $current_section ) ) {
			$current_section = 'page_setup';
		}

		switch ( $current_section ) {
			case "page_setup":
				return apply_filters(
					'easyreservations_page_setup_settings',
					array(
						array(
							'title' => __( 'Page setup', 'easyReservations' ),
							'desc'  => __( 'These pages need to be set so that easyReservations knows where to send users to checkout.', 'easyReservations' ),
							'type'  => 'title',
							'id'    => 'advanced_page_options',
						),

						array(
							'title'    => __( 'Catalog page', 'easyReservations' ),
							/* translators: %s: URL to settings. */
							'desc'     => sprintf( __( 'The base page can also be used in your <a href="%s">resource permalinks</a>.', 'easyReservations' ), admin_url( 'options-permalink.php' ) ),
							'id'       => 'reservations_shop_page_id',
							'option'   => 'reservations_shop_page_id',
							'type'     => 'single_select_page',
							'default'  => '',
							'class'    => 'er-enhanced-select-nostd',
							'css'      => 'min-width:300px;',
							'desc_tip' => __( 'This sets the base page of your shop - this is where your resource archive will be.', 'easyReservations' ),
						),

						array(
							'title'    => __( 'Cart page', 'easyReservations' ),
							/* Translators: %s Page contents. */
							'desc'     => sprintf( __( 'Page contents: [%s]', 'easyReservations' ), apply_filters( 'easyreservations_cart_shortcode_tag', 'easy_cart' ) ),
							'id'       => 'reservations_cart_page_id',
							'option'   => 'reservations_cart_page_id',
							'type'     => 'single_select_page',
							'default'  => '',
							'class'    => 'er-enhanced-select-nostd',
							'css'      => 'min-width:300px;',
							'args'     => array(
								'exclude' =>
									array(
										er_get_page_id( 'checkout' ),
										er_get_page_id( 'myaccount' ),
									),
							),
							'desc_tip' => true,
							'autoload' => false,
						),

						array(
							'title'    => __( 'Checkout page', 'easyReservations' ),
							/* Translators: %s Page contents. */
							'desc'     => sprintf( __( 'Page contents: [%s]', 'easyReservations' ), apply_filters( 'easyreservations_checkout_shortcode_tag', 'easy_checkout' ) ),
							'id'       => 'reservations_checkout_page_id',
							'option'   => 'reservations_checkout_page_id',
							'type'     => 'single_select_page',
							'default'  => '',
							'class'    => 'er-enhanced-select-nostd',
							'css'      => 'min-width:300px;',
							'args'     => array(
								'exclude' =>
									array(
										er_get_page_id( 'cart' ),
										er_get_page_id( 'myaccount' ),
									),
							),
							'desc_tip' => true,
							'autoload' => false,
						),

						array(
							'title'    => __( 'My account page', 'easyReservations' ),
							/* Translators: %s Page contents. */
							'desc'     => sprintf( __( 'Page contents: [%s]', 'easyReservations' ), apply_filters( 'easyreservations_my_account_shortcode_tag', 'easy_my_account' ) ),
							'id'       => 'reservations_myaccount_page_id',
							'option'   => 'reservations_myaccount_page_id',
							'type'     => 'single_select_page',
							'default'  => '',
							'class'    => 'er-enhanced-select-nostd',
							'css'      => 'min-width:300px;',
							'args'     => array(
								'exclude' =>
									array(
										er_get_page_id( 'cart' ),
										er_get_page_id( 'checkout' ),
									),
							),
							'desc_tip' => true,
							'autoload' => false,
						),

						array(
							'title'    => __( 'Terms and conditions', 'easyReservations' ),
							'desc'     => __( 'If you define a "Terms" page the customer will be asked if they accept them when checking out.', 'easyReservations' ),
							'id'       => 'reservations_terms_page_id',
							'option'   => 'reservations_terms_page_id',
							'default'  => '',
							'class'    => 'er-enhanced-select-nostd',
							'css'      => 'min-width:300px;',
							'type'     => 'single_select_page',
							'args'     => array( 'exclude' => er_get_page_id( 'checkout' ) ),
							'desc_tip' => true,
							'autoload' => false,
						),

						array(
							'type' => 'sectionend',
							'id'   => 'advanced_page_options',
						),

						array(
							'title' => '',
							'type'  => 'title',
							'id'    => 'checkout_process_options',
						),

						'force_ssl_checkout' => array(
							'title'           => __( 'Secure checkout', 'easyReservations' ),
							'desc'            => __( 'Force secure checkout', 'easyReservations' ),
							'id'              => 'reservations_force_ssl_checkout',
							'option'          => 'reservations_force_ssl_checkout',
							'default'         => 'no',
							'type'            => 'checkbox',
							'input-group'     => 'start',
							'show_if_checked' => 'option',
							/* Translators: %s Docs URL. */
							'desc_tip'        => sprintf( __( 'Force SSL (HTTPS) on the checkout pages (<a href="%s" target="_blank">an SSL Certificate is required</a>).', 'easyReservations' ), 'https://easyreservations.org/documentation/ssl-and-https/#section-3' ),
						),

						'unforce_ssl_checkout' => array(
							'desc'            => __( 'Force HTTP when leaving the checkout', 'easyReservations' ),
							'id'              => 'reservations_unforce_ssl_checkout',
							'option'          => 'reservations_unforce_ssl_checkout',
							'default'         => 'no',
							'type'            => 'checkbox',
							'input-group'     => 'end',
							'show_if_checked' => 'yes',
						),

						array(
							'title'   => __( 'Add to cart behaviour', 'easyReservations' ),
							'desc'    => __( 'Redirect to the selected continue page after successful addition', 'easyReservations' ),
							'id'      => 'reservations_cart_redirect_after_add',
							'option'  => 'reservations_cart_redirect_after_add',
							'default' => 'no',
							'type'    => 'checkbox',
						),

						array(
							'type' => 'sectionend',
							'id'   => 'checkout_process_options',
						),

						array(
							'title' => __( 'Checkout endpoints', 'easyReservations' ),
							'type'  => 'title',
							'desc'  => __( 'Endpoints are appended to your page URLs to handle specific actions during the checkout process. They should be unique.', 'easyReservations' ),
							'id'    => 'account_endpoint_options',
						),

						array(
							'title'    => __( 'Pay', 'easyReservations' ),
							'desc'     => __( 'Endpoint for the "Checkout &rarr; Pay" page.', 'easyReservations' ),
							'id'       => 'reservations_checkout_pay_endpoint',
							'option'   => 'reservations_checkout_pay_endpoint',
							'type'     => 'text',
							'default'  => 'order-payment',
							'desc_tip' => true,
						),

						array(
							'title'    => __( 'Order received', 'easyReservations' ),
							'desc'     => __( 'Endpoint for the "Checkout &rarr; Order received" page.', 'easyReservations' ),
							'id'       => 'reservations_checkout_order_received_endpoint',
							'option'   => 'reservations_checkout_order_received_endpoint',
							'type'     => 'text',
							'default'  => 'order-received',
							'desc_tip' => true,
						),

						array(
							'title'    => __( 'Add payment method', 'easyReservations' ),
							'desc'     => __( 'Endpoint for the "Checkout &rarr; Add payment method" page.', 'easyReservations' ),
							'id'       => 'reservations_myaccount_add_payment_method_endpoint',
							'option'   => 'reservations_myaccount_add_payment_method_endpoint',
							'type'     => 'text',
							'default'  => 'add-payment-method',
							'desc_tip' => true,
						),

						array(
							'title'    => __( 'Delete payment method', 'easyReservations' ),
							'desc'     => __( 'Endpoint for the delete payment method page.', 'easyReservations' ),
							'id'       => 'reservations_myaccount_delete_payment_method_endpoint',
							'option'   => 'reservations_myaccount_delete_payment_method_endpoint',
							'type'     => 'text',
							'default'  => 'delete-payment-method',
							'desc_tip' => true,
						),

						array(
							'title'    => __( 'Set default payment method', 'easyReservations' ),
							'desc'     => __( 'Endpoint for the setting a default payment method page.', 'easyReservations' ),
							'id'       => 'reservations_myaccount_set_default_payment_method_endpoint',
							'option'   => 'reservations_myaccount_set_default_payment_method_endpoint',
							'type'     => 'text',
							'default'  => 'set-default-payment-method',
							'desc_tip' => true,
						),

						array(
							'type' => 'sectionend',
							'id'   => 'account_endpoint_options',
						),

						array(
							'title' => __( 'Account endpoints', 'easyReservations' ),
							'type'  => 'title',
							'desc'  => __( 'Endpoints are appended to your page URLs to handle specific actions on the accounts pages. They should be unique and can be left blank to disable the endpoint.', 'easyReservations' ),
							'id'    => 'account_endpoint_options',
						),

						array(
							'title'    => __( 'Orders', 'easyReservations' ),
							'desc'     => __( 'Endpoint for the "My account &rarr; Orders" page.', 'easyReservations' ),
							'id'       => 'reservations_myaccount_orders_endpoint',
							'option'   => 'reservations_myaccount_orders_endpoint',
							'type'     => 'text',
							'default'  => 'my-orders',
							'desc_tip' => true,
						),

						array(
							'title'    => __( 'View order', 'easyReservations' ),
							'desc'     => __( 'Endpoint for the "My account &rarr; View order" page.', 'easyReservations' ),
							'id'       => 'reservations_myaccount_view_order_endpoint',
							'option'   => 'reservations_myaccount_view_order_endpoint',
							'type'     => 'text',
							'default'  => 'view-order',
							'desc_tip' => true,
						),

						array(
							'title'    => __( 'Edit account', 'easyReservations' ),
							'desc'     => __( 'Endpoint for the "My account &rarr; Edit account" page.', 'easyReservations' ),
							'id'       => 'reservations_myaccount_edit_account_endpoint',
							'option'   => 'reservations_myaccount_edit_account_endpoint',
							'type'     => 'text',
							'default'  => 'edit-account',
							'desc_tip' => true,
						),

						array(
							'title'    => __( 'Addresses', 'easyReservations' ),
							'desc'     => __( 'Endpoint for the "My account &rarr; Addresses" page.', 'easyReservations' ),
							'id'       => 'reservations_myaccount_edit_address_endpoint',
							'option'   => 'reservations_myaccount_edit_address_endpoint',
							'type'     => 'text',
							'default'  => 'edit-address',
							'desc_tip' => true,
						),

						array(
							'title'    => __( 'Payment methods', 'easyReservations' ),
							'desc'     => __( 'Endpoint for the "My account &rarr; Payment methods" page.', 'easyReservations' ),
							'id'       => 'reservations_myaccount_payment_methods_endpoint',
							'option'   => 'reservations_myaccount_payment_methods_endpoint',
							'type'     => 'text',
							'default'  => 'payment-methods',
							'desc_tip' => true,
						),

						array(
							'title'    => __( 'Lost password', 'easyReservations' ),
							'desc'     => __( 'Endpoint for the "My account &rarr; Lost password" page.', 'easyReservations' ),
							'id'       => 'reservations_myaccount_lost_password_endpoint',
							'option'   => 'reservations_myaccount_lost_password_endpoint',
							'type'     => 'text',
							'default'  => 'lost-password',
							'desc_tip' => true,
						),

						array(
							'title'    => __( 'Logout', 'easyReservations' ),
							'desc'     => __( 'Endpoint for the triggering logout. You can add this to your menus via a custom link: yoursite.com/?customer-logout=true', 'easyReservations' ),
							'id'       => 'reservations_logout_endpoint',
							'option'   => 'reservations_logout_endpoint',
							'type'     => 'text',
							'default'  => 'customer-logout',
							'desc_tip' => true,
						),

						array(
							'type' => 'sectionend',
							'id'   => 'account_endpoint_options',
						),
					)
				);

				break;
			case 'easyreservations':
				$tracking_info_text = sprintf( '<a href="%s" target="_blank">%s</a>', 'https://woocommerce.com/usage-tracking', esc_html__( 'easyReservations.org Usage Tracking Documentation', 'easyReservations' ) );

				return apply_filters(
					'easyreservations_org_integration_settings',
					array(
						array(
							'title' => esc_html__( 'Usage Tracking', 'easyReservations' ),
							'type'  => 'title',
							'id'    => 'tracking_options',
							'desc'  => __( 'Gathering usage data allows us to make easyReservations better — your store will be considered as we evaluate new features, judge the quality of an update, or determine if an improvement makes sense.', 'easyReservations' ),
						),
						array(
							'title'         => __( 'Enable tracking', 'easyReservations' ),
							'desc'          => __( 'Allow usage of easyReservations to be tracked', 'easyReservations' ),
							/* Translators: %s URL to tracking info screen. */
							'desc_tip'      => sprintf( esc_html__( 'To opt out, leave this box unticked. Your store remains untracked, and no data will be collected. Read about what usage data is tracked at: %s.', 'easyReservations' ), $tracking_info_text ),
							'id'            => 'reservations_allow_tracking',
							'option'        => 'reservations_allow_tracking',
							'type'          => 'checkbox',
							'checkboxgroup' => 'start',
							'default'       => 'no',
							'autoload'      => false,
						),
						array(
							'type' => 'sectionend',
							'id'   => 'tracking_options',
						),
					)
				);

				break;
			default:
				do_action( 'easyreservations_admin_advanced_settings_' . $current_section );
				break;
		}

		return array();
	}
}

return new ER_Settings_Advanced();
