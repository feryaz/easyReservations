<?php
/**
 * Class ER_Email_Customer_Reset_Password file.
 *
 * @package easyReservations\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'ER_Email_Customer_Reset_Password', false ) ) :

	/**
	 * Customer Reset Password.
	 *
	 * An email sent to the customer when they reset their password.
	 *
	 * @class       ER_Email_Customer_Reset_Password
	 * @package     easyReservations/Classes/Emails
	 * @extends     ER_Email
	 */
	class ER_Email_Customer_Reset_Password extends ER_Email {

		/**
		 * User ID.
		 *
		 * @var integer
		 */
		public $user_id;

		/**
		 * User login name.
		 *
		 * @var string
		 */
		public $user_login;

		/**
		 * User email.
		 *
		 * @var string
		 */
		public $user_email;

		/**
		 * Reset key.
		 *
		 * @var string
		 */
		public $reset_key;

		/**
		 * Constructor.
		 */
		public function __construct() {

			$this->id             = 'customer_reset_password';
			$this->customer_email = true;

			$this->title       = __( 'Reset password', 'easyReservations' );
			$this->description = __( 'Customer "reset password" emails are sent when customers reset their passwords.', 'easyReservations' );

			$this->template_html  = 'emails/customer-reset-password.php';
			$this->template_plain = 'emails/plain/customer-reset-password.php';

			// Trigger.
			add_action( 'easyreservations_reset_password_notification', array( $this, 'trigger' ), 10, 2 );

			// Call parent constructor.
			parent::__construct();
		}

		/**
		 * Get email subject.
		 *
		 * @return string
		 */
		public function get_default_subject() {
			return __( 'Password Reset Request for {site_title}', 'easyReservations' );
		}

		/**
		 * Get email heading.
		 *
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Password Reset Request', 'easyReservations' );
		}

		/**
		 * Trigger.
		 *
		 * @param string $user_login User login.
		 * @param string $reset_key Password reset key.
		 */
		public function trigger( $user_login = '', $reset_key = '' ) {
			if ( $user_login && $reset_key ) {
				$this->object     = get_user_by( 'login', $user_login );
				$this->user_id    = $this->object->ID;
				$this->user_login = $user_login;
				$this->reset_key  = $reset_key;
				$this->user_email = stripslashes( $this->object->user_email );
				$this->recipient  = $this->user_email;

				$this->setup_locale( get_user_locale( $this->object->ID ) );
			} else {
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
					'email_heading'      => $this->get_heading(),
					'user_id'            => $this->user_id,
					'user_login'         => $this->user_login,
					'reset_key'          => $this->reset_key,
					'blogname'           => $this->get_blogname(),
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
					'email_heading'      => $this->get_heading(),
					'user_id'            => $this->user_id,
					'user_login'         => $this->user_login,
					'reset_key'          => $this->reset_key,
					'blogname'           => $this->get_blogname(),
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
			return __( 'Thanks for reading.', 'easyReservations' );
		}
	}

endif;

return new ER_Email_Customer_Reset_Password();
