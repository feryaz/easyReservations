<?php
/**
 * Privacy/GDPR related functionality which ties into WordPress functionality.
 *
 * @package easyReservations\Classes
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ER_Privacy_Background_Process', false ) ) {
	include_once __DIR__ . '/class-er-privacy-background-process.php';
}

/**
 * ER_Privacy Class.
 */
class ER_Privacy extends ER_Abstract_Privacy {

	/**
	 * Background process to clean up orders.
	 *
	 * @var ER_Privacy_Background_Process
	 */
	protected static $background_process;

	/**
	 * Init - hook into events.
	 */
	public function __construct() {
		parent::__construct( __( 'easyReservations', 'easyReservations' ) );

		if ( ! self::$background_process ) {
			self::$background_process = new ER_Privacy_Background_Process();
		}

		// Include supporting classes.
		include_once __DIR__ . '/class-er-privacy-erasers.php';
		include_once __DIR__ . '/class-er-privacy-exporters.php';

		// This hook registers easyReservations data exporters.
		$this->add_exporter( 'easyreservations-customer-data', __( 'easyReservations Customer Data', 'easyReservations' ), array( 'ER_Privacy_Exporters', 'customer_data_exporter' ) );
		$this->add_exporter( 'easyreservations-customer-orders', __( 'easyReservations Customer Orders', 'easyReservations' ), array( 'ER_Privacy_Exporters', 'order_data_exporter' ) );
		$this->add_exporter( 'easyreservations-customer-tokens', __( 'easyReservations Customer Payment Tokens', 'easyReservations' ), array( 'ER_Privacy_Exporters', 'customer_tokens_exporter' ) );

		// This hook registers easyReservations data erasers.
		$this->add_eraser( 'easyreservations-customer-data', __( 'easyReservations Customer Data', 'easyReservations' ), array( 'ER_Privacy_Erasers', 'customer_data_eraser' ) );
		$this->add_eraser( 'easyreservations-customer-orders', __( 'easyReservations Customer Orders', 'easyReservations' ), array( 'ER_Privacy_Erasers', 'order_data_eraser' ) );
		$this->add_eraser( 'easyreservations-customer-tokens', __( 'easyReservations Customer Payment Tokens', 'easyReservations' ), array( 'ER_Privacy_Erasers', 'customer_tokens_eraser' ) );

		// Cleanup orders daily - this is a callback on a daily cron event.
		add_action( 'easyreservations_cleanup_personal_data', array( $this, 'queue_cleanup_personal_data' ) );

		// Handles custom anonomization types not included in core.
		add_filter( 'wp_privacy_anonymize_data', array( $this, 'anonymize_custom_data_types' ), 10, 3 );

		// When this is fired, data is removed in a given order. Called from bulk actions.
		add_action( 'easyreservations_remove_order_personal_data', array( 'ER_Privacy_Erasers', 'remove_order_personal_data' ) );
	}

