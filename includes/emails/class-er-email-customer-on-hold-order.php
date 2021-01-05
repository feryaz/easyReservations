<?php
/**
 * Class ER_Email_Customer_On_Hold_Order file.
 *
 * @package easyReservations\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'ER_Email_Customer_On_Hold_Order', false ) ) :

	/**
	 * Customer On-hold Order Email.
	 *
	 * An email sent to the customer when a new order is on-hold for.
	 *
	 * @class       ER_Email_Customer_On_Hold_Order
	 * @package     easyReservations/Classes/Emails
	 * @extends     ER_Email
	 */
	class ER_Email_Customer_On_Hold_Order extends ER_Email {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'customer_on_hold_order';
			$this->customer_email = true;
			$this->title          = __( 'Order on-hold', 'easyReservations' );
			$this->description    = __( 'This is an order notification sent to customers containing order details after an order is placed on-hold.', 'easyReservations' );
			$this->template_html  = 'emails/customer-on-hold-order.php';
			$this->template_plain = 'emails/plain/customer-on-hold-order.php';
			$this->placeholders   = array(
				'{order_date}'   => '',
				'{order_number}' => '',
			);

			// Triggers for this email.
			add_action( 'easyreservations_order_status_pending_to_on-hold_notification', array( $this, 'trigger' ), 10, 2 );
			add_action( 'easyreservations_order_status_failed_to_on-hold_notification', array( $this, 'trigger' ), 10, 2 );
			add_action( 'easyreservations_order_status_cancelled_to_on-hold_notification', array( $this, 'trigger' ), 10, 2 );

			// Call parent constructor.
			parent::__construct();
		}

		/**
		 * Get email subject.
		 *
		 * @return string
		 */
		public function get_default_subject() {
			return __( 'Your {site_title} order has been received!', 'easyReservations' );
		}

		/**
		 * Get email heading.
		 *
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Thank you for your order', 'easyReservations' );
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * @param int            $order_id The order ID.
		 * @param ER_Order|false $order Order object.
		 */
		public function trigger( $order_id, $order = false ) {
			if ( $order_id && ! is_a( $order, 'ER_Order' ) ) {
				$order = er_get_order( $order_id );
			}

			if ( is_a( $order, 'ER_Order' ) ) {
				$this->setup_locale( $order->get_locale() );

				$this->object                         = $order;
				$this->recipient                      = $this->object->get_email();
				$this->placeholders['{order_date}']   = er_format_datetime( $this->object->get_date_created() );
				$this->placeholders['{order_number}'] = $this->object->get_order_number();
			}

			if ( $this->is_enabled() && $this->get_recipient() ) {
				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			}

			$this->restore_locale();
		}

		/**
		 * Get content html.
		 *
		 * @return string
		 */
		public function get_content_html() {
			return er_get_template_html(
				$this->template_html,
				array(
					'order'              => $this->object,
					'email_heading'      => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin'      => false,
					'plain_text'         => false,
					'email'              => $this,
				)
			);
		}

		/**
		 * Get content plain.
		 *
		 * @return string
		 */
		public function get_content_plain() {
			return er_get_template_html(
				$this->template_plain,
				array(
					'order'              => $this->object,
					'email_heading'      => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin'      => false,
					'plain_text'         => true,
					'email'              => $this,
				)
			);
		}

		/**
		 * Default content to show below main email content.
		 *
		 * @return string
		 */
		public function get_default_additional_content() {
			return __( 'We look forward to fulfilling your order soon.', 'easyReservations' );
		}
	}

endif;

return new ER_Email_Customer_On_Hold_Order();
