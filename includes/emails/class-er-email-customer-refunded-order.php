<?php
/**
 * Class ER_Email_Customer_Refunded_Order file.
 *
 * @package easyReservations\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'ER_Email_Customer_Refunded_Order', false ) ) :

	/**
	 * Customer Refunded Order Email.
	 *
	 * Order refunded emails are sent to the customer when the order is marked refunded.
	 *
	 * @class    ER_Email_Customer_Refunded_Order
	 * @package  easyReservations/Classes/Emails
	 * @extends  ER_Email
	 */
	class ER_Email_Customer_Refunded_Order extends ER_Email {

		/**
		 * Refund order.
		 *
		 * @var ER_Order|bool
		 */
		public $refund;

		/**
		 * Is the order partial refunded?
		 *
		 * @var bool
		 */
		public $partial_refund;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->customer_email = true;
			$this->id             = 'customer_refunded_order';
			$this->title          = __( 'Refunded order', 'easyReservations' );
			$this->description    = __( 'Order refunded emails are sent to customers when their orders are refunded.', 'easyReservations' );
			$this->template_html  = 'emails/customer-refunded-order.php';
			$this->template_plain = 'emails/plain/customer-refunded-order.php';
			$this->placeholders   = array(
				'{order_date}'   => '',
				'{order_number}' => '',
			);

			// Triggers for this email.
			add_action( 'easyreservations_order_fully_refunded_notification', array( $this, 'trigger_full' ), 10, 2 );
			add_action( 'easyreservations_order_partially_refunded_notification', array( $this, 'trigger_partial' ), 10, 2 );

			// Call parent constructor.
			parent::__construct();
		}

		/**
		 * Get email subject.
		 *
		 * @param bool $partial Whether it is a partial refund or a full refund.
		 *
		 * @return string
		 */
		public function get_default_subject( $partial = false ) {
			if ( $partial ) {
				return __( 'Your {site_title} order #{order_number} has been partially refunded', 'easyReservations' );
			} else {
				return __( 'Your {site_title} order #{order_number} has been refunded', 'easyReservations' );
			}
		}

		/**
		 * Get email heading.
		 *
		 * @param bool $partial Whether it is a partial refund or a full refund.
		 *
		 * @return string
		 */
		public function get_default_heading( $partial = false ) {
			if ( $partial ) {
				return __( 'Partial Refund: Order {order_number}', 'easyReservations' );
			} else {
				return __( 'Order Refunded: {order_number}', 'easyReservations' );
			}
		}

		/**
		 * Get email subject.
		 *
		 * @return string
		 */
		public function get_subject() {
			if ( $this->partial_refund ) {
				$subject = $this->get_option( 'subject_partial', $this->get_default_subject( true ) );
			} else {
				$subject = $this->get_option( 'subject_full', $this->get_default_subject() );
			}

			return apply_filters( 'easyreservations_email_subject_customer_refunded_order', $this->format_string( $subject ), $this->object, $this );
		}

		/**
		 * Get email heading.
		 *
		 * @return string
		 */
		public function get_heading() {
			if ( $this->partial_refund ) {
				$heading = $this->get_option( 'heading_partial', $this->get_default_heading( true ) );
			} else {
				$heading = $this->get_option( 'heading_full', $this->get_default_heading() );
			}

			return apply_filters( 'easyreservations_email_heading_customer_refunded_order', $this->format_string( $heading ), $this->object, $this );
		}

		/**
		 * Full refund notification.
		 *
		 * @param int $order_id Order ID.
		 * @param int $refund_id Refund ID.
		 */
		public function trigger_full( $order_id, $refund_id = null ) {
			$this->trigger( $order_id, false, $refund_id );
		}

		/**
		 * Partial refund notification.
		 *
		 * @param int $order_id Order ID.
		 * @param int $refund_id Refund ID.
		 */
		public function trigger_partial( $order_id, $refund_id = null ) {
			$this->trigger( $order_id, true, $refund_id );
		}

		/**
		 * Trigger.
		 *
		 * @param int  $order_id Order ID.
		 * @param bool $partial_refund Whether it is a partial refund or a full refund.
		 * @param int  $refund_id Refund ID.
		 */
		public function trigger( $order_id, $partial_refund = false, $refund_id = null ) {
			$this->partial_refund = $partial_refund;
			$this->id             = $this->partial_refund ? 'customer_partially_refunded_order' : 'customer_refunded_order';

			if ( $order_id ) {
				$this->object                         = er_get_order( $order_id );
				$this->recipient                      = $this->object->get_email();
				$this->placeholders['{order_date}']   = er_format_datetime( $this->object->get_date_created() );
				$this->placeholders['{order_number}'] = $this->object->get_order_number();

				$this->setup_locale( $this->object->get_locale() );
			} else {
				$this->setup_locale();
			}

			if ( ! empty( $refund_id ) ) {
				$this->refund = er_get_order( $refund_id );
			} else {
				$this->refund = false;
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
					'refund'             => $this->refund,
					'partial_refund'     => $this->partial_refund,
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
					'refund'             => $this->refund,
					'partial_refund'     => $this->partial_refund,
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
			return __( 'We hope to see you again soon.', 'easyReservations' );
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
				'subject_full'       => array(
					'title'       => __( 'Full refund subject', 'easyReservations' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_subject(),
					'default'     => '',
				),
				'subject_partial'    => array(
					'title'       => __( 'Partial refund subject', 'easyReservations' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_subject( true ),
					'default'     => '',
				),
				'heading_full'       => array(
					'title'       => __( 'Full refund email heading', 'easyReservations' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_heading(),
					'default'     => '',
				),
				'heading_partial'    => array(
					'title'       => __( 'Partial refund email heading', 'easyReservations' ),
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

return new ER_Email_Customer_Refunded_Order();