	/**
	 * Add privacy policy content for the privacy policy page.
	 */
	public function get_privacy_message() {
		$content = '<div class="wp-suggested-text">' .
           '<p class="privacy-policy-tutorial">' .
				__( 'This sample language includes the basics around what personal data your store may be collecting, storing and sharing, as well as who may have access to that data. Depending on what settings are enabled and which additional plugins are used, the specific information shared by your store will vary. We recommend consulting with a lawyer when deciding what information to disclose on your privacy policy.', 'easyReservations' ) .
			'</p>' .
			'<p>' . __( 'We collect information about you during the checkout process on our store.', 'easyReservations' ) . '</p>' .
			'<h2>' . __( 'What we collect and store', 'easyReservations' ) . '</h2>' .
			'<p>' . __( 'While you visit our site, we’ll track:', 'easyReservations' ) . '</p>' .
			'<ul>' .
				'<li>' . __( 'Resources you’ve viewed:  we’ll use this to, for example, show you resources you’ve recently viewed', 'easyReservations' ) . '</li>' .
				'<li>' . __( 'Location, IP address and browser type: we’ll use this for purposes like estimating taxes and shipping', 'easyReservations' ) . '</li>' .
				'<li>' . __( 'Shipping address: we’ll ask you to enter this so we can, for instance, estimate shipping before you place an order, and send you the order!', 'easyReservations' ) . '</li>' .
			'</ul>' .
			'<p>' . __( 'We’ll also use cookies to keep track of cart contents while you’re browsing our site.', 'easyReservations' ) . '</p>' .
			'<p class="privacy-policy-tutorial">' .
				'<p class="wp-policy-help">' . __( 'Note: you may want to further detail your cookie policy, and link to that section from here.', 'easyReservations' ) . '</p>' .
            '</p>' .
		           '<p>' . __( 'When you purchase from us, we’ll ask you to provide information including your name, billing address, shipping address, email address, phone number, credit card/payment details and optional account information like username and password. We’ll use this information for purposes, such as, to:', 'easyReservations' ) . '</p>' .
           '<ul>' .
				'<li>' . __( 'Send you information about your account and order', 'easyReservations' ) . '</li>' .
				'<li>' . __( 'Respond to your requests, including refunds and complaints', 'easyReservations' ) . '</li>' .
				'<li>' . __( 'Process payments and prevent fraud', 'easyReservations' ) . '</li>' .
				'<li>' . __( 'Set up your account for our store', 'easyReservations' ) . '</li>' .
				'<li>' . __( 'Comply with any legal obligations we have, such as calculating taxes', 'easyReservations' ) . '</li>' .
				'<li>' . __( 'Improve our store offerings', 'easyReservations' ) . '</li>' .
				'<li>' . __( 'Send you marketing messages, if you choose to receive them', 'easyReservations' ) . '</li>' .
			'</ul>' .
			'<p>' . __( 'If you create an account, we will store your name, address, email and phone number, which will be used to populate the checkout for future orders.', 'easyReservations' ) . '</p>' .
			'<p>' . __( 'We generally store information about you for as long as we need the information for the purposes for which we collect and use it, and we are not legally required to continue to keep it. For example, we will store order information for XXX years for tax and accounting purposes. This includes your name, email address and billing and shipping addresses.', 'easyReservations' ) . '</p>' .
			'<p>' . __( 'We will also store comments or reviews, if you choose to leave them.', 'easyReservations' ) . '</p>' .
			'<h2>' . __( 'Who on our team has access', 'easyReservations' ) . '</h2>' .
			'<p>' . __( 'Members of our team have access to the information you provide us. For example, both Administrators and Shop Managers can access:', 'easyReservations' ) . '</p>' .
			'<ul>' .
				'<li>' . __( 'Order information like what was purchased, when it was purchased and where it should be sent, and', 'easyReservations' ) . '</li>' .
				'<li>' . __( 'Customer information like your name, email address, and billing and shipping information.', 'easyReservations' ) . '</li>' .
			'</ul>' .
			'<p>' . __( 'Our team members have access to this information to help fulfill orders, process refunds and support you.', 'easyReservations' ) . '</p>' .
			'<h2>' . __( 'What we share with others', 'easyReservations' ) . '</h2>' .
           '<p class="privacy-policy-tutorial">' .
				__( 'In this section you should list who you’re sharing data with, and for what purpose. This could include, but may not be limited to, analytics, marketing, payment gateways, shipping providers, and third party embeds.', 'easyReservations' ) .
           '</p>' .
			'<p>' . __( 'We share information with third parties who help us provide our orders and store services to you; for example --', 'easyReservations' ) . '</p>' .
			'<h3>' . __( 'Payments', 'easyReservations' ) . '</h3>' .
           '<p class="privacy-policy-tutorial">' .
				'<p class="wp-policy-help">' . __( 'In this subsection you should list which third party payment processors you’re using to take payments on your store since these may handle customer data. We’ve included PayPal as an example, but you should remove this if you’re not using PayPal.', 'easyReservations' ) . '</p>' .
           '</p>' .
			'<p>' . __( 'We accept payments through PayPal. When processing payments, some of your data will be passed to PayPal, including information required to process or support the payment, such as the purchase total and billing information.', 'easyReservations' ) . '</p>' .
			'<p>' . __( 'Please see the <a href="https://www.paypal.com/us/webapps/mpp/ua/privacy-full">PayPal Privacy Policy</a> for more details.', 'easyReservations' ) . '</p>' .
			'</div>';

		return apply_filters( 'easyreservations_privacy_policy_content', $content );
	}

