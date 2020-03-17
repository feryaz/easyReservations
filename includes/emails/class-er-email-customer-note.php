<?php
/**
 * Class ER_Email_Customer_Note file.
 *
 * @package easyReservations\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'ER_Email_Customer_Note', false ) ) :

	/**
	 * Customer Note Order Email.
	 *
	 * Customer note emails are sent when you add a note to an order.
	 *
	 * @class       ER_Email_Customer_Note
	 * @package     easyReservations/Classes/Emails
	 * @extends     ER_Email
	 */
	class ER_Email_Customer_Note extends ER_Email {

		/**
		 * Customer note.
		 *
		 * @var string
		 */
		public $customer_note;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'customer_note';
			$this->customer_email = true;
			$this->title          = __( 'Customer note', 'easyReservations' );
			$this->description    = __( 'Customer note emails are sent when you add a note to an order.', 'easyReservations' );
			$this->template_html  = 'emails/customer-note.php';
			$this->template_plain = 'emails/plain/customer-note.php';
			$this->placeholders   = array(
				'{order_date}'   => '',
				'{order_number}' => '',
			);

			// Triggers.
			add_action( 'easyreservations_new_customer_note_notification', array( $this, 'trigger' ) );

			// Call parent constructor.
			parent::__construct();
		}

		/**
		 * Get email subject.
		 *
		 * @return string
		 */
		public function get_default_subject() {
			return __( 'Note added to your {site_title} order from {order_date}', 'easyReservations' );
		}

		/**
		 * Get email heading.
		 *
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'A note has been added to your order', 'easyReservations' );
		}

		/**
		 * Trigger.
		 *
		 * @param array $args Email arguments.
		 */
		public function trigger( $args ) {
			if ( ! empty( $args ) ) {
				$defaults = array(
					'order_id'      => '',
					'customer_note' => '',
				);

				$args = wp_parse_args( $args, $defaults );

				$order_id      = $args['order_id'];
				$customer_note = $args['customer_note'];

				if ( $order_id ) {
					$this->object = er_get_order( $order_id );

					if ( $this->object ) {
						$this->setup_locale( $this->object->get_locale() );

						$this->recipient                      = $this->object->get_email();
						$this->customer_note                  = $customer_note;
						$this->placeholders['{order_date}']   = er_format_datetime( $this->object->get_date_created() );
						$this->placeholders['{order_number}'] = $this->object->get_order_number();
					}
				}
			}

			if ( ! $this->object ) {
				$this->setup_locale();
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
					'customer_note'      => $this->customer_note,
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
					'customer_note'      => $this->customer_note,
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
			return __( 'Thanks for reading.', 'easyReservations' );
		}
	}

endif;

return new ER_Email_Customer_Note();
