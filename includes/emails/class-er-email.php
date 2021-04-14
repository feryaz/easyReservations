<?php
/**
 * Class ER_Email file.
 *
 * @package easyReservations\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'ER_Email', false ) ) {
	return;
}

/**
 * Email Class
 *
 * easyReservations Email Class which is extended by specific email template classes to add emails to easyReservations
 *
 * @class       ER_Email
 * @package     easyReservations/Classes/Emails
 * @extends     ER_Settings_API
 */
class ER_Email extends ER_Settings_API {

	/**
	 * Email method ID.
	 *
	 * @var String
	 */
	public $id;

	/**
	 * Email method title.
	 *
	 * @var string
	 */
	public $title;

	/**
	 * 'yes' if the method is enabled.
	 *
	 * @var string yes, no
	 */
	public $enabled;

	/**
	 * Description for the email.
	 *
	 * @var string
	 */
	public $description;

	/**
	 * Default heading.
	 *
	 * Supported for backwards compatibility but we recommend overloading the
	 * get_default_x methods instead so localization can be done when needed.
	 *
	 * @var string
	 */
	public $heading = '';

	/**
	 * Default subject.
	 *
	 * Supported for backwards compatibility but we recommend overloading the
	 * get_default_x methods instead so localization can be done when needed.
	 *
	 * @var string
	 */
	public $subject = '';

	/**
	 * Plain text template path.
	 *
	 * @var string
	 */
	public $template_plain;

	/**
	 * HTML template path.
	 *
	 * @var string
	 */
	public $template_html;

	/**
	 * Template path.
	 *
	 * @var string
	 */
	public $template_base;

	/**
	 * Recipients for the email.
	 *
	 * @var string
	 */
	public $recipient;

	/**
	 * Object this email is for, for example a customer, resource, or email.
	 *
	 * @var object|bool
	 */
	public $object;

	/**
	 * Mime boundary (for multipart emails).
	 *
	 * @var string
	 */
	public $mime_boundary;

	/**
	 * Mime boundary header (for multipart emails).
	 *
	 * @var string
	 */
	public $mime_boundary_header;

	/**
	 * True when email is being sent.
	 *
	 * @var bool
	 */
	public $sending;

	/**
	 *  List of preg* regular expression patterns to search for,
	 *  used in conjunction with $plain_replace.
	 *  https://raw.github.com/ushahidi/wp-silcc/master/class.html2text.inc
	 *
	 * @var array $plain_search
	 * @see $plain_replace
	 */
	public $plain_search = array(
		"/\r/",                                                  // Non-legal carriage return.
		'/&(nbsp|#0*160);/i',                                    // Non-breaking space.
		'/&(quot|rdquo|ldquo|#0*8220|#0*8221|#0*147|#0*148);/i', // Double quotes.
		'/&(apos|rsquo|lsquo|#0*8216|#0*8217);/i',               // Single quotes.
		'/&gt;/i',                                               // Greater-than.
		'/&lt;/i',                                               // Less-than.
		'/&#0*38;/i',                                            // Ampersand.
		'/&amp;/i',                                              // Ampersand.
		'/&(copy|#0*169);/i',                                    // Copyright.
		'/&(trade|#0*8482|#0*153);/i',                           // Trademark.
		'/&(reg|#0*174);/i',                                     // Registered.
		'/&(mdash|#0*151|#0*8212);/i',                           // mdash.
		'/&(ndash|minus|#0*8211|#0*8722);/i',                    // ndash.
		'/&(bull|#0*149|#0*8226);/i',                            // Bullet.
		'/&(pound|#0*163);/i',                                   // Pound sign.
		'/&(euro|#0*8364);/i',                                   // Euro sign.
		'/&(dollar|#0*36);/i',                                   // Dollar sign.
		'/&[^&\s;]+;/i',                                         // Unknown/unhandled entities.
		'/[ ]{2,}/',                                             // Runs of spaces, post-handling.
	);

