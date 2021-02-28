<?php
/**
 * Content wrappers
 *
 * This template can be overridden by copying it to yourtheme/easyreservations/global/wrapper-start.php.
 *
 * HOWEVER, on occasion easyReservations will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package easyReservations/Templates
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$template = get_template();

switch ( $template ) {
	case 'twentyten':
		echo '<div id="container"><div id="content" role="main">';
		break;
	case 'twentyeleven':
		echo '<div id="primary"><div id="content" role="main" class="twentyeleven">';
		break;
	case 'twentytwelve':
		echo '<div id="primary" class="site-content"><div id="content" role="main" class="twentytwelve">';
		break;
	case 'twentythirteen':
		echo '<div id="primary" class="site-content"><div id="content" role="main" class="entry-content twentythirteen">';
		break;
	case 'twentyfourteen':
		echo '<div id="primary" class="content-area"><div id="content" role="main" class="site-content twentyfourteen"><div class="tfer">';
		break;
	case 'twentyfifteen':
		echo '<div id="primary" role="main" class="content-area twentyfifteen"><div id="main" class="site-main t15er">';
		break;
	case 'twentysixteen':
		echo '<div id="primary" class="content-area twentysixteen"><main id="main" class="site-main" role="main">';
		break;
	case 'twentyseventeen':
		echo '<div class="wrap"><div id="primary" class="content-area twentyseventeen"><main id="main" class="site-main" role="main">';
		break;
	case 'twentynineteen':
	case 'twentytwenty':
	case 'twentytwentyone':
		echo '<section id="primary" class="content-area"><main id="main" class="site-main">';
		break;
	default:
		echo '<div id="primary" class="content-area"><main id="main" class="site-main" role="main">';
		break;
}
