<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$hour_options = er_form_time_options();
$minute_options = er_form_number_options( "00", 59 );
?>
<h2><?php esc_html_e( 'Add Slot', 'easyReservations' ); ?></h2>
<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=resource&resource=' . $resource->get_id() ) ); ?>#slots" id="slot" name="slot">
	<?php wp_nonce_field( 'easy-resource-slot' ); ?>
	<input type="hidden" name="slot_edit" id="slot_edit">
	<table class="form-table">
		<tbody>
		<tr>
			<th>
				<label for="slot_name">
					<?php esc_html_e( 'Label', 'easyReservations' ); ?>
					<?php er_print_help( 'Will be displayed in the ' ); ?>
				</label>
			</th>
			<td class="forminp">
				<?php
				er_form_get_field(
					array(
						'id'    => 'slot_name',
						'type'  => 'text',
						'value' => ''
					)
				);
				?>
			</td>
		</tr>
		<tr>
			<th>
				<label for="slot_range_from">
					<?php esc_html_e( 'Active between', 'easyReservations' ); ?>
					<?php er_print_help( 'When this slot is selectable for arrival' ); ?>
				</label>
			</th>
			<td class="forminp">
                <span class="input-wrapper">
                    <input type="text" class="er-datepicker" data-target="slot_range_to" id="slot_range_from" name="slot_range_from" style="width:94px" autocomplete="off">
                    <span class="input-box clickable"><span class="dashicons dashicons-calendar-alt"></span></span>
                </span>
				and
				<span class="input-wrapper">
                    <input type="text" class="er-datepicker" id="slot_range_to" name="slot_range_to" style="width:94px" autocomplete="off">
                    <span class="input-box clickable"><span class="dashicons dashicons-calendar-alt"></span></span>
                </span>
			</td>
		</tr>
		<tr>
			<th>
				<label for="slot_repeat_amount">
					<?php esc_html_e( 'Repeat', 'easyReservations' ); ?>
					<?php er_print_help( 'Repeats the slot so you don\'t have to add it multiple times. This only works for short slots (duration = 0) and cannot reach into the next day.' ); ?>
				</label>
			</th>
			<td class="forminp">
				Repeat this <span id="slot_duration_display"></span> hour slot
				<?php
				er_form_get_field(
					array(
						'id'      => 'slot_repeat_amount',
						'type'    => 'select',
						'css'     => 'width:auto;',
						'options' => er_form_number_options( 0, 50 ),
						'value'   => 0
					)
				);
				?>
				times with
				<?php
				er_form_get_field(
					array(
						'id'      => 'slot_repeat_break',
						'type'    => 'select',
						'css'     => 'width:auto;',
						'options' => er_form_number_options( 0, 600 ),
						'value'   => 0
					)
				);
				?>
				minute breaks in between until <span id="slot_repeat_end"></span>
			</td>
		</tr>
		</tbody>
	</table>
	<h2><?php esc_html_e( 'Arrival', 'easyReservations' ); ?></h2>
	<table class="form-table">
		<tbody>
		<tr>
			<th>
				<label><?php esc_html_e( 'Week days', 'easyReservations' ); ?></label>
			</th>
			<td class="forminp">
				<?php echo er_form_days_options( 'slot_days[]', 9 ); ?>
			</td>
		</tr>
		<tr>
			<th>
				<label><?php esc_html_e( 'Time', 'easyReservations' ); ?></label>
			</th>
			<td class="forminp">
				<?php
				er_form_get_field(
					array(
						'id'      => 'slot-from-hour',
						'type'    => 'select',
						'options' => $hour_options,
						'value'   => 12
					)
				);
				er_form_get_field(
					array(
						'id'      => 'slot-from-minute',
						'type'    => 'select',
						'options' => $minute_options,
						'value'   => "00"
					)
				);
				?>
			</td>
		</tr>
		</tbody>
	</table>
	<h2><?php esc_html_e( 'Departure', 'easyReservations' ); ?></h2>
	<table class="form-table">
		<tbody>
		<tr>
			<th>
				<label for="slot_duration"><?php esc_html_e( 'Duration', 'easyReservations' ); ?></label>
			</th>
			<td class="forminp">
				<?php
				er_form_get_field(
					array(
						'id'      => 'slot_duration',
						'type'    => 'select',
						'options' => er_form_number_options( 0, 250 ),
						'value'   => 0,
						'css'     => 'width:60px;',
					)
				);
				?>
				<?php echo er_date_get_interval_label( DAY_IN_SECONDS ); ?>
			</td>
		</tr>
		<tr>
			<th>
				<label for="slot-to-hour"><?php esc_html_e( 'Time', 'easyReservations' ); ?></label>
			</th>
			<td class="forminp">
				<?php
				er_form_get_field(
					array(
						'id'      => 'slot-to-hour',
						'type'    => 'select',
						'options' => $hour_options,
						'value'   => 12
					)
				);
				er_form_get_field(
					array(
						'id'      => 'slot-to-minute',
						'type'    => 'select',
						'options' => $minute_options,
						'value'   => "00"
					)
				);
				?>
			</td>
		</tr>
		</tbody>
	</table>
	<h2><?php esc_html_e( 'Price', 'easyReservations' ); ?></h2>
	<table class="form-table">
		<tbody>
		<tr>
			<th>
				<label for="slot_base_price"><?php esc_html_e( 'Base price', 'easyReservations' ); ?></label>
			</th>
			<td class="forminp">
				<?php
				er_form_get_field(
					array(
						'id'         => 'slot_base_price',
						'class'      => 'er_input_price',
						'type'       => 'text',
						'together'   => true,
						'icon-after' => er_get_currency_symbol(),
						'value'      => ''
					)
				);
				?>
			</td>
		</tr>
		<tr>
			<th>
				<label for="slot_children_price"><?php esc_html_e( 'Children price', 'easyReservations' ); ?></label>
			</th>
			<td class="forminp">
				<?php
				er_form_get_field(
					array(
						'id'         => 'slot_children_price',
						'class'      => 'er_input_price',
						'type'       => 'text',
						'together'   => true,
						'icon-after' => er_get_currency_symbol(),
						'value'      => ''
					)
				);
				?>
			</td>
		</tr>
		</tbody>
	</table>
	<input type="submit" class="button-primary easyreservations-save-button" value="<?php esc_html_e( 'Add slot', 'easyReservations' ); ?>">