	/**
	 * Spawn events for order cleanup.
	 */
	public function queue_cleanup_personal_data() {
		self::$background_process->push_to_queue( array( 'task' => 'trash_pending_orders' ) );
		self::$background_process->push_to_queue( array( 'task' => 'trash_failed_orders' ) );
		self::$background_process->push_to_queue( array( 'task' => 'trash_cancelled_orders' ) );
		self::$background_process->push_to_queue( array( 'task' => 'anonymize_completed_orders' ) );
		self::$background_process->push_to_queue( array( 'task' => 'delete_inactive_accounts' ) );
		self::$background_process->save()->dispatch();
	}

	/**
	 * Handle some custom types of data and anonymize them.
	 *
	 * @param string $anonymous Anonymized string.
	 * @param string $type Type of data.
	 * @param string $data The data being anonymized.
	 * @return string Anonymized string.
	 */
	public function anonymize_custom_data_types( $anonymous, $type, $data ) {
		switch ( $type ) {
			case 'address_state':
			case 'address_country':
				$anonymous = ''; // Empty string - we don't want to store anything after removal.
				break;
			case 'phone':
				$anonymous = preg_replace( '/\d/u', '0', $data );
				break;
			case 'numeric_id':
				$anonymous = 0;
				break;
		}
		return $anonymous;
	}

	/**
	 * Find and trash old orders.
	 *
	 * @param  int $limit Limit orders to process per batch.
	 * @return int Number of orders processed.
	 */
	public static function trash_pending_orders( $limit = 20 ) {
		$option = er_parse_relative_date_option( get_option( 'reservations_trash_pending_orders' ) );

		if ( empty( $option['number'] ) ) {
			return 0;
		}

		return self::trash_orders_query(
			apply_filters(
				'easyreservations_trash_pending_orders_query_args',
				array(
					'date_created' => '<' . strtotime( '-' . $option['number'] . ' ' . $option['unit'] ),
					'limit'        => $limit, // Batches of 20.
					'status'       => 'pending',
					'type'         => 'shop_order',
				)
			)
		);
	}

	/**
	 * Find and trash old orders.
	 *
	 * @param  int $limit Limit orders to process per batch.
	 * @return int Number of orders processed.
	 */
	public static function trash_failed_orders( $limit = 20 ) {
		$option = er_parse_relative_date_option( get_option( 'reservations_trash_failed_orders' ) );

		if ( empty( $option['number'] ) ) {
			return 0;
		}

		return self::trash_orders_query(
			apply_filters(
				'easyreservations_trash_failed_orders_query_args',
				array(
					'date_created' => '<' . strtotime( '-' . $option['number'] . ' ' . $option['unit'] ),
					'limit'        => $limit, // Batches of 20.
					'status'       => 'failed',
					'type'         => 'shop_order',
				)
			)
		);
	}

	/**
	 * Find and trash old orders.
	 *
	 * @param  int $limit Limit orders to process per batch.
	 * @return int Number of orders processed.
	 */
	public static function trash_cancelled_orders( $limit = 20 ) {
		$option = er_parse_relative_date_option( get_option( 'reservations_trash_cancelled_orders' ) );

		if ( empty( $option['number'] ) ) {
			return 0;
		}

		return self::trash_orders_query(
			apply_filters(
				'easyreservations_trash_cancelled_orders_query_args',
				array(
					'date_created' => '<' . strtotime( '-' . $option['number'] . ' ' . $option['unit'] ),
					'limit'        => $limit, // Batches of 20.
					'status'       => 'cancelled',
					'type'         => 'shop_order',
				)
			)
		);
	}

