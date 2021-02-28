<?php
/**
 * Setup Wizard Class
 *
 * Takes new users through some basic steps to setup their store.
 *
 * @package     easyReservations/Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ER_Admin_Setup_Wizard class.
 */
class ER_Admin_Setup_Wizard {

	/**
	 * Current step
	 *
	 * @var string
	 */
	private $step = '';

	/**
	 * Steps for the setup wizard
	 *
	 * @var array
	 */
	private $steps = array();

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		if ( apply_filters( 'easyreservations_enable_setup_wizard', true ) && current_user_can( 'manage_easyreservations' ) ) {
			add_action( 'admin_menu', array( $this, 'admin_menus' ) );
			add_action( 'admin_init', array( $this, 'setup_wizard' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}
	}

	/**
	 * Add admin menus/screens.
	 */
	public function admin_menus() {
		add_dashboard_page( '', '', 'manage_options', 'er-setup', '' );
	}

	/**
	 * Register/enqueue scripts and styles for the Setup Wizard.
	 *
	 * Hooked onto 'admin_enqueue_scripts'.
	 */
	public function enqueue_scripts() {
		// Whether or not there is a pending background install of Jetpack.
		$suffix          = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$version         = RESERVATIONS_VERSION;

		wp_enqueue_style( 'er-admin-style' );
		wp_enqueue_style( 'er-setup', ER()->plugin_url() . '/assets/css/er-setup.css', array( 'dashicons', 'install' ), $version );

		wp_register_script( 'er-setup', ER()->plugin_url() . '/assets/js/admin/er-setup' . $suffix . '.js', array( 'jquery', 'selectWoo', 'er-enhanced-select', 'jquery-blockui', 'wp-util', 'jquery-tiptip', 'backbone', 'er-backbone-modal' ), $version );
		wp_localize_script(
			'er-setup',
			'er_setup_params',
			array(
				'states'                  => ER()->countries->get_states(),
				'postcodes'               => $this->get_postcodes(),
				'current_step'            => isset( $this->steps[ $this->step ] ) ? $this->step : false,
				'i18n'                    => array(
				),
			)
		);
	}

	/**
	 * Helper method to get postcode configurations from `ER()->countries->get_country_locale()`.
	 * We don't use `wp_list_pluck` because it will throw notices when postcode configuration is not defined for a country.
	 *
	 * @return array
	 */
	private function get_postcodes() {
		$locales   = ER()->countries->get_country_locale();
		$postcodes = array();
		foreach ( $locales as $country_code => $locale ) {
			if ( isset( $locale['postcode'] ) ) {
				$postcodes[ $country_code ] = $locale['postcode'];
			}
		}
		return $postcodes;
	}

	/**
	 * Show the setup wizard.
	 */
	public function setup_wizard() {
		if ( empty( $_GET['page'] ) || 'er-setup' !== $_GET['page'] ) { // WPCS: CSRF ok, input var ok.
			return;
		}
		$default_steps = array(
			'store_setup'    => array(
				'name'    => __( 'Store setup', 'easyReservations' ),
				'view'    => array( $this, 'er_setup_store_setup' ),
				'handler' => array( $this, 'er_setup_store_setup_save' ),
			),
			'activate'       => array(
				'name'    => __( 'Activate', 'easyReservations' ),
				'view'    => array( $this, 'er_setup_activate' ),
				'handler' => array( $this, 'er_setup_activate_save' ),
			),
			'next_steps'     => array(
				'name'    => __( 'Ready!', 'easyReservations' ),
				'view'    => array( $this, 'er_setup_ready' ),
				'handler' => '',
			),
		);

		// Hide activate section when the user does not have capabilities to install plugins, think multiside admins not being a super admin.
		if ( ! current_user_can( 'install_plugins' ) ) {
			unset( $default_steps['activate'] );
		}

		$this->steps = apply_filters( 'easyreservations_setup_wizard_steps', $default_steps );
		$this->step  = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : current( array_keys( $this->steps ) ); // WPCS: CSRF ok, input var ok.

		// @codingStandardsIgnoreStart
		if ( ! empty( $_POST['save_step'] ) && isset( $this->steps[ $this->step ]['handler'] ) ) {
			call_user_func( $this->steps[ $this->step ]['handler'], $this );
		}
		// @codingStandardsIgnoreEnd

		ob_start();
		$this->setup_wizard_header();
		$this->setup_wizard_steps();
		$this->setup_wizard_content();
		$this->setup_wizard_footer();
		exit;
	}

	/**
	 * Get the URL for the next step's screen.
	 *
	 * @param string $step  slug (default: current step).
	 *
	 * @return string       URL for next step if a next step exists.
	 *                      Admin URL if it's the last step.
	 *                      Empty string on failure.
	 */
	public function get_next_step_link( $step = '' ) {
		if ( ! $step ) {
			$step = $this->step;
		}

		$keys = array_keys( $this->steps );
		if ( end( $keys ) === $step ) {
			return admin_url();
		}

		$step_index = array_search( $step, $keys, true );
		if ( false === $step_index ) {
			return '';
		}

		return add_query_arg( 'step', $keys[ $step_index + 1 ], remove_query_arg( 'activate_error' ) );
	}

	/**
	 * Setup Wizard Header.
	 */
	public function setup_wizard_header() {
		// same as default WP from wp-admin/admin-header.php.
		$wp_version_class = 'branch-' . str_replace( array( '.', ',' ), '-', floatval( get_bloginfo( 'version' ) ) );

		set_current_screen();
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta name="viewport" content="width=device-width" />
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<title><?php esc_html_e( 'easyReservations &rsaquo; Setup Wizard', 'easyReservations' ); ?></title>
			<?php do_action( 'admin_enqueue_scripts' ); ?>
			<?php wp_print_scripts( 'er-setup' ); ?>
			<?php do_action( 'admin_print_styles' ); ?>
			<?php do_action( 'admin_head' ); ?>
		</head>
		<body class="er-setup wp-core-ui <?php echo esc_attr( 'er-setup-step__' . $this->step ); ?> <?php echo esc_attr( $wp_version_class ); ?>">
		<h1 class="er-logo"><a href="https://easyreservations.org"><img src="<?php echo esc_url( ER()->plugin_url() ); ?>/assets/images/logo32.png" alt="<?php esc_attr_e( 'easyReservations', 'easyReservations' ); ?>" /></a></h1>
		<?php
	}

	/**
	 * Setup Wizard Footer.
	 */
	public function setup_wizard_footer() {
		?>
			<?php if ( 'new_onboarding' === $this->step ) : ?>
				<a class="er-setup-footer-links" href="<?php echo esc_url( $this->get_next_step_link() ); ?>"><?php esc_html_e( 'Continue with the old setup wizard', 'easyReservations' ); ?></a>
			<?php elseif ( 'store_setup' === $this->step ) : ?>
				<a class="er-setup-footer-links" href="<?php echo esc_url( admin_url() ); ?>"><?php esc_html_e( 'Not right now', 'easyReservations' ); ?></a>
			<?php elseif ( 'recommended' === $this->step || 'activate' === $this->step ) : ?>
				<a class="er-setup-footer-links" href="<?php echo esc_url( $this->get_next_step_link() ); ?>"><?php esc_html_e( 'Skip this step', 'easyReservations' ); ?></a>
			<?php endif; ?>
			<?php do_action( 'easyreservations_setup_footer' ); ?>
			</body>
		</html>
		<?php
	}

	/**
	 * Output the steps.
	 */
	public function setup_wizard_steps() {
		$output_steps      = $this->steps;

		// Hide the activate step if Jetpack is already active, unless easyReservations Services
		// features are selected, or unless the Activate step was already taken.
		if ( 'yes' !== get_transient( 'er_setup_activated' ) ) {
			unset( $output_steps['activate'] );
		}

		unset( $output_steps['new_onboarding'] );

		?>
		<ol class="er-setup-steps">
			<?php
			foreach ( $output_steps as $step_key => $step ) {
				$is_completed = array_search( $this->step, array_keys( $this->steps ), true ) > array_search( $step_key, array_keys( $this->steps ), true );

				if ( $step_key === $this->step ) {
					?>
					<li class="active"><?php echo esc_html( $step['name'] ); ?></li>
					<?php
				} elseif ( $is_completed ) {
					?>
					<li class="done">
						<a href="<?php echo esc_url( add_query_arg( 'step', $step_key, remove_query_arg( 'activate_error' ) ) ); ?>"><?php echo esc_html( $step['name'] ); ?></a>
					</li>
					<?php
				} else {
					?>
					<li><?php echo esc_html( $step['name'] ); ?></li>
					<?php
				}
			}
			?>
		</ol>
		<?php
	}

	/**
	 * Output the content for the current step.
	 */
	public function setup_wizard_content() {
		echo '<div class="er-setup-content easyreservations easy-ui">';
		if ( ! empty( $this->steps[ $this->step ]['view'] ) ) {
			call_user_func( $this->steps[ $this->step ]['view'], $this );
		}
		echo '</div>';
	}

	/**
	 * Initial "store setup" step.
	 * Location, product type, page setup, and tracking opt-in.
	 */
	public function er_setup_store_setup() {
		$address        = ER()->countries->get_base_address();
		$address_2      = ER()->countries->get_base_address_2();
		$city           = ER()->countries->get_base_city();
		$state          = ER()->countries->get_base_state();
		$country        = ER()->countries->get_base_country();
		$postcode       = ER()->countries->get_base_postcode();
		$currency       = er_get_currency();
		$sell_in_person = get_option( 'reservations_sell_in_person', 'none_selected' );

		$locale_info         = include ER()->plugin_path() . '/i18n/locale-info.php';
		$currency_by_country = wp_list_pluck( $locale_info, 'currency_code' );
		?>
		<form method="post" class="address-step">
			<input type="hidden" name="save_step" value="store_setup" />
			<?php wp_nonce_field( 'er-setup' ); ?>
			<p class="store-setup"><?php esc_html_e( 'The following wizard will help you configure your store and get you started quickly.', 'easyReservations' ); ?></p>

			<div class="store-address-container">

				<label for="store_country" class="location-prompt"><?php esc_html_e( 'Where is your store based?', 'easyReservations' ); ?></label>
				<select id="store_country" name="store_country" required data-placeholder="<?php esc_attr_e( 'Choose a country / region&hellip;', 'easyReservations' ); ?>" aria-label="<?php esc_attr_e( 'Country / Region', 'easyReservations' ); ?>" class="location-input er-enhanced-select dropdown">
					<?php foreach ( ER()->countries->get_countries() as $code => $label ) : ?>
						<option <?php selected( $code, $country ); ?> value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>

				<label class="location-prompt" for="store_address"><?php esc_html_e( 'Address', 'easyReservations' ); ?></label>
				<input type="text" id="store_address" class="location-input" name="store_address" required value="<?php echo esc_attr( $address ); ?>" />

				<label class="location-prompt" for="store_address_2"><?php esc_html_e( 'Address line 2', 'easyReservations' ); ?></label>
				<input type="text" id="store_address_2" class="location-input" name="store_address_2" value="<?php echo esc_attr( $address_2 ); ?>" />

				<div class="city-and-postcode">
					<div>
						<label class="location-prompt" for="store_city"><?php esc_html_e( 'City', 'easyReservations' ); ?></label>
						<input type="text" id="store_city" class="location-input" name="store_city" required value="<?php echo esc_attr( $city ); ?>" />
					</div>
					<div class="store-state-container hidden">
						<label for="store_state" class="location-prompt">
							<?php esc_html_e( 'State', 'easyReservations' ); ?>
						</label>
						<select id="store_state" name="store_state" data-placeholder="<?php esc_attr_e( 'Choose a state&hellip;', 'easyReservations' ); ?>" aria-label="<?php esc_attr_e( 'State', 'easyReservations' ); ?>" class="location-input er-enhanced-select dropdown"></select>
					</div>
					<div>
						<label class="location-prompt" for="store_postcode"><?php esc_html_e( 'Postcode / ZIP', 'easyReservations' ); ?></label>
						<input type="text" id="store_postcode" class="location-input" name="store_postcode" required value="<?php echo esc_attr( $postcode ); ?>" />
					</div>
				</div>
			</div>

			<div class="store-currency-container">
			<label class="location-prompt" for="currency_code">
				<?php esc_html_e( 'What currency do you accept payments in?', 'easyReservations' ); ?>
			</label>
			<select
				id="currency_code"
				name="currency_code"
				required
				data-placeholder="<?php esc_attr_e( 'Choose a currency&hellip;', 'easyReservations' ); ?>"
				class="location-input er-enhanced-select dropdown"
			>
				<option value=""><?php esc_html_e( 'Choose a currency&hellip;', 'easyReservations' ); ?></option>
				<?php foreach ( er_get_currencies() as $code => $name ) : ?>
					<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $currency, $code ); ?>>
						<?php
						$symbol = er_get_currency_symbol( $code );

						if ( $symbol === $code ) {
							/* translators: 1: currency name 2: currency code */
							echo esc_html( sprintf( __( '%1$s (%2$s)', 'easyReservations' ), $name, $code ) );
						} else {
							/* translators: 1: currency name 2: currency symbol, 3: currency code */
							echo esc_html( sprintf( __( '%1$s (%2$s %3$s)', 'easyReservations' ), $name, $symbol, $code ) );
						}
						?>
					</option>
				<?php endforeach; ?>
			</select>
			<script type="text/javascript">
				var er_setup_currencies = JSON.parse( decodeURIComponent( '<?php echo rawurlencode( wp_json_encode( $currency_by_country ) ); ?>' ) );
				var er_base_state       = "<?php echo esc_js( $state ); ?>";
			</script>
			</div>

			<div class="sell-in-person-container">
			<input
				type="checkbox"
				id="easyreservations_sell_in_person"
				name="sell_in_person"
				value="yes"
				<?php checked( $sell_in_person, true ); ?>
			/>
			<label class="location-prompt" for="easyreservations_sell_in_person">
				<?php esc_html_e( 'I will also be selling products or services in person.', 'easyReservations' ); ?>
			</label>
			</div>

			<input type="checkbox" id="er_tracker_checkbox" name="er_tracker_checkbox" value="yes" <?php checked( 'yes', get_option( 'reservations_allow_tracking', 'no' ) ); ?> />

			<?php $this->tracking_modal(); ?>

			<p class="er-setup-actions step">
				<button class="button-primary button button-large" value="<?php esc_attr_e( "Let's go!", 'easyReservations' ); ?>" name="save_step"><?php esc_html_e( "Let's go!", 'easyReservations' ); ?></button>
			</p>
		</form>
		<?php
	}

