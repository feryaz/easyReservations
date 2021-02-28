<?php
/**
 * Post Types Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ER_Admin_Post_Types Class.
 *
 * Handles the edit posts views and some functionality on the edit post screen for ER post types.
 */
class ER_Admin_Post_Types {

	/**
	 * Constructor.
	 */
	public function __construct() {
		include_once dirname( __FILE__ ) . '/class-er-admin-meta-boxes.php';

		// Load correct list table classes for current screen.
		add_action( 'current_screen', array( $this, 'setup_screen' ) );
		add_action( 'check_ajax_referer', array( $this, 'setup_screen' ) );
		add_action( 'wp_loaded', array( $this, 'save_screen_setting' ), 10, 3 );

		// Admin notices.
		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
		add_filter( 'bulk_post_updated_messages', array( $this, 'bulk_post_updated_messages' ), 10, 2 );

		// Disable Auto Save.
		add_action( 'admin_print_scripts', array( $this, 'disable_autosave' ) );

		// Extra post data and screen elements.
		add_action( 'edit_form_top', array( $this, 'edit_form_top' ) );
		add_filter( 'enter_title_here', array( $this, 'enter_title_here' ), 1, 2 );

		// Hide template for CPT archive.
		add_filter( 'theme_page_templates', array( $this, 'hide_cpt_archive_templates' ), 10, 3 );
		add_action( 'edit_form_top', array( $this, 'show_cpt_archive_notice' ) );

		// Add a post display state for special ER pages.
		add_filter( 'display_post_states', array( $this, 'add_display_post_states' ), 10, 2 );
	}

	/**
	 * Looks at the current screen and loads the correct list table handler.
	 */
	public function setup_screen() {
		global $er_list_table;

		$screen_id = false;

		if ( function_exists( 'get_current_screen' ) ) {
			$screen    = get_current_screen();
			$screen_id = isset( $screen, $screen->id ) ? $screen->id : '';
		}

		if ( ! empty( $_REQUEST['screen'] ) ) { // WPCS: input var ok.
			$screen_id = er_clean( wp_unslash( $_REQUEST['screen'] ) ); // WPCS: input var ok, sanitization ok.
		}

		switch ( $screen_id ) {
			case 'edit-easy_order':
				include_once __DIR__ . '/list-tables/class-er-admin-list-table-orders.php';
				$er_list_table = new ER_Admin_List_Table_Orders();
				break;
			case 'edit-easy_reservation':
				include_once __DIR__ . '/list-tables/class-er-admin-list-table-reservations.php';
				$er_list_table = new ER_Admin_List_Table_Reservations();
				break;
			case 'edit-easy-rooms':
				include_once __DIR__ . '/list-tables/class-er-admin-list-table-resources.php';
				$er_list_table = new ER_Admin_List_Table_Resources();
				break;
			case 'edit-easy_coupon':
				do_action( 'easyreservations_load_coupon_list_table' );
				break;
		}

		// Ensure the table handler is only loaded once. Prevents multiple loads if a plugin calls check_ajax_referer many times.
		remove_action( 'current_screen', array( $this, 'setup_screen' ) );
		remove_action( 'check_ajax_referer', array( $this, 'setup_screen' ) );
	}

	/**
	 * Save custom screen setting of our post types
	 */
	public function save_screen_setting() {

		if ( isset( $_POST['wp_screen_options'] ) && is_array( $_POST['wp_screen_options'] ) ) {
			check_admin_referer( 'screen-options-nonce', 'screenoptionnonce' );

			switch ( sanitize_key( $_POST['wp_screen_options']['option'] ) ) {
				case 'edit_easy_reservation_per_page':
					update_user_meta( get_current_user_id(), 'timeline_hourly', isset( $_POST['timeline_hourly'] ) ? 'on' : 'off' );
					update_user_meta( get_current_user_id(), 'timeline_snapping', isset( $_POST['timeline_snapping'] ) ? 'on' : 'off' );

					break;
			}
		}
	}

