<?php
/**
 * Adds settings to the permalinks admin settings page
 *
 * @class       ER_Admin_Permalink_Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ER_Admin_Permalink_Settings Class.
 */
class ER_Admin_Permalink_Settings {

	/**
	 * Permalink settings.
	 *
	 * @var array
	 */
	private $permalinks = array();

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		$this->settings_init();
		$this->settings_save();
	}

	/**
	 * Init our settings.
	 */
	public function settings_init() {
		add_settings_section( 'easyreservations-permalink', __( 'Resource permalinks', 'easyReservations' ), array( $this, 'settings' ), 'permalink' );

		$this->permalinks = er_get_permalink_structure();
	}

	/**
	 * Show the settings.
	 */
	public function settings() {
		/* translators: %s: Home URL */
		echo wp_kses_post( wpautop( sprintf( __( 'If you like, you may enter custom structures for your resource URLs here. For example, using <code>shop</code> would make your resource links like <code>%sshop/sample-resource/</code>.', 'easyReservations' ), esc_url( home_url( '/' ) ) ) ) );

		$shop_page_id  = er_get_page_id( 'shop' );
		$base_slug     = urldecode( ( $shop_page_id > 0 && get_post( $shop_page_id ) ) ? get_page_uri( $shop_page_id ) : _x( 'shop', 'default-slug', 'easyReservations' ) );
		$resource_base = _x( 'resource', 'default-slug', 'easyReservations' );

		$structures = array(
			0 => '',
			1 => '/' . trailingslashit( $base_slug )
		);
		?>
        <table class="form-table er-permalink-structure">
            <tbody>
            <tr>
                <th>
                    <label><input name="resource_permalink" type="radio" value="<?php echo esc_attr( $structures[0] ); ?>" class="ertog" <?php checked( $structures[0], $this->permalinks['resource_base'] ); ?> /> <?php esc_html_e( 'Default', 'easyReservations' ); ?>
                    </label></th>
                <td><code class="default-example"><?php echo esc_html( home_url() ); ?>/?resource=sample-resource</code>
                    <code class="non-default-example"><?php echo esc_html( home_url() ); ?>/<?php echo esc_html( $resource_base ); ?>/sample-resource/</code>
                </td>
            </tr>
			<?php if ( $shop_page_id ) : ?>
                <tr>
                    <th>
                        <label><input name="resource_permalink" type="radio" value="<?php echo esc_attr( $structures[1] ); ?>" class="ertog" <?php checked( $structures[1], $this->permalinks['resource_base'] ); ?> /> <?php esc_html_e( 'Shop base', 'easyReservations' ); ?>
                        </label></th>
                    <td>
                        <code><?php echo esc_html( home_url() ); ?>/<?php echo esc_html( $base_slug ); ?>/sample-resource/</code>
                    </td>
                </tr>
			<?php endif; ?>
            <tr>
                <th>
                    <label><input name="resource_permalink" id="easyreservations_custom_selection" type="radio" value="custom" class="tog" <?php checked( in_array( $this->permalinks['resource_base'], $structures, true ), false ); ?> />
						<?php esc_html_e( 'Custom base', 'easyReservations' ); ?></label></th>
                <td>
                    <input name="resource_permalink_structure" id="easyreservations_permalink_structure" type="text" value="<?php echo esc_attr( $this->permalinks['resource_base'] ? trailingslashit( $this->permalinks['resource_base'] ) : '' ); ?>" class="regular-text code">
                    <span class="description"><?php esc_html_e( 'Enter a custom base to use. A base must be set or WordPress will use default instead.', 'easyReservations' ); ?></span>
                </td>
            </tr>
            </tbody>
        </table>
		<?php wp_nonce_field( 'er-permalinks', 'er-permalinks-nonce' ); ?>
        <script type="text/javascript">
			jQuery( function() {
				jQuery( 'input.ertog' ).on( 'change', function() {
					jQuery( '#easyreservations_permalink_structure' ).val( jQuery( this ).val() );
				} );
				jQuery( '.permalink-structure input' ).on( 'change', function() {
					jQuery( '.er-permalink-structure' ).find( 'code.non-default-example, code.default-example' ).hide();
					if ( jQuery( this ).val() ) {
						jQuery( '.er-permalink-structure code.non-default-example' ).show();
						jQuery( '.er-permalink-structure input' ).removeAttr( 'disabled' );
					} else {
						jQuery( '.er-permalink-structure code.default-example' ).show();
						jQuery( '.er-permalink-structure input:eq(0)' ).trigger( 'click' );
						jQuery( '.er-permalink-structure input' ).attr( 'disabled', 'disabled' );
					}
				} );
				jQuery( '.permalink-structure input:checked' ).trigger( 'change' );
				jQuery( '#easyreservations_permalink_structure' ).focus( function() {
					jQuery( '#easyreservations_custom_selection' ).trigger( 'click' );
				} );
			} );
        </script>
		<?php
	}

	/**
	 * Save the settings.
	 */
	public function settings_save() {
		if ( ! is_admin() ) {
			return;
		}

		// We need to save the options ourselves; settings api does not trigger save for the permalinks page.
		if ( isset( $_POST['permalink_structure'], $_POST['er-permalinks-nonce'], $_POST['resource_permalink'] ) && wp_verify_nonce( wp_unslash( $_POST['er-permalinks-nonce'] ), 'er-permalinks' ) ) { // WPCS: input var ok, sanitization ok.
			er_switch_to_site_locale();

			$permalinks = (array) get_option( 'reservations_permalinks', array() );

			// Generate resource base.
			$resource_base = isset( $_POST['resource_permalink'] ) ? er_clean( wp_unslash( $_POST['resource_permalink'] ) ) : ''; // WPCS: input var ok, sanitization ok.

			if ( 'custom' === $resource_base ) {
				if ( isset( $_POST['resource_permalink_structure'] ) ) { // WPCS: input var ok.
					$resource_base = preg_replace( '#/+#', '/', '/' . str_replace( '#', '', trim( wp_unslash( $_POST['resource_permalink_structure'] ) ) ) ); // WPCS: input var ok, sanitization ok.
				} else {
					$resource_base = '/';
				}
			} elseif ( empty( $resource_base ) ) {
				$resource_base = _x( 'resource', 'slug', 'easyReservations' );
			}

			$permalinks['resource_base'] = er_sanitize_permalink( $resource_base );

			// Shop base may require verbose page rules if nesting pages.
			$shop_page_id   = er_get_page_id( 'shop' );
			$shop_permalink = ( $shop_page_id > 0 && get_post( $shop_page_id ) ) ? get_page_uri( $shop_page_id ) : _x( 'shop', 'default-slug', 'easyReservations' );

			if ( $shop_page_id && stristr( trim( $permalinks['resource_base'], '/' ), $shop_permalink ) ) {
				$permalinks['use_verbose_page_rules'] = true;
			}

			update_option( 'reservations_permalinks', $permalinks );
			er_restore_locale();
		}
	}
}

return new ER_Admin_Permalink_Settings();