	/**
	 * Template for the usage tracking modal.
	 */
	public function tracking_modal() {
		?>
		<script type="text/template" id="tmpl-er-modal-tracking-setup">
			<div class="er-backbone-modal easyreservations-tracker">
				<div class="er-backbone-modal-content">
					<section class="er-backbone-modal-main" role="main">
						<header class="er-backbone-modal-header">
							<h1><?php esc_html_e( 'Help improve easyReservations with usage tracking', 'easyReservations' ); ?></h1>
						</header>
						<article>
							<p>
							<?php
								printf(
									wp_kses(
										/* translators: %1$s: usage tracking help link */
										__( 'Learn more about how usage tracking works, and how you\'ll be helping in our <a href="%1$s" target="_blank">usage tracking documentation</a>.', 'easyReservations' ),
										array(
											'a' => array(
												'href'   => array(),
												'target' => array(),
											),
										)
									),
									'https://woocommerce.com/usage-tracking/'
								);
							?>
							</p>
							<p class="easyreservations-tracker-checkbox">
								<input type="checkbox" id="er_tracker_checkbox_dialog" name="er_tracker_checkbox_dialog" value="yes" <?php checked( 'yes', get_option( 'reservations_allow_tracking', 'no' ) ); ?> />
								<label for="er_tracker_checkbox_dialog"><?php esc_html_e( 'Enable usage tracking and help improve easyReservations', 'easyReservations' ); ?></label>
							</p>
						</article>
						<footer>
							<div class="inner">
								<button class="button button-primary button-large" id="er_tracker_submit" aria-label="<?php esc_attr_e( 'Continue', 'easyReservations' ); ?>"><?php esc_html_e( 'Continue', 'easyReservations' ); ?></button>
							</div>
						</footer>
					</section>
				</div>
			</div>
			<div class="er-backbone-modal-backdrop modal-close"></div>
		</script>
		<?php
	}

