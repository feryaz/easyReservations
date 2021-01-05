<?php
/**
 * This template can be overridden by copying it to yourtheme/easyreservations/form/date-selection.php.
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

defined( 'ABSPATH' ) || exit;

$arrival        = isset( $_POST['arrival'] ) ? sanitize_text_field( $_POST['arrival'] ) : '';
$departure      = isset( $_POST['departure'] ) ? sanitize_text_field( $_POST['departure'] ) : '';
$arrival_time   = isset( $_POST['arrival_hour'] ) ? intval( $_POST['arrival_hour'] ) * HOUR_IN_SECONDS + ( isset( $_POST['arrival_minute'] ) ? intval( $_POST['arrival_minute'] ) * 60 : 0 ) : false;
$departure_time = isset( $_POST['departure_hour'] ) ? intval( $_POST['departure_hour'] ) * HOUR_IN_SECONDS + ( isset( $_POST['departure_minute'] ) ? intval( $_POST['arrival_minute'] ) * 60 : 0 ) : false;

?>
<div class="easy-date-selection easy-ui" id="easy_selection_<?php echo esc_attr( $uid ); ?>">
	<?php wp_nonce_field( 'er-date-selection', 'easy-date-selection-nonce' ); ?>
    <input type="hidden" name="slot" value="-1">
    <div class="header">
		<?php if ( $display_departure ): ?>
            <div class="departure">
				<?php esc_html_e( 'Departure', 'easyReservations' ); ?>
                <span class="text">
                    <span class="date">
                        <?php
                        if ( $departure !== false ) {
	                        echo esc_html( $departure );
                        } else {
	                        echo '&#8212;';
                        }
                        ?>
                    </span>
                    <span class="time">
                        <?php echo $departure_time ? esc_html( date( er_time_format(), $departure_time ) ) : ''; ?>
                    </span>
                </span>
            </div>
		<?php endif; ?>
        <div class="arrival">
			<?php
			if ( $display_departure ) {
				esc_html_e( 'Arrival', 'easyReservations' );
			} else {
				esc_html_e( 'Date', 'easyReservations' );
			}
			?>
            <span class="text">
                <span class="date">
                    <?php
                    if ( $arrival ) {
	                    echo esc_html( $arrival );
                    } else {
	                    esc_html_e( 'Select Date', 'easyReservations' );
                    }
                    ?>
                </span>
                <span class="time">
                    <?php if ( $arrival_time !== false ) {
	                    echo esc_html( date( er_time_format(), $arrival_time ) );
                    } ?>
                </span>
            </span>
            <input type="hidden" name="arrival" class="input-text validate validate-required" value="<?php echo esc_attr( $arrival ); ?>">
            <input type="hidden" name="arrival_hour" value="<?php echo esc_attr( isset( $_POST['arrival_hour'] ) ? intval( $_POST['arrival_hour'] ) : '' ); ?>">
            <input type="hidden" name="arrival_minute" value="<?php echo esc_attr( isset( $_POST['arrival_minute'] ) ? intval( $_POST['arrival_minute'] ) : '' ); ?>">
            <input type="hidden" name="departure" class="input-text validate" value="<?php echo esc_attr( $departure ); ?> ">
            <input type="hidden" name="departure_hour" value="<?php echo esc_attr( isset( $_POST['departure_hour'] ) ? intval( $_POST['departure_hour'] ) : '' ); ?>">
            <input type="hidden" name="departure_minute" value="<?php echo esc_attr( isset( $_POST['departure_minute'] ) ? intval( $_POST['departure_minute'] ) : '' ); ?>">
        </div>
    </div>
    <div class="calendar">
        <div class="datepicker"></div>
        <input type="hidden" value="" name="datepicker-alt-field" id="datepicker-alt-field"/>
        <div class="time-prototype" style="display:none;">
			<?php
			if ( $time_selection == 'time' ) {
				er_form_get_field(
					array(
						'id'      => 'time',
						'type'    => 'select',
						'class'   => 'do-not-validate',
						'options' => er_form_time_options( $increment, $range ),
						'value'   => false
					)
				);
			} else {
				er_form_get_field(
					array(
						'id'          => 'time_hour',
						'type'        => 'select',
						'class'       => 'do-not-validate',
						'options'     => er_form_time_options(),
						'value'       => false,
						'input-group' => 'start',
					)
				);

				er_form_get_field(
					array(
						'id'          => 'time_minute',
						'type'        => 'select',
						'class'       => 'do-not-validate',
						'options'     => er_form_number_options( "00", 59 ),
						'value'       => false,
						'input-group' => 'end',
					)
				);
			}
			?>
            <input type="button" class="button alt apply-time" value="<?php esc_attr_e( 'Select', 'easyReservations' ); ?>">
        </div>
    </div>
</div>