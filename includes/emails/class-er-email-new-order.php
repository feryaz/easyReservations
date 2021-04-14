<?php
/**
 * Class ER_Email_New_Order file
 *
 * @package easyReservations\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ER_Email_New_Order' ) ) :

	/**
	 * New Order Email.
	 *
	 * An email sent to the admin when a new order is received/paid for.
	 *
	 * @class       ER_Email_New_Order
	 * @package     easyReservations/Classes/Emails
	 * @extends     ER_Email
	 */
	class ER_Email_New_Order extends ER_Email {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'new_order';
			$this->title          = __( 'New order', 'easyReservations' );
			$this->description    = __( 'New order emails are sent to chosen recipient(s) when a new order is received.', 'easyReservations' );
			$this->template_html  = 'emails/admin-new-order.php';
			$this->template_plain = 'emails/plain/admin-new-order.php';
			$this->placeholders   = array(
				'{order_date}'   => '',
				'{order_number}' => '',
			);

			// Triggers for this email.
			add_action( 'easyreservations_order_status_pending_to_processing_notification', array( $this, 'trigger' ), 10, 2 );
			add_action( 'easyreservations_order_status_pending_to_completed_notification', array( $this, 'trigger' ), 10, 2 );
			add_action( 'easyreservations_order_status_pending_to_on-hold_notification', array( $this, 'trigger' ), 10, 2 );
			add_action( 'easyreservations_order_status_failed_to_processing_notification', array( $this, 'trigger' ), 10, 2 );
			add_action( 'easyreservations_order_status_failed_to_completed_notification', array( $this, 'trigger' ), 10, 2 );
			add_action( 'easyreservations_order_status_failed_to_on-hold_notification', array( $this, 'trigger' ), 10, 2 );
			add_action( 'easyreservations_order_status_cancelled_to_processing_notification', array( $this, 'trigger' ), 10, 2 );
			add_action( 'easyreservations_order_status_cancelled_to_completed_notification', array( $this, 'trigger' ), 10, 2 );
			add_action( 'easyreservations_order_status_cancelled_to_on-hold_notification', array( $this, 'trigger' ), 10, 2 );

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
			return __( '[{site_title}]: New order #{order_number}', 'easyReservations' );
		}

		/**
		 * Get email heading.
		 *
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'New Order: #{order_number}', 'easyReservations' );
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
				$this->placeholders['{order_date}']   = er_format_datetime( $this->object->get_date_created() );
				$this->placeholders['{order_number}'] = $this->object->get_order_number();

				$email_already_sent = $order->get_meta( '_new_order_email_sent' );
			}

			/**
			 * Controls if new order emails can be resend multiple times.
			 *
			 * @param bool $allows Defaults to true.
			 *
			 * @since 5.0.0
			 */
			if ( 'true' === $email_already_sent && ! apply_filters( 'easyreservations_new_order_email_allows_resend', false ) ) {
				return;
			}

			if ( $this->is_enabled() && $this->get_recipient() ) {
				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

				$order->update_meta_data( '_new_order_email_sent', 'true' );
				$order->save();
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
			return __( 'Congratulations on the sale', 'easyReservations' );
		}

		/**
		 * Initialise settings form fields.
		 */
		public function init_form_fields() {
			/* translators: %s: list of placeholders */
			$placeholder_text  = sprintf( __( 'Available placeholders: %s', 'easyReservations' ), '<code>' . implode( '</code>, <code>', array_keys( $this->placeholders ) ) . '</code>' );
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
					/* translators: %s: WP admin email */
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

return new ER_Email_New_Order();