	/**
	 *  List of pattern replacements corresponding to patterns searched.
	 *
	 * @var array $plain_replace
	 * @see $plain_search
	 */
	public $plain_replace = array(
		'',                                             // Non-legal carriage return.
		' ',                                            // Non-breaking space.
		'"',                                            // Double quotes.
		"'",                                            // Single quotes.
		'>',                                            // Greater-than.
		'<',                                            // Less-than.
		'&',                                            // Ampersand.
		'&',                                            // Ampersand.
		'(c)',                                          // Copyright.
		'(tm)',                                         // Trademark.
		'(R)',                                          // Registered.
		'--',                                           // mdash.
		'-',                                            // ndash.
		'*',                                            // Bullet.
		'£',                                            // Pound sign.
		'EUR',                                          // Euro sign. € ?.
		'$',                                            // Dollar sign.
		'',                                             // Unknown/unhandled entities.
		' ',                                             // Runs of spaces, post-handling.
	);

	/**
	 * True when the email notification is sent manually only.
	 *
	 * @var bool
	 */
	protected $manual = false;

	/**
	 * True when the email notification is sent to customers.
	 *
	 * @var bool
	 */
	protected $customer_email = false;

	/**
	 * Strings to find/replace in subjects/headings.
	 *
	 * @var array
	 */
	protected $placeholders = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Find/replace.
		$this->placeholders = array_merge(
			array(
				'{site_title}'   => $this->get_blogname(),
				'{site_url}' => wp_parse_url( home_url(), PHP_URL_HOST ),
				'{site_address}' => wp_parse_url( home_url(), PHP_URL_HOST ),
			),
			$this->placeholders
		);

		// Init settings.
		$this->init_form_fields();
		$this->init_settings();

		// Default template base if not declared in child constructor.
		if ( is_null( $this->template_base ) ) {
			$this->template_base = ER()->plugin_path() . '/templates/';
		}

		$this->email_type = $this->get_option( 'email_type' );
		$this->enabled    = $this->get_option( 'enabled' );

