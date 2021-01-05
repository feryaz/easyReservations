<?php
/**
 * easyReservations Uninstall
 *
 * Uninstalling easyReservations deletes user roles, pages, tables, and options.
 *
 * @package easyReservations\Uninstaller
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

function easyreservations_delete_plugin() {
	global $wpdb, $wp_version;

	wp_clear_scheduled_hook( 'easyreservations_scheduled_sales' );
	wp_clear_scheduled_hook( 'easyreservations_cancel_unpaid_orders' );
	wp_clear_scheduled_hook( 'easyreservations_cleanup_sessions' );
	wp_clear_scheduled_hook( 'easyreservations_cleanup_personal_data' );
	wp_clear_scheduled_hook( 'easyreservations_cleanup_logs' );

	$remove_all_data = get_option( 'reservations_uninstall', 'no' );

	if ( $remove_all_data === 'yes' ) {
		include_once dirname( __FILE__ ) . '/includes/class-er-install.php';

		ER_Install::remove_roles();

		// Pages.
		wp_trash_post( get_option( 'reservations_shop_page_id' ) );
		wp_trash_post( get_option( 'reservations_cart_page_id' ) );
		wp_trash_post( get_option( 'reservations_checkout_page_id' ) );
		wp_trash_post( get_option( 'reservations_myaccount_page_id' ) );
		wp_trash_post( get_option( 'reservations_edit_address_page_id' ) );
		wp_trash_post( get_option( 'reservations_view_order_page_id' ) );
		wp_trash_post( get_option( 'reservations_change_password_page_id' ) );
		wp_trash_post( get_option( 'reservations_logout_page_id' ) );

		//Options
		delete_option( 'reservations_version' );
		delete_option( 'reservations_db_version' );
		delete_option( 'reservations_enable_taxes' );
		delete_option( 'reservations_prices_include_tax' );
		delete_option( 'reservations_tax_display_cart' );
		delete_option( 'reservations_tax_display_shop' );
		delete_option( 'reservations_tax_total_display' );
		delete_option( 'reservations_tax_round_at_subtotal' );
		delete_option( 'reservations_registration_generate_username' );
		delete_option( 'reservations_registration_generate_password' );
		delete_option( 'reservations_registration_privacy_policy_text' );
		delete_option( 'reservations_checkout_terms_and_conditions_checkbox_text' );
		delete_option( 'reservations_checkout_privacy_policy_text' );
		delete_option( 'reservations_checkout_address_2_field' );
		delete_option( 'reservations_checkout_company_field' );
		delete_option( 'reservations_checkout_phone_field' );
		delete_option( 'reservations_email_background_color' );
		delete_option( 'reservations_email_base_color' );
		delete_option( 'reservations_email_body_background_color' );
		delete_option( 'reservations_email_from_name' );
		delete_option( 'reservations_email_from_address' );
		delete_option( 'reservations_email_footer_text' );
		delete_option( 'reservations_email_header_image' );
		delete_option( 'reservations_email_text_color' );
		delete_option( 'reservations_stock_email_recipient' );
		delete_option( 'reservations_cart_redirect_after_add' );
		delete_option( 'reservations_checkout_highlight_required_fields' );
		delete_option( 'reservations_enable_guest_checkout' );
		delete_option( 'reservations_enable_signup_and_login_from_checkout' );
		delete_option( 'reservations_force_ssl_checkout' );
		delete_option( 'reservations_unforce_ssl_checkout' );
		delete_option( 'reservations_cart_page_id' );
		delete_option( 'reservations_checkout_page_id' );
		delete_option( 'reservations_myaccount_page_id' );
		delete_option( 'reservations_shop_page_id' );
		delete_option( 'reservations_terms_page_id' );
		delete_option( 'reservations_wait_for_payment_minutes' );
		delete_option( 'reservations_gateway_order' );
		delete_option( 'reservations_checkout_pay_endpoint' );
		delete_option( 'reservations_checkout_order_received_endpoint' );
		delete_option( 'reservations_myaccount_orders_endpoint' );
		delete_option( 'reservations_myaccount_view_order_endpoint' );
		delete_option( 'reservations_myaccount_edit_account_endpoint' );
		delete_option( 'reservations_myaccount_edit_address_endpoint' );
		delete_option( 'reservations_myaccount_payment_methods_endpoint' );
		delete_option( 'reservations_myaccount_lost_password_endpoint' );
		delete_option( 'reservations_logout_endpoint' );
		delete_option( 'reservations_myaccount_add_payment_method_endpoint' );
		delete_option( 'reservations_myaccount_delete_payment_method_endpoint' );
		delete_option( 'reservations_myaccount_set_default_payment_method_endpoint' );
		delete_option( 'reservations_reservation_name' );
		delete_option( 'reservations_block_after' );
		delete_option( 'reservations_merge_resources' );
		delete_option( 'reservations_availability_filters' );
		delete_option( 'reservations_tax_rates' );
		delete_option( 'reservations_date_format' );
		delete_option( 'reservations_time_format' );
		delete_option( 'reservations_use_time' );
		delete_option( 'reservations_earliest_arrival' );
		delete_option( 'reservations_price_thousand_sep' );
		delete_option( 'reservations_price_decimal_sep' );
		delete_option( 'reservations_price_decimals' );
		delete_option( 'reservations_currency_pos' );
		delete_option( 'reservations_currency' );
		delete_option( 'reservations_single_image_width' );
		delete_option( 'reservations_thumbnail_image_width' );
		delete_option( 'reservations_thumbnail_cropping' );
		delete_option( 'reservations_thumbnail_cropping_custom_height' );
		delete_option( 'reservations_thumbnail_cropping_custom_width' );
		delete_option( 'reservations_queue_flush_rewrite_rules' );
		delete_option( 'reservations_permalinks' );
		delete_option( 'reservations_placeholder_image' );
		delete_option( 'reservations_catalog_columns' );
		delete_option( 'reservations_catalog_rows' );
		delete_option( 'reservations_resource_page_display_price' );
		delete_option( 'reservations_default_catalog_orderby' );
		delete_option( 'reservations_meta_box_errors' );
		delete_option( 'reservations_admin_notices' );
		delete_option( 'reservations_permission_dashboard' );
		delete_option( 'reservations_permission_resources' );
		delete_option( 'reservations_permission_settings' );
		delete_option( 'reservations_allow_bulk_remove_personal_data' );
		delete_option( 'reservations_enable_order_comments' );
		delete_option( 'reservations_calc_discounts_sequentially' );
		delete_option( 'reservations_deposit' );
		delete_option( 'reservations_deposit_options' );
		delete_option( 'reservations_deposit_optional' );
		delete_option( 'reservations_sync_import_past' );
		delete_option( 'reservations_sync_import_sources' );
		delete_option( 'reservations_enable_coupons' );
		delete_option( 'reservations_stripe_settings' );
		delete_option( 'reservations_paypal_settings' );
		delete_option( 'default_resource_cat' );

		delete_option( 'reservations_backgroundiffull' );
		delete_option( 'reservations_border_bottom' );
		delete_option( 'reservations_border_side' );
		delete_option( 'reservations_colorbackgroundfree' );
		delete_option( 'reservations_fontcoloriffull' );
		delete_option( 'reservations_fontcolorifempty' );
		delete_option( 'reservations_colorborder' );
		delete_option( 'reservations_overview_size' );
		delete_option( 'reservations_email_to_userapp_subj' );
		delete_option( 'reservations_email_to_userapp_msg' );
		delete_option( 'reservations_email_to_userdel_subj' );
		delete_option( 'reservations_email_to_userdel_msg' );
		delete_option( 'reservations_email_to_admin_subj' );
		delete_option( 'reservations_email_to_admin_msg' );
		delete_option( 'reservations_email_to_user_subj' );
		delete_option( 'reservations_email_to_user_msg' );
		delete_option( 'reservations_email_to_user_edited_subj' );
		delete_option( 'reservations_email_to_user_edited_msg' );
		delete_option( 'reservations_email_to_admin_edited_subj' );
		delete_option( 'reservations_email_to_admin_edited_msg' );
		delete_option( 'reservations_email_to_user_admin_edited_subj' );
		delete_option( 'reservations_email_to_user_admin_edited_msg' );
		delete_option( 'reservations_email_sendmail_subj' );
		delete_option( 'reservations_email_sendmail_msg' );
		delete_option( 'reservations_email_sendmail' );
		delete_option( 'reservations_email_to_admin' );
		delete_option( 'reservations_email_to_admin' );
		delete_option( 'reservations_email_to_user' );
		delete_option( 'reservations_email_to_userapp' );
		delete_option( 'reservations_email_to_userdel' );
		delete_option( 'reservations_email_to_user_admin_edited' );
		delete_option( 'reservations_email_to_user_edited' );
		delete_option( 'reservations_email_to_admin_paypal' );
		delete_option( 'reservations_email_to_user_paypal' );
		delete_option( 'reservations_overview_size' );
		delete_option( 'reservations_currency' );
		delete_option( 'reservations_room_category' );
		delete_option( 'reservations_special_offer_cat' );
		delete_option( 'reservations_regular_guests' );
		delete_option( 'reservations_paypal_options' );
		delete_option( 'reservations_authorize_options' );
		delete_option( 'reservations_wallet_options' );
		delete_option( 'reservations_dibs_options' );
		delete_option( 'reservations_ogone_options' );
		delete_option( 'reservations_autoapprove' );
		delete_option( 'reservations_availability_check_pending' );
		delete_option( 'reservations_main_options' );
		delete_option( 'reservations_show_days' );
		delete_option( 'reservations_price_per_persons' );
		delete_option( 'reservations_on_page' );
		delete_option( 'reservations_support_mail' );
		delete_option( 'reservations_coupons' );
		delete_option( 'reservations_uninstall' );
		delete_option( 'reservations_settings' );
		delete_option( 'reservations_form' );
		delete_option( 'reservations_edit_options' );
		delete_option( 'reservations_edit_url' );
		delete_option( 'reservations_main_permission' );
		delete_option( 'reservations_custom_fields' );
		delete_option( 'reservations_active_modules' );
		delete_option( 'reservations_login' );
		delete_option( 'reservations_invoice_number' );
		delete_option( 'reservations_invoice_options' );
		delete_option( 'reservations_credit_card_options' );
		delete_option( 'reservations_google_wallet_queue' );
		delete_option( 'reservations_search_attributes' );
		delete_option( 'reservations_search_bar' );
		delete_option( 'reservations_search_posttype' );
		delete_option( 'reservations_datepicker' );
		delete_option( 'reservations_ics_import' );
		delete_option( 'reservations_woocommerce' );
		delete_option( 'reservations_chat_options' );
		delete_option( 'reservations_edit_options' );
		delete_option( 'reservations_woo_product_ids' );
		delete_option( 'easyreservations_successful_script' );

		// Delete Tables.
		$table_name = $wpdb->prefix . "reservations";
		$wpdb->query( "DROP TABLE IF EXISTS $table_name" );

		$table_name = $wpdb->prefix . "reservationsmeta";
		$wpdb->query( "DROP TABLE IF EXISTS $table_name" );

		$table_name = $wpdb->prefix . "reservations_sessions";
		$wpdb->query( "DROP TABLE IF EXISTS $table_name" );

		$table_name = $wpdb->prefix . "receipt_items";
		$wpdb->query( "DROP TABLE IF EXISTS $table_name" );

		$table_name = $wpdb->prefix . "reservations_payment_tokens";
		$wpdb->query( "DROP TABLE IF EXISTS $table_name" );

		$table_name = $wpdb->prefix . "payment_tokenmeta";
		$wpdb->query( "DROP TABLE IF EXISTS $table_name" );

		// Delete posts + data.
		$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type IN ( 'easy-rooms', 'easy_order', 'easy_order_refund', 'easy_coupon' );" );
		$wpdb->query( "DELETE meta FROM {$wpdb->postmeta} meta LEFT JOIN {$wpdb->posts} posts ON posts.ID = meta.post_id WHERE posts.ID IS NULL;" );

		$wpdb->query( "DELETE FROM {$wpdb->comments} WHERE comment_type IN ( 'er_order_note' );" );
		$wpdb->query( "DELETE meta FROM {$wpdb->commentmeta} meta LEFT JOIN {$wpdb->comments} comments ON comments.comment_ID = meta.comment_id WHERE comments.comment_ID IS NULL;" );

		// Delete terms
		foreach ( array( 'resource_cat', 'resource_tag' ) as $taxonomy ) {
			$wpdb->delete(
				$wpdb->term_taxonomy,
				array(
					'taxonomy' => $taxonomy,
				)
			);
		}

		// Delete orphan relationships.
		$wpdb->query( "DELETE tr FROM {$wpdb->term_relationships} tr LEFT JOIN {$wpdb->posts} posts ON posts.ID = tr.object_id WHERE posts.ID IS NULL;" );

		// Delete orphan terms.
		$wpdb->query( "DELETE t FROM {$wpdb->terms} t LEFT JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id WHERE tt.term_id IS NULL;" );

		// Delete orphan term meta.
		if ( ! empty( $wpdb->termmeta ) ) {
			$wpdb->query( "DELETE tm FROM {$wpdb->termmeta} tm LEFT JOIN {$wpdb->term_taxonomy} tt ON tm.term_id = tt.term_id WHERE tt.term_id IS NULL;" );
		}
	}
}

easyreservations_delete_plugin();
wp_cache_flush();

?>