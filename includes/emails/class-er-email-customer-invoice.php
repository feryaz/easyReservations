<?php
/**
 * Class ER_Email_Customer_Invoice file.
 *
 * @package easyReservations\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'ER_Email_Customer_Invoice', false ) ) :

	/**
	 * Customer Invoice.
	 *
	 * An email sent to the customer via admin.
	 *
	 * @class       ER_Email_Customer_Invoice
	 * @package     easyReservations/Classes/Emails
	 * @extends     ER_Email
	 */
	class ER_Email_Customer_Invoice extends ER_Email {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'customer_invoice';
			$this->customer_email = true;
			$this->title          = __( 'Customer invoice / Order details', 'easyReservations' );
			$this->description    = __( 'Customer invoice emails can be sent to customers containing their order information and payment links.', 'easyReservations' );
			$this->template_html  = 'emails/customer-invoice.php';
			$this->template_plain = 'emails/plain/customer-invoice.php';
			$this->placeholders   = array(
				'{order_date}'   => '',
				'{order_number}' => '',
			);

			// Call parent constructor.
			parent::__construct();

			$this->manual = true;
		}

		/**
		 * Get email subject.
		 *
		 * @param bool $paid Whether the order has been paid or not.
		 *
		 * @return string
		 */
		public function get_default_subject( $paid = false ) {
			if ( $paid ) {
				return __( 'Invoice for order #{order_number} on {site_title}', 'easyReservations' );
			} else {
				return __( 'Your latest {site_title} invoice', 'easyReservations' );
			}
		}

		/**
		 * Get email heading.
		 *
		 * @param bool $paid Whether the order has been paid or not.
		 *
		 * @return string
		 */
		public function get_default_heading( $paid = false ) {
			if ( $paid ) {
				return __( 'Invoice for order #{order_number}', 'easyReservations' );
			} else {
				return __( 'Your invoice for order #{order_number}', 'easyReservations' );
			}
		}

		/**
		 * Get email subject.
		 *
		 * @return string
		 */
		public function get_subject() {
			if ( $this->object->has_status( array( 'completed', 'processing' ) ) ) {
				$subject = $this->get_option( 'subject_paid', $this->get_default_subject( true ) );

				return apply_filters( 'easyreservations_email_subject_customer_invoice_paid', $this->format_string( $subject ), $this->object, $this );
			}

			$subject = $this->get_option( 'subject', $this->get_default_subject() );

			return apply_filters( 'easyreservations_email_subject_customer_invoice', $this->format_string( $subject ), $this->object, $this );
		}

		/**
		 * Get email heading.
		 *
		 * @return string
		 */
		public function get_heading() {
			if ( $this->object->has_status( er_get_is_paid_statuses() ) ) {
				$heading = $this->get_option( 'heading_paid', $this->get_default_heading( true ) );

				return apply_filters( 'easyreservations_email_heading_customer_invoice_paid', $this->format_string( $heading ), $this->object, $this );
			}

			$heading = $this->get_option( 'heading', $this->get_default_heading() );

			return apply_filters( 'easyreservations_email_heading_customer_invoice', $this->format_string( $heading ), $this->object, $this );
		}

		/**
		 * Default content to show below main email content.
		 *
		 * @return string
		 */
		public function get_default_additional_content() {
			return __( 'Thanks for using {site_url}!', 'easyReservations' );
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * @param int      $order_id The order ID.
		 * @param ER_Order $order Order object.
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

			if ( $this->get_recipient() ) {
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
		 * Initialise settings form fields.
		 */
		public function init_form_fields() {
			/* translators: %s: list of placeholders */
			$placeholder_text  = sprintf( __( 'Available placeholders: %s', 'easyReservations' ), '<code>' . esc_html( implode( '</code>, <code>', array_keys( $this->placeholders ) ) ) . '</code>' );
			$this->form_fields = array(
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
				'subject_paid'       => array(
					'title'       => __( 'Subject (paid)', 'easyReservations' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_subject( true ),
					'default'     => '',
				),
				'heading_paid'       => array(
					'title'       => __( 'Email heading (paid)', 'easyReservations' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_heading( true ),
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

return new ER_Email_Customer_Invoice();
