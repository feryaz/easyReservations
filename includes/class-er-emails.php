<?php

defined( 'ABSPATH' ) || exit;

class ER_Emails {

	/**
	 * The single instance of the class
	 *
	 * @var ER_Emails
	 */
	protected static $_instance = null;

	/**
	 * Background emailer class.
	 *
	 * @var ER_Background_Emailer
	 */
	protected static $background_emailer = null;

	/**
	 * Array of email notification classes
	 *
	 * @var ER_Email[]
	 */
	public $emails = array();

	/**
	 * Main ER_Emails Instance.
	 *
	 * Ensures only one instance of ER_Emails is loaded or can be loaded.
	 *
	 * @return ER_Emails Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cloning is forbidden.', 'easyReservations' ), '6.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', 'easyReservations' ), '6.0' );
	}

	/**
	 * Constructor for the email class hooks in all emails that can be sent.
	 */
	public function __construct() {
		$this->init();

		// Email Header, Footer and content hooks.
		add_action( 'easyreservations_email_header', array( $this, 'email_header' ) );
		add_action( 'easyreservations_email_footer', array( $this, 'email_footer' ) );
		add_action( 'easyreservations_email_order_details', array( $this, 'order_details' ), 10, 4 );
		add_action( 'easyreservations_email_order_meta', array( $this, 'order_meta' ), 10, 3 );
		add_action( 'easyreservations_email_customer_details', array( $this, 'customer_details' ), 10, 3 );
		add_action( 'easyreservations_email_customer_details', array( $this, 'email_addresses' ), 20, 3 );

		// Hooks for sending emails during store events.
		add_action( 'easyreservations_product_on_backorder_notification', array( $this, 'backorder' ) );
		add_action( 'easyreservations_created_customer_notification', array( $this, 'customer_new_account' ), 10, 3 );

		// Hook for replacing {site_title} in email-footer.
		add_filter( 'easyreservations_email_footer_text', array( $this, 'replace_placeholders' ) );

		// Let 3rd parties unhook the above via this hook.
		do_action( 'easyreservations_email', $this );
	}

	/**
	 * Init email classes.
	 */
	public function init() {
		// Include email classes.
		include_once dirname( __FILE__ ) . '/emails/class-er-email.php';

		$this->emails['ER_Email_New_Order']                 = include __DIR__ . '/emails/class-er-email-new-order.php';
		$this->emails['ER_Email_Cancelled_Order']           = include __DIR__ . '/emails/class-er-email-cancelled-order.php';
		$this->emails['ER_Email_Failed_Order']              = include __DIR__ . '/emails/class-er-email-failed-order.php';
		$this->emails['ER_Email_Customer_On_Hold_Order']    = include __DIR__ . '/emails/class-er-email-customer-on-hold-order.php';
		$this->emails['ER_Email_Customer_Processing_Order'] = include __DIR__ . '/emails/class-er-email-customer-processing-order.php';
		$this->emails['ER_Email_Customer_Completed_Order']  = include __DIR__ . '/emails/class-er-email-customer-completed-order.php';
		$this->emails['ER_Email_Customer_Refunded_Order']   = include __DIR__ . '/emails/class-er-email-customer-refunded-order.php';
		$this->emails['ER_Email_Customer_Invoice']          = include __DIR__ . '/emails/class-er-email-customer-invoice.php';
		$this->emails['ER_Email_Customer_Note']             = include __DIR__ . '/emails/class-er-email-customer-note.php';
		$this->emails['ER_Email_Customer_Reset_Password']   = include __DIR__ . '/emails/class-er-email-customer-reset-password.php';
		$this->emails['ER_Email_Customer_New_Account']      = include __DIR__ . '/emails/class-er-email-customer-new-account.php';

		$this->emails = apply_filters( 'easyreservations_email_classes', $this->emails );
	}

