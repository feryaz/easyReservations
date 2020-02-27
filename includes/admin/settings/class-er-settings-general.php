<?php
defined( 'ABSPATH' ) || exit;

/**
 * ER_Settings_General.
 */
class ER_Settings_General extends ER_Settings_Page {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id    = 'general';
        $this->label = __( 'General', 'easyReservations' );

        add_action( 'easyreservations_admin_field_reservation_name_tags', array( $this, 'reservation_name_tags' ) );
        add_action( 'easyreservations_admin_field_reservation_item_tags', array( $this, 'reservation_item_tags' ) );

        parent::__construct();
    }

    /**
     * Get settings array.
     *
     * @return array
     */
    public function get_settings() {
        $currency_locals  = er_get_currencies();
        $currency_symbols = er_get_currency_symbol( false );

        foreach ( $currency_locals as $code => $name ) {
            $currency_locals[$code] = $name . ' (' . $currency_symbols[$code] . ')';
        }

        $number_options    = er_form_number_options( 1, 250 );
        $number_options[0] = __( 'Disabled', 'easyReservations' );
        sort( $number_options );

        $hours      = er_date_get_interval_label( HOUR_IN_SECONDS, 2 );
        $days       = er_date_get_interval_label( DAY_IN_SECONDS, 2 );
        $months     = er_date_get_interval_label( MONTH_IN_SECONDS, 2 );
        $minutes    = __( 'minutes', 'easyReservations' );
        $time_array = array(
            0      => '0 ' . $minutes,
            5      => '5 ' . $minutes,
            10     => '10 ' . $minutes,
            15     => '15 ' . $minutes,
            30     => '30 ' . $minutes,
            45     => '45 ' . $minutes,
            60     => '1 ' . er_date_get_interval_label( HOUR_IN_SECONDS, 1 ),
            90     => '1.5 ' . $hours,
            120    => '2 ' . $hours,
            150    => '2.5 ' . $hours,
            180    => '3 ' . $hours,
            240    => '4 ' . $hours,
            300    => '5 ' . $hours,
            360    => '6 ' . $hours,
            600    => '10 ' . $hours,
            720    => '12 ' . $hours,
            1080   => '18 ' . $hours,
            1440   => '1 ' . er_date_get_interval_label( DAY_IN_SECONDS, 1 ),
            2160   => '1.5 ' . $days,
            2880   => '2 ' . $days,
            4320   => '3 ' . $days,
            5760   => '4 ' . $days,
            7200   => '5 ' . $days,
            8640   => '6 ' . $days,
            10080  => '7 ' . $days,
            20160  => '14 ' . $days,
            40320  => '1 ' . er_date_get_interval_label( MONTH_IN_SECONDS, 1 ),
            80640  => '2 ' . $months,
            120960 => '3 ' . $months
        );

        $general = apply_filters(
            'easyreservations_general_settings_general',
            array(
                array(
                    'title' => __( 'General options', 'easyReservations' ),
                    'type'  => 'title',
                    'desc'  => 'This is an alpha version. Please report any problems you may encounter. <a href="http://easyreservations.org/easyreservations-6-0-alpha/" target="_blank">Read more and download premium</a>.',
                    'id'    => 'general_options',
                ),

                array(
                    'title'    => __( 'Country / State', 'easyReservations' ),
                    'desc'     => __( 'The country and state or province, if any, in which your business is located.', 'easyReservations' ),
                    'id'       => 'reservations_default_location',
                    'option'   => 'reservations_default_location',
                    'default'  => 'USA',
                    'type'     => 'single_select_country',
                    'desc_tip' => true,
                ),

                array(
                    'title'    => __( 'Enable taxes', 'easyReservations' ),
                    'desc'     => __( 'Enable tax rates and calculations', 'easyReservations' ),
                    'id'       => 'reservations_enable_taxes',
                    'option'   => 'reservations_enable_taxes',
                    'default'  => 'yes',
                    'type'     => 'checkbox',
                    'desc_tip' => __( 'Rates will be configurable and taxes will be calculated during checkout.', 'easyReservations' ),
                ),

                array(
                    'title'   => __( 'Display time', 'easyReservations' ),
                    'desc'    => __( 'Affects how arrival and departure are displayed throughout the plugin.', 'easyReservations' ),
                    'id'      => 'reservations_use_time',
                    'option'  => 'reservations_use_time',
                    'default' => 'yes',
                    'type'    => 'checkbox',
                ),

                array(
                    'title'   => __( 'Uninstall', 'easyReservations' ),
                    'desc'    => __( 'If enabled all data gets deleted when uninstalling easyReservations.', 'easyReservations' ),
                    'id'      => 'reservations_uninstall',
                    'option'  => 'reservations_uninstall',
                    'default' => 'yes',
                    'type'    => 'checkbox',
                ),
            )
        );

        $general[] = array(
            'type' => 'sectionend',
            'id'   => 'general_options',
        );

        $format = apply_filters(
            'easyreservations_general_settings_format',
            array(
                array(
                    'title' => __( 'Format options', 'easyReservations' ),
                    'type'  => 'title',
                    'desc'  => __( 'The following options affect how data is displayed on the frontend.', 'easyReservations' ),
                    'id'    => 'format_options',
                ),

                array(
                    'title'    => __( 'Currency', 'easyReservations' ),
                    'desc'     => __( 'This controls what currency prices are listed at in the catalog and which currency gateways will take payments in.', 'easyReservations' ),
                    'id'       => 'reservations_currency',
                    'option'   => 'reservations_currency',
                    'default'  => 'USD',
                    'type'     => 'select',
                    'class'    => 'er-enhanced-select',
                    'desc_tip' => true,
                    'options'  => $currency_locals,
                ),

                array(
                    'title'    => __( 'Currency position', 'easyReservations' ),
                    'desc'     => __( 'This controls the position of the currency symbol.', 'easyReservations' ),
                    'id'       => 'reservations_currency_pos',
                    'option'   => 'reservations_currency_pos',
                    'class'    => 'er-enhanced-select',
                    'default'  => 'left',
                    'type'     => 'select',
                    'options'  => array(
                        'left'        => __( 'Left', 'easyReservations' ),
                        'right'       => __( 'Right', 'easyReservations' ),
                        'left_space'  => __( 'Left with space', 'easyReservations' ),
                        'right_space' => __( 'Right with space', 'easyReservations' ),
                    ),
                    'desc_tip' => true,
                ),

                array(
                    'title'    => __( 'Thousand separator', 'easyReservations' ),
                    'desc'     => __( 'This sets the thousand separator of displayed prices.', 'easyReservations' ),
                    'id'       => 'reservations_price_thousand_sep',
                    'option'   => 'reservations_price_thousand_sep',
                    'css'      => 'width:50px;',
                    'default'  => ',',
                    'type'     => 'text',
                    'desc_tip' => true,
                ),

                array(
                    'title'    => __( 'Decimal separator', 'easyReservations' ),
                    'desc'     => __( 'This sets the decimal separator of displayed prices.', 'easyReservations' ),
                    'id'       => 'reservations_price_decimal_sep',
                    'option'   => 'reservations_price_decimal_sep',
                    'css'      => 'width:50px;',
                    'default'  => '.',
                    'type'     => 'text',
                    'desc_tip' => true,
                ),

                array(
                    'title'             => __( 'Number of decimals', 'easyReservations' ),
                    'desc'              => __( 'This sets the number of decimal points shown in displayed prices.', 'easyReservations' ),
                    'id'                => 'reservations_price_decimals',
                    'option'            => 'reservations_price_decimals',
                    'css'               => 'width:50px;',
                    'default'           => '2',
                    'desc_tip'          => true,
                    'type'              => 'number',
                    'attributes' => array(
                        'min'  => 0,
                        'step' => 1,
                    ),
                ),

                array(
                    'title'    => __( 'Date format', 'easyReservations' ),
                    'desc'     => __( 'How dates get displayed throughout the plugin.', 'easyReservations' ),
                    'id'       => 'reservations_date_format',
                    'option'   => 'reservations_date_format',
                    'class'    => 'er-enhanced-select',
                    'default'  => 'd.m.Y',
                    'desc_tip' => true,
                    'type'     => 'select',
                    'options'  => array(
                        'Y/m/d' => date( 'Y/m/d' ),
                        'Y-m-d' => date( 'Y-m-d' ),
                        'm/d/Y' => date( 'm/d/Y' ),
                        'd-m-Y' => date( 'd-m-Y' ),
                        'd.m.Y' => date( 'd.m.Y' )
                    ),
                ),

                array(
                    'title'    => __( 'Time format', 'easyReservations' ),
                    'desc'     => __( 'How time gets displayed throughout the plugin.', 'easyReservations' ),
                    'id'       => 'reservations_time_format',
                    'option'   => 'reservations_time_format',
                    'class'    => 'er-enhanced-select',
                    'default'  => 'H:i',
                    'desc_tip' => true,
                    'type'     => 'select',
                    'options'  => array(
                        'H:i'   => date( 'H:i' ),
                        'h:i a' => date( 'h:i a' ),
                        'h:i A' => date( 'h:i A' )
                    ),
                ),

            )
        );

        $format[] = array(
            'type' => 'sectionend',
            'id'   => 'format_options',
        );

        $appearance = apply_filters(
            'easyreservations_general_settings_appearance',
            array(
                array(
                    'title' => __( 'Appearance options', 'easyReservations' ),
                    'type'  => 'title',
                    'desc'  => '',
                    'id'    => 'appearance_options',
                ),
                /**
                array(
                    'title'    => __( 'Primary color', 'easyReservations' ),
                    'desc'     => __( 'This sets the decimal separator of displayed prices.', 'easyReservations' ),
                    'id'       => 'reservations_primary_color',
                    'option'   => 'reservations_primary_color',
                    'default'  => '#fff',
                    'type'     => 'color',
                    'desc_tip' => true,
                ),*/

                array(
                    'title'    => __( 'Reservation name', 'easyReservations' ),
                    'desc'     => __( 'How the reservations is called in error messages and the admin area.', 'easyReservations' ),
                    'id'       => 'reservations_reservation_name',
                    'option'   => 'reservations_reservation_name',
                    'desc_tip' => true,
                    'type'     => 'text',
                    'default'  => '[resource] @[arrival] for [billing_units]d'
                ),

                array( 'type' => 'reservation_name_tags' ),

                array(
                    'title'    => __( 'Reservation item', 'easyReservations' ),
                    'desc'     => __( 'How the reservations is displayed in cart, invoices and emails.', 'easyReservations' ),
                    'id'       => 'reservations_reservation_item_label',
                    'option'   => 'reservations_reservation_item_label',
                    'default'  => '[resource-link]

<b>Arrival</b>
[arrival]

<b>Durartion</b>
[billing_units]d

<b>Test</b>
[custom id="1"]',
                    'desc_tip' => true,
                    'type'     => 'textarea',
                ),

                array( 'type' => 'reservation_item_tags' ),
            )
        );

        $appearance[] = array(
            'type' => 'sectionend',
            'id'   => 'appearance_options',
        );

        $availability = apply_filters(
            'easyreservations_general_settings_availability',
            array(
                array(
                    'title' => __( 'Availability', 'easyReservations' ),
                    'type'  => 'title',
                    'desc' => __( 'The following options affect the availability of your resources.', 'easyReservations' ),
                    'id'    => 'availability_options',
                ),

                array(
                    'title'             => __( 'Wait for ordering (minutes)', 'easyReservations' ),
                    'desc'              => __( 'Keep reservations in shopping cart for x minutes. After that the temporary reservations are deleted from the database and the customer has to reserve again.', 'easyReservations' ),
                    'id'                => 'reservations_wait_for_ordering_minutes',
                    'option'            => 'reservations_wait_for_ordering_minutes',
                    'type'              => 'number',
                    'attributes' => array(
                        'min'  => 1,
                        'step' => 1,
                    ),
                    'css'               => 'width: 80px;',
                    'default'           => '60',
                    'autoload'          => false,
                ),

                array(
                    'title'             => __( 'Wait for payment (minutes)', 'easyReservations' ),
                    'desc'              => __( 'Wait for payment for x minutes. When this limit is reached, the pending order will be cancelled. Leave blank to disable.', 'easyReservations' ),
                    'id'                => 'reservations_wait_for_payment_minutes',
                    'option'            => 'reservations_wait_for_payment_minutes',
                    'type'              => 'number',
                    'attributes' => array(
                        'min'  => 0,
                        'step' => 1,
                    ),
                    'css'               => 'width: 80px;',
                    'default'           => '60',
                    'autoload'          => false,
                ),

                array(
                    'title'    => __( 'Earliest possible arrival', 'easyReservations' ),
                    'desc_tip'     => __( 'How long from present until arrivals are possible.', 'easyReservations' ),
                    'id'       => 'reservations_earliest_arrival',
                    'option'   => 'reservations_earliest_arrival',
                    'default'  => 0,
                    'class' => 'er-enhanced-select',
                    'type'     => 'select',
                    'css' => 'width:120px',
                    'options'  => $time_array
                ),

                array(
                    'title'    => __( 'Merge resources', 'easyReservations' ),
                    'desc'     => __( 'If enabled this overrides resources quantity and only allows X reservations in all resources together.', 'easyReservations' ),
                    'id'       => 'reservations_merge_resources',
                    'option'   => 'reservations_merge_resources',
                    'default'  => 0,
                    'class' => 'er-enhanced-select',
                    'css' => 'width:120px',
                    'type'     => 'select',
                    'desc_tip' => true,
                    'options'  => $number_options
                ),

                array(
                    'title'       => __( 'Block time before arrival', 'easyReservations' ),
                    'desc_tip'    => __( 'Block time before reservations arrival for preparation or clean-up.', 'easyReservations' ),
                    'id'          => 'reservations_block_before',
                    'option'      => 'reservations_block_before',
                    'class'       => 'er-enhanced-select',
                    'default'     => '0',
                    'css'     => 'width:120px',
                    'type'        => 'select',
                    'options'     => $time_array,
                ),

                array(
                    'title' => __( 'Block time after departure', 'easyReservations' ),
                    'desc_tip' => __( 'Block time after reservations departure for preparation or clean-up.', 'easyReservations' ),
                    'id'          => 'reservations_block_after',
                    'option'      => 'reservations_block_after',
                    'class'       => 'er-enhanced-select',
                    'default'     => '0',
                    'css'         => 'width:120px',
                    'type'        => 'select',
                    'options'     => $time_array,
                ),
            )
        );

        $availability[] = array(
            'type' => 'sectionend',
            'id'   => 'availability_options',
        );

        return apply_filters( 'easyreservations_get_settings_' . $this->id, array_merge( $general, $format, $availability, $appearance ) );
    }

    public function reservation_name_tags(){
        ?>
        <tr>
            <th></th>
            <td>
                <code data-tag="ID" data-target="reservations_reservation_name"><?php esc_html_e( 'Reservation number', 'easyReservations' ); ?></code>
                <code data-tag="resource" data-target="reservations_reservation_name"><?php esc_html_e( 'Resource Title', 'easyReservations' ); ?></code>
                <code data-tag="arrival" data-target="reservations_reservation_name"><?php esc_html_e( 'Arrival', 'easyReservations' ); ?></code>
                <code data-tag="departure" data-target="reservations_reservation_name"><?php esc_html_e( 'Departure', 'easyReservations' ); ?></code>
                <code data-tag="billing_units" data-target="reservations_reservation_name"><?php esc_html_e( 'Billing units', 'easyReservations' ); ?></code>
                <code data-tag="persons" data-target="reservations_reservation_name"><?php esc_html_e( 'Persons', 'easyReservations' ); ?></code>
                <code data-tag="adults" data-target="reservations_reservation_name"><?php esc_html_e( 'Adults', 'easyReservations' ); ?></code>
                <code data-tag="children" data-target="reservations_reservation_name"><?php esc_html_e( 'Children', 'easyReservations' ); ?></code>
                <code data-tag="reserved" data-target="reservations_reservation_name"><?php esc_html_e( 'Reserved', 'easyReservations' ); ?></code>
                <code data-tag='custom id="X"' data-target="reservations_reservation_name"><?php esc_html_e( 'Custom data', 'easyReservations' ); ?></code>
            </td>
        </tr>
        <?php
    }

    public function reservation_item_tags(){
        ?>
        <tr>
            <th></th>
            <td>
                <code data-tag="ID" data-target="reservations_reservation_item_label"><?php esc_html_e( 'Reservation number', 'easyReservations' ); ?></code>
                <code data-tag="resource" data-target="reservations_reservation_item_label"><?php esc_html_e( 'Resource title', 'easyReservations' ); ?></code>
                <code data-tag="resource-link" data-target="reservations_reservation_item_label"><?php esc_html_e( 'Resource link', 'easyReservations' ); ?></code>
                <code data-tag="arrival" data-target="reservations_reservation_item_label"><?php esc_html_e( 'Arrival', 'easyReservations' ); ?></code>
                <code data-tag="departure" data-target="reservations_reservation_item_label"><?php esc_html_e( 'Departure', 'easyReservations' ); ?></code>
                <code data-tag="billing_units" data-target="reservations_reservation_item_label"><?php esc_html_e( 'Billing units', 'easyReservations' ); ?></code>
                <code data-tag="persons" data-target="reservations_reservation_item_label"><?php esc_html_e( 'Persons', 'easyReservations' ); ?></code>
                <code data-tag="adults" data-target="reservations_reservation_item_label"><?php esc_html_e( 'Adults', 'easyReservations' ); ?></code>
                <code data-tag="children" data-target="reservations_reservation_item_label"><?php esc_html_e( 'Children', 'easyReservations' ); ?></code>
                <code data-tag="reserved" data-target="reservations_reservation_item_label"><?php esc_html_e( 'Reserved', 'easyReservations' ); ?></code>
                <code data-tag='custom id="X"' data-target="reservations_reservation_item_label"><?php esc_html_e( 'Custom data', 'easyReservations' ); ?></code>
            </td>
        </tr>
        <?php
    }

    /**
     * Output a color picker input box.
     *
     * @param mixed  $name Name of input.
     * @param string $id ID of input.
     * @param mixed  $value Value of input.
     * @param string $desc (default: '') Description for input.
     */
    public function color_picker( $name, $id, $value, $desc = '' ) {
        echo '<div class="color_box">' . er_get_help( $desc ) . '
			<input name="' . esc_attr( $id ) . '" id="' . esc_attr( $id ) . '" type="text" value="' . esc_attr( $value ) . '" class="colorpick" /> <div id="colorPickerDiv_' . esc_attr( $id ) . '" class="colorpickdiv"></div>
		</div>';
    }
}

return new ER_Settings_General();
