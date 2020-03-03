<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

$exists = get_option( 'reservations_settings', null );

if( !is_null($exists) ){
    include  'easyreservations-update-6.0.alpha.1.php';
}

exit;