	/**
	 * Hook in all transactional emails.
	 */
	public static function init_transactional_emails() {
		$email_actions = apply_filters(
			'easyreservations_email_actions',
			array(
				'easyreservations_product_on_backorder',
				'easyreservations_order_status_pending_to_processing',
				'easyreservations_order_status_pending_to_completed',
				'easyreservations_order_status_processing_to_cancelled',
				'easyreservations_order_status_pending_to_failed',
				'easyreservations_order_status_pending_to_on-hold',
				'easyreservations_order_status_failed_to_processing',
				'easyreservations_order_status_failed_to_completed',
				'easyreservations_order_status_failed_to_on-hold',
				'easyreservations_order_status_cancelled_to_processing',
				'easyreservations_order_status_cancelled_to_completed',
				'easyreservations_order_status_cancelled_to_on-hold',
				'easyreservations_order_status_on-hold_to_processing',
				'easyreservations_order_status_on-hold_to_cancelled',
				'easyreservations_order_status_on-hold_to_failed',
				'easyreservations_order_status_completed',
				'easyreservations_order_fully_refunded',
				'easyreservations_order_partially_refunded',
				'easyreservations_new_customer_note',
				'easyreservations_created_customer',
			)
		);

		if ( apply_filters( 'easyreservations_defer_transactional_emails', false ) ) {
			self::$background_emailer = new ER_Background_Emailer();

			foreach ( $email_actions as $action ) {
				add_action( $action, array( __CLASS__, 'queue_transactional_email' ), 10, 10 );
			}
		} else {
			foreach ( $email_actions as $action ) {
				add_action( $action, array( __CLASS__, 'send_transactional_email' ), 10, 10 );
			}
		}
	}

	/**
	 * Queues transactional email so it's not sent in current request if enabled,
	 * otherwise falls back to send now.
	 *
	 * @param mixed ...$args Optional arguments.
	 */
	public static function queue_transactional_email( ...$args ) {
		if ( is_a( self::$background_emailer, 'ER_Background_Emailer' ) ) {
			self::$background_emailer->push_to_queue(
				array(
					'filter' => current_filter(),
					'args'   => func_get_args(),
				)
			);
		} else {
			self::send_transactional_email( ...$args );
		}
	}

	/**
	 * Init the mailer instance and call the notifications for the current filter.
	 *
	 * @param string $filter Filter name.
	 * @param array  $args Email args (default: []).
	 *
	 * @internal
	 */
	public static function send_queued_transactional_email( $filter = '', $args = array() ) {
		if ( apply_filters( 'easyreservations_allow_send_queued_transactional_email', true, $filter, $args ) ) {
			self::instance(); // Init self so emails exist.

			// Ensure gateways are loaded in case they need to insert data into the emails.
			ER()->payment_gateways();

			do_action_ref_array( $filter . '_notification', $args );
		}
	}