		add_action( 'phpmailer_init', array( $this, 'handle_multipart' ) );
		add_action( 'easyreservations_update_options_emails_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Handle multipart mail.
	 *
	 * @param PHPMailer $mailer PHPMailer object.
	 *
	 * @return PHPMailer
	 */
	public function handle_multipart( $mailer ) {
		if ( $this->sending && 'multipart' === $this->get_email_type() ) {
			$mailer->AltBody = wordwrap( // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
				preg_replace( $this->plain_search, $this->plain_replace, wp_strip_all_tags( $this->get_content_plain() ) )
			);
			$this->sending   = false;
		}

		return $mailer;
	}

	/**
	 * Format email string.
	 *
	 * @param mixed $string Text to replace placeholders in.
	 *
	 * @return string
	 */
	public function format_string( $string ) {
		$find    = array_keys( $this->placeholders );
		$replace = array_values( $this->placeholders );

		// Take care of blogname which is no longer defined as a valid placeholder.
		$find[]    = '{blogname}';
		$replace[] = $this->get_blogname();

		/**
		 * Filter for main find/replace.
		 */
		return apply_filters( 'easyreservations_email_format_string', str_replace( $find, $replace, $string ), $this );
	}

	/**
	 * Set the locale to the store locale for customer emails to make sure emails are in the store language.
	 *
	 * @param string|bool $locale
	 */
	public function setup_locale( $locale = false ) {
		if ( $this->is_customer_email() && apply_filters( 'easyreservations_email_setup_locale', true ) ) {
			er_switch_to_site_locale( $locale );
		}
	}

	/**
	 * Restore the locale to the default locale. Use after finished with setup_locale.
	 */
	public function restore_locale() {
		if ( $this->is_customer_email() && apply_filters( 'easyreservations_email_restore_locale', true ) ) {
			er_restore_locale();
		}
	}

	/**
	 * Get email subject.
	 *
	 * @return string
	 */
	public function get_default_subject() {
		return $this->subject;
	}

	/**
	 * Get email heading.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return $this->heading;
	}

	/**
	 * Default content to show below main email content.
	 *
	 * @return string
	 */
	public function get_default_additional_content() {
		return '';
	}

	/**
	 * Return content from the additional_content field.
	 *
	 * Displayed above the footer.
	 *
	 * @return string
	 */
	public function get_additional_content() {
		$content = $this->get_option( 'additional_content', '' );

		return apply_filters( 'easyreservations_email_additional_content_' . $this->id, $this->format_string( $content ), $this->object, $this );
	}

	/**
	 * Get email subject.
	 *
	 * @return string
	 */
	public function get_subject() {
		return apply_filters( 'easyreservations_email_subject_' . $this->id, $this->format_string( $this->get_option( 'subject', $this->get_default_subject() ) ), $this->object, $this );
	}

	/**
	 * Get email heading.
	 *
	 * @return string
	 */
	public function get_heading() {
		return apply_filters( 'easyreservations_email_heading_' . $this->id, $this->format_string( $this->get_option( 'heading', $this->get_default_heading() ) ), $this->object, $this );
	}

	/**
	 * Get valid recipients.
	 *
	 * @return string
	 */
	public function get_recipient() {
		$recipient  = apply_filters( 'easyreservations_email_recipient_' . $this->id, $this->recipient, $this->object, $this );
		$recipients = array_map( 'trim', explode( ',', $recipient ) );
		$recipients = array_filter( $recipients, 'is_email' );

		return implode( ', ', $recipients );
	}

	/**
	 * Get email headers.
	 *
	 * @return string
	 */
	public function get_headers() {
		$header = 'Content-Type: ' . $this->get_content_type() . "\r\n";

		if ( in_array( $this->id, array( 'new_order', 'cancelled_order', 'failed_order' ), true ) ) {
			if ( $this->object && $this->object->get_email() && ( $this->object->get_first_name() || $this->object->get_last_name() ) ) {
				$header .= 'Reply-to: ' . $this->object->get_first_name() . ' ' . $this->object->get_last_name() . ' <' . $this->object->get_email() . ">\r\n";
			}
		} elseif ( $this->get_from_address() && $this->get_from_name() ) {
			$header .= 'Reply-to: ' . $this->get_from_name() . ' <' . $this->get_from_address() . ">\r\n";
		}

		return apply_filters( 'easyreservations_email_headers', $header, $this->id, $this->object, $this );
	}

	/**
	 * Get email attachments.
	 *
	 * @return array
	 */
	public function get_attachments() {
		return apply_filters( 'easyreservations_email_attachments', array(), $this->id, $this->object, $this );
	}

	/**
	 * Return email type.
	 *
	 * @return string
	 */
	public function get_email_type() {
		return $this->email_type && class_exists( 'DOMDocument' ) ? $this->email_type : 'plain';
	}

	/**
	 * Get email content type.
	 *
	 * @param string $default_content_type Default wp_mail() content type.
	 *
	 * @return string
	 */
	public function get_content_type( $default_content_type = '' ) {
		switch ( $this->get_email_type() ) {
			case 'html':
				$content_type = 'text/html';
				break;
			case 'multipart':
				$content_type = 'multipart/alternative';
				break;
			default:
				$content_type = 'text/plain';
				break;
		}

		return apply_filters( 'easyreservations_email_content_type', $content_type, $this, $default_content_type );
	}

	/**
	 * Return the email's title
	 *
	 * @return string
	 */
	public function get_title() {
		return apply_filters( 'easyreservations_email_title', $this->title, $this );
	}

	/**
	 * Return the email's description
	 *
	 * @return string
	 */
	public function get_description() {
		return apply_filters( 'easyreservations_email_description', $this->description, $this );
	}

	/**
	 * Proxy to parent's get_option and attempt to localize the result using gettext.
	 *
	 * @param string $key Option key.
	 * @param mixed  $empty_value Value to use when option is empty.
	 *
	 * @return string
	 */
	public function get_option( $key, $empty_value = null ) {
		$value = parent::get_option( $key, $empty_value );

		return apply_filters( 'easyreservations_email_get_option', $value, $this, $value, $key, $empty_value );
	}

	/**
	 * Checks if this email is enabled and will be sent.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return apply_filters( 'easyreservations_email_enabled_' . $this->id, 'yes' === $this->enabled, $this->object, $this );
	}

	/**
	 * Checks if this email is manually sent
	 *
	 * @return bool
	 */
	public function is_manual() {
		return $this->manual;
	}

	/**
	 * Checks if this email is customer focussed.
	 *
	 * @return bool
	 */
	public function is_customer_email() {
		return $this->customer_email;
	}

	/**
	 * Get WordPress blog name.
	 *
	 * @return string
	 */
	public function get_blogname() {
		return wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	}

	/**
	 * Get email content.
	 *
	 * @return string
	 */
	public function get_content() {
		$this->sending = true;

		if ( 'plain' === $this->get_email_type() ) {
			$email_content = wordwrap( preg_replace( $this->plain_search, $this->plain_replace, wp_strip_all_tags( $this->get_content_plain() ) ), 70 );
		} else {
			$email_content = $this->get_content_html();
		}

		return $email_content;
	}

	/**
	 * Apply inline styles to dynamic content.
	 *
	 * We only inline CSS for html emails, and to do so we use Emogrifier library (if supported).
	 *
	 * @param string|null $content Content that will receive inline styles.
	 *
	 * @return string
	 */
	public function style_inline( $content ) {
		if ( in_array( $this->get_content_type(), array( 'text/html', 'multipart/alternative' ), true ) ) {
			ob_start();
			er_get_template( 'emails/email-styles.php' );
			$css = apply_filters( 'easyreservations_email_styles', ob_get_clean(), $this );

			$emogrifier_class = 'Pelago\\Emogrifier';

			if ( $this->supports_emogrifier() && class_exists( $emogrifier_class ) ) {
				try {
					$emogrifier = new $emogrifier_class( $content, $css );

					do_action( 'easyreservations_emogrifier', $emogrifier, $this );

					$content    = $emogrifier->emogrify();
					$html_prune = \Pelago\Emogrifier\HtmlProcessor\HtmlPruner::fromHtml( $content );
					$html_prune->removeElementsWithDisplayNone();
					$content = $html_prune->render();				} catch ( Exception $e ) {
				} catch ( Exception $e ) {
					er_get_logger()->error( $e->getMessage(), array( 'source' => 'emogrifier' ) );
				}
			} else {
				$content = '<style type="text/css">' . $css . '</style>' . $content;
			}
		}

		return $content;
	}

	/**
	 * Return if emogrifier library is supported.
	 *
	 * @return bool
	 */
	protected function supports_emogrifier() {
		return class_exists( 'DOMDocument' );
	}

	/**
	 * Get the email content in plain text format.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		return '';
	}

	/**
	 * Get the email content in HTML format.
	 *
	 * @return string
	 */
	public function get_content_html() {
		return '';
	}

	/**
	 * Get the from name for outgoing emails.
	 *
	 * @param string $from_name Default wp_mail() name associated with the "from" email address.
	 *
	 * @return string
	 */
	public function get_from_name( $from_name = '' ) {
		$from_name = apply_filters( 'easyreservations_email_from_name', get_option( 'reservations_email_from_name' ), $this, $from_name );

		return wp_specialchars_decode( esc_html( $from_name ), ENT_QUOTES );
	}

	/**
	 * Get the from address for outgoing emails.
	 *
	 * @param string $from_email Default wp_mail() email address to send from.
	 *
	 * @return string
	 */
	public function get_from_address( $from_email = '' ) {
		$from_email = apply_filters( 'easyreservations_email_from_address', get_option( 'reservations_email_from_address' ), $this, $from_email );

		return sanitize_email( $from_email );
	}

	/**
	 * Send an email.
	 *
	 * @param string $to Email to.
	 * @param string $subject Email subject.
	 * @param string $message Email message.
	 * @param string $headers Email headers.
	 * @param array  $attachments Email attachments.
	 *
	 * @return bool success
	 */
	public function send( $to, $subject, $message, $headers, $attachments ) {
		add_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
		add_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
		add_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ) );