	/**
	 * Save initial store settings.
	 */
	public function er_setup_store_setup_save() {
		check_admin_referer( 'er-setup' );

		$address        = isset( $_POST['store_address'] ) ? er_clean( wp_unslash( $_POST['store_address'] ) ) : '';
		$address_2      = isset( $_POST['store_address_2'] ) ? er_clean( wp_unslash( $_POST['store_address_2'] ) ) : '';
		$city           = isset( $_POST['store_city'] ) ? er_clean( wp_unslash( $_POST['store_city'] ) ) : '';
		$country        = isset( $_POST['store_country'] ) ? er_clean( wp_unslash( $_POST['store_country'] ) ) : '';
		$state          = isset( $_POST['store_state'] ) ? er_clean( wp_unslash( $_POST['store_state'] ) ) : '*';
		$postcode       = isset( $_POST['store_postcode'] ) ? er_clean( wp_unslash( $_POST['store_postcode'] ) ) : '';
		$currency_code  = isset( $_POST['currency_code'] ) ? er_clean( wp_unslash( $_POST['currency_code'] ) ) : '';
		$sell_in_person = isset( $_POST['sell_in_person'] ) && ( 'yes' === er_clean( wp_unslash( $_POST['sell_in_person'] ) ) );
		$tracking       = isset( $_POST['er_tracker_checkbox'] ) && ( 'yes' === er_clean( wp_unslash( $_POST['er_tracker_checkbox'] ) ) );

		update_option( 'reservations_store_address', $address );
		update_option( 'reservations_store_address_2', $address_2 );
		update_option( 'reservations_store_city', $city );
		update_option( 'reservations_store_postcode', $postcode );
		update_option( 'reservations_default_location', $country . ':' . $state );
		update_option( 'reservations_currency', $currency_code );
		update_option( 'reservations_sell_in_person', $sell_in_person );

		$locale_info = include ER()->plugin_path() . '/i18n/locale-info.php';

		if ( isset( $locale_info[ $country ] ) ) {
			update_option( 'reservations_weight_unit', $locale_info[ $country ]['weight_unit'] );
			update_option( 'reservations_dimension_unit', $locale_info[ $country ]['dimension_unit'] );

			// Set currency formatting options based on chosen location and currency.
			if ( $locale_info[ $country ]['currency_code'] === $currency_code ) {
				update_option( 'reservations_currency_pos', $locale_info[ $country ]['currency_pos'] );
				update_option( 'reservations_price_decimal_sep', $locale_info[ $country ]['decimal_sep'] );
				update_option( 'reservations_price_num_decimals', $locale_info[ $country ]['num_decimals'] );
				update_option( 'reservations_price_thousand_sep', $locale_info[ $country ]['thousand_sep'] );
			}
		}

		if ( $tracking ) {
			update_option( 'reservations_allow_tracking', 'yes' );
			wp_schedule_single_event( time() + 10, 'easyreservations_tracker_send_event', array( true ) );
		} else {
			update_option( 'reservations_allow_tracking', 'no' );
		}

		ER_Install::create_pages();
		wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	/**
	 * Helper method to retrieve the current user's email address.
	 *
	 * @return string Email address
	 */
	protected function get_current_user_email() {
		$current_user = wp_get_current_user();
		$user_email   = $current_user->user_email;

		return $user_email;
	}

	/**
	 * Go to the next step if Jetpack was connected.
	 */
	protected function er_setup_activate_actions() {
		if (
			isset( $_GET['from'] ) &&
			'wpcom' === $_GET['from']
		) {
			wp_redirect( esc_url_raw( remove_query_arg( 'from', $this->get_next_step_link() ) ) );
			exit;
		}
	}

	/**
	 * Activate step.
	 */
	public function er_setup_activate() {
		$this->er_setup_activate_actions();

		?>
        <p class="er-setup-actions step">
            <a
                href="<?php echo esc_url( $this->get_next_step_link() ); ?>"
                class="button-primary button button-large"
            >
                <?php esc_html_e( 'Finish setting up your store', 'easyReservations' ); ?>
            </a>
        </p>
	    <?php
	}

	/**
	 * Activate step save.
	 *
	 * Install, activate, and launch connection flow for Jetpack.
	 */
	public function er_setup_activate_save() {
		check_admin_referer( 'er-setup' );

		set_transient( 'er_setup_activated', 'yes', MINUTE_IN_SECONDS * 10 );

		wp_redirect( esc_url_raw( esc_url_raw( add_query_arg( array(
			'page'           => 'er-setup',
			'step'           => 'activate',
			'from'           => 'wpcom',
			'activate_error' => false,
		), admin_url() ) ) ) );

		exit;
	}

	/**
	 * Final step.
	 */
	public function er_setup_ready() {
		// We've made it! Don't prompt the user to run the wizard again.
		ER_Admin_Notices::remove_notice( 'install', true );

		$user_email   = $this->get_current_user_email();
		$videos_url   = 'https://docs.woocommerce.com/document/woocommerce-guided-tour-videos/?utm_source=setupwizard&utm_medium=product&utm_content=videos&utm_campaign=woocommerceplugin';
		$docs_url     = 'https://easyreservations.org/topic/getting-started/';
		$help_text    = sprintf(
			/* translators: %1$s: link to videos, %2$s: link to docs */
			__( 'Watch our <a href="%1$s" target="_blank">guided tour videos</a> to learn more about easyReservations, and visit easyreservations.org to learn more about <a href="%2$s" target="_blank">getting started</a>.', 'easyReservations' ),
			$videos_url,
			$docs_url
		);
		?>
		<h1><?php esc_html_e( "You're ready to start selling!", 'easyReservations' ); ?></h1>

		<div class="easyreservations-message easyreservations-newsletter">
			<p><?php esc_html_e( "We're here for you — get tips, product updates, and inspiration straight to your mailbox.", 'easyReservations' ); ?></p>
			<form action="//woocommerce.us8.list-manage.com/subscribe/post?u=2c1434dc56f9506bf3c3ecd21&amp;id=13860df971&amp;SIGNUPPAGE=plugin" method="post" target="_blank" novalidate>
				<div class="newsletter-form-container">
					<input
						class="newsletter-form-email"
						type="email"
						value="<?php echo esc_attr( $user_email ); ?>"
						name="EMAIL"
						placeholder="<?php esc_attr_e( 'Email address', 'easyReservations' ); ?>"
						required
					>
					<p class="er-setup-actions step newsletter-form-button-container">
						<button
							type="submit"
							value="<?php esc_attr_e( 'Yes please!', 'easyReservations' ); ?>"
							name="subscribe"
							id="mc-embedded-subscribe"
							class="button-primary button newsletter-form-button"
						><?php esc_html_e( 'Yes please!', 'easyReservations' ); ?></button>
					</p>
				</div>
			</form>
		</div>

		<ul class="er-wizard-next-steps">
			<li class="er-wizard-next-step-item">
				<div class="er-wizard-next-step-description">
					<p class="next-step-heading"><?php esc_html_e( 'Next step', 'easyReservations' ); ?></p>
					<h3 class="next-step-description"><?php esc_html_e( 'Create some resources', 'easyReservations' ); ?></h3>
					<p class="next-step-extra-info"><?php esc_html_e( "You're ready to add resources to your store.", 'easyReservations' ); ?></p>
				</div>
				<div class="er-wizard-next-step-action">
					<p class="er-setup-actions step">
						<a class="button button-primary button-large" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=easy-rooms&tutorial=true' ) ); ?>">
							<?php esc_html_e( 'Create a resource', 'easyReservations' ); ?>
						</a>
					</p>
				</div>
			</li>
			<li class="er-wizard-next-step-item">
				<div class="er-wizard-next-step-description">
					<p class="next-step-heading"><?php esc_html_e( 'Have an existing store?', 'easyReservations' ); ?></p>
					<h3 class="next-step-description"><?php esc_html_e( 'Import resources', 'easyReservations' ); ?></h3>
					<p class="next-step-extra-info"><?php esc_html_e( 'Transfer existing resources to your new store — just import a CSV file.', 'easyReservations' ); ?></p>
				</div>
				<div class="er-wizard-next-step-action">
					<p class="er-setup-actions step">
						<a class="button button-large" href="<?php echo esc_url( admin_url( 'edit.php?post_type=easy-rooms&page=resource_importer' ) ); ?>">
							<?php esc_html_e( 'Import resources', 'easyReservations' ); ?>
						</a>
					</p>
				</div>
			</li>
			<li class="er-wizard-additional-steps">
				<div class="er-wizard-next-step-description">
					<p class="next-step-heading"><?php esc_html_e( 'You can also:', 'easyReservations' ); ?></p>
				</div>
				<div class="er-wizard-next-step-action">
					<p class="er-setup-actions step">
						<a class="button button-large" href="<?php echo esc_url( admin_url() ); ?>">
							<?php esc_html_e( 'Visit Dashboard', 'easyReservations' ); ?>
						</a>
						<a class="button button-large" href="<?php echo esc_url( admin_url( 'admin.php?page=er-settings' ) ); ?>">
							<?php esc_html_e( 'Review Settings', 'easyReservations' ); ?>
						</a>
						<a class="button button-large" href="<?php echo esc_url( add_query_arg( array( 'autofocus' => array( 'panel' => 'easyReservations' ), 'url' => er_get_page_permalink( 'shop' ) ), admin_url( 'customize.php' ) ) ); ?>">
							<?php esc_html_e( 'View &amp; Customize', 'easyReservations' ); ?>
						</a>
					</p>
				</div>
			</li>
		</ul>
		<p class="next-steps-help-text"><?php echo wp_kses_post( $help_text ); ?></p>
		<?php
	}
}

new ER_Admin_Setup_Wizard();
