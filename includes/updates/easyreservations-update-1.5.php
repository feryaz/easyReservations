<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$permission = array( 'dashboard' => 'edit_posts', 'statistics' => 'edit_posts', 'resources' => 'edit_posts', 'settings' => 'edit_posts' );
update_option( 'reservations_main_permission', $permission );
add_option( 'reservations_email_to_user', array( 'msg' => get_option( 'reservations_email_to_user_msg' ), 'subj' => get_option( 'reservations_email_to_user_subj' ), 'active' => 1 ), '', 'no' );
add_option( 'reservations_email_to_userapp', array( 'msg' => get_option( 'reservations_email_to_userapp_msg' ), 'subj' => get_option( 'reservations_email_to_userapp_subj' ), 'active' => 1 ), '', 'no' );
add_option( 'reservations_email_to_userdel', array( 'msg' => get_option( 'reservations_email_to_userdel_msg' ), 'subj' => get_option( 'reservations_email_to_userdel_subj' ), 'active' => 1 ), '', 'no' );
add_option( 'reservations_email_to_admin', array( 'msg' => get_option( 'reservations_email_to_admin_msg' ), 'subj' => get_option( 'reservations_email_to_admin_subj' ), 'active' => 1 ), '', 'no' );
add_option( 'reservations_email_to_user_edited', array( 'msg' => get_option( 'reservations_email_to_user_edited_msg' ), 'subj' => get_option( 'reservations_email_to_user_edited_subj' ), 'active' => 1 ), '', 'no' );
add_option( 'reservations_email_to_admin_edited', array( 'msg' => get_option( 'reservations_email_to_admin_edited_msg' ), 'subj' => get_option( 'reservations_email_to_admin_edited_subj' ), 'active' => 1 ), '', 'no' );
add_option( 'reservations_email_to_user_admin_edited', array( 'msg' => get_option( 'reservations_email_to_user_admin_edited_msg' ), 'subj' => get_option( 'reservations_email_to_user_admin_edited_subj' ), 'active' => 1 ), '', 'no' );
add_option( 'reservations_email_sendmail', array( 'msg' => get_option( 'reservations_email_sendmail_msg' ), 'subj' => get_option( 'reservations_email_sendmail_subj' ), 'active' => 1 ), '', 'no' );
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
global $wpdb;
$wpdb->query( "DELETE FROM " . $wpdb->prefix . "postmeta WHERE meta_key = 'reservations_filter' " );