</form>
<script>
	jQuery( document ).ready( function() {
		jQuery( '#slot-from-hour, #slot-from-minute, #slot-to-hour, #slot-to-minute, #slot_duration, #slot_repeat_break, #slot_repeat_amount' ).on( 'change', function() {
			setDuration();
		} );

		function setDuration() {
			var arrival = parseFloat( jQuery( '#slot-from-hour' ).val() ) * 60 + parseFloat( jQuery( '#slot-from-minute' ).val() );
			var departure = parseFloat( jQuery( '#slot-to-hour' ).val() ) * 60 + parseFloat( jQuery( '#slot-to-minute' ).val() );
			var duration = departure + parseInt( jQuery( '#slot_duration' ).val() ) * 1440 - arrival;
			jQuery( '#slot_duration_display' ).html( Math.round( duration / 60 * 100 ) / 100 );
			if ( duration > 0 && duration < 721 ) {
				var i = 0;
				var test = arrival;
				var slot_break = parseInt( jQuery( '#slot_repeat_break' ).val() );
				while ( test <= 1440 ) {
					test += duration + slot_break;
					i++;
				}
				i = i - 2;

				var repeat_amount = jQuery( '#slot_repeat_amount' );
				var repeat = parseInt( repeat_amount.val() );

				jQuery( '#slot_repeat_amount option' ).attr( 'disabled', false );
				jQuery( '#slot_repeat_amount option[value=' + ( i ) + ']' ).nextAll().attr( 'disabled', true );
				if ( repeat >= i ) {
					repeat = i;
					repeat_amount.val( i );
				}

				var end = arrival + ( duration + slot_break ) * ( repeat + 1 );
				if ( end < 1441 ) {
					var hours = Math.floor( end / 60 );
					var minutes = end - hours * 60;
					if ( minutes < 10 ) {
						minutes = '0' + minutes;
					}
					jQuery( '#slot_repeat_end' ).html( hours + ':' + minutes );
				} else {
					jQuery( '#slot_repeat_end' ).html( '' );
				}
			} else {
				jQuery( '#slot_repeat_amount' ).val( 0 );
				jQuery( '#slot_repeat_amount option' ).attr( 'disabled', true );
				jQuery( '#slot_repeat_amount option[value=0]' ).attr( 'disabled', false );
			}
		}

		setDuration();

		jQuery( '.slot-edit' ).on( 'click', function( e ) {
			var slot = jQuery( this ).attr( 'data-slot' );
			slot_edit( slot );
		} );

		jQuery( '.slot-copy' ).on( 'click', function( e ) {
			var slot = slots[ jQuery( this ).attr( 'data-slot' ) ],
				aux  = document.createElement( "input" );
			aux.setAttribute( "value", JSON.stringify( slot ) );
			document.body.appendChild( aux );
			aux.select();
			document.execCommand( "copy" );
			document.body.removeChild( aux );
		} );

		jQuery( '.paste-slot-input' ).on( 'input', function( e ) {
			var json = true;
			try {
				json = JSON.parse( jQuery( this ).val() );
			} catch ( err ) {
				json = false;
			}

			if ( json && json !== null && typeof json == 'object' ) {
				slot_edit( false, json );
				jQuery( this ).val( '' ).addClass( 'hidden' );
			}
		} );

		function slot_edit( i, single_filter ) {
			var slot;
			if ( i === false ) {
				slot = single_filter;
			} else {
				slot = slots[ i ];
				jQuery( '#slot_edit' ).val( parseInt( i ) + 1 );
			}

			jQuery( '#slot_name' ).val( slot[ 'name' ] );
			jQuery( '#slot_range_from' ).val( slot[ 'from_str' ] );
			jQuery( '#slot_range_to' ).val( slot[ 'to_str' ] );
			jQuery( '#slot_duration' ).val( slot[ 'duration' ] );
			jQuery( '#slot_min_adults' ).val( slot[ 'adults-min' ] );
			jQuery( '#slot_max_adults' ).val( slot[ 'adults-max' ] );
			jQuery( '#slot_min_children' ).val( slot[ 'children-min' ] );
			jQuery( '#slot_max_children' ).val( slot[ 'children-max' ] );
			jQuery( '#slot_base_price' ).val( slot[ 'base-price' ] );
			jQuery( '#slot_children_price' ).val( slot[ 'children-price' ] );

			var hour = Math.floor( slot[ 'from' ] / 60 );
			jQuery( '#slot-from-hour' ).val( hour );
			jQuery( '#slot-from-minute' ).val( slot[ 'from' ] - ( hour * 60 ) );

			hour = Math.floor( slot[ 'to' ] / 60 );
			jQuery( '#slot-to-hour' ).val( hour );
			jQuery( '#slot-to-minute' ).val( slot[ 'to' ] - ( hour * 60 ) );

			if ( slot[ 'repeat' ] ) {
				jQuery( '#slot_repeat_amount' ).val( slot[ 'repeat' ] );
				jQuery( '#slot_repeat' ).attr( 'checked', true );
			} else {
				jQuery( '#slot_repeat' ).attr( 'checked', false );
			}

			var checkboxes = jQuery( 'input[name="slot_days[]"]' ),
				count      = 1;

			checkboxes.each( function() {
				var checked = false;
				if ( jQuery.inArray( count, slot[ 'days' ] ) !== -1 ) {
					checked = true;
				}
				jQuery( this ).attr( 'checked', checked );
				count++;
			} );

			jQuery( '.easyreservations-save-button' ).val( '<?php echo stripslashes( esc_html__( 'Edit slot', 'easyReservations' ) ); ?>' );
		}
	} );
</script>