		$message              = apply_filters( 'easyreservations_mail_content', $this->style_inline( $message ) );
		$mail_callback        = apply_filters( 'easyreservations_mail_callback', 'wp_mail', $this );
		$mail_callback_params = apply_filters( 'easyreservations_mail_callback_params', array( $to, $subject, $message, $headers, $attachments ), $this );
		$return               = $mail_callback( ...$mail_callback_params );

		remove_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
		remove_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
		remove_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ) );

		return $return;
	}

	/**
	 * Initialise Settings Form Fields - these are generic email options most will use.
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

	/**
	 * Email type options.
	 *
	 * @return array
	 */
	public function get_email_type_options() {
		$types = array( 'plain' => __( 'Plain text', 'easyReservations' ) );

		return apply_filters( 'easyreservations_email_types', $types );
	}

	/**
	 * Admin Panel Options Processing.
	 */
	public function process_admin_options() {
		// Save regular options.
		parent::process_admin_options();

		$post_data = $this->get_post_data();

		// Save templates.
		if ( isset( $post_data['template_html_code'] ) ) {
			$this->save_template( $post_data['template_html_code'], $this->template_html );
		}
		if ( isset( $post_data['template_plain_code'] ) ) {
			$this->save_template( $post_data['template_plain_code'], $this->template_plain );
		}
	}

	/**
	 * Get template.
	 *
	 * @param string $type Template type. Can be either 'template_html' or 'template_plain'.
	 *
	 * @return string
	 */
	public function get_template( $type ) {
		$type = basename( $type );

		if ( 'template_html' === $type ) {
			return $this->template_html;
		} elseif ( 'template_plain' === $type ) {
			return $this->template_plain;
		}

		return '';
	}

	/**
	 * Save the email templates.
	 *
	 * @param string $template_code Template code.
	 * @param string $template_path Template path.
	 */
	protected function save_template( $template_code, $template_path ) {
		if ( current_user_can( 'edit_themes' ) && ! empty( $template_code ) && ! empty( $template_path ) ) {
			$saved = false;
			$file  = get_stylesheet_directory() . '/' . ER()->template_path() . $template_path;
			$code  = wp_unslash( $template_code );

			if ( is_writeable( $file ) ) { // phpcs:ignore WordPress.VIP.FileSystemWritesDisallow.file_ops_is_writeable
				$f = fopen( $file, 'w+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen

				if ( false !== $f ) {
					fwrite( $f, $code ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
					fclose( $f ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
					$saved = true;
				}
			}

			if ( ! $saved ) {
				$redirect = add_query_arg( 'er_error', rawurlencode( __( 'Could not write to template file.', 'easyReservations' ) ) );
				wp_safe_redirect( $redirect );
				exit;
			}
		}
	}

	/**
	 * Get the template file in the current theme.
	 *
	 * @param string $template Template name.
	 *
	 * @return string
	 */
	public function get_theme_template_file( $template ) {
		return get_stylesheet_directory() . '/' . apply_filters( 'easyreservations_template_directory', 'easyreservations', $template ) . '/' . $template;
	}

	/**
	 * Move template action.
	 *
	 * @param string $template_type Template type.
	 */
	protected function move_template_action( $template_type ) {
		$template = $this->get_template( $template_type );
		if ( ! empty( $template ) ) {
			$theme_file = $this->get_theme_template_file( $template );

			if ( wp_mkdir_p( dirname( $theme_file ) ) && ! file_exists( $theme_file ) ) {

				// Locate template file.
				$core_file     = $this->template_base . $template;
				$template_file = apply_filters( 'easyreservations_locate_core_template', $core_file, $template, $this->template_base, $this->id );

				// Copy template file.
				copy( $template_file, $theme_file );

				/**
				 * Action hook fired after copying email template file.
				 *
				 * @param string $template_type The copied template type
				 * @param string $email The email object
				 */
				do_action( 'easyreservations_copy_email_template', $template_type, $this );

				?>
                <div class="updated">
                    <p><?php echo esc_html__( 'Template file copied to theme.', 'easyreservations' ); ?></p>
                </div>
				<?php
			}
		}
	}

	/**
	 * Delete template action.
	 *
	 * @param string $template_type Template type.
	 */
	protected function delete_template_action( $template_type ) {
		$template = $this->get_template( $template_type );

		if ( $template ) {
			if ( ! empty( $template ) ) {
				$theme_file = $this->get_theme_template_file( $template );

				if ( file_exists( $theme_file ) ) {
					unlink( $theme_file ); // phpcs:ignore WordPress.VIP.FileSystemWritesDisallow.file_ops_unlink

					/**
					 * Action hook fired after deleting template file.
					 *
					 * @param string $template The deleted template type
					 * @param string $email The email object
					 */
					do_action( 'easyreservations_delete_email_template', $template_type, $this );
					?>
                    <div class="updated">
                        <p><?php echo esc_html__( 'Template file deleted from theme.', 'easyreservations' ); ?></p>
                    </div>
					<?php
				}
			}
		}
	}

	/**
	 * Admin actions.
	 */
	protected function admin_actions() {
		// Handle any actions.
		if (
			( ! empty( $this->template_html ) || ! empty( $this->template_plain ) )
			&& ( ! empty( $_GET['move_template'] ) || ! empty( $_GET['delete_template'] ) )
			&& 'GET' === $_SERVER['REQUEST_METHOD'] // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		) {
			if ( empty( $_GET['_er_email_nonce'] ) || ! wp_verify_nonce( er_clean( wp_unslash( $_GET['_er_email_nonce'] ) ), 'easyreservations_email_template_nonce' ) ) {
				wp_die( esc_html__( 'Action failed. Please refresh the page and retry.', 'easyReservations' ) );
			}

			if ( ! current_user_can( 'edit_themes' ) ) {
				wp_die( esc_html__( 'You don&#8217;t have permission to do this.', 'easyReservations' ) );
			}

			if ( ! empty( $_GET['move_template'] ) ) {
				$this->move_template_action( er_clean( wp_unslash( $_GET['move_template'] ) ) );
			}

			if ( ! empty( $_GET['delete_template'] ) ) {
				$this->delete_template_action( er_clean( wp_unslash( $_GET['delete_template'] ) ) );
			}
		}
	}

	/**
	 * Admin Options.
	 *
	 * Setup the email settings screen.
	 * Override this in your email.
	 */
	public function admin_options() {
		// Do admin actions.
		$this->admin_actions();
		?>
        <h2><?php echo esc_html( $this->get_title() ); ?><?php er_back_link( __( 'Return to emails', 'easyReservations' ), admin_url( 'admin.php?page=er-settings&tab=emails' ) ); ?></h2>

		<?php echo wpautop( wp_kses_post( $this->get_description() ) ); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped ?>

		<?php
		/**
		 * Action hook fired before displaying email settings.
		 *
		 * @param string $email The email object
		 */
		do_action( 'easyreservations_email_settings_before', $this );
		?>

        <table class="form-table">
			<?php $this->generate_settings_html(); ?>
        </table>

		<?php
		/**
		 * Action hook fired after displaying email settings.
		 *
		 * @param string $email The email object
		 */
		do_action( 'easyreservations_email_settings_after', $this );
		?>

		<?php

		if ( current_user_can( 'edit_themes' ) && ( ! empty( $this->template_html ) || ! empty( $this->template_plain ) ) ) {
			?>
            <div id="template">
				<?php
				$templates = array(
					'template_html'  => __( 'HTML template', 'easyReservations' ),
					'template_plain' => __( 'Plain text template', 'easyReservations' ),
				);

				foreach ( $templates as $template_type => $title ) :
					$template = $this->get_template( $template_type );

					if ( empty( $template ) ) {
						continue;
					}

					$local_file    = $this->get_theme_template_file( $template );
					$core_file     = $this->template_base . $template;
					$template_file = apply_filters( 'easyreservations_locate_core_template', $core_file,  $template, $this->id );
					$template_dir  = apply_filters( 'easyreservations_template_directory', 'easyReservations', $template );

					?>
                    <div class="template <?php echo esc_attr( $template_type ); ?>">
                        <h4><?php echo wp_kses_post( $title ); ?></h4>
						<?php if ( file_exists( $local_file ) ) : ?>
                            <p>
                                <a href="#" class="button toggle_editor"></a>

								<?php if ( is_writable( $local_file ) ) : // phpcs:ignore WordPress.VIP.FileSystemWritesDisallow.file_ops_is_writable ?>
                                    <a href="<?php echo esc_url( wp_nonce_url( remove_query_arg( array( 'move_template', 'saved' ), add_query_arg( 'delete_template', $template_type ) ), 'easyreservations_email_template_nonce', '_er_email_nonce' ) ); ?>" class="delete_template button">
										<?php esc_html_e( 'Delete template file', 'easyReservations' ); ?>
                                    </a>
								<?php endif; ?>

								<?php
								/* translators: %s: Path to template file */
								printf( esc_html__( 'This template has been overridden by your theme and can be found in: %s.', 'easyReservations' ), '<code>' . esc_html( trailingslashit( basename( get_stylesheet_directory() ) ) . $template_dir . '/' . $template ) . '</code>' );
								?>
                            </p>

                            <div class="editor" style="display:none">
								<textarea class="code" cols="25" rows="20"
								<?php
								if ( ! is_writable( $local_file ) ) : // phpcs:ignore WordPress.VIP.FileSystemWritesDisallow.file_ops_is_writable
									?>
                                    readonly="readonly" disabled="disabled"
								<?php else : ?>
                                    data-name="<?php echo esc_attr( $template_type ) . '_code'; ?>"<?php endif; ?>><?php echo esc_html( file_get_contents( $local_file ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents ?></textarea>
                            </div>
						<?php elseif ( file_exists( $template_file ) ) : ?>
                            <p>
                                <a href="#" class="button toggle_editor"></a>

								<?php
								$emails_dir    = get_stylesheet_directory() . '/' . $template_dir . '/emails';
								$templates_dir = get_stylesheet_directory() . '/' . $template_dir;
								$theme_dir     = get_stylesheet_directory();

								if ( is_dir( $emails_dir ) ) {
									$target_dir = $emails_dir;
								} elseif ( is_dir( $templates_dir ) ) {
									$target_dir = $templates_dir;
								} else {
									$target_dir = $theme_dir;
								}

								if ( is_writable( $target_dir ) ) : // phpcs:ignore WordPress.VIP.FileSystemWritesDisallow.file_ops_is_writable
									?>
                                    <a href="<?php echo esc_url( wp_nonce_url( remove_query_arg( array( 'delete_template', 'saved' ), add_query_arg( 'move_template', $template_type ) ), 'easyreservations_email_template_nonce', '_er_email_nonce' ) ); ?>" class="button">
										<?php esc_html_e( 'Copy file to theme', 'easyReservations' ); ?>
                                    </a>
								<?php endif; ?>

								<?php
								/* translators: 1: Path to template file 2: Path to theme folder */
								printf( esc_html__( 'To override and edit this email template copy %1$s to your theme folder: %2$s.', 'easyReservations' ), '<code>' . esc_html( plugin_basename( $template_file ) ) . '</code>', '<code>' . esc_html( trailingslashit( basename( get_stylesheet_directory() ) ) . $template_dir . '/' . $template ) . '</code>' );
								?>
                            </p>

                            <div class="editor" style="display:none">
                                <textarea class="code" readonly="readonly" disabled="disabled" cols="25" rows="20"><?php echo esc_html( file_get_contents( $template_file ) );  // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents ?></textarea>
                            </div>
						<?php else : ?>
                            <p><?php esc_html_e( 'File was not found.', 'easyReservations' ); ?></p>
						<?php endif; ?>
                    </div>
				<?php endforeach; ?>
            </div>

			<?php
			er_enqueue_js(
				"jQuery( 'select.email_type' ).on( 'change',  function() {

					var val = jQuery( this ).val();

					jQuery( '.template_plain, .template_html' ).show();

					if ( val != 'multipart' && val != 'html' ) {
						jQuery('.template_html').hide();
					}

					if ( val != 'multipart' && val != 'plain' ) {
						jQuery('.template_plain').hide();
					}

				}).trigger( 'change' );

				var view = '" . esc_js( __( 'View template', 'easyReservations' ) ) . "';
				var hide = '" . esc_js( __( 'Hide template', 'easyReservations' ) ) . "';


				jQuery( 'a.toggle_editor' ).text( view ).on( 'click', function() {
					var label = hide;

					if ( jQuery( this ).closest(' .template' ).find( '.editor' ).is(':visible') ) {
						var label = view;
					}

					jQuery( this ).text( label ).closest(' .template' ).find( '.editor' ).slideToggle();
					return false;
				} );

				jQuery( 'a.delete_template' ).on( 'click', function() {
					if ( window.confirm('" . esc_js( __( 'Are you sure you want to delete this template file?', 'easyReservations' ) ) . "') ) {
						return true;
					}

					return false;
				});

				jQuery( '.editor textarea' ).on( 'change',  function() {
					var name = jQuery( this ).attr( 'data-name' );

					if ( name ) {
						jQuery( this ).attr( 'name', name );
					}
				});"
			);
		}
	}
}
