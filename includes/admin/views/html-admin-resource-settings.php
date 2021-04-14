<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$requirements = $resource->get_requirements();

$arrival_possible_on   = isset( $requirements['start-on'] ) ? $requirements['start-on'] : 0;
$departure_possible_on = isset( $requirements['end-on'] ) ? $requirements['end-on'] : 0;
$starton_h             = isset( $requirements['start-h'] ) ? $requirements['start-h'] : array( 0, 23 );
$endon_h               = isset( $requirements['end-h'] ) ? $requirements['end-h'] : array( 0, 23 );

$nights_min = isset( $requirements['nights-min'] ) ? intval( $requirements['nights-min'] ) : 0;
$nights_max = isset( $requirements['nights-max'] ) ? intval( $requirements['nights-max'] ) : 0;
$pers_min   = isset( $requirements['pers-min'] ) ? intval( $requirements['pers-min'] ) : 1;
$pers_max   = isset( $requirements['pers-max'] ) ? intval( $requirements['pers-max'] ) : 0;

$interval_string = er_date_get_interval_label( $resource->get_billing_interval(), 2 );

$max_requirement_options    = er_form_number_options( 1, 250 );
$max_requirement_options[0] = '&infin;';
sort( $max_requirement_options );

$time_options = er_form_time_options();
?>
<form id="resource_settings" name="resource_settings" method="post">
    <input type="hidden" name="easy-resource-settings" value="<?php echo esc_attr( wp_create_nonce( 'easy-resource-settings' ) ); ?>">
	<?php
	ER_Admin::output_settings( array(
		array(
			'type'  => 'title',
			'title' => __( 'Billing', 'easyReservations' ),
		),
		array(
			'title'      => __( 'Base price', 'easyReservations' ),
			'desc'       => __( 'per billing unit.', 'easyReservations' ),
			'desc_tip'   => __( 'The amount of money one billing unit costs if no filter gets applied. Must be positive.', 'easyReservations' ),
			'id'         => 'base_price',
			'type'       => 'text',
			'icon-after' => er_get_currency_symbol(),
			'together'   => true,
			'value'      => $resource->get_base_price()
		),
		array(
			'title'      => __( 'Children price', 'easyReservations' ),
			'desc'       => __( 'per billing unit.', 'easyReservations' ),
			'desc_tip'   => __( 'The amount of money that one children costs per billing unit. Must be positive, but can be percentage.', 'easyReservations' ),
			'id'         => 'children_price',
			'type'       => 'text',
			'icon-after' => er_get_currency_symbol(),
			'together'   => true,
			'value'      => $resource->get_children_price()
		),
		array(
			'title'       => __( 'Billing interval', 'easyReservations' ),
			'desc'        => __( 'The interval by which reservations get billed. In daily mode every 24 hours get charged while the nightly mode only charges once per day regardless of arrival and departure time.', 'easyReservations' ),
			'desc_tip'    => true,
			'id'          => 'billing_method',
			'type'        => 'select',
			'input-group' => 'start',
			'together'    => true,
			'options'     => array(
				'0' => __( 'Started', 'easyReservations' ),
				'1' => __( 'Completed', 'easyReservations' )
			),
			'value'       => $resource->get_billing_method()
		),
		array(
			'id'          => 'billing_interval',
			'type'        => 'select',
			'input-group' => 'end',
			'together'    => true,
			'options'     => array(
				'1800'    => er_date_get_interval_label( 1800, 1 ),
				'3600'    => er_date_get_interval_label( HOUR_IN_SECONDS, 1 ),
				'86400'   => er_date_get_interval_label( DAY_IN_SECONDS, 1 ),
				'86401'   => er_date_get_interval_label( 86401, 1 ),
				'604800'  => er_date_get_interval_label( WEEK_IN_SECONDS, 1 ),
				'2592000' => er_date_get_interval_label( MONTH_IN_SECONDS, 1 )
			),
			'value'       => $resource->get_billing_method() == 3 ? 86401 : $resource->get_billing_interval()
		),
		array(
			'title'       => __( 'Billing', 'easyReservations' ),
			'desc_tip'    => __( 'Base price gets multiplied by amount of adults, children price by amount of children.', 'easyReservations' ),
			'desc'        => sprintf( esc_html__( 'Price per %s', 'easyReservations' ), esc_html__( 'person', 'easyReservations' ) ),
			'input-group' => 'start',
			'id'          => 'billing_per_person',
			'type'        => 'checkbox',
			'value'       => $resource->bill_per_person()
		),
		array(
			'desc_tip'    => __( 'Regardless of reservations duration.', 'easyReservations' ),
			'desc'        => esc_html__( 'Bill only once', 'easyReservations' ),
			'input-group' => 'end',
			'id'          => 'billing_once',
			'type'        => 'checkbox',
			'value'       => $resource->bill_only_once() ? 'yes' : 'no'
		),
		array(
			'type' => 'sectionend',
		),

		array(
			'type'  => 'title',
			'title' => __( 'Availability', 'easyReservations' ),
		),

		array(
			'title'    => __( 'Frequency', 'easyReservations' ),
			'desc_tip' => __( 'In which frequency the resource can be reserved. Defines how availability gets displayed.', 'easyReservations' ),
			'id'       => 'frequency',
			'type'     => 'select',
			'class'    => 'er-enhanced-select',
			'options'  => array(
				'1800'  => __( 'Half-hourly', 'easyReservations' ),
				'3600'  => __( 'Hourly', 'easyReservations' ),
				'86400' => __( 'Daily', 'easyReservations' ),
			),
			'value'    => $resource->get_frequency()
		),
		array(
			'title'    => __( 'Spaces/Quantity', 'easyReservations' ),
			'desc_tip' => __( 'How often the resource can be reserved at the same time. Below you can define a label for each of those spaces.', 'easyReservations' ),
			'id'       => 'quantity',
			'type'     => 'select',
			'class'    => 'er-enhanced-select',
			'options'  => er_form_number_options( 1, 250 ),
			'value'    => $resource->get_quantity()
		),
		array(
			'title'    => __( 'Availability per', 'easyReservations' ),
			'desc_tip' => '<b>Per object</b><br>The quantity defines how often the resource can get reserved at the same time regardless of the amount of persons. Each space can have a label.<br><br><b>Per person/adult/children</b><br>The quantity defines how many persons/adults/children can reserve at the same time regardless of the amount of reservations. The resource will be summarized in one row in the timeline',
			'id'       => 'availability_by',
			'type'     => 'select',
			'class'    => 'er-enhanced-select',
			'options'  => array(
				'unit'     => __( 'Object', 'easyReservations' ),
				'pers'     => ucfirst( __( 'person', 'easyReservations' ) ),
				'adult'    => ucfirst( __( 'adult', 'easyReservations' ) ),
				'children' => ucfirst( __( 'child', 'easyReservations' ) )
			),
			'value'    => $resource->availability_by()
		),
		array(
			'type' => 'sectionend',
		),

		array(
			'type'  => 'title',
			'title' => __( 'Requirements', 'easyReservations' ),
		),

		array(
			'title'       => ucfirst( $interval_string ),
			'desc_tip'    => 'Required amount of ' . $interval_string . ' that can be reserved. ',
			'desc'        => '-',
			'id'          => 'units_minimum',
			'input-group' => 'start',
			'type'        => 'select',
			'css'         => 'width:200px',
			'options'     => er_form_number_options( 0, 250 ),
			'value'       => $nights_min
		),
		array(
			'id'          => 'units_max',
			'type'        => 'select',
			'css'         => 'width:200px',
			'input-group' => 'end',
			'options'     => $max_requirement_options,
			'value'       => $nights_max
		),
		array(
			'title'       => __( 'Person', 'easyReservations' ),
			'desc_tip'    => 'Required and maximum amount of adults+children that can be reserved. ',
			'desc'        => '-',
			'id'          => 'person_minimum',
			'type'        => 'select',
			'css'         => 'width:200px',
			'input-group' => 'start',
			'options'     => er_form_number_options( 1, 250 ),
			'value'       => $pers_min
		),
		array(
			'id'          => 'person_max',
			'type'        => 'select',
			'input-group' => 'end',
			'css'         => 'width:200px',
			'options'     => $max_requirement_options,
			'value'       => $pers_max
		),
		array(
			'title'       => __( 'Arrival possible on', 'easyReservations' ),
			'desc'        => er_date_get_label( 0, 0, 0 ),
			'id'          => 'arrival_on_mon',
			'name'        => 'arrival_on[]',
			'input-group' => 'start',
			'type'        => 'checkbox',
			'value'       => $arrival_possible_on === 0 || in_array( 1, $arrival_possible_on ) ? 'yes' : 'no',
			'default'     => 1,
		),
		array(
			'desc'        => er_date_get_label( 0, 0, 1 ),
			'id'          => 'arrival_on_tue',
			'name'        => 'arrival_on[]',
			'input-group' => 'middle',
			'type'        => 'checkbox',
			'value'       => $arrival_possible_on === 0 || in_array( 2, $arrival_possible_on ) ? 'yes' : 'no',
			'default'     => 2
		),
		array(
			'desc'        => er_date_get_label( 0, 0, 2 ),
			'id'          => 'arrival_on_wed',
			'name'        => 'arrival_on[]',
			'input-group' => 'middle',
			'type'        => 'checkbox',
			'value'       => $arrival_possible_on === 0 || in_array( 3, $arrival_possible_on ) ? 'yes' : 'no',
			'default'     => 3
		),
		array(
			'desc'        => er_date_get_label( 0, 0, 3 ),
			'id'          => 'arrival_on_thu',
			'name'        => 'arrival_on[]',
			'input-group' => 'middle',
			'type'        => 'checkbox',
			'value'       => $arrival_possible_on === 0 || in_array( 4, $arrival_possible_on ) ? 'yes' : 'no',
			'default'     => 4
		),
		array(
			'desc'        => er_date_get_label( 0, 0, 4 ),
			'id'          => 'arrival_on_fri',
			'name'        => 'arrival_on[]',
			'input-group' => 'middle',
			'type'        => 'checkbox',
			'value'       => $arrival_possible_on === 0 || in_array( 5, $arrival_possible_on ) ? 'yes' : 'no',
			'default'     => 5
		),
		array(
			'desc'        => er_date_get_label( 0, 0, 5 ),
			'id'          => 'arrival_on_sat',
			'name'        => 'arrival_on[]',
			'input-group' => 'middle',
			'type'        => 'checkbox',
			'value'       => $arrival_possible_on === 0 || in_array( 6, $arrival_possible_on ) ? 'yes' : 'no',
			'default'     => 6
		),
		array(
			'desc'        => er_date_get_label( 0, 0, 6 ),
			'id'          => 'arrival_on_sun',
			'name'        => 'arrival_on[]',
			'input-group' => 'end',
			'type'        => 'checkbox',
			'value'       => $arrival_possible_on === 0 || in_array( 7, $arrival_possible_on ) ? 'yes' : 'no',
			'default'     => 7
		),
		array(
			'title'       => __( 'Arrival possible between', 'easyReservations' ),
			'desc'        => __( 'and', 'easyReservations' ),
			'id'          => 'arrival_between_from',
			'input-group' => 'start',
			'type'        => 'select',
			'class'       => 'er-enhanced-select',
			'options'     => $time_options,
			'value'       => $starton_h[0]
		),
		array(
			'id'          => 'arrival_between_to',
			'type'        => 'select',
			'class'       => 'er-enhanced-select',
			'input-group' => 'end',
			'options'     => $time_options,
			'value'       => $starton_h[1]
		),
		array(
			'title'       => __( 'Departure possible on', 'easyReservations' ),
			'desc'        => er_date_get_label( 0, 0, 0 ),
			'id'          => 'departure_on_mon',
			'name'        => 'departure_on[]',
			'input-group' => 'start',
			'type'        => 'checkbox',
			'value'       => $departure_possible_on === 0 || in_array( 1, $departure_possible_on ) ? 'yes' : 'no',
			'default'     => 1,
		),
		array(
			'desc'        => er_date_get_label( 0, 0, 1 ),
			'id'          => 'departure_on_tue',
			'name'        => 'departure_on[]',
			'input-group' => 'middle',
			'type'        => 'checkbox',
			'value'       => $departure_possible_on === 0 || in_array( 2, $departure_possible_on ) ? 'yes' : 'no',
			'default'     => 2,
		),
		array(
			'desc'        => er_date_get_label( 0, 0, 2 ),
			'id'          => 'departure_on_wed',
			'name'        => 'departure_on[]',
			'input-group' => 'middle',
			'type'        => 'checkbox',
			'value'       => $departure_possible_on === 0 || in_array( 3, $departure_possible_on ) ? 'yes' : 'no',
			'default'     => 3,
		),
		array(
			'desc'        => er_date_get_label( 0, 0, 3 ),
			'id'          => 'departure_on_thu',
			'name'        => 'departure_on[]',
			'input-group' => 'middle',
			'type'        => 'checkbox',
			'value'       => $departure_possible_on === 0 || in_array( 4, $departure_possible_on ) ? 'yes' : 'no',
			'default'     => 4,
		),
		array(
			'desc'        => er_date_get_label( 0, 0, 4 ),
			'id'          => 'departure_on_fri',
			'name'        => 'departure_on[]',
			'input-group' => 'middle',
			'type'        => 'checkbox',
			'value'       => $departure_possible_on === 0 || in_array( 5, $departure_possible_on ) ? 'yes' : 'no',
			'default'     => 5,
		),
		array(
			'desc'        => er_date_get_label( 0, 0, 5 ),
			'id'          => 'departure_on_sat',
			'name'        => 'departure_on[]',
			'input-group' => 'middle',
			'type'        => 'checkbox',
			'value'       => $departure_possible_on === 0 || in_array( 6, $departure_possible_on ) ? 'yes' : 'no',
			'default'     => 6,
		),
		array(
			'desc'        => er_date_get_label( 0, 0, 6 ),
			'id'          => 'departure_on_sun',
			'name'        => 'departure_on[]',
			'input-group' => 'end',
			'type'        => 'checkbox',
			'value'       => $departure_possible_on === 0 || in_array( 7, $departure_possible_on ) ? 'yes' : 'no',
			'default'     => 7,
		),
		array(
			'title'       => __( 'Departure possible between', 'easyReservations' ),
			'desc'        => __( 'and', 'easyReservations' ),
			'id'          => 'departure_between_from',
			'input-group' => 'start',
			'type'        => 'select',
			'class'       => 'er-enhanced-select',
			'options'     => $time_options,
			'value'       => $endon_h[0]
		),
		array(
			'id'          => 'departure_between_to',
			'type'        => 'select',
			'class'       => 'er-enhanced-select',
			'input-group' => 'end',
			'options'     => $time_options,
			'value'       => $endon_h[1]
		),

		array(
			'type' => 'sectionend',
		),

		array(
			'type'  => 'title',
			'title' => __( 'Appearance', 'easyReservations' ),
		),
		array(
			'title'       => __( 'Form template', 'easyReservations' ),
			'desc_tip'    => 'Select form template to use on resource page in frontend.',
			'id'          => 'resource_form_template',
			'type'        => 'select',
			'class'       => 'er-enhanced-select',
			'input-group' => 'start',
			'options'     => er_form_template_options(),
			'value'       => get_post_meta( $resource->get_id(), 'form_template', true )
		),

		array(
			'type' => 'sectionend',
		),
	) );
	?>
    <input type="hidden" id="hidden_billing_field">
    <button name="save" class="button-primary easyreservations-save-button" type="submit" value="<?php esc_attr_e( 'Save changes', 'easyreservations' ); ?>"><?php esc_html_e( 'Save changes', 'easyreservations' ); ?></button>
	<?php wp_nonce_field( 'easyreservations-resource-settings' ); ?>
</form>
<script language="javascript" type="text/javascript">
	jQuery( '#billing_interval' ).on( 'change', checkBillingUnit );

	function checkBillingUnit() {
		var interval             = jQuery( '#billing_interval' ),
			billing_method       = jQuery( '#billing_method' ),
			hidden_billing_field = jQuery( '#hidden_billing_field' );

		if ( interval.val() === "86401" ) {
			billing_method.attr( 'disabled', 'disabled' );
			hidden_billing_field.attr( 'name', 'billing_method' ).val( 3 );
		} else {
			billing_method.attr( 'disabled', false );
			hidden_billing_field.attr( 'name', '' ).val( '' );
		}
	}

	jQuery( document ).ready( function( $ ) {
		checkBillingUnit();
	} );
</script>