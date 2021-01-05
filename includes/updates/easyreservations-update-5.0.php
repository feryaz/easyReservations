<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$charset_collate  = $wpdb->get_charset_collate();
$max_index_length = 191;
$table_name       = $wpdb->prefix . "reservationmeta";

$sql = "CREATE TABLE $table_name (
			  meta_id bigint(20) unsigned NOT NULL auto_increment,
			  reservation_id bigint(20) unsigned NOT NULL default '0',
			  meta_key varchar(255) default NULL,
			  meta_value longtext,
			  PRIMARY KEY  (meta_id),
			  KEY reservation_id (reservation_id),
			  KEY meta_key (meta_key($max_index_length))
			) $charset_collate;";

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
dbDelta( $sql );

$wpdb->query( "ALTER TABLE {$wpdb->prefix}reservations ADD paid DECIMAL(13, 4) NOT NULL default 0" );
$wpdb->query( "ALTER TABLE {$wpdb->prefix}reservations ADD price_temp DECIMAL(13, 4)" );

$custom_fields     = get_option( 'reservations_custom_fields', array() );
$new_custom_fields = array();

$reservations = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}reservations" );
foreach ( $reservations as $reservation ) {
	$customs = maybe_unserialize( $reservation->custom );
	if ( ! empty( $customs ) ) {
		foreach ( $customs as $custom ) {
			$type = 'custom';
			if ( $custom["type"] == 'cstm' ) {
				if ( ! isset( $custom['id'] ) ) {
					if ( ! isset( $new_custom_fields[ $custom['title'] ] ) ) {
						if ( isset( $custom_fields['id'] ) ) {
							$custom_fields['id'] = $custom_fields['id'] + 1;
						} else {
							$custom_fields['id'] = 1;
						}

						$custom_fields['fields'][ $custom_fields['id'] ] = array(
							'title'  => $custom['title'],
							'type'   => 'text',
							'value'  => '',
							'unused' => ''
						);
						$new_custom_fields[ $custom['title'] ]           = $custom_fields['id'];
						$custom['id']                                    = $custom_fields['id'];
					} else {
						$custom['id'] = $new_custom_fields[ $custom['title'] ];
					}
					unset( $custom['title'] );
				}
				unset( $custom['mode'] );
			} else {
				$type = $custom['type'];
			}
			unset( $custom['type'] );

			add_reservation_meta( $reservation->id, $type, $custom );
		}
		$wpdb->query( "UPDATE {$wpdb->prefix}reservations SET custom='' WHERE id='$reservation->id' " );
	}

	$customs = maybe_unserialize( $reservation->customp );

	if ( ! empty( $customs ) ) {
		foreach ( $customs as $custom ) {
			$type = 'custom';
			if ( $custom["type"] == 'cstm' ) {
				if ( ! isset( $custom['id'] ) ) {
					if ( ! isset( $new_custom_fields[ $custom['title'] ] ) ) {
						if ( isset( $custom_fields['id'] ) ) {
							$custom_fields['id'] = $custom_fields['id'] + 1;
						} else {
							$custom_fields['id'] = 1;
						}

						$uid = uniqid();

						$custom_fields['fields'][ $custom_fields['id'] ] = array(
							'title'   => $custom['title'],
							'type'    => 'select',
							'value'   => '',
							'unused'  => '',
							'price'   => 1,
							'options' =>
								array(
									$uid =>
										array(
											'value' => $custom['value'],
											'price' => $custom['amount']
										)
								)
						);
						$custom['value']                                 = $uid;
						$new_custom_fields[ $custom['title'] ]           = $custom_fields['id'];
						$custom['id']                                    = $custom_fields['id'];
					} else {
						$uid = uniqid();

						$custom_fields['fields'][ $new_custom_fields[ $custom['title'] ] ]['options'][ $uid ] = array(
							'value' => $custom['value'],
							'price' => $custom['amount'],
						);

						$custom['value'] = $uid;
						$custom['id']    = $new_custom_fields[ $custom['title'] ];
					}
					unset( $custom['title'] );
					unset( $custom['amount'] );
				}
				unset( $custom['mode'] );
			} else {
				$type = $custom['type'];
			}
			unset( $custom['type'] );

			add_reservation_meta( $reservation->id, $type, $custom );
		}
		$wpdb->query( "UPDATE {$wpdb->prefix}reservations SET customp='' WHERE id='$reservation->id' " );
	}

	$explode = explode( ';', $reservation->price );
	if ( empty( $explode[0] ) ) {
		$explode[0] = $reservation->price;
	}
	if ( is_numeric( $explode[0] ) && $explode[0] > 0 ) {
		$wpdb->query( "UPDATE {$wpdb->prefix}reservations SET price_temp='$explode[0]' WHERE id='$reservation->id' " );
	}
	if ( isset( $explode[1] ) && ! empty( $explode[1] ) && is_numeric( $explode[1] ) && $explode[1] > 0 ) {
		$wpdb->query( "UPDATE {$wpdb->prefix}reservations SET paid='$explode[1]' WHERE id='$reservation->id' " );
	}
}

update_option( 'reservations_custom_fields', $custom_fields );

$wpdb->query( "ALTER TABLE {$wpdb->prefix}reservations DROP price" );
$wpdb->query( "ALTER TABLE {$wpdb->prefix}reservations DROP custom" );
$wpdb->query( "ALTER TABLE {$wpdb->prefix}reservations DROP customp" );
$wpdb->query( "ALTER TABLE {$wpdb->prefix}reservations CHANGE `price_temp` `price` DECIMAL(13,4)" );
$wpdb->query( "ALTER TABLE {$wpdb->prefix}reservations CHANGE `reservated` `reserved` datetime NOT NULL" );
$wpdb->query( "ALTER TABLE {$wpdb->prefix}reservations CHANGE `roomnumber` `space` int(10) NOT NULL" );
$wpdb->query( "ALTER TABLE {$wpdb->prefix}reservations CHANGE `room` `resource` int(10) NOT NULL" );
$wpdb->query( "ALTER TABLE {$wpdb->prefix}reservations CHANGE `number` `adults` int(10) NOT NULL" );
$wpdb->query( "ALTER TABLE {$wpdb->prefix}reservations CHANGE `childs` `children` int(10) NOT NULL" );

$forms = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT option_name FROM {$wpdb->prefix}options WHERE option_name like %s ",
		$wpdb->esc_like( "reservations_form" ) . '%'
	)
);

foreach ( $forms as $form_option ) {
	$form = get_option( $form_option->option_name );
	foreach ( $new_custom_fields as $key => $value ) {
		$form = preg_replace( '/(custom|customp) .{1,10} ' . $key . '/', 'custom id="' . $value . '"', $form );
	}
	if ( ! empty( $form ) ) {
		update_option( $form_option->option_name, $form );
	}
}