<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$amount_text = esc_html__( 'Amount of %s', 'easyReservations' );
$days = er_date_get_label( 0 );
$months = er_date_get_label( 1 );
$hour_string = esc_html__( 'From %1$s till %2$s', 'easyReservations' );

$number_options = er_form_number_options( 1, 99 );
$number_options_inf = $number_options;
$number_options_inf[0] = '&infin;';
sort( $number_options_inf );

$time_options = er_form_time_options();

?>
<h2>
	<?php esc_html_e( 'Add filter', 'easyReservations' ); ?>
	<a onclick="reset_filter_form()" class="dashicons dashicons-no"> </a>
	<a onclick="jQuery('.paste-input').toggleClass('hidden');" class="dashicons dashicons-upload tips" data-tip="<?php echo sprintf( esc_attr__( 'Paste %s', 'easyReservations' ), esc_attr__( 'filter', 'easyReservations' ) ); ?>"> </a>
	<input type="text" placeholder="Paste here" class="paste-input hidden">
</h2>
<nav class="nav-tab-wrapper easy-navigation filter-navigation">
	<a href="#" class="nav-tab nav-tab-active" onclick="display_price_filter()"><?php esc_html_e( 'Price', 'easyReservations' ); ?></a>
	<a href="#" class="nav-tab" onclick="display_availability_filter()"><?php esc_html_e( 'Availability', 'easyReservations' ); ?></a>
	<a href="#" class="nav-tab" onclick="display_requirement_filter()"><?php esc_html_e( 'Requirement', 'easyReservations' ); ?></a>
