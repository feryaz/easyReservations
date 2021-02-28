<?php
/**
 * Template Loader
 *
 * @package easyReservations/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Template loader class.
 */
class ER_Template_Loader {

	/**
	 * Store the shop page ID.
	 *
	 * @var integer
	 */
	private static $shop_page_id = 0;

	/**
	 * Store whether we're processing a resource inside the_content filter.
	 *
	 * @var boolean
	 */
	private static $in_content_filter = false;

	/**
	 * Is easyReservations support defined?
	 *
	 * @var boolean
	 */
	private static $theme_support = false;

	/**
	 * Hook in methods.
	 */
	public static function init() {
		self::$theme_support = current_theme_supports( 'easyreservations' );
		self::$shop_page_id  = er_get_page_id( 'shop' );

		// Supported themes.
		if ( self::$theme_support ) {
			add_filter( 'template_include', array( __CLASS__, 'template_loader' ) );
		} else {
			// Unsupported themes.
			add_action( 'template_redirect', array( __CLASS__, 'unsupported_theme_init' ) );
		}
	}

	/**
	 * Load a template.
	 *
	 * Handles template usage so that we can use our own templates instead of the theme's.
	 *
	 * Templates are in the 'templates' folder. easyReservations looks for theme
	 * overrides in /theme/easyreservations/ by default.
	 *
	 * For beginners, it also looks for a easyreservations.php template first. If the user adds
	 * this to the theme (containing a easyreservations() inside) this will be used for all
	 * easyReservations templates.
	 *
	 * @param string $template Template to load.
	 *
	 * @return string
	 */
	public static function template_loader( $template ) {
		if ( is_embed() ) {
			return $template;
		}

		$default_file = self::get_template_loader_default_file();

		if ( $default_file ) {
			/**
			 * Filter hook to choose which files to find before easyReservations does it's own logic.
			 *
			 * @var array
			 */
			$search_files = self::get_template_loader_files( $default_file );
			$template     = locate_template( $search_files );

			if ( ! $template || RESERVATIONS_TEMPLATE_DEBUG_MODE ) {
				$template = ER()->plugin_path() . '/templates/' . $default_file;
			}
		}

		return $template;
	}

	/**
	 * Get the default filename for a template.
	 *
	 * @return string
	 */
	private static function get_template_loader_default_file() {
		if ( is_singular( 'easy-rooms' ) ) {
			$default_file = 'single-resource.php';
		} elseif ( is_post_type_archive( 'easy-rooms' ) || is_page( er_get_page_id( 'shop' ) ) ) {
			$default_file = self::$theme_support ? 'archive-resource.php' : '';
		} else {
			$default_file = '';
		}

		return $default_file;
	}

	/**
	 * Get an array of filenames to search for a given template.
	 *
	 * @param string $default_file The default file name.
	 *
	 * @return string[]
	 */
	private static function get_template_loader_files( $default_file ) {
		$templates   = apply_filters( 'easyreservations_template_loader_files', array(), $default_file );
		$templates[] = 'easyreservations.php';

		if ( is_page_template() ) {
			$page_template = get_page_template_slug();

			if ( $page_template ) {
				$validated_file = validate_file( $page_template );
				if ( 0 === $validated_file ) {
					$templates[] = $page_template;
				} else {
					error_log( "easyReservations: Unable to validate template path: \"$page_template\". Error Code: $validated_file." );
				}
			}
		}

		if ( is_singular( 'easy-rooms' ) ) {
			$object       = get_queried_object();
			$name_decoded = urldecode( $object->post_name );
			if ( $name_decoded !== $object->post_name ) {
				$templates[] = "single-resource-{$name_decoded}.php";
			}
			$templates[] = "single-resource-{$object->post_name}.php";
		}

		$templates[] = $default_file;
		$templates[] = ER()->template_path() . $default_file;

		return array_unique( $templates );
	}

	/**
	 * Unsupported theme compatibility methods.
	 */

	/**
	 * Hook in methods to enhance the unsupported theme experience on pages.
	 */
	public static function unsupported_theme_init() {
		if ( is_easyreservations_resource() ) {
			self::unsupported_theme_resource_page_init();
		} else if ( 0 < self::$shop_page_id ) {
			self::unsupported_theme_shop_page_init();
		}
	}

	/**
	 * Hook in methods to enhance the unsupported theme experience on the Shop page.
	 */
	private static function unsupported_theme_shop_page_init() {
		add_filter( 'the_content', array( __CLASS__, 'unsupported_theme_shop_content_filter' ), 10 );
		add_filter( 'the_title', array( __CLASS__, 'unsupported_theme_title_filter' ), 10, 2 );
		add_filter( 'comments_number', array( __CLASS__, 'unsupported_theme_comments_number_filter' ) );
	}

	/**
	 * Hook in methods to enhance the unsupported theme experience on Resource pages.
	 */
	private static function unsupported_theme_resource_page_init() {
		add_filter( 'the_content', array( __CLASS__, 'unsupported_theme_resource_content_filter' ), 10 );
		add_filter( 'post_thumbnail_html', array( __CLASS__, 'unsupported_theme_single_featured_image_filter' ) );
		remove_action( 'easyreservations_before_main_content', 'easyreservations_output_content_wrapper', 10 );
		remove_action( 'easyreservations_after_main_content', 'easyreservations_output_content_wrapper_end', 10 );
		add_theme_support( 'er-resource-gallery-zoom' );
		add_theme_support( 'er-resource-gallery-lightbox' );
		add_theme_support( 'er-resource-gallery-slider' );
	}

