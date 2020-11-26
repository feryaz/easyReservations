<?php
defined( 'ABSPATH' ) || exit;

/**
 * ER_Settings_General.
 */
class ER_Settings_Emails extends ER_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'emails';
		$this->label = __( 'Emails', 'easyReservations' );

		add_action( 'easyreservations_admin_field_email_notification', array( $this, 'email_notification_setting' ) );
		parent::__construct();
	}

	/**
	 * Get sections.
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections = array(
			'' => __( 'Email options', 'easyReservations' ),
		);

		return apply_filters( 'easyreservations_get_sections_' . $this->id, $sections );
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	public function get_settings() {
		$settings = apply_filters(
			'easyreservations_email_settings',
			array(
				array(
					'title' => __( 'Email notifications', 'easyReservations' ),
					'desc'  => __( 'Email notifications sent from easyReservations are listed below. Click on an email to configure it.', 'easyReservations' ),
					'type'  => 'title',
					'id'    => 'email_notification_settings',
				),

				array( 'type' => 'email_notification' ),

				array(
					'type' => 'sectionend',
					'id'   => 'email_notification_settings',
				),

				array(
					'type' => 'sectionend',
					'id'   => 'email_recipient_options',
				),

				array(
					'title' => __( 'Email sender options', 'easyReservations' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'email_options',
				),

				array(
					'title'    => __( '"From" name', 'easyReservations' ),
					'desc'     => __( 'How the sender name appears in outgoing easyReservations emails.', 'easyReservations' ),
					'id'       => 'reservations_email_from_name',
					'option'   => 'reservations_email_from_name',
					'type'     => 'text',
					'css'      => 'min-width:400px;',
					'default'  => esc_attr( get_bloginfo( 'name', 'display' ) ),
					'autoload' => false,
					'desc_tip' => true,
				),

				array(
					'title'      => __( '"From" address', 'easyReservations' ),
					'desc'       => __( 'How the sender email appears in outgoing easyReservations emails.', 'easyReservations' ),
					'id'         => 'reservations_email_from_address',
					'option'     => 'reservations_email_from_address',
					'type'       => 'email',
					'attributes' => array(
						'multiple' => 'multiple',
					),
					'css'        => 'min-width:400px;',
					'default'    => get_option( 'admin_email' ),
					'autoload'   => false,
					'desc_tip'   => true,
				),

				array(
					'type' => 'sectionend',
					'id'   => 'email_options',
				),

				array(
					'title' => __( 'Email template', 'easyReservations' ),
					'type'  => 'title',
					/* translators: %s: Nonced email preview link */
					'desc'  => sprintf( __( 'This section lets you customize the easyReservations emails. <a href="%s" target="_blank">Click here to preview your email template</a>.', 'easyReservations' ), wp_nonce_url( admin_url( '?preview_easyreservations_mail=true' ), 'preview-mail' ) ),
					'id'    => 'email_template_options',
				),

				array(
					'title'       => __( 'Header image', 'easyReservations' ),
					'desc'        => __( 'URL to an image you want to show in the email header. Upload images using the media uploader (Admin > Media).', 'easyReservations' ),
					'id'          => 'reservations_email_header_image',
					'option'      => 'reservations_email_header_image',
					'type'        => 'text',
					'css'         => 'min-width:400px;',
					'placeholder' => __( 'N/A', 'easyReservations' ),
					'default'     => '',
					'autoload'    => false,
					'desc_tip'    => true,
				),

				array(
					'title'       => __( 'Footer text', 'easyReservations' ),
					/* translators: %s: Available placeholders for use */
					'desc'        => __( 'The text to appear in the footer of all easyReservations emails.', 'easyReservations' ) . ' ' . sprintf( __( 'Available placeholders: %s', 'easyReservations' ), '{site_title} {site_url}' ),
					'id'          => 'reservations_email_footer_text',
					'option'      => 'reservations_email_footer_text',
					'css'         => 'width:400px; height: 75px;',
					'placeholder' => __( 'N/A', 'easyReservations' ),
					'type'        => 'textarea',
					'default'     => '{site_title} &mdash; Built with {easyReservations}',
					'autoload'    => false,
					'desc_tip'    => true,
				),

				array(
					'title'    => __( 'Base color', 'easyReservations' ),
					/* translators: %s: default color */
					'desc'     => sprintf( __( 'The base color for easyReservations email templates. Default %s.', 'easyReservations' ), '<code>#54a0ff</code>' ),
					'id'       => 'reservations_email_base_color',
					'option'   => 'reservations_email_base_color',
					'type'     => 'color',
					'css'      => 'width:6em;',
					'default'  => '#54a0ff',
					'autoload' => false,
					'desc_tip' => true,
				),

				array(
					'title'    => __( 'Background color', 'easyReservations' ),
					/* translators: %s: default color */
					'desc'     => sprintf( __( 'The background color for easyReservations email templates. Default %s.', 'easyReservations' ), '<code>#f7f7f7</code>' ),
					'id'       => 'reservations_email_background_color',
					'option'   => 'reservations_email_background_color',
					'type'     => 'color',
					'css'      => 'width:6em;',
					'default'  => '#f7f7f7',
					'autoload' => false,
					'desc_tip' => true,
				),

				array(
					'title'    => __( 'Body background color', 'easyReservations' ),
					/* translators: %s: default color */
					'desc'     => sprintf( __( 'The main body background color. Default %s.', 'easyReservations' ), '<code>#ffffff</code>' ),
					'id'       => 'reservations_email_body_background_color',
					'option'   => 'reservations_email_body_background_color',
					'type'     => 'color',
					'css'      => 'width:6em;',
					'default'  => '#ffffff',
					'autoload' => false,
					'desc_tip' => true,
				),

				array(
					'title'    => __( 'Body text color', 'easyReservations' ),
					/* translators: %s: default color */
					'desc'     => sprintf( __( 'The main body text color. Default %s.', 'easyReservations' ), '<code>#3c3c3c</code>' ),
					'id'       => 'reservations_email_text_color',
					'option'   => 'reservations_email_text_color',
					'type'     => 'color',
					'css'      => 'width:6em;',
					'default'  => '#3c3c3c',
					'autoload' => false,
					'desc_tip' => true,
				),

				array(
					'type' => 'sectionend',
					'id'   => 'email_template_options',
				),

			)
		);

		return apply_filters( 'easyreservations_get_settings_' . $this->id, $settings );
	}

	/**
	 * Output the settings.
	 */
	public function output() {
		global $current_section;

		// Define emails that can be customised here.
		$mailer          = ER()->mailer();
		$email_templates = $mailer->get_emails();

		if ( $current_section ) {
			foreach ( $email_templates as $email_key => $email ) {
				if ( strtolower( $email_key ) === $current_section ) {
					$email->admin_options();
					break;
				}
			}
		} else {
			$settings = $this->get_settings();
			ER_Admin::output_settings( $settings );
		}
	}

	/**
	 * Save settings.
	 */
	public function save() {
		global $current_section;

		if ( ! $current_section ) {
			ER_Admin_Settings::save_fields( $this->get_settings() );
		} else {
			$er_emails = ER_Emails::instance();

			if ( in_array( $current_section, array_map( 'sanitize_title', array_keys( $er_emails->get_emails() ) ), true ) ) {
				foreach ( $er_emails->get_emails() as $email_id => $email ) {
					if ( sanitize_title( $email_id ) === $current_section ) {
						do_action( 'easyreservations_update_options_' . $this->id . '_' . $email->id );
					}
				}
			} else {
				do_action( 'easyreservations_update_options_' . $this->id . '_' . $current_section );
			}
		}
	}

	/**
	 * Output email notification settings.
	 */
	public function email_notification_setting() {
		// Define emails that can be customised here.
		$mailer          = ER()->mailer();
		$email_templates = $mailer->get_emails();

		?>
        <tr valign="top">
            <td class="er_emails_wrapper" colspan="2">
                <table class="er_emails widefat" cellspacing="0">
                    <thead>
                    <tr>
						<?php
						$columns = apply_filters(
							'easyreservations_email_setting_columns',
							array(
								'status'     => '',
								'name'       => __( 'Email', 'easyReservations' ),
								'email_type' => __( 'Content type', 'easyReservations' ),
								'recipient'  => __( 'Recipient(s)', 'easyReservations' ),
								'actions'    => '',
							)
						);
						foreach ( $columns as $key => $column ) {
							echo '<th class="er-email-settings-table-' . esc_attr( $key ) . '">' . esc_html( $column ) . '</th>';
						}
						?>
                    </tr>
                    </thead>
                    <tbody>
					<?php
					foreach ( $email_templates as $email_key => $email ) {
						echo '<tr>';

						foreach ( $columns as $key => $column ) {

							switch ( $key ) {
								case 'name':
									echo '<td class="er-email-settings-table-' . esc_attr( $key ) . '">
										<a href="' . esc_url( admin_url( 'admin.php?page=er-settings&tab=emails&section=' . strtolower( $email_key ) ) ) . '">' . esc_html( $email->get_title() ) . '</a>
										' . er_get_help( $email->get_description() ) . '
									</td>';
									break;
								case 'recipient':
									echo '<td class="er-email-settings-table-' . esc_attr( $key ) . '">
										' . esc_html( $email->is_customer_email() ? __( 'Customer', 'easyReservations' ) : $email->get_recipient() ) . '
									</td>';
									break;
								case 'status':
									echo '<td class="er-email-settings-table-' . esc_attr( $key ) . '">';

									if ( $email->is_manual() ) {
										echo '<span class="status-manual tips" data-tip="' . esc_attr__( 'Manually sent', 'easyReservations' ) . '">' . esc_html__( 'Manual', 'easyReservations' ) . '</span>';
									} elseif ( $email->is_enabled() ) {
										echo '<span class="status-enabled tips" data-tip="' . esc_attr__( 'Enabled', 'easyReservations' ) . '">' . esc_html__( 'Yes', 'easyReservations' ) . '</span>';
									} else {
										echo '<span class="status-disabled tips" data-tip="' . esc_attr__( 'Disabled', 'easyReservations' ) . '">-</span>';
									}

									echo '</td>';
									break;
								case 'email_type':
									echo '<td class="er-email-settings-table-' . esc_attr( $key ) . '">
										' . esc_html( $email->get_content_type() ) . '
									</td>';
									break;
								case 'actions':
									echo '<td class="er-email-settings-table-' . esc_attr( $key ) . '">
										<a class="button alignright" href="' . esc_url( admin_url( 'admin.php?page=er-settings&tab=emails&section=' . strtolower( $email_key ) ) ) . '">' . esc_html__( 'Manage', 'easyReservations' ) . '</a>
									</td>';
									break;
								default:
									do_action( 'easyreservations_email_setting_column_' . $key, $email );
									break;
							}
						}

						echo '</tr>';
					}
					?>
                    </tbody>
                </table>
            </td>
        </tr>
		<?php
	}
}

return new ER_Settings_Emails();
