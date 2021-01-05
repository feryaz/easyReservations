<?php
/**
 * Shortcodes
 *
 * @package easyReservations/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * ER_Shortcodes class.
 */
class ER_Shortcodes {

	/**
	 * Init shortcodes.
	 */
	public static function init() {
		$shortcodes = array(
			'resource'        => __CLASS__ . '::resource',
			'resource_page'   => __CLASS__ . '::resource_page',
			'resources'       => __CLASS__ . '::resources',
			'easy_messages'   => __CLASS__ . '::messages',
			'easy_cart'       => __CLASS__ . '::cart',
			'easy_checkout'   => __CLASS__ . '::checkout',
			'easy_form'       => __CLASS__ . '::form',
			'easy_my_account' => __CLASS__ . '::my_account',
		);

		foreach ( $shortcodes as $shortcode => $function ) {
			add_shortcode( apply_filters( "{$shortcode}_shortcode_tag", $shortcode ), $function );
		}
	}

	/**
	 * Shortcode Wrapper.
	 *
	 * @param callable $function Callback function.
	 * @param array    $atts Attributes. Default to empty array.
	 * @param array    $wrapper Customer wrapper data.
	 *
	 * @return string
	 */
	public static function shortcode_wrapper(
		$function,
		$atts = array(),
		$wrapper = array(
			'class'  => 'easyreservations',
			'before' => null,
			'after'  => null,
		)
	) {
		ob_start();

		// @codingStandardsIgnoreStart
		echo empty( $wrapper['before'] ) ? '<div class="' . esc_attr( $wrapper['class'] ) . '">' : $wrapper['before'];
		call_user_func( $function, $atts );
		echo empty( $wrapper['after'] ) ? '</div>' : $wrapper['after'];

		// @codingStandardsIgnoreEnd

		return ob_get_clean();
	}

	/**
	 * Cart page shortcode.
	 *
	 * @return string
	 */
	public static function cart() {
		return is_null( ER()->cart ) ? '' : self::shortcode_wrapper( array( 'ER_Shortcode_Cart', 'output' ) );
	}

	/**
	 * Checkout page shortcode.
	 *
	 * @param array $atts Attributes.
	 *
	 * @return string
	 */
	public static function checkout( $atts ) {
		return self::shortcode_wrapper( array( 'ER_Shortcode_Checkout', 'output' ), $atts );
	}

	/**
	 * Form page shortcode.
	 *
	 * @param array $atts Attributes.
	 *
	 * @return string
	 */
	public static function form( $atts ) {
		return self::shortcode_wrapper( array( 'ER_Shortcode_Form', 'output' ), $atts );
	}

	/**
	 * My account page shortcode.
	 *
	 * @param array $atts Attributes.
	 *
	 * @return string
	 */
	public static function my_account( $atts ) {
		return self::shortcode_wrapper( array( 'ER_Shortcode_My_Account', 'output' ), $atts );
	}

	/**
	 * List multiple resources shortcode.
	 *
	 * @param array $atts Attributes.
	 *
	 * @return string
	 */
	public static function resources( $atts ) {
		$atts = (array) $atts;

		$shortcode = new ER_Shortcode_Resources( $atts, 'resources' );

		return $shortcode->get_content();
	}

	/**
	 * Display a single resource.
	 *
	 * @param array $atts Attributes.
	 *
	 * @return string
	 */
	public static function resource( $atts ) {
		if ( empty( $atts ) ) {
			return '';
		}

		$atts['ids']   = isset( $atts['id'] ) ? $atts['id'] : '';
		$atts['limit'] = '1';
		$shortcode     = new ER_Shortcode_Resources( (array) $atts, 'resource' );

		return $shortcode->get_content();
	}

	/**
	 * Show a single resource page.
	 *
	 * @param array $atts Attributes.
	 *
	 * @return string
	 */
	public static function resource_page( $atts ) {
		if ( empty( $atts ) ) {
			return '';
		}

		if ( ! isset( $atts['id'] ) && ! isset( $atts['sku'] ) ) {
			return '';
		}

		$args = array(
			'posts_per_page'      => 1,
			'post_type'           => 'easy-rooms',
			'post_status'         => ( ! empty( $atts['status'] ) ) ? $atts['status'] : 'publish',
			'ignore_sticky_posts' => 1,
			'no_found_rows'       => 1,
		);

		if ( isset( $atts['id'] ) ) {
			$args['p'] = absint( $atts['id'] );
		}

		// Don't render titles if desired.
		if ( isset( $atts['show_title'] ) && ! $atts['show_title'] ) {
			remove_action( 'easyreservations_single_resource_summary', 'easyreservations_template_single_title', 5 );
		}

		// Change form action to avoid redirect.
		add_filter( 'easyreservations_add_to_cart_form_action', '__return_empty_string' );

		$single_resource = new WP_Query( $args );

		$preselected_id = '0';

		// For "is_single" to always make load comments_template() for reviews.
		$single_resource->is_single = true;

		ob_start();

		global $wp_query;

		// Backup query object so following loops think this is a resource page.
		$previous_wp_query = $wp_query;
		// @codingStandardsIgnoreStart
		$wp_query = $single_resource;
		// @codingStandardsIgnoreEnd

		while ( $single_resource->have_posts() ) {

			$single_resource->the_post();

			?>
            <div class="single-easy-rooms" data-resource-page-preselected-id="<?php echo esc_attr( $preselected_id ); ?>">
				<?php er_get_template_part( 'content', 'single-resource' ); ?>
            </div>
			<?php
		}

		// Restore $previous_wp_query and reset post data.
		// @codingStandardsIgnoreStart
		$wp_query = $previous_wp_query;
		// @codingStandardsIgnoreEnd
		wp_reset_postdata();

		// Re-enable titles if they were removed.
		if ( isset( $atts['show_title'] ) && ! $atts['show_title'] ) {
			add_action( 'easyreservations_single_resource_summary', 'easyreservations_template_single_title', 5 );
		}

		remove_filter( 'easyreservations_add_to_cart_form_action', '__return_empty_string' );

		return '<div class="easyreservations">' . ob_get_clean() . '</div>';
	}

	/**
	 * Show messages.
	 *
	 * @return string
	 */
	public static function messages() {
		return '<div class="easyreservations">' . er_print_notices( true ) . '</div>';
	}
}