</nav>
<form method="post" id="filter_form" name="filter_form" class="easy-ui">
	<input type="hidden" name="filter_type" id="filter_type">
	<?php wp_nonce_field( 'easy-resource-filter', 'easy-resource-filter' ); ?>
	<table class="form-table" id="filter-table">
		<tbody>
		<tr id="filter_form_name" class="hide-it">
			<th>
				<label for="filter_form_name_field">
					<?php esc_html_e( 'Label', 'easyReservations' ); ?><?php er_print_help( 'Can get displayed in the invoice or the receipt. ' ); ?>
				</label>
			</th>
			<td>
				<input type="text" name="filter_form_name_field" id="filter_form_name_field">
			</td>
		</tr>
		<tr id="filter_form_importance" class="hide-it">
			<th>
				<label for="price_filter_imp">
					<?php esc_html_e( 'Priority', 'easyReservations' ); ?>
					<?php er_print_help( 'The priority defines the order in which the filter get checked. They get sorted from low to high and only the first matched filter gets applied. Filter that change the base price get applied once per billing unit, whereas discount and extra charge filters only get applied once per filter condition type.' ); ?>
				</label>
			</th>
			<td>
				<?php
				er_form_get_field( array(
					'id'      => 'price_filter_imp',
					'class'   => 'er-enhanced-select',
					'type'    => 'select',
					'options' => $number_options,
					'value'   => 1
				) );
				?>
			</td>
		</tr>
		</tbody>
	</table>
	<div id="filter_form_usetime" class="hide-it">
		<hr>
		<p>
			<?php esc_html_e( 'This condition(s) must be met for the filter to get applied. Only the first matched filter for each type of condition gets applied - sorted by priority from low to high.', 'easyReservations' ); ?>
		</p>
		<table class="form-table">
			<tbody>
			<tr>
				<td>
					<label style="margin-left:5px">
						<input type="checkbox" name="filter_form_usetime_checkbox" id="filter_form_usetime_checkbox" onclick="show_use_time();">
						<?php echo sprintf( esc_html__( 'Filter by %s', 'easyReservations' ), esc_html_x( 'time', 'time filter label', 'easyReservations' ) ); ?>
					</label>
				</td>
			</tr>
			</tbody>
		</table>
	</div>
	<div id="filter_form_time_cond" class="hide-it">
		<hr>
		<h2><?php esc_html_e( 'Time condition', 'easyReservations' ); ?></h2>
		<table class="form-table">
			<tbody>
			<tr>
				<th>
					<label class="wrapper">
						<input type="checkbox" name="price_filter_cond_range" id="price_filter_cond_range" value="range">
						<?php esc_html_e( 'Date range', 'easyReservations' ); ?>
					</label>
				</th>
			</tr>
			<tr>
				<th><label for="price_filter_range_from"><?php esc_html_e( 'From', 'easyReservations' ); ?></label></th>
				<td class="forminp" onclick="jQuery('#price_filter_cond_range').attr('checked', true);">
                    <span class="input-wrapper">
                        <input type="text" id="price_filter_range_from" name="price_filter_range_from" data-target="price_filter_range_to" class="er-datepicker" style="width:94px">
                        <span class="input-box clickable"><span class="dashicons dashicons-calendar-alt"></span></span>
                    </span>
					<?php
					er_form_get_field( array(
						'id'      => 'filter_range_from_hour',
						'type'    => 'select',
						'options' => $time_options,
						'value'   => 12
					) );
					er_form_get_field( array(
						'id'      => 'filter_range_from_minute',
						'type'    => 'select',
						'options' => er_form_number_options( "00", 59 ),
						'value'   => "00"
					) );
					?>
				</td>
			</tr>
			<tr>
				<th><label for="price_filter_range_to"><?php esc_html_e( 'To', 'easyReservations' ); ?></label></th>
				<td onclick="jQuery('#price_filter_cond_range').attr('checked', true);">
                    <span class="input-wrapper">
                        <input type="text" id="price_filter_range_to" name="price_filter_range_to" class="er-datepicker to" style="width:94px;">
                        <span class="input-box clickable"><span class="dashicons dashicons-calendar-alt"></span></span>
                    </span>
					<?php
					er_form_get_field( array(
						'id'      => 'filter_range_to_hour',
						'type'    => 'select',
						'options' => $time_options,
						'value'   => 12
					) );
					er_form_get_field( array(
						'id'      => 'filter_range_to_minute',
						'type'    => 'select',
						'options' => er_form_number_options( "00", 59 ),
						'value'   => "00"
					) );
					?>
					<label style="display:block;margin-top: 10px">
						<input type="checkbox" name="price_filter_range_every" id="price_filter_range_every" value="1">
						<?php esc_html_e( 'Apply every year', 'easyReservations' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th colspan="2">
					<label class="wrapper">
						<input type="checkbox" name="price_filter_cond_unit" id="price_filter_cond_unit" value="range">
						<?php esc_html_e( 'Time unit', 'easyReservations' ); ?>
					</label>
				</th>
			</tr>
			<tr>
				<th>
					<label>
						<?php esc_html_e( 'Hours', 'easyReservations' ); ?>
					</label>
				</th>
				<td onclick="jQuery('#price_filter_cond_unit').attr('checked', true);" style="line-height: 20px">
                    <span>
                        <i>
                            <?php echo sprintf( esc_html__( 'Select nothing to apply to entire %s', 'easyReservations' ), er_date_get_interval_label( DAY_IN_SECONDS, 1 ) ); ?>
                        </i>
                    </span>
					<span style="min-width:99%;display:block;float:left">
                        <div style="margin:3px;width:60px;float:left;">
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_hour[]" value="0">00:00</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_hour[]" value="1">01:00</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_hour[]" value="2">02:00</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_hour[]" value="3">03:00</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_hour[]" value="4">04:00</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_hour[]" value="5">05:00</label>
                        </div>
                        <div style="margin:3px;width:60px;float:left;">
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_hour[]" value="6">06:00</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_hour[]" value="7">07:00</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_hour[]" value="8">08:00</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_hour[]" value="9">09:00</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_hour[]" value="10">10:00</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_hour[]" value="11">11:00</label>
                        </div>
                        <div style="margin:3px;width:60px;float:left;">
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_hour[]" value="12">12:00</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_hour[]" value="13">13:00</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_hour[]" value="14">14:00</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_hour[]" value="15">15:00</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_hour[]" value="16">16:00</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_hour[]" value="17">17:00</label>
                        </div>
                        <div style="margin:3px;width: 60px;px;float:left;">
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_hour[]" value="18">18:00</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_hour[]" value="19">19:00</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_hour[]" value="20">20:00</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_hour[]" value="21">21:00</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_hour[]" value="22">22:00</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_hour[]" value="23">23:00</label>
                        </div>
                    </span>
				</td>
			</tr>
			<tr>
				<th>
					<label>
						<?php echo er_date_get_interval_label( DAY_IN_SECONDS, 0, true ); ?>
					</label>
				</th>
				<td onclick="jQuery('#price_filter_cond_unit').attr('checked', true);" style="line-height: 20px">
                    <span>
                        <i>
                            <?php echo sprintf( esc_html__( 'Select nothing to apply to entire %s', 'easyReservations' ), esc_html__( 'calendar week', 'easyReservations' ) ); ?>
                        </i>
                    </span>
					<span style="min-width:99%;display:block;float:left">
                        <div style="margin:3px;width:110px;float:left;">
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_days[]" value="1"><?php esc_html_e( $days[0] ); ?></label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_days[]" value="2"><?php esc_html_e( $days[1] ); ?></label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_days[]" value="3"><?php esc_html_e( $days[2] ); ?></label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_days[]" value="4"><?php esc_html_e( $days[3] ); ?></label>
                        </div>
                        <div style="margin:3px;width:110px;float:left;">
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_days[]" value="5"><?php esc_html_e( $days[4] ); ?></label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_days[]" value="6"><?php esc_html_e( $days[5] ); ?></label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_days[]" value="7"><?php esc_html_e( $days[6] ); ?></label>
                        </div>
                    </span>
				</td>
			</tr>
			<tr>
				<th>
					<label>
						<?php esc_html_e( 'Calendar week', 'easyReservations' ); ?>
					</label>
				</th>
				<td onclick="jQuery('#price_filter_cond_unit').attr('checked', true);" style="line-height: 20px">
                    <span style="">
                        <i>
                           <?php echo sprintf( esc_html__( 'Select nothing to apply to entire %s', 'easyReservations' ), er_date_get_interval_label( MONTH_IN_SECONDS, 1 ) ); ?>
                        </i>
                    </span>
					<span style="min-width:99%;display:block;float:left">
                        <div style="margin:3px;width:50px;float:left;">
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="1">01</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="2">02</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="3">03</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="4">04</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="5">05</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="6">06</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="7">07</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="8">08</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="9">09</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="10">10</label>
                        </div>
                        <div style="margin:3px;width:50px;float:left;">
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="11">11</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="12">12</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="13">13</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="14">14</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="15">15</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="16">16</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="17">17</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="18">18</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="19">19</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="20">20</label>
                        </div>
                        <div style="margin:3px;width:50px;float:left;">
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="21">21</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="22">22</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="23">23</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="24">24</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="25">25</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="26">26</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="27">27</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="28">28</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="29">29</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="30">30</label>
                        </div>
                        <div style="margin:3px;width:50px;float:left;">
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="31">31</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="32">32</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="33">33</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="34">34</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="35">35</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="36">36</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="37">37</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="38">38</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="39">39</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="40">40</label>
                        </div>
                        <div style="margin:3px;width:50px;float:left">
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="41">41</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="42">42</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="43">43</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="44">44</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="45">45</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="46">46</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="47">47</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="48">48</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="49">49</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="50">50</label>
                        </div>
                        <div style="margin:3px;width:50px;float:left">
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="51">51</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="52">52</label>
                            <label class="wrapper"><input type="checkbox" name="price_filter_unit_cw[]" value="53">53</label>
                        </div>
                    </span>
				</td>
			</tr>
			<tr>
				<th>
					<label>
						<?php esc_html_e( 'Months', 'easyReservations' ); ?>
					</label>
				</th>
				<td onclick="jQuery('#price_filter_cond_unit').attr('checked', true);" style="line-height: 20px">
                    <span>
                        <i>
                            <?php echo sprintf( esc_html__( 'Select nothing to apply to entire %s', 'easyReservations' ), esc_html__( 'quarter', 'easyReservations' ) ); ?>
                        </i>
                    </span>
					<div>
						<label style="width:110px;float:left"><input type="checkbox" name="price_filter_unit_month[]" value="1"><?php echo $months[0]; ?>
						</label>
						<label style="width:110px;float:left">
							<input type="checkbox" name="price_filter_unit_month[]" value="2"><?php echo $months[1]; ?>
						</label>
						<label style="width:110px;"><input type="checkbox" name="price_filter_unit_month[]" value="3"><?php echo $months[2]; ?>
						</label>
					</div>
					<div>
						<label style="width:110px;float:left">
							<input type="checkbox" name="price_filter_unit_month[]" value="4"><?php echo $months[3]; ?>
						</label>
						<label style="width:110px;float:left">
							<input type="checkbox" name="price_filter_unit_month[]" value="5"><?php echo $months[4]; ?>
						</label>
						<label style="width:110px;">
							<input type="checkbox" name="price_filter_unit_month[]" value="6"><?php echo $months[5]; ?>
						</label>
					</div>
					<div>
						<label style="width:110px;float:left">
							<input type="checkbox" name="price_filter_unit_month[]" value="7"><?php echo $months[6]; ?>
						</label>
						<label style="width:110px;float:left">
							<input type="checkbox" name="price_filter_unit_month[]" value="8"><?php echo $months[7]; ?>
						</label>
						<label style="width:110px;">
							<input type="checkbox" name="price_filter_unit_month[]" value="9"><?php echo $months[8]; ?>
						</label>
					</div>
					<div>
						<label style="width:110px;float:left">
							<input type="checkbox" name="price_filter_unit_month[]" value="10"><?php echo $months[9]; ?>
						</label>
						<label style="width:110px;float:left">
							<input type="checkbox" name="price_filter_unit_month[]" value="11"><?php echo $months[10]; ?>
						</label>
						<label style="width:110px;">
							<input type="checkbox" name="price_filter_unit_month[]" value="12"><?php echo $months[11]; ?>
						</label>
					</div>
				</td>
			</tr>
			<tr>
				<th>
					<label>
						<?php esc_html_e( 'Quarter', 'easyReservations' ); ?>
					</label>
				</th>
				<td onclick="jQuery('#price_filter_cond_unit').attr('checked', true);" style="line-height: 20px">
                    <span>
                        <i>
                            <?php echo sprintf( esc_html__( 'Select nothing to apply to entire %s', 'easyReservations' ), esc_html__( 'year', 'easyReservations' ) ); ?>
                        </i>
                    </span>
					<div>
						<label style="width:50px"><input type="checkbox" name="price_filter_unit_quarter[]" value="4">4</label>
						<label style="width:50px;float:left"><input type="checkbox" name="price_filter_unit_quarter[]" value="1">1</label>
						<label style="width:50px;float:left"><input type="checkbox" name="price_filter_unit_quarter[]" value="2">2</label>
						<label style="width:50px;float:left"><input type="checkbox" name="price_filter_unit_quarter[]" value="3">3</label>
					</div>
				</td>
			</tr>
			<tr>
				<th>
					<label>
						<?php esc_html_e( 'Year', 'easyReservations' ); ?>
					</label>
				</th>
				<td onclick="jQuery('#price_filter_cond_unit').attr('checked', true);" style="line-height: 20px">
					<div>
						<label style="width:75px"><input type="checkbox" name="price_filter_unit_year[]" value="2025">2025</label>
						<label style="width:75px;float:left"><input type="checkbox" name="price_filter_unit_year[]" value="2021">2021</label>
						<label style="width:75px;float:left"><input type="checkbox" name="price_filter_unit_year[]" value="2022">2022</label>
						<label style="width:75px;float:left"><input type="checkbox" name="price_filter_unit_year[]" value="2023">2023</label>
						<label style="width:75px;float:left"><input type="checkbox" name="price_filter_unit_year[]" value="2024">2024</label>
					</div>
					<div>
						<label style="width:75px"><input type="checkbox" name="price_filter_unit_year[]" value="2030">2030</label>
						<label style="width:75px;float:left"><input type="checkbox" name="price_filter_unit_year[]" value="2026">2026</label>
						<label style="width:75px;float:left"><input type="checkbox" name="price_filter_unit_year[]" value="2027">2027</label>
						<label style="width:75px;float:left"><input type="checkbox" name="price_filter_unit_year[]" value="2028">2028</label>
						<label style="width:75px;float:left"><input type="checkbox" name="price_filter_unit_year[]" value="2029">2029</label>
					</div>
				</td>
			</tr>
			</tbody>
		</table>
	</div>
	<hr>
	<?php if ( isset( $resource ) && $resource ): ?>
	<?php $requirements = $resource->get_requirements(); ?>
	<div id="filter_form_requirements" class="hide-it">
		<h2><?php esc_html_e( 'Requirements', 'easyReservations' ); ?></h2>
		<table class="form-table">
			<tbody>
			<tr>
				<th>
					<label for="req_filter_min_nights"><?php echo er_date_get_interval_label( $resource->get_billing_interval(), 2, true ); ?></label>
				</th>
				<td>
                    <span style="display: inline-block">
                        <?php
                        er_form_get_field( array(
	                        'id'       => 'req_filter_min_nights',
	                        'type'     => 'select',
	                        'together' => true,
	                        'icon'     => __( 'Min', 'easyReservations' ),
	                        'options'  => $number_options,
	                        'value'    => $requirements['nights-min']
                        ) );
                        ?> -
                        <?php
                        er_form_get_field( array(
	                        'id'       => 'req_filter_max_nights',
	                        'type'     => 'select',
	                        'together' => true,
	                        'icon'     => __( 'Max', 'easyReservations' ),
	                        'options'  => $number_options_inf,
	                        'value'    => $requirements['nights-max']
                        ) );
                        ?>
                    </span>
				</td>
			</tr>
			<tr>
				<th>
					<label for="req_filter_min_pers"><?php esc_html_e( 'Persons', 'easyReservations' ); ?></label>
				</th>
				<td>
                    <span style="display: inline-block">
                        <?php
                        er_form_get_field( array(
	                        'id'       => 'req_filter_min_pers',
	                        'type'     => 'select',
	                        'together' => true,
	                        'icon'     => __( 'Min', 'easyReservations' ),
	                        'options'  => $number_options,
	                        'value'    => $requirements['pers-min']
                        ) );
                        ?> -
                        <?php
                        er_form_get_field( array(
	                        'id'       => 'req_filter_max_pers',
	                        'type'     => 'select',
	                        'together' => true,
	                        'icon'     => __( 'Max', 'easyReservations' ),
	                        'options'  => $number_options_inf,
	                        'value'    => $requirements['pers-max']
                        ) );
                        ?>
                    </span>
				</td>
			</tr>
			<tr>
				<th>
					<label><?php esc_html_e( 'Arrival possible on', 'easyReservations' ); ?></label>
				</th>
				<td>
					<?php echo er_form_days_options( 'req_filter_start_on[]', 0 ); ?>
				</td>
			</tr>
			<tr>
				<th>
				</th>
				<td>
					<?php
					echo sprintf( $hour_string,
						er_form_get_field( array(
							'id'      => 'filter-start-h0',
							'type'    => 'select',
							'options' => $time_options,
							'value'   => 0
						), true ),
						er_form_get_field( array(
							'id'      => 'filter-start-h1',
							'type'    => 'select',
							'options' => $time_options,
							'value'   => 23
						), true )
					); ?>
				</td>
			</tr>
			<tr>
				<th>
					<label><?php esc_html_e( 'Departure possible on', 'easyReservations' ); ?></label>
				</th>
				<td>
					<?php echo er_form_days_options( 'req_filter_end_on[]', 0 ); ?>
				</td>
			</tr>
			<tr>
				<th>
				</th>
				<td>
					<?php
					echo sprintf( $hour_string,
						er_form_get_field( array(
							'id'      => 'filter-end-h0',
							'type'    => 'select',
							'options' => $time_options,
							'value'   => 0
						), true ),
						er_form_get_field( array(
							'id'      => 'filter-end-h1',
							'type'    => 'select',
							'options' => $time_options,
							'value'   => 23
						), true )
					); ?>
				</td>
			</tr>
			</tbody>
		</table>
	</div>
	<div id="filter_form_condition" class="hide-it">
		<table class="form-table">
			<tbody>
			<tr>
				<td>
					<label style="margin-left:5px">
						<input type="checkbox" name="filter_form_condition_checkbox" id="filter_form_condition_checkbox"
							onclick="show_use_condition();">
						<?php echo sprintf( esc_html__( 'Filter by %s', 'easyReservations' ), esc_html__( 'logical condition', 'easyReservations' ) ); ?>
					</label>
				</td>
			</tr>
			</tbody>
		</table>
	</div>
	<div id="filter_form_discount" class="hide-it">
		<hr>
		<h2><?php esc_html_e( 'Logical condition', 'easyReservations' ); ?></h2>
		<table class="form-table">
			<tbody>
			<tr>
				<th>
					<label for="filter_form_discount_type"><?php esc_html_e( 'Type', 'easyReservations' ); ?></label>
				</th>
				<td>
					<select name="filter_form_discount_type" id="filter_form_discount_type"
						onchange="setWord(this.value)">
						<option value="early"><?php echo esc_html( er_date_get_interval_label( $resource->get_billing_interval(), 0, true ) ); ?> <?php echo sprintf( esc_html__( 'between %1$s and %2$s', 'easyReservations' ), esc_html__( 'reservation', 'easyReservations' ), esc_html__( 'arrival', 'easyReservations' ) ); ?></option>
						<option value="stay"><?php echo esc_html( sprintf( $amount_text, er_date_get_interval_label( $resource->get_billing_interval() ) ) ); ?></option>
						<option value="pers"><?php echo esc_html( sprintf( $amount_text, __( 'adults and children', 'easyReservations' ) ) ); ?></option>
						<option value="adul"><?php echo esc_html( sprintf( $amount_text, __( 'adults', 'easyReservations' ) ) ); ?></option>
						<option value="child"><?php echo esc_html( sprintf( $amount_text, __( 'children', 'easyReservations' ) ) ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th>
					<label for="filter_form_discount_cond"><?php esc_html_e( 'Condition', 'easyReservations' ); ?></label>
				</th>
				<td>
                    <span style="display: block">
                        <?php
                        er_form_get_field( array(
	                        'id'      => 'filter_form_discount_cond',
	                        'type'    => 'select',
	                        'options' => $number_options,
	                        'value'   => 1
                        ) );
                        ?>
                        <span id="filter_form_discount_cond_verb"><?php esc_html_e( 'Days', 'easyReservations' ); ?></span>
                    </span>
				</td>
			</tr>
			</tbody>
		</table>
	</div>
	<div id="filter_form_price" class="hide-it">
		<hr>
		<h2><?php esc_html_e( 'Price', 'easyReservations' ); ?></h2>
		<p>
			<?php esc_html_e( 'Base price filter get checked for and applied each billing unit - extra charge and discount filter only once per reservation based on arrival.', 'easyReservations' ); ?>
		</p>
		<table class="form-table">
			<tbody>
			<tr>
				<th>
					<label for=filter-price-mode"><?php esc_html_e( 'Type', 'easyReservations' ); ?></label>
				</th>
				<td>
					<select onchange="easy_change_amount(this);" name="filter-price-mode" id="filter-price-mode">
						<option value="charge"><?php esc_html_e( 'Extra charge', 'easyReservations' ); ?></option>
						<option value="discount"><?php esc_html_e( 'Discount', 'easyReservations' ); ?></option>
						<option value="baseprice"><?php esc_html_e( 'Change base price', 'easyReservations' ); ?></option>
					</select>
				</td>
			</tr>
            <tr id="filter-mode-field">
                <th>
                    <label for="filter_form_discount_mode"><?php esc_html_e( 'Mode', 'easyReservations' ); ?></label>
                </th>
                <td>
                    <span>
                        <select name="filter_form_discount_mode" id="filter_form_discount_mode">
                            <option value="price_res"><?php echo sprintf( esc_html__( 'Price per %s', 'easyReservations' ), esc_html__( 'reservation', 'easyReservations' ) ); ?></option>
                            <option value="price_halfhour"><?php echo sprintf( esc_html__( 'Price per %s', 'easyReservations' ), esc_html( er_date_get_interval_label( 1800, 1 ) ) ); ?></option>
                            <option value="price_hour"><?php echo sprintf( esc_html__( 'Price per %s', 'easyReservations' ), esc_html( er_date_get_interval_label( HOUR_IN_SECONDS, 1 ) ) ); ?></option>
                            <option value="price_realday"><?php echo sprintf( esc_html__( 'Price per %s', 'easyReservations' ), esc_html( er_date_get_interval_label( DAY_IN_SECONDS, 1 ) ) ); ?></option>
                            <option value="price_night"><?php echo sprintf( esc_html__( 'Price per %s', 'easyReservations' ), esc_html( er_date_get_interval_label( 86401, 1 ) ) ); ?></option>
                            <option value="price_week"><?php echo sprintf( esc_html__( 'Price per %s', 'easyReservations' ), esc_html( er_date_get_interval_label( WEEK_IN_SECONDS, 1 ) ) ); ?></option>
                            <option value="price_month"><?php echo sprintf( esc_html__( 'Price per %s', 'easyReservations' ), esc_html( er_date_get_interval_label( MONTH_IN_SECONDS, 1 ) ) ); ?></option>
                            <option value="price_day"><?php echo sprintf( esc_html__( 'Price per %s', 'easyReservations' ), esc_html__( 'billing unit', 'easyReservations' ) ); ?></option>
                            <option value="price_pers"><?php echo sprintf( esc_html__( 'Price per %s', 'easyReservations' ), esc_html__( 'person', 'easyReservations' ) ); ?></option>
                            <option value="price_adul"><?php echo sprintf( esc_html__( 'Price per %s', 'easyReservations' ), esc_html__( 'adult', 'easyReservations' ) ); ?></option>
                            <option value="price_child"><?php echo sprintf( esc_html__( 'Price per %s', 'easyReservations' ), esc_html__( 'child', 'easyReservations' ) ); ?></option>
                            <option value="price_both"><?php echo sprintf( esc_html__( 'Price per %s and %s', 'easyReservations' ), esc_html( er_date_get_interval_label( $resource->get_billing_interval(), 1 ) ), esc_html__( 'person', 'easyReservations' ) ); ?></option>
                            <option value="price_day_adult"><?php echo sprintf( esc_html__( 'Price per %s and %s', 'easyReservations' ), esc_html( er_date_get_interval_label( $resource->get_billing_interval(), 1 ) ), esc_html__( 'adult', 'easyReservations' ) ); ?></option>
                            <option value="price_day_child"><?php echo sprintf( esc_html__( 'Price per %s and %s', 'easyReservations' ), esc_html( er_date_get_interval_label( $resource->get_billing_interval(), 1 ) ), esc_html__( 'child', 'easyReservations' ) ); ?></option>
                            <option value="%"><?php esc_html_e( 'Percent', 'easyReservations' ); ?></option>
                        </select>
                    </span>
                </td>
            </tr>

            <tr>
				<th><label for="filter-price-field"><?php esc_html_e( 'Price', 'easyReservations' ); ?></label></th>
				<td>
                    <span class="input-wrapper">
                        <input type="text" name="filter-price-field" id="filter-price-field" class="er_input_price" value="-100">
                        <span class="input-box"><?php echo er_get_currency_symbol(); ?></span>
                    </span>
				</td>
			</tr>
			<tr class="filter-children-price-container hidden">
				<th>
					<label for="filter-children-price"><?php esc_html_e( 'Children price', 'easyReservations' ); ?></label>
				</th>
				<td>
                    <span class="input-wrapper">
                        <input type="text" name="filter-children-price" id="filter-children-price" class="er_input_price" value="">
                        <span class="input-box"><?php echo er_get_currency_symbol(); ?></span>
                    </span>
				</td>
			</tr>
			</tbody>
		</table>
	</div>
	<?php endif; ?>
	<div id="filter_form_unavailable" class="hide-it">
		<table class="form-table">
			<tbody>
			<tr>
				<td>
					<label style="margin-left:5px;display: block">
						<input type="checkbox" name="filter_form_unavailable_checkbox" id="filter_form_unavailable_checkbox">
						<?php esc_html_e( 'Unavailable', 'easyReservations' ); ?>
					</label>
					<label style="margin-left:5px;display: block">
						<input type="checkbox" name="filter_form_arrival_checkbox" id="filter_form_arrival_checkbox">
						<?php esc_html_e( 'Arrival not possible', 'easyReservations' ); ?>
					</label>
					<label style="margin-left:5px;display: block">
						<input type="checkbox" name="filter_form_departure_checkbox" id="filter_form_departure_checkbox">
						<?php esc_html_e( 'Departure not possible', 'easyReservations' ); ?>
					</label>
				</td>
			</tr>
			</tbody>
		</table>
	</div>
	<div id="filter_form_button" class="hide-it">
		<input class="button-primary" id="filter_form_button_input" type="button"
			value="<?php echo sprintf( esc_html__( 'Add %s', 'easyReservations' ), esc_html__( 'filter', 'easyReservations' ) ); ?>"
			onclick="beforeFiltersubmit(); return false;">
	</div>
	<div id="filter_form_hidden"></div>
</form>
<script language="javascript" type="text/javascript">
	function display_price_filter() {
		show_add_price();
		document.filter_form.reset();
		jQuery( '#filter-price-field' ).val( 100 )
	}

	function display_availability_filter() {
		show_add_avail();
		document.filter_form.reset();
	}

	function display_requirement_filter() {
		show_add_req();
		document.filter_form.reset();
	}

	function beforeFiltersubmit() {
		if ( document.getElementById( 'filter_form_name_field' ).value == "" ) {
			document.getElementById( 'filter_form_name_field' ).style.border = "1px solid #f00";
			jQuery( '#filter_form_name_field' ).focus();
			return false;
		} else {
			document.getElementById( 'filter_form' ).submit();
		}
	}

	function is_int( value ) {
		if ( ( parseFloat( value ) == parseInt( value ) ) && !isNaN( value ) ) {
			return true;
		} else {
			return false;
		}
	}

	jQuery( '.paste-input' ).on( 'input', function( e ) {
		let isJson = true;
		try {
			var json = JSON.parse( jQuery( this ).val() );
		} catch ( err ) {
			isJson = false;
		}

		if ( isJson && json !== null && typeof json == 'object' ) {
			filter_edit( false, json );
			jQuery( this ).val( '' ).addClass( 'hidden' );
		}
	} );

	function filter_copy( i ) {
		var aux = document.createElement( "input" );
		aux.setAttribute( "value", JSON.stringify( filter[ i ] ) );
		document.body.appendChild( aux );
		aux.select();
		document.execCommand( "copy" );
		document.body.removeChild( aux );
	}

	function filter_edit( i, single_filter ) {
		reset_filter_form();
		if ( i === false ) {
			theFilter = single_filter;
		} else {
			var theFilter = filter[ i ];
			document.getElementById( 'filter_form_button_input' ).value = '<?php echo addslashes( sprintf( esc_html__( 'Edit %s', 'easyReservations' ), esc_html__( 'filter', 'easyReservations' ) ) ); ?>';
			document.getElementById( 'filter_form_hidden' ).innerHTML = '<input type="hidden" id="price_filter_edit" name="price_filter_edit" value="' + i + '">';
		}
		var type = theFilter[ 'type' ];
		document.getElementById( 'filter_form_name_field' ).value = theFilter[ 'name' ];

		if ( type === 'price' || type === 'unavail' || type === 'req' || theFilter[ 'timecond' ] ) {
			var cond = theFilter[ 'cond' ];
			if ( theFilter[ 'timecond' ] ) {
				cond = theFilter[ 'timecond' ];
			}
			if ( cond === 'date' ) {
				document.getElementById( 'price_filter_cond_range' ).checked = true;
				var timestamp_date = theFilter[ 'date_str' ];
				if ( timestamp_date !== '' ) {
					var date_date = new Date( timestamp_date );
					document.getElementById( 'filter_range_from_hour' ).selectedIndex = date_date.getHours();
					document.getElementById( 'filter_range_to_hour' ).selectedIndex = date_date.getHours();
					document.getElementById( 'filter_range_from_minute' ).selectedIndex = date_date.getMinutes();
					document.getElementById( 'filter_range_to_minute' ).selectedIndex = date_date.getMinutes();
					document.getElementById( 'price_filter_range_from' ).value = ( ( date_date.getDate() < 10 ) ? '0' + date_date.getDate() : date_date.getDate() ) + '.' + ( ( ( date_date.getMonth() + 1 ) < 10 ) ? '0' + ( date_date.getMonth() + 1 ) : ( date_date.getMonth() + 1 ) ) + '.' + ( ( date_date.getYear() < 999 ) ? date_date.getYear() + 1900 : date_date.getYear() );
					document.getElementById( 'price_filter_range_to' ).value = ( ( date_date.getDate() < 10 ) ? '0' + date_date.getDate() : date_date.getDate() ) + '.' + ( ( ( date_date.getMonth() + 1 ) < 10 ) ? '0' + ( date_date.getMonth() + 1 ) : ( date_date.getMonth() + 1 ) ) + '.' + ( ( date_date.getYear() < 999 ) ? date_date.getYear() + 1900 : date_date.getYear() );
				}
			} else if ( cond === 'range' || theFilter[ 'from' ] ) {
				if ( theFilter[ 'every' ] ) {
					document.getElementById( 'price_filter_range_every' ).checked = true;
				}
				document.getElementById( 'price_filter_cond_range' ).checked = true;
				if ( theFilter[ 'from_str' ] !== '' ) {
					var date_from = new Date( theFilter[ 'from_str' ] );
					document.getElementById( 'filter_range_from_hour' ).selectedIndex = date_from.getHours();
					document.getElementById( 'filter_range_from_minute' ).selectedIndex = date_from.getMinutes();
					document.getElementById( 'price_filter_range_from' ).value = ( ( date_from.getDate() < 10 ) ? '0' + date_from.getDate() : date_from.getDate() ) + '.' + ( ( ( date_from.getMonth() + 1 ) < 10 ) ? '0' + ( date_from.getMonth() + 1 ) : ( date_from.getMonth() + 1 ) ) + '.' + ( ( date_from.getYear() < 999 ) ? date_from.getYear() + 1900 : date_from.getYear() );
				} else {
					document.getElementById( 'price_filter_range_from' ).value = theFilter[ 'from' ];
				}

				if ( theFilter[ 'to_str' ] !== '' ) {
					var date_to = new Date( theFilter[ 'to_str' ] );
					document.getElementById( 'filter_range_to_hour' ).selectedIndex = date_to.getHours();
					document.getElementById( 'filter_range_to_minute' ).selectedIndex = date_to.getMinutes();
					document.getElementById( 'price_filter_range_to' ).value = ( ( date_to.getDate() < 10 ) ? '0' + date_to.getDate() : date_to.getDate() ) + '.' + ( ( ( date_to.getMonth() + 1 ) < 10 ) ? '0' + ( date_to.getMonth() + 1 ) : ( date_to.getMonth() + 1 ) ) + '.' + ( ( date_to.getYear() < 999 ) ? date_to.getYear() + 1900 : date_to.getYear() );
				} else {
					document.getElementById( 'price_filter_range_to' ).value = theFilter[ 'to' ];
				}
			}
			if ( ( theFilter[ 'timecond' ] && theFilter[ 'timecond' ] === 'unit' ) || ( theFilter[ 'cond' ] && theFilter[ 'cond' ] === 'unit' ) ) {
				document.getElementById( 'price_filter_cond_unit' ).checked = true;
				var hour_checkboxes = document.getElementsByName( 'price_filter_unit_hour[]' );
				if ( hour_checkboxes && theFilter[ 'hour' ] != '' && theFilter[ 'hour' ] ) {
					var hours = theFilter[ 'hour' ];
					var explode_hours = hours.split( "," );
					for ( var x = 0; x < explode_hours.length; x++ ) {
						var nr = explode_hours[ x ];
						hour_checkboxes[ nr ].checked = true;
					}
				}
				var dayCheckboxes = document.getElementsByName( 'price_filter_unit_days[]' );
				if ( dayCheckboxes && theFilter[ 'day' ] != '' && theFilter[ 'day' ] ) {
					var days = theFilter[ 'day' ];
					var explode_days = days.split( "," );
					for ( var x = 0; x < explode_days.length; x++ ) {
						var nr = explode_days[ x ];
						if ( dayCheckboxes[ nr - 1 ] ) {
							dayCheckboxes[ nr - 1 ].checked = true;
						}
					}
				}
				var cw_checkboxes = document.getElementsByName( 'price_filter_unit_cw[]' );
				if ( theFilter[ 'cw' ] != '' && theFilter[ 'cw' ] ) {
					var cws = theFilter[ 'cw' ];
					var explode_cws = cws.split( "," );
					for ( var x = 0; x < explode_cws.length; x++ ) {
						var nr = explode_cws[ x ];
						if ( cw_checkboxes[ nr - 1 ] ) {
							cw_checkboxes[ nr - 1 ].checked = true;
						}
					}
				}
				var month_checkboxes = document.getElementsByName( 'price_filter_unit_month[]' );
				if ( theFilter[ 'month' ] != '' && theFilter[ 'month' ] ) {
					var month = theFilter[ 'month' ];
					var explode_month = month.split( "," );
					for ( var x = 0; x < explode_month.length; x++ ) {
						var nr = explode_month[ x ];
						if ( month_checkboxes[ nr - 1 ] ) {
							month_checkboxes[ nr - 1 ].checked = true;
						}
					}
				}
				var q_checkboxes = document.getElementsByName( 'price_filter_unit_quarter[]' );
				if ( theFilter[ 'quarter' ] != '' && theFilter[ 'quarter' ] ) {
					var quarters = theFilter[ 'quarter' ];
					var explode_quarters = quarters.split( "," );
					for ( var x = 0; x < explode_quarters.length; x++ ) {
						var nr = explode_quarters[ x ];
						if ( q_checkboxes[ nr - 1 ] ) {
							q_checkboxes[ nr - 1 ].checked = true;
						}
					}
				}
				var yearCheckboxes = document.getElementsByName( 'price_filter_unit_year[]' );
				if ( theFilter[ 'year' ] != '' && theFilter[ 'year' ] ) {
					var years = theFilter[ 'year' ];
					var explode_years = years.split( "," );
					for ( var x = 0; x < explode_years.length; x++ ) {
						var nr = explode_years[ x ] - 2014;
						if ( yearCheckboxes[ nr - 1 ] ) {
							yearCheckboxes[ nr - 1 ].checked = true;
						}
					}
				}
			}
		}

		if ( type === 'unavail' ) {
			if ( theFilter[ 'arrival' ] ) {
				jQuery( '#filter_form_arrival_checkbox' ).prop( 'checked', true );
			}
			if ( theFilter[ 'departure' ] ) {
				jQuery( '#filter_form_departure_checkbox' ).prop( 'checked', true );
			}

			if ( !theFilter[ 'arrival' ] && !theFilter[ 'arrival' ] ) {
				jQuery( '#filter_form_unavailable_checkbox' ).prop( 'checked', true );
			}

			show_add_avail();
		} else if ( type === 'req' ) {
			var reqs = theFilter[ 'req' ];
			document.getElementById( 'req_filter_min_pers' ).selectedIndex = parseFloat( reqs[ 'pers-min' ] ) - 1;
			document.getElementById( 'req_filter_max_pers' ).selectedIndex = reqs[ 'pers-max' ];
			document.getElementById( 'req_filter_min_nights' ).selectedIndex = parseFloat( reqs[ 'nights-min' ] ) - 1;
			document.getElementById( 'req_filter_max_nights' ).selectedIndex = reqs[ 'nights-max' ];
			var dayCheckboxes = document.getElementsByName( 'req_filter_start_on[]' );
			jQuery( dayCheckboxes ).prop( 'checked', false );
			if ( dayCheckboxes && reqs[ 'start-on' ] !== '' ) {
				if ( reqs[ 'start-on' ] == 0 ) {
					jQuery( dayCheckboxes ).prop( 'checked', true );
				}
				var explode_days = reqs[ 'start-on' ];
				for ( var x = 0; x < explode_days.length; x++ ) {
					var nr = explode_days[ x ];
					dayCheckboxes[ nr - 1 ].checked = true;
				}
			}

			var end_checkboxes = document.getElementsByName( 'req_filter_end_on[]' );

			jQuery( end_checkboxes ).prop( 'checked', false );
			if ( end_checkboxes && reqs[ 'end-on' ] !== '' ) {
				var explode_ends = reqs[ 'end-on' ];
				if ( reqs[ 'end-on' ] == 0 ) {
					jQuery( end_checkboxes ).prop( 'checked', true );
				}

				for ( var x = 0; x < explode_ends.length; x++ ) {
					var nr = explode_ends[ x ];
					end_checkboxes[ nr - 1 ].checked = true;
				}
			}
			if ( reqs[ 'start-h' ] ) {
				jQuery( 'select[name="filter-start-h0"]' ).val( reqs[ 'start-h' ][ 0 ] );
				jQuery( 'select[name="filter-start-h1"]' ).val( reqs[ 'start-h' ][ 1 ] );
			}
			if ( reqs[ 'end-h' ] ) {
				jQuery( 'select[name="filter-end-h0"]' ).val( reqs[ 'end-h' ][ 0 ] );
				jQuery( 'select[name="filter-end-h1"]' ).val( reqs[ 'end-h' ][ 1 ] );
			}
			show_add_req();
		} else {
			show_add_price();
			var timecond = false;
			var condcond = false;
			var condtype = false;
			if ( theFilter[ 'imp' ] ) {
				document.getElementById( 'price_filter_imp' ).selectedIndex = theFilter[ 'imp' ] - 1;
			}

			var price = theFilter[ 'price' ];
			var pricemodus = document.getElementsByName( 'filter-price-mode' );
			jQuery( '#filter-price-field' ).val( price )

			if ( theFilter[ 'children-price' ] ) {
				document.getElementById( 'filter-children-price' ).value = theFilter[ 'children-price' ];
			}

			if ( type == 'price' ) {
				jQuery( '.filter-children-price-container' ).removeClass( 'hidden' );
				pricemodus[ 0 ].selectedIndex = 2;
			} else if ( price > 0 ) {
				pricemodus[ 0 ].selectedIndex = 0;
			} else {
				pricemodus[ 0 ].selectedIndex = 1;
			}

			if ( type == 'price' ) {
				jQuery( '#filter-mode-field' ).addClass( 'hidden' );
				if ( theFilter[ 'cond' ] ) {
					timecond = 'cond';
				}
				if ( theFilter[ 'basecond' ] ) {
					condcond = 'basecond';
				}
				if ( theFilter[ 'condtype' ] ) {
					condtype = 'condtype';
				}
			} else {
				if ( theFilter[ 'timecond' ] ) {
					timecond = 'timecond';
				}
				if ( theFilter[ 'cond' ] ) {
					condcond = 'cond';
				}
				if ( theFilter[ 'type' ] ) {
					condtype = 'type';
				}
			}
			if ( timecond ) {
				show_use_time( 1 );
			}
			if ( condcond ) {
				type = theFilter[ condtype ];
				jQuery( '#filter_form_discount_type' ).val( type );
				setWord( type );
				document.getElementById( 'filter_form_discount_cond' ).selectedIndex = theFilter[ condcond ] - 1;

				if ( theFilter[ 'modus' ] ) {
					jQuery( '#filter_form_discount_mode' ).val( theFilter[ 'modus' ] );
				}
				show_use_condition( 1 );
			}
		}
	}

	function show_add_price() {
		jQuery( '#filter_form_name,#filter_form_importance,#filter_form_usetime,#filter_form_condition' ).removeClass( 'hidden' ).removeClass( 'hide-it' );
		jQuery( '#filter_form_time_cond,#filter_form_price,#filter_form_button,#filter_form_discount,#filter_form_requirements,#filter_form_unavailable' ).addClass( 'hidden' );

		document.getElementById( 'filter_type' ).value = "price";
	}

	function show_use_time( start ) {
		if ( start ) {
			document.getElementById( 'filter_form_usetime_checkbox' ).checked = true;
		}
		if ( document.getElementById( 'filter_form_usetime_checkbox' ).checked == true ) {
			show_price( 1 );
			jQuery( '#filter_form_button, #filter_form_time_cond' ).removeClass();
		} else {
			jQuery( '#filter_form_time_cond' ).addClass( 'hidden' );
			if ( document.getElementById( 'filter_form_condition_checkbox' ).checked !== true ) {
				show_price();
			}
		}
	}

	function show_use_condition( start ) {
		if ( start ) {
			document.getElementById( 'filter_form_condition_checkbox' ).checked = true;
		}
		if ( document.getElementById( 'filter_form_condition_checkbox' ).checked == true ) {
			show_price( 1 );
			jQuery( '#filter_form_button, #filter_form_discount' ).removeClass();
		} else {
			jQuery( '#filter_form_discount' ).addClass( 'hidden' );
			if ( document.getElementById( 'filter_form_usetime_checkbox' ).checked !== true ) {
				show_price();
			}
		}
	}

	function show_price( on ) {
		if ( on ) {
			document.getElementById( 'filter_form_price' ).className = '';
		} else {
			document.getElementById( 'filter_form_price' ).className = 'hidden';
		}
	}

	function show_add_avail() {
		jQuery( '#filter_form_importance, #filter_form_name, #filter_form_time_cond, #filter_form_unavailable, #filter_form_button' ).removeClass();
		jQuery( '#filter_form_requirements, #filter_form_usetime, #filter_form_price, #filter_form_discount, #filter_form_condition' ).addClass( 'hidden' );

		jQuery( '#filter_type' ).val( 'unavail' );
	}

	function show_add_req() {
		jQuery( '#filter_form_importance, #filter_form_button, #filter_form_requirements, #filter_form_time_cond, #filter_form_name' ).removeClass();
		jQuery( '#filter_form_discount, #filter_form_price, #filter_form_usetime, #filter_form_condition, #filter_form_unavailable' ).addClass( 'hidden' );

		document.getElementById( 'filter_type' ).value = "req";
	}

	function reset_filter_form() {
		jQuery( '#filter_form_name, #filter_form_time_cond, #filter_form_usetime, #filter_form_requirements, #filter_form_discount, #filter_form_discount, #filter_form_price, #filter_form_importance, #filter_form_condition, #filter_form_unavailable' ).addClass( 'hidden' );
		jQuery( '#filter-mode-field' ).removeClass( 'hidden' );
		document.filter_form.reset();
		jQuery( '#filter_type' ).val( '' );
		jQuery( '#filter_form_button_input' ).val( '<?php echo sprintf( esc_html__( 'Add %s', 'easyReservations' ), esc_html__( 'filter', 'easyReservations' ) ); ?>' );
		jQuery( '#filter_form_hidden' ).html( '' );
		display_price_filter();
	}

	function setWord( v ) {
		if ( v == 'early' || v == 'stay' ) {
			var verb = '<?php echo er_date_get_interval_label( isset( $resource ) ? $resource->get_billing_interval() : DAY_IN_SECONDS ); ?>';
		}
		if ( v == 'pers' ) {
			var verb = '<?php echo addslashes( esc_html__( 'adults and children', 'easyReservations' ) ); ?>';
		}
		if ( v == 'adul' ) {
			var verb = '<?php echo addslashes( esc_html__( 'adults', 'easyReservations' ) ); ?>';
		}
		if ( v == 'child' ) {
			var verb = '<?php echo addslashes( esc_html__( 'children', 'easyReservations' ) ); ?>';
		}
		document.getElementById( 'filter_form_discount_cond_verb' ).innerHTML = verb;
	}

	jQuery( document ).ready( function( $ ) {
		if ( $.fn.easyNavigation ) {
			$( '.filter-navigation' ).easyNavigation( false );
		}

		$( '#filter_form_unavailable_checkbox' ).on( 'click', function() {
			if ( this.checked ) {
				$( '#filter_form_arrival_checkbox, #filter_form_departure_checkbox' ).prop( 'checked', false );
			}
		} );

		$( '#filter_form_arrival_checkbox, #filter_form_departure_checkbox' ).on( 'click', function() {
			if ( this.checked ) {
				$( '#filter_form_unavailable_checkbox' ).prop( 'checked', false );
			}
		} );

		display_price_filter();
	} );

	function easy_change_amount( t ) {
		jQuery( '#filter-mode-field' ).removeClass( 'hidden' );
		jQuery( '.filter-children-price-container' ).addClass( 'hidden' );
		var fieldbefore = jQuery( '#filter-price-field' ).val();
		if ( t ) {
			var end = fieldbefore;
			if ( t.value == 'discount' ) {
				if ( fieldbefore[ 0 ] == '-' ) {
					end = fieldbefore;
				} else {
					end = '-' + fieldbefore;
				}
			} else if ( t.value == 'baseprice' ) {
				if ( fieldbefore[ 0 ] == '-' ) {
					end = fieldbefore.substr( 1 );
				}
				document.getElementById( 'filter_form_discount_mode' ).selectedIndex = 1;
				jQuery( '#filter-mode-field' ).addClass( 'hidden' );
				jQuery( '.filter-children-price-container' ).removeClass( 'hidden' );
			} else {
				if ( fieldbefore[ 0 ] == '-' ) {
					end = fieldbefore.substr( 1 );
				}
			}
			jQuery( '#filter-price-field' ).val( end );
		}
	}

</script>