	/**
	 * Init the mailer instance and call the notifications for the current filter.
	 *
	 * @param array $args Email args (default: []).
	 *
	 * @internal
	 */
	public static function send_transactional_email( $args = array() ) {
		try {
			$args = func_get_args();
			self::instance(); // Init self so emails exist.
			do_action_ref_array( current_filter() . '_notification', $args );
		} catch ( Exception $e ) {
			$error  = 'Transactional email triggered fatal error for callback ' . current_filter();
			$logger = er_get_logger();
			$logger->critical(
				$error . PHP_EOL,
				array(
					'source' => 'transactional-emails',
				)
			);
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				trigger_error( $error, E_USER_WARNING ); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped, WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
			}
		}
	}

	/**
	 * Return the email classes - used in admin to load settings.
	 *
	 * @return ER_Email[]
	 */
	public function get_emails() {
		return $this->emails;
	}

	/**
	 * Get from name for email.
	 *
	 * @return string
	 */
	public function get_from_name() {
		return wp_specialchars_decode( get_option( 'reservations_email_from_name' ), ENT_QUOTES );
	}

	/**
	 * Get from email address.
	 *
	 * @return string
	 */
	public function get_from_address() {
		return sanitize_email( get_option( 'reservations_email_from_address' ) );
	}

	/**
	 * Get the email header.
	 *
	 * @param mixed $email_heading Heading for the email.
	 */
	public function email_header( $email_heading ) {
		er_get_template( 'emails/email-header.php', array( 'email_heading' => $email_heading ) );
	}

	/**
	 * Get the email footer.
	 */
	public function email_footer() {
		er_get_template( 'emails/email-footer.php' );
	}

	/**
	 * Replace placeholder text in strings.
	 *
	 * @param string $string Email footer text.
	 *
	 * @return string         Email footer text with any replacements done.
	 */
	public function replace_placeholders( $string ) {
		$domain = wp_parse_url( home_url(), PHP_URL_HOST );

		return str_replace(
			array(
				'{site_title}',
				'{site_url}',
				'{site_address}',
				'{easyreservations}',
				'{easyReservations}',
			),
			array(
				$this->get_blogname(),
				$domain,
				$domain,
				'<a href="https://easyreservations.org">easyReservations</a>',
				'<a href="https://easyreservations.org">easyReservations</a>',
			),
			$string
		);
	}

	/**
	 * Wraps a message in the easyReservations mail template.
	 *
	 * @param string $email_heading Heading text.
	 * @param string $message Email message.
	 * @param bool   $plain_text Set true to send as plain text. Default to false.
	 *
	 * @return string
	 */
	public function wrap_message( $email_heading, $message, $plain_text = false ) {
		// Buffer.
		ob_start();

		do_action( 'easyreservations_email_header', $email_heading, null );

		echo wpautop( wptexturize( $message ) ); // WPCS: XSS ok.

		do_action( 'easyreservations_email_footer', null );

		// Get contents.
		$message = ob_get_clean();

		return $message;
	}

	/**
	 * Send the email.
	 *
	 * @param mixed  $to Receiver.
	 * @param mixed  $subject Email subject.
	 * @param mixed  $message Message.
	 * @param string $headers Email headers (default: "Content-Type: text/html\r\n").
	 * @param string $attachments Attachments (default: "").
	 *
	 * @return bool
	 */
	public function send( $to, $subject, $message, $headers = "Content-Type: text/html\r\n", $attachments = '' ) {
		// Send.
		$email = new ER_Email();

		return $email->send( $to, $subject, $message, $headers, $attachments );
	}

	/**
	 * Prepare and send the customer invoice email on demand.
	 *
	 * @param int|ER_Order $order Order instance or ID.
	 */
	public function customer_invoice( $order ) {
		$email = $this->emails['ER_Email_Customer_Invoice'];

		if ( ! is_object( $order ) ) {
			$order = er_get_order( absint( $order ) );
		}

		$email->trigger( $order->get_id(), $order );
	}

	/**
	 * Customer new account welcome email.
	 *
	 * @param int   $customer_id Customer ID.
	 * @param array $new_customer_data New customer data.
	 * @param bool  $password_generated If password is generated.
	 */
	public function customer_new_account( $customer_id, $new_customer_data = array(), $password_generated = false ) {
		if ( ! $customer_id ) {
			return;
		}

		$user_pass = ! empty( $new_customer_data['user_pass'] ) ? $new_customer_data['user_pass'] : '';

		$email = $this->emails['ER_Email_Customer_New_Account'];
		$email->trigger( $customer_id, $user_pass, $password_generated );
	}

	/**
	 * Show the order details table
	 *
	 * @param ER_Order $order Order instance.
	 * @param bool     $sent_to_admin If should sent to admin.
	 * @param bool     $plain_text If is plain text email.
	 * @param string   $email Email address.
	 */
	public function order_details( $order, $sent_to_admin = false, $plain_text = false, $email = '' ) {
		if ( $plain_text ) {
			er_get_template(
				'emails/plain/email-order-details.php',
				array(
					'order'         => $order,
					'sent_to_admin' => $sent_to_admin,
					'plain_text'    => $plain_text,
					'email'         => $email,
				)
			);
		} else {
			er_get_template(
				'emails/email-order-details.php',
				array(
					'order'         => $order,
					'sent_to_admin' => $sent_to_admin,
					'plain_text'    => $plain_text,
					'email'         => $email,
				)
			);
		}
	}

	/**
	 * Add order meta to email templates.
	 *
	 * @param ER_Order $order Order instance.
	 * @param bool     $sent_to_admin If should sent to admin.
	 * @param bool     $plain_text If is plain text email.
	 */
	public function order_meta( $order, $sent_to_admin = false, $plain_text = false ) {
		$fields = apply_filters( 'easyreservations_email_order_meta_fields', array(), $sent_to_admin, $order );

		if ( $fields ) {
			if ( $plain_text ) {
				foreach ( $fields as $field ) {
					if ( isset( $field['label'] ) && isset( $field['value'] ) && $field['value'] ) {
						echo $field['label'] . ': ' . $field['value'] . "\n"; // WPCS: XSS ok.
					}
				}
			} else {
				foreach ( $fields as $field ) {
					if ( isset( $field['label'] ) && isset( $field['value'] ) && $field['value'] ) {
						echo '<p><strong>' . $field['label'] . ':</strong> ' . $field['value'] . '</p>'; // WPCS: XSS ok.
					}
				}
			}
		}
	}

	/**
	 * Is customer detail field valid?
	 *
	 * @param array $field Field data to check if is valid.
	 *
	 * @return boolean
	 */
	public function customer_detail_field_is_valid( $field ) {
		return isset( $field['label'] ) && ! empty( $field['value'] );
	}

	/**
	 * Allows developers to add additional customer details to templates.
	 *
	 * @param ER_Order $order Order instance.
	 * @param bool     $sent_to_admin If should sent to admin.
	 * @param bool     $plain_text If is plain text email.
	 */
	public function customer_details( $order, $sent_to_admin = false, $plain_text = false ) {
		if ( ! is_a( $order, 'ER_Order' ) ) {
			return;
		}

		$fields = array_filter( apply_filters( 'easyreservations_email_customer_details_fields', array(), $sent_to_admin, $order ), array(
			$this,
			'customer_detail_field_is_valid'
		) );

		if ( ! empty( $fields ) ) {
			if ( $plain_text ) {
				er_get_template( 'emails/plain/email-customer-details.php', array( 'fields' => $fields ) );
			} else {
				er_get_template( 'emails/email-customer-details.php', array( 'fields' => $fields ) );
			}
		}
	}

	/**
	 * Get the email addresses.
	 *
	 * @param ER_Order $order Order instance.
	 * @param bool     $sent_to_admin If should sent to admin.
	 * @param bool     $plain_text If is plain text email.
	 */
	public function email_addresses( $order, $sent_to_admin = false, $plain_text = false ) {
		if ( ! is_a( $order, 'ER_Order' ) ) {
			return;
		}
		if ( $plain_text ) {
			er_get_template(
				'emails/plain/email-addresses.php',
				array(
					'order'         => $order,
					'sent_to_admin' => $sent_to_admin,
				)
			);
		} else {
			er_get_template(
				'emails/email-addresses.php',
				array(
					'order'         => $order,
					'sent_to_admin' => $sent_to_admin,
				)
			);
		}
	}

	/**
	 * Get blog name formatted for emails.
	 *
	 * @return string
	 */
	private function get_blogname() {
		return wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	}

	/**
	 * Backorder notification email.
	 *
	 * @param array $args Arguments.
	 */
	public function backorder( $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'resource' => '',
				'order_id' => '',
			)
		);

		$order = er_get_order( $args['order_id'] );
		if (
			! $args['resource'] ||
			! is_object( $args['resource'] ) ||
			! $order
		) {
			return;
		}

		$subject = sprintf( '[%s] %s', $this->get_blogname(), __( 'Resource overbooking', 'easyReservations' ) );
		/* translators: 1: resource quantity 2: resource name 3: order number */
		$message = sprintf( __( '%1$s has been overbooked in order #%2$s.', 'easyReservations' ), html_entity_decode( wp_strip_all_tags( $args['resource']->get_title() ), ENT_QUOTES, get_bloginfo( 'charset' ) ), $order->get_order_number() );

		wp_mail(
			apply_filters( 'easyreservations_email_recipient_backorder', get_option( 'reservations_stock_email_recipient' ), $args, null ),
			apply_filters( 'easyreservations_email_subject_backorder', $subject, $args, null ),
			apply_filters( 'easyreservations_email_content_backorder', $message, $args ),
			apply_filters( 'easyreservations_email_headers', '', 'backorder', $args, null ),
			apply_filters( 'easyreservations_email_attachments', array(), 'backorder', $args, null )
		);
	}
}