	/**
	 * For a given query trash all matches.
	 *
	 * @param array $query Query array to pass to er_get_orders().
	 * @return int Count of orders that were trashed.
	 */
	protected static function trash_orders_query( $query ) {
		$orders = er_get_orders( $query );
		$count  = 0;

		if ( $orders ) {
			foreach ( $orders as $order ) {
				$order->delete( false );
				$count ++;
			}
		}

		return $count;
	}

	/**
	 * Anonymize old completed orders.
	 *
	 * @param  int $limit Limit orders to process per batch.
	 * @return int Number of orders processed.
	 */
	public static function anonymize_completed_orders( $limit = 20 ) {
		$option = er_parse_relative_date_option( get_option( 'reservations_anonymize_completed_orders' ) );

		if ( empty( $option['number'] ) ) {
			return 0;
		}

		return self::anonymize_orders_query(
			apply_filters(
				'easyreservations_anonymize_completed_orders_query_args',
				array(
					'date_created' => '<' . strtotime( '-' . $option['number'] . ' ' . $option['unit'] ),
					'limit'        => $limit, // Batches of 20.
					'status'       => 'er-completed',
					'anonymized'   => false,
					'type'         => 'shop_order',
				)
			)
		);
	}

	/**
	 * For a given query, anonymize all matches.
	 *
	 * @param array $query Query array to pass to er_get_orders().
	 * @return int Count of orders that were anonymized.
	 */
	protected static function anonymize_orders_query( $query ) {
		$orders = er_get_orders( $query );
		$count  = 0;

		if ( $orders ) {
			foreach ( $orders as $order ) {
				ER_Privacy_Erasers::remove_order_personal_data( $order );
				$count ++;
			}
		}

		return $count;
	}

	/**
	 * Delete inactive accounts.
	 *
	 * @param  int $limit Limit users to process per batch.
	 * @return int Number of users processed.
	 */
	public static function delete_inactive_accounts( $limit = 20 ) {
		$option = er_parse_relative_date_option( get_option( 'reservations_delete_inactive_accounts' ) );

		if ( empty( $option['number'] ) ) {
			return 0;
		}

		return self::delete_inactive_accounts_query( strtotime( '-' . $option['number'] . ' ' . $option['unit'] ), $limit );
	}

	/**
	 * Delete inactive accounts.
	 *
	 * @param int $timestamp Timestamp to delete customers before.
	 * @param int $limit     Limit number of users to delete per run.
	 * @return int Count of customers that were deleted.
	 */
	protected static function delete_inactive_accounts_query( $timestamp, $limit = 20 ) {
		$count      = 0;
		$user_query = new WP_User_Query(
			array(
				'fields'     => 'ID',
				'number'     => $limit,
				'role__in'   => apply_filters(
					'easyreservations_delete_inactive_account_roles',
					array(
						'Customer',
						'Subscriber',
					)
				),
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'AND',
					array(
						'key'     => 'er_last_active',
						'value'   => (string) $timestamp,
						'compare' => '<',
						'type'    => 'NUMERIC',
					),
					array(
						'key'     => 'er_last_active',
						'value'   => '0',
						'compare' => '>',
						'type'    => 'NUMERIC',
					),
				),
			)
		);

		$user_ids = $user_query->get_results();

		if ( $user_ids ) {
			if ( ! function_exists( 'wp_delete_user' ) ) {
				require_once ABSPATH . 'wp-admin/includes/user.php';
			}

			foreach ( $user_ids as $user_id ) {
				wp_delete_user( $user_id );
				$count ++;
			}
		}

		return $count;
	}
}

new ER_Privacy();
