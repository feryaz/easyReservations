<?php
/**
 * easyReservations Account Settings.
 *
 * @package easyReservations/Admin
 */

defined( 'ABSPATH' ) || exit;

/**
 * ER_Settings_Accounts.
 */
class ER_Settings_Accounts extends ER_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'account';
		$this->label = __( 'Accounts &amp; Privacy', 'easyReservations' );
		parent::__construct();
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	public function get_settings() {
		$erasure_text = esc_html__( 'account erasure request', 'easyReservations' );
		$privacy_text = esc_html__( 'privacy page', 'easyReservations' );

		if ( current_user_can( 'manage_privacy_options' ) ) {
			if ( version_compare( get_bloginfo( 'version' ), '5.3', '<' ) ) {
				$erasure_text = sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'tools.php?page=remove_personal_data' ) ), $erasure_text );
			} else {
				$erasure_text = sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'erase-personal-data.php' ) ), $erasure_text );
			}
			$privacy_text = sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'options-privacy.php' ) ), $privacy_text );
		}

		$account_settings = array(
			array(
				'title' => __( 'Accounts', 'easyReservations' ),
				'type'  => 'title',
				'id'    => 'account_registration_options',
			),

			array(
				'title'       => __( 'Guest checkout', 'easyReservations' ),
				'desc'        => __( 'Allow customers to place orders without an account', 'easyReservations' ),
				'id'          => 'reservations_enable_guest_checkout',
				'option'      => 'reservations_enable_guest_checkout',
				'default'     => 'yes',
				'type'        => 'checkbox',
				'input-group' => 'start',
				'autoload'    => false,
			),
			array(
				'desc'        => __( 'Allow customers to log into an existing account during checkout', 'easyReservations' ),
				'id'          => 'reservations_enable_checkout_login_reminder',
				'option'      => 'reservations_enable_checkout_login_reminder',
				'default'     => 'no',
				'type'        => 'checkbox',
				'input-group' => 'end',
				'autoload'    => false,
			),
			array(
				'title'       => __( 'Account creation', 'easyReservations' ),
				'desc'        => __( 'Allow customers to create an account during checkout', 'easyReservations' ),
				'id'          => 'reservations_enable_signup_and_login_from_checkout',
				'option'      => 'reservations_enable_signup_and_login_from_checkout',
				'default'     => 'no',
				'type'        => 'checkbox',
				'input-group' => 'start',
				'autoload'    => false,
			),
			array(
				'desc'        => __( 'Allow customers to create an account on the "My account" page', 'easyReservations' ),
				'id'          => 'reservations_enable_myaccount_registration',
				'option'      => 'reservations_enable_myaccount_registration',
				'default'     => 'no',
				'type'        => 'checkbox',
				'input-group' => '',
				'autoload'    => false,
			),
			array(
				'desc'        => __( 'When creating an account, automatically generate an account username for the customer based on their name, surname or email', 'easyReservations' ),
				'id'          => 'reservations_registration_generate_username',
				'option'      => 'reservations_registration_generate_username',
				'default'     => 'yes',
				'type'        => 'checkbox',
				'input-group' => '',
				'autoload'    => false,
			),
			array(
				'desc'        => __( 'When creating an account, automatically generate an account password', 'easyReservations' ),
				'id'          => 'reservations_registration_generate_password',
				'option'      => 'reservations_registration_generate_password',
				'default'     => 'yes',
				'type'        => 'checkbox',
				'input-group' => 'end',
				'autoload'    => false,
			),
			array(
				'title'       => __( 'Account erasure requests', 'easyReservations' ),
				'desc'        => __( 'Remove personal data from orders on request', 'easyReservations' ),
				/* Translators: %s URL to erasure request screen. */
				'desc_tip'    => sprintf( esc_html__( 'When handling an %s, should personal data within orders be retained or removed?', 'easyReservations' ), $erasure_text ),
				'id'          => 'reservations_erasure_request_removes_order_data',
				'option'      => 'reservations_erasure_request_removes_order_data',
				'type'        => 'checkbox',
				'default'     => 'no',
				'input-group' => 'start',
				'autoload'    => false,
			),
			array(
				'title'       => __( 'Personal data removal', 'easyReservations' ),
				'desc'        => __( 'Allow personal data to be removed in bulk from orders', 'easyReservations' ),
				'desc_tip'    => __( 'Adds an option to the orders screen for removing personal data in bulk. Note that removing personal data cannot be undone.', 'easyReservations' ),
				'id'          => 'reservations_allow_bulk_remove_personal_data',
				'option'      => 'reservations_allow_bulk_remove_personal_data',
				'type'        => 'checkbox',
				'input-group' => 'start',
				'default'     => 'no',
				'autoload'    => false,
			),

			array(
				'type' => 'sectionend',
				'id'   => 'account_registration_options',
			),

			array(
				'title' => __( 'Privacy policy', 'easyReservations' ),
				'type'  => 'title',
				'id'    => 'privacy_policy_options',
				/* translators: %s: privacy page link. */
				'desc' => sprintf( esc_html__( 'This section controls the display of your website privacy policy. The privacy notices below will not show up unless a %s is set.', 'easyReservations' ), $privacy_text ),
			),

			array(
				'title'    => __( 'Registration privacy policy', 'easyReservations' ),
				'desc_tip' => __( 'Optionally add some text about your store privacy policy to show on account registration forms.', 'easyReservations' ),
				'id'       => 'reservations_registration_privacy_policy_text',
				'option'   => 'reservations_registration_privacy_policy_text',
				/* translators: %s privacy policy page name and link */
				'default'  => sprintf( __( 'Your personal data will be used to support your experience throughout this website, to manage access to your account, and for other purposes described in our %s.', 'easyReservations' ), '[privacy_policy]' ),
				'type'     => 'textarea',
				'css'      => 'min-width: 50%; height: 75px;',
			),

			array(
				'title'    => __( 'Checkout privacy policy', 'easyReservations' ),
				'desc_tip' => __( 'Optionally add some text about your store privacy policy to show during checkout.', 'easyReservations' ),
				'id'       => 'reservations_checkout_privacy_policy_text',
				'option'   => 'reservations_checkout_privacy_policy_text',
				/* translators: %s privacy policy page name and link */
				'default'  => sprintf( __( 'Your personal data will be used to process your order, support your experience throughout this website, and for other purposes described in our %s.', 'easyReservations' ), '[privacy_policy]' ),
				'type'     => 'textarea',
				'css'      => 'min-width: 50%; height: 75px;',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'privacy_policy_options',
			),
			array(
				'title' => __( 'Personal data retention', 'easyReservations' ),
				'desc'  => __( 'Choose how long to retain personal data when it\'s no longer needed for processing. Leave the following options blank to retain this data indefinitely.', 'easyReservations' ),
				'type'  => 'title',
				'id'    => 'personal_data_retention',
			),
			array(
				'title'       => __( 'Retain inactive accounts ', 'easyReservations' ),
				'desc_tip'    => __( 'Inactive accounts are those which have not logged in, or placed an order, for the specified duration. They will be deleted. Any orders will be converted into guest orders.', 'easyReservations' ),
				'id'          => 'reservations_delete_inactive_accounts',
				'option'      => 'reservations_delete_inactive_accounts',
				'type'        => 'relative_date_selector',
				'placeholder' => __( 'N/A', 'easyReservations' ),
				'default'     => array(
					'number' => '',
					'unit'   => 'months',
				),
				'autoload'    => false,
			),
			array(
				'title'       => __( 'Retain pending orders ', 'easyReservations' ),
				'desc_tip'    => __( 'Pending orders are unpaid and may have been abandoned by the customer. They will be trashed after the specified duration.', 'easyReservations' ),
				'id'          => 'reservations_trash_pending_orders',
				'option'      => 'reservations_trash_pending_orders',
				'type'        => 'relative_date_selector',
				'placeholder' => __( 'N/A', 'easyReservations' ),
				'default'     => '',
				'autoload'    => false,
			),
			array(
				'title'       => __( 'Retain failed orders', 'easyReservations' ),
				'desc_tip'    => __( 'Failed orders are unpaid and may have been abandoned by the customer. They will be trashed after the specified duration.', 'easyReservations' ),
				'id'          => 'reservations_trash_failed_orders',
				'option'      => 'reservations_trash_failed_orders',
				'type'        => 'relative_date_selector',
				'placeholder' => __( 'N/A', 'easyReservations' ),
				'default'     => '',
				'autoload'    => false,
			),
			array(
				'title'       => __( 'Retain cancelled orders', 'easyReservations' ),
				'desc_tip'    => __( 'Cancelled orders are unpaid and may have been cancelled by the store owner or customer. They will be trashed after the specified duration.', 'easyReservations' ),
				'id'          => 'reservations_trash_cancelled_orders',
				'option'      => 'reservations_trash_cancelled_orders',
				'type'        => 'relative_date_selector',
				'placeholder' => __( 'N/A', 'easyReservations' ),
				'default'     => '',
				'autoload'    => false,
			),
			array(
				'title'       => __( 'Retain completed orders', 'easyReservations' ),
				'desc_tip'    => __( 'Retain completed orders for a specified duration before anonymizing the personal data within them.', 'easyReservations' ),
				'id'          => 'reservations_anonymize_completed_orders',
				'option'      => 'reservations_anonymize_completed_orders',
				'type'        => 'relative_date_selector',
				'placeholder' => __( 'N/A', 'easyReservations' ),
				'default'     => array(
					'number' => '',
					'unit'   => 'months',
				),
				'autoload'    => false,
			),
			array(
				'type' => 'sectionend',
				'id'   => 'personal_data_retention',
			),
		);

		return apply_filters( 'easyreservations_get_settings_' . $this->id, $account_settings );
	}
}

return new ER_Settings_Accounts();
