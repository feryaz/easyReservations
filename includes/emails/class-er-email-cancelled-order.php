<?php
/**
 * Class ER_Email_Cancelled_Order file.
 *
 * @package easyReservations\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ER_Email_Cancelled_Order', false ) ) :

	/**
	 * Cancelled Order Email.
	 *
	 * An email sent to the admin when an order is cancelled.
	 *
	 * @class       ER_Email_Cancelled_Order
	 * @package     easyReservations/Classes/Emails
	 * @extends     ER_Email
	 */
	class ER_Email_Cancelled_Order extends ER_Email {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'cancelled_order';
			$this->title          = __( 'Cancelled order', 'easyReservations' );
			$this->description    = __( 'Cancelled order emails are sent to chosen recipient(s) when orders have been marked cancelled (if they were previously processing or on-hold).', 'easyReservations' );
			$this->template_html  = 'emails/admin-cancelled-order.php';
			$this->template_plain = 'emails/plain/admin-cancelled-order.php';
			$this->placeholders   = array(
				'{order_date}'              => '',
				'{order_number}'            => '',
				'{order_full_name}' => '',
			);

			// Triggers for this email.
			add_action( 'easyreservations_order_status_processing_to_cancelled_notification', array( $this, 'trigger' ), 10, 2 );
			add_action( 'easyreservations_order_status_on-hold_to_cancelled_notification', array( $this, 'trigger' ), 10, 2 );

			// Call parent constructor.
			parent::__construct();

			// Other settings.
			$this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
		}

		/**
		 * Get email subject.
		 *
		 * @return string
		 */
		public function get_default_subject() {
			return __( '[{site_title}]: Order #{order_number} has been cancelled', 'easyReservations' );
		}

		/**
		 * Get email heading.
		 *
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Order Cancelled: #{order_number}', 'easyReservations' );
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
				$this->object                                    = $order;
				$this->placeholders['{order_date}']              = er_format_datetime( $this->object->get_date_created() );
				$this->placeholders['{order_number}']            = $this->object->get_order_number();
				$this->placeholders['{order_full_name}'] = $this->object->get_formatted_full_name();
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
					'sent_to_admin'      => true,
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
					'sent_to_admin'      => true,
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

		/**
		 * Initialise settings form fields.
		 */
		public function init_form_fields() {
			/* translators: %s: list of placeholders */
			$placeholder_text  = sprintf( __( 'Available placeholders: %s', 'easyReservations' ), '<code>' . esc_html( implode( '</code>, <code>', array_keys( $this->placeholders ) ) ) . '</code>' );
			$this->form_fields = array(
				'enabled'            => array(
					'title'   => __( 'Enable/Disable', 'easyReservations' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable this email notification', 'easyReservations' ),
					'default' => 'yes',
				),
				'recipient'          => array(
					'title'       => __( 'Recipient(s)', 'easyReservations' ),
					'type'        => 'text',
					/* translators: %s: admin email */
					'description' => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to %s.', 'easyReservations' ), '<code>' . esc_attr( get_option( 'admin_email' ) ) . '</code>' ),
					'placeholder' => '',
					'default'     => '',
					'desc_tip'    => true,
				),
				'subject'            => array(
					'title'       => __( 'Subject', 'easyReservations' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_subject(),
					'default'     => '',
				),
				'heading'            => array(
					'title'       => __( 'Email heading', 'easyReservations' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_heading(),
					'default'     => '',
				),
				'additional_content' => array(
					'title'       => __( 'Additional content', 'easyReservations' ),
					'description' => __( 'Text to appear below the main email content.', 'easyReservations' ) . ' ' . $placeholder_text,
					'css'         => 'width:400px; height: 75px;',
					'placeholder' => __( 'N/A', 'easyReservations' ),
					'type'        => 'textarea',
					'default'     => $this->get_default_additional_content(),
					'desc_tip'    => true,
				),
				'email_type'         => array(
					'title'       => __( 'Email type', 'easyReservations' ),
					'type'        => 'select',
					'description' => __( 'Choose which format of email to send.', 'easyReservations' ),
					'default'     => 'plain',
					'class'       => 'email_type er-enhanced-select',
					'options'     => $this->get_email_type_options(),
					'desc_tip'    => true,
				),
			);
		}
	}

endif;

return new ER_Email_Cancelled_Order();
