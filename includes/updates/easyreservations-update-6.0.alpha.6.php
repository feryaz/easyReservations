<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_null( get_option( 'reservations_settings', null ) ) ) {
	include 'easyreservations-update-6.0.alpha.1.php';
}