	/**
	 * Get information about the current shop page view.
	 *
	 * @return object
	 */
	private static function get_current_shop_view_args() {
		return (object) array(
			'page'    => absint( max( 1, absint( get_query_var( 'paged' ) ) ) ),
			'columns' => er_get_default_resources_per_row(),
			'rows'    => er_get_default_resource_rows_per_page(),
		);
	}

	/**
	 * Filter the title and insert easyReservations content on the shop page.
	 *
	 * For non-ER themes, this will setup the main shop page to be shortcode based to improve default appearance.
	 *
	 * @param string $title Existing title.
	 * @param int    $id ID of the post being filtered.
	 *
	 * @return string
	 */
	public static function unsupported_theme_title_filter( $title, $id = null ) {
		if ( self::$theme_support || ! $id !== self::$shop_page_id ) {
			return $title;
		}

		if ( is_page( self::$shop_page_id ) || ( is_home() && 'page' === get_option( 'show_on_front' ) && absint( get_option( 'page_on_front' ) ) === self::$shop_page_id ) ) {
			$args         = self::get_current_shop_view_args();
			$title_suffix = array();

			if ( $args->page > 1 ) {
				/* translators: %d: Page number. */
				$title_suffix[] = sprintf( esc_html__( 'Page %d', 'easyReservations' ), $args->page );
			}

			if ( $title_suffix ) {
				$title = $title . ' &ndash; ' . implode( ', ', $title_suffix );
			}
		}

		return $title;
	}

	/**
	 * Filter the content and insert easyReservations content on the shop page.
	 *
	 * For non-ER themes, this will setup the main shop page to be shortcode based to improve default appearance.
	 *
	 * @param string $content Existing post content.
	 *
	 * @return string
	 */
	public static function unsupported_theme_shop_content_filter( $content ) {
		global $wp_query;

		if ( self::$theme_support || ! is_main_query() || ! in_the_loop() ) {
			return $content;
		}

		self::$in_content_filter = true;

		// Remove the filter we're in to avoid nested calls.
		remove_filter( 'the_content', array( __CLASS__, 'unsupported_theme_shop_content_filter' ) );

		// Unsupported theme shop page.
		if ( is_page( self::$shop_page_id ) ) {
			$args      = self::get_current_shop_view_args();
			$shortcode = new ER_Shortcode_Resources(
				array_merge(
					array(
						'page'     => $args->page,
						'columns'  => $args->columns,
						'rows'     => $args->rows,
						'orderby'  => 'menu_order title',
						'order'    => 'ASC',
						'paginate' => true,
						'cache'    => false,
					)
				),
				'resources'
			);

			// Allow queries to run e.g. layered nav.
			add_action( 'pre_get_posts', array( ER()->query, 'resource_query' ) );

			$content = $content . $shortcode->get_content();

			// Remove actions and self to avoid nested calls.
			remove_action( 'pre_get_posts', array( ER()->query, 'resource_query' ) );
		}

		self::$in_content_filter = false;

		return $content;
	}

	/**
	 * Filter the content and insert easyReservations content on the resource page.
	 *
	 * For non-ER themes, this will setup the main resource page to be shortcode based to improve default appearance.
	 *
	 * @param string $content Existing post content.
	 *
	 * @return string
	 */
	public static function unsupported_theme_resource_content_filter( $content ) {
		if ( self::$theme_support || ! is_main_query() || ! in_the_loop() ) {
			return $content;
		}

		self::$in_content_filter = true;

		// Remove the filter we're in to avoid nested calls.
		remove_filter( 'the_content', array( __CLASS__, 'unsupported_theme_resource_content_filter' ) );

		if ( is_easyreservations_resource() ) {
			$content = do_shortcode( '[resource_page id="' . get_the_ID() . '" show_title=0 status="any"]' );
		}

		self::$in_content_filter = false;

		return $content;
	}

	/**
	 * Suppress the comments number on the Shop page for unsupported themes since there is no commenting on the Shop page.
	 *
	 * @param string $comments_number The comments number text.
	 *
	 * @return string
	 */
	public static function unsupported_theme_comments_number_filter( $comments_number ) {
		if ( is_page( self::$shop_page_id ) ) {
			return '';
		}

		return $comments_number;
	}

	/**
	 * Are we filtering content for unsupported themes?
	 *
	 * @return bool
	 */
	public static function in_content_filter() {
		return (bool) self::$in_content_filter;
	}

	/**
	 * Prevent the main featured image on resource pages because there will be another featured image
	 * in the gallery.
	 *
	 * @param string $html Img element HTML.
	 *
	 * @return string
	 */
	public static function unsupported_theme_single_featured_image_filter( $html ) {
		if ( self::in_content_filter() || ! is_easyreservations_resource() || ! is_main_query() ) {
			return $html;
		}

		return '';
	}

	/**
	 * Remove the Review tab and just use the regular comment form.
	 *
	 * @param array $tabs Tab info.
	 *
	 * @return array
	 */
	public static function unsupported_theme_remove_review_tab( $tabs ) {
		unset( $tabs['reviews'] );

		return $tabs;
	}
}

add_action( 'init', array( 'ER_Template_Loader', 'init' ) );