	/**
	 * Change messages when a post type is updated.
	 *
	 * @param array $messages Array of messages.
	 *
	 * @return array
	 */
	public function post_updated_messages( $messages ) {
		global $post;

		$messages['easy-rooms'] = array(
			0  => '', // Unused. Messages start at index 1.
			/* translators: %s: resource view URL. */
			1  => sprintf( __( 'Resource updated. <a href="%s">View Resource</a>', 'easyReservations' ), esc_url( get_permalink( $post->ID ) ) ),
			2  => __( 'Custom field updated.', 'easyReservations' ),
			3  => __( 'Custom field deleted.', 'easyReservations' ),
			4  => __( 'Resource updated.', 'easyReservations' ),
			5  => __( 'Revision restored.', 'easyReservations' ),
			/* translators: %s: resource url */
			6  => sprintf( __( 'Resource published. <a href="%s">View Resource</a>', 'easyReservations' ), esc_url( get_permalink( $post->ID ) ) ),
			7  => __( 'Resource saved.', 'easyReservations' ),
			/* translators: %s: resource url */
			8  => sprintf( __( 'Resource submitted. <a target="_blank" href="%s">Preview resource</a>', 'easyReservations' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post->ID ) ) ) ),
			9  => sprintf(
			/* translators: 1: date 2: resource url */
				__( 'Resource scheduled for: %1$s. <a target="_blank" href="%2$s">Preview resource</a>', 'easyReservations' ),
				'<strong>' . date_i18n( __( 'M j, Y @ G:i', 'easyReservations' ), strtotime( $post->post_date ) ) . '</strong>',
				esc_url( get_permalink( $post->ID ) )
			),
			/* translators: %s: resource url */
			10 => sprintf( __( 'Resource draft updated. <a target="_blank" href="%s">Preview resource</a>', 'easyReservations' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post->ID ) ) ) ),
		);

		$messages['easy_order'] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Order updated.', 'easyReservations' ),
			2  => __( 'Custom field updated.', 'easyReservations' ),
			3  => __( 'Custom field deleted.', 'easyReservations' ),
			4  => __( 'Order updated.', 'easyReservations' ),
			5  => __( 'Revision restored.', 'easyReservations' ),
			6  => __( 'Order updated.', 'easyReservations' ),
			7  => __( 'Order saved.', 'easyReservations' ),
			8  => __( 'Order submitted.', 'easyReservations' ),
			9  => sprintf(
			/* translators: %s: date */
				__( 'Order scheduled for: %s.', 'easyReservations' ),
				'<strong>' . date_i18n( __( 'M j, Y @ G:i', 'easyReservations' ), strtotime( $post->post_date ) ) . '</strong>'
			),
			10 => __( 'Order draft updated.', 'easyReservations' ),
			11 => __( 'Order updated and sent.', 'easyReservations' ),
		);

		$messages['easy_coupon'] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Coupon updated.', 'easyReservations' ),
			2  => __( 'Custom field updated.', 'easyReservations' ),
			3  => __( 'Custom field deleted.', 'easyReservations' ),
			4  => __( 'Coupon updated.', 'easyReservations' ),
			5  => __( 'Revision restored.', 'easyReservations' ),
			6  => __( 'Coupon updated.', 'easyReservations' ),
			7  => __( 'Coupon saved.', 'easyReservations' ),
			8  => __( 'Coupon submitted.', 'easyReservations' ),
			9  => sprintf(
			/* translators: %s: date */
				__( 'Coupon scheduled for: %s.', 'easyReservations' ),
				'<strong>' . date_i18n( __( 'M j, Y @ G:i', 'easyReservations' ), strtotime( $post->post_date ) ) . '</strong>'
			),
			10 => __( 'Coupon draft updated.', 'easyReservations' ),
		);

		return $messages;
	}

	/**
	 * Specify custom bulk actions messages for different post types.
	 *
	 * @param array $bulk_messages Array of messages.
	 * @param array $bulk_counts Array of how many objects were updated.
	 *
	 * @return array
	 */
	public function bulk_post_updated_messages( $bulk_messages, $bulk_counts ) {
		$bulk_messages['easy-rooms'] = array(
			/* translators: %s: resource count */
			'updated'   => _n( '%s resource updated.', '%s resources updated.', $bulk_counts['updated'], 'easyReservations' ),
			/* translators: %s: resource count */
			'locked'    => _n( '%s resource not updated, somebody is editing it.', '%s resources not updated, somebody is editing them.', $bulk_counts['locked'], 'easyReservations' ),
			/* translators: %s: resource count */
			'deleted'   => _n( '%s resource permanently deleted.', '%s resources permanently deleted.', $bulk_counts['deleted'], 'easyReservations' ),
			/* translators: %s: resource count */
			'trashed'   => _n( '%s resource moved to the Trash.', '%s resources moved to the Trash.', $bulk_counts['trashed'], 'easyReservations' ),
			/* translators: %s: resource count */
			'untrashed' => _n( '%s resource restored from the Trash.', '%s resources restored from the Trash.', $bulk_counts['untrashed'], 'easyReservations' ),
		);

		$bulk_messages['easy_order'] = array(
			/* translators: %s: order count */
			'updated'   => _n( '%s order updated.', '%s orders updated.', $bulk_counts['updated'], 'easyReservations' ),
			/* translators: %s: order count */
			'locked'    => _n( '%s order not updated, somebody is editing it.', '%s orders not updated, somebody is editing them.', $bulk_counts['locked'], 'easyReservations' ),
			/* translators: %s: order count */
			'deleted'   => _n( '%s order permanently deleted.', '%s orders permanently deleted.', $bulk_counts['deleted'], 'easyReservations' ),
			/* translators: %s: order count */
			'trashed'   => _n( '%s order moved to the Trash.', '%s orders moved to the Trash.', $bulk_counts['trashed'], 'easyReservations' ),
			/* translators: %s: order count */
			'untrashed' => _n( '%s order restored from the Trash.', '%s orders restored from the Trash.', $bulk_counts['untrashed'], 'easyReservations' ),
		);

		$bulk_messages['easy_coupon'] = array(
			/* translators: %s: coupon count */
			'updated'   => _n( '%s coupon updated.', '%s coupons updated.', $bulk_counts['updated'], 'easyReservations' ),
			/* translators: %s: coupon count */
			'locked'    => _n( '%s coupon not updated, somebody is editing it.', '%s coupons not updated, somebody is editing them.', $bulk_counts['locked'], 'easyReservations' ),
			/* translators: %s: coupon count */
			'deleted'   => _n( '%s coupon permanently deleted.', '%s coupons permanently deleted.', $bulk_counts['deleted'], 'easyReservations' ),
			/* translators: %s: coupon count */
			'trashed'   => _n( '%s coupon moved to the Trash.', '%s coupons moved to the Trash.', $bulk_counts['trashed'], 'easyReservations' ),
			/* translators: %s: coupon count */
			'untrashed' => _n( '%s coupon restored from the Trash.', '%s coupons restored from the Trash.', $bulk_counts['untrashed'], 'easyReservations' ),
		);

		return $bulk_messages;
	}

	/**
	 * Disable the auto-save functionality for Orders.
	 */
	public function disable_autosave() {
		global $post;

		if ( $post && in_array( get_post_type( $post->ID ), array( 'easy_order', 'easy_order_refund' ), true ) ) {
			wp_dequeue_script( 'autosave' );
		}
	}

	/**
	 * Output extra data on post forms.
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function edit_form_top( $post ) {
		echo '<input type="hidden" id="original_post_title" name="original_post_title" value="' . esc_attr( $post->post_title ) . '" />';
	}

	/**
	 * Change title boxes in admin.
	 *
	 * @param string  $text Text to shown.
	 * @param WP_Post $post Current post object.
	 *
	 * @return string
	 */
	public function enter_title_here( $text, $post ) {
		switch ( $post->post_type ) {
			case 'easy-rooms':
				$text = esc_html__( 'Resource name', 'easyReservations' );
				break;
		}

		return $text;
	}

	/**
	 * When editing the shop page, we should hide templates.
	 *
	 * @param array   $page_templates Templates array.
	 * @param string  $theme Classname.
	 * @param WP_Post $post The current post object.
	 *
	 * @return array
	 */
	public function hide_cpt_archive_templates( $page_templates, $theme, $post ) {
		$shop_page_id = er_get_page_id( 'shop' );

		if ( $post && absint( $post->ID ) === $shop_page_id ) {
			$page_templates = array();
		}

		return $page_templates;
	}

	/**
	 * Show a notice above the CPT archive.
	 *
	 * @param WP_Post $post The current post object.
	 */
	public function show_cpt_archive_notice( $post ) {
		$shop_page_id = er_get_page_id( 'shop' );

		if ( $post && absint( $post->ID ) === $shop_page_id ) {
			echo '<div class="notice notice-info">';
			/* translators: %s: URL to read more about the shop page. */
			echo '<p>' . sprintf( wp_kses_post( __( 'This is the easyReservations catalog page. The shop page is a special archive that lists your resources. <a href="%s">You can read more about this here</a>.', 'easyReservations' ) ), 'https://easyreservations.org/documentation/easyreservations-pages/' ) . '</p>';
			echo '</div>';
		}
	}

	/**
	 * Add a post display state for special ER pages in the page list table.
	 *
	 * @param array   $post_states An array of post display states.
	 * @param WP_Post $post The current post object.
	 */
	public function add_display_post_states( $post_states, $post ) {
		if ( er_get_page_id( 'shop' ) === $post->ID ) {
			$post_states['er_page_for_shop'] = __( 'Catalog Page', 'easyReservations' );
		}

		if ( er_get_page_id( 'cart' ) === $post->ID ) {
			$post_states['er_page_for_cart'] = __( 'Cart Page', 'easyReservations' );
		}

		if ( er_get_page_id( 'checkout' ) === $post->ID ) {
			$post_states['er_page_for_checkout'] = __( 'Checkout Page', 'easyReservations' );
		}

		if ( er_get_page_id( 'myaccount' ) === $post->ID ) {
			$post_states['er_page_for_myaccount'] = __( 'My Account Page', 'easyReservations' );
		}

		if ( er_get_page_id( 'terms' ) === $post->ID ) {
			$post_states['er_page_for_terms'] = __( 'Terms and Conditions Page', 'easyReservations' );
		}

		return $post_states;
	}
}

new ER_Admin_Post_Types();
