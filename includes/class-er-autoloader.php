<?php
/**
 * Autoloader to reduce memory usage
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ER_Autoloader' ) ):

	class ER_Autoloader {

		/**
		 * Path to the includes directory.
		 *
		 * @var string
		 */
		private $include_path = '';

		/**
		 * The Constructor.
		 */
		public function __construct() {
			//Check if an autoload function is already loaded
			if ( function_exists( "__autoload" ) ) {
				spl_autoload_register( "__autoload" );
			}

			//Register autoload function
			spl_autoload_register( array( $this, 'autoload' ) );

			$this->include_path = untrailingslashit( plugin_dir_path( RESERVATIONS_PLUGIN_FILE ) ) . '/includes/';
		}

		/**
		 * Auto-load ER classes on demand to reduce memory consumption.
		 *
		 * @param string $class Class name.
		 */
		public function autoload( $class ) {
			//Check if class of easyReservations is requested
			$class = strtolower( $class );
			if ( 0 !== strpos( $class, 'er_' ) && 0 !== strpos( $class, 'erp_' ) ) {
				return;
			}

			$class_parts = explode( '_', $class );
			$file        = sanitize_file_name( 'class-' . str_replace( '_', '-', $class ) . '.php' );

			if ( $class_parts[0] == 'er' ) {
				$path = $this->include_path;
			} elseif ( $class_parts[0] == 'erp' && defined( 'RESERVATIONS_PREMIUM_PLUGIN_FILE' ) ) {
				$path = untrailingslashit( plugin_dir_path( RESERVATIONS_PREMIUM_PLUGIN_FILE ) ) . '/includes/';
			} else {
				$path = apply_filters( 'easyreservations_autoload_path_' . $class_parts['0'], false );
			}

			//Add subfolder to path if necessary
			if ( strpos( $class, 'er_admin' ) === 0 ) {
				$path .= 'admin/';
			} elseif ( 0 === strpos( $class, 'er_shortcode_' ) ) {
				$path = $this->include_path . 'shortcodes/';
			} elseif ( strpos( $class, 'er_meta_box' ) === 0 || strpos( $class, 'erp_meta_box' ) === 0 ) {
				$path .= 'admin/meta-boxes/';
			}

			//Check if readable and include once
			if ( is_readable( $path . $file ) ) {
				include_once( $path . $file );

				return true;
			}

			return false;
		}
	}

	return new ER_Autoloader();

endif;
