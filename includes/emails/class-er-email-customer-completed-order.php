<?php
/**
 * Class ER_Email_Customer_Completed_Order file.
 *
 * @package easyReservations\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'ER_Email_Customer_Completed_Order', false ) ) :

	/**
	 * Customer Completed Order Email.
	 *
	 * Order complete emails are sent to the customer when the order is marked complete and usual indicates that the order has been shipped.
	 *
	 * @class       ER_Email_Customer_Completed_Order
	 * @package     easyReservations/Classes/Emails
	 * @extends     ER_Email
	 */
	class ER_Email_Customer_Completed_Order extends ER_Email {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'customer_completed_order';
			$this->customer_email = true;
			$this->title          = __( 'Completed order', 'easyReservations' );
			$this->description    = __( 'Order complete emails are sent to customers when their orders are marked completed and usually indicate that their reservations are approved.', 'easyReservations' );
			$this->template_html  = 'emails/customer-completed-order.php';
			$this->template_plain = 'emails/plain/customer-completed-order.php';
			$this->placeholders   = array(
				'{order_date}'   => '',
				'{order_number}' => '',
			);

			// Triggers for this email.
			add_action( 'easyreservations_order_status_completed_notification', array( $this, 'trigger' ), 10, 2 );

			// Call parent constructor.
			parent::__construct();
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

			$this->setup_locale( $order->get_locale() );

			if ( is_a( $order, 'ER_Order' ) ) {
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
		 * Get email subject.
		 *
		 * @return string
		 */
		public function get_default_subject() {
			return __( 'Your {site_title} order is now complete', 'easyReservations' );
		}

		/**
		 * Get email heading.
		 *
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Thanks for shopping with us', 'easyReservations' );
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
			return __( 'Thanks for shopping with us.', 'easyReservations' );
		}
	}

endif;

return new ER_Email_Customer_Completed_Order();
