<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

if ( isset( $_GET["form"] ) ) {
	$current_form_name = sanitize_key( $_GET['form'] );
	$reservations_form = wp_kses_post( get_option( "reservations_form_" . $current_form_name ) );
} else {
	$current_form_name = '';
	$reservations_form = wp_kses_post( get_option( "reservations_form" ) );
}

$custom_fields = ER_Custom_Data::get_settings();
$custom_fields_array = array();
if ( $custom_fields && ! empty( $custom_fields ) ) {
	foreach ( $custom_fields as $id => $custom ) {
		$custom_fields_array[ $id ] = $custom['title'];
	}
}

$new_form = '';
if ( ! empty( $reservations_form ) ) {
	foreach ( explode( "\r\n", ( $reservations_form ) ) as $v ) {
		$new_form .= nl2br( htmlspecialchars( $v, ENT_COMPAT ) );
	}
	$tags = er_form_template_parser( $new_form, true );
	foreach ( $tags as &$v ) {
		$explode  = explode( ' ', $v );
		$new_form = str_replace( '[' . $v . ']', '<formtag attr="' . esc_attr( $explode[0] ) . '">[' . esc_html( $v ) . ']</formtag>', $new_form );
	}
}

wp_enqueue_script( 'jquery-ui-accordion' );
wp_enqueue_script( 'er-settings-form' );

?>
<div style="width:99%;line-height: 22px;margin-top: 5px;height:30px">
	<ul class="subsubsub" style="display:block;margin-top:3px">
		<li>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=er-settings&tab=form' ) ); ?>" class="<?php if ( empty( $current_form_name ) ) {
				echo 'current';
			} ?>"><?php esc_html_e( 'Standard', 'easyReservations' ); ?></a>
		</li>
		<?php
		$forms = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_name FROM " . $wpdb->prefix . "options WHERE option_name like %s ",
				$wpdb->esc_like( "reservations_form_" ) . '%'
			)
		);

		foreach ( $forms as $form_option ) {
			$form_option_name = str_replace( 'reservations_form_', '', sanitize_key( $form_option->option_name ) );
			if ( ! empty( $form_option_name ) ) {
				echo '<li>&nbsp;| ';
				echo '<a href="' . esc_url( admin_url( 'admin.php?page=er-settings&tab=form&form=' . $form_option_name ) ) . '" ';
				echo 'class="' . ( $form_option_name == $current_form_name ? 'current' : '' ) . '">' . esc_html( ucfirst( str_replace( '-', ' ', $form_option_name ) ) ) . '</a>';
				echo '</li>';
			}
		}
		?>
	</ul>
	<div style="float:right" class="easy-ui">
		<input name="form_name" type="text" style="width:200px;">
		<button name="save" type="submit" class="button-primary"><?php esc_html_e( 'Add', 'easyReservations' ); ?></button>
		<?php wp_nonce_field( 'easy-add-form' ); ?>
	</div>
</div>
</form>

<div id="form_container" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" contenteditable="true">
	<?php echo stripslashes( $new_form ); ?>
</div>
<div id="form_settings_tags_container" class="easy-ui">
	<div id="accordion" style="width:100%">
		<h3><?php esc_html_e( 'Date fields', 'easyReservations' ); ?></h3>
		<div class="table">
			<table class="formtable">
				<thead>
				<tr>
					<th></th>
					<th><?php esc_html_e( 'Type', 'easyReservations' ); ?></th>
					<th><?php esc_html_e( 'Default', 'easyReservations' ); ?></th>
				</tr>
				</thead>
				<tbody>
				<tr attr="date">
					<td style="text-align:center;"><span class="dashicons dashicons-calendar-alt"></span>
					</td>
					<td>
						<strong><?php esc_html_e( 'Date selection', 'easyReservations' ); ?></strong><br><i><?php esc_html_e( 'For arrival and departure', 'easyReservations' ); ?></i>
					</td>
					<td>&#10008;</td>
				</tr>
				<tr attr="date-from">
					<td style="text-align:center;"><span class="dashicons dashicons-arrow-down-alt"></span></td>
					<td>
						<strong><?php esc_html_e( 'Arrival date', 'easyReservations' ); ?></strong><br><i><?php esc_html_e( 'Text field with datepicker', 'easyReservations' ); ?></i>
					</td>
					<td>&#10008;</td>
				</tr>
				<tr attr="arrival_hour">
					<td style="text-align:center;"><span class="dashicons dashicons-clock"></span></td>
					<td>
						<strong><?php esc_html_e( 'Arrival hour', 'easyReservations' ); ?></strong><br><i><?php esc_html_e( 'Select field as of the time pattern selection', 'easyReservations' ); ?></i>
					</td>
					<td>00</td>
				</tr>
				<tr attr="arrival_minute">
					<td style="text-align:center;"><span class="dashicons dashicons-clock"></span></td>
					<td>
						<strong><?php esc_html_e( 'Arrival minute', 'easyReservations' ); ?></strong><br><i><?php esc_html_e( 'Select field', 'easyReservations' ); ?> 00-59</i>
					</td>
					<td>12</td>
				</tr>
				<tr attr="date-to">
					<td style="text-align:center;"><span class="dashicons dashicons-arrow-up-alt"></span>
					</td>
					<td>
						<strong><?php esc_html_e( 'Departure date', 'easyReservations' ); ?></strong><br><i><?php esc_html_e( 'Text field with datepicker', 'easyReservations' ); ?></i>
					</td>
					<td>&#10008;</td>
				</tr>
				<tr attr="departure_hour">
					<td style="text-align:center;"><span class="dashicons dashicons-clock"></span></td>
					<td>
						<strong><?php esc_html_e( 'Departure hour', 'easyReservations' ); ?></strong><br><i><?php esc_html_e( 'Select field as of the time pattern selection', 'easyReservations' ); ?></i>
					</td>
					<td>12</td>
				</tr>
				<tr attr="departure_minute">
					<td style="text-align:center;"><span class="dashicons dashicons-clock"></span></td>
					<td>
						<strong><?php esc_html_e( 'Departure minute', 'easyReservations' ); ?></strong><br><i><?php esc_html_e( 'Select field', 'easyReservations' ); ?> 00-59</i>
					</td>
					<td>00</td>
				</tr>
				<tr attr="units">
					<td style="text-align:center;"><span class="dashicons dashicons-calendar"></span>
					</td>
					<td>
						<strong><?php esc_html_e( 'Billing units', 'easyReservations' ); ?></strong><br><i><?php esc_html_e( 'Select field to choose length of stay', 'easyReservations' ); ?></i>
					</td>
					<td>1</td>
				</tr>
				</tbody>
			</table>
		</div>
		<h3><?php esc_html_e( 'Information fields', 'easyReservations' ); ?></h3>
		<div class="table">
			<table class="formtable">
				<thead>
				<tr>
					<th></th>
					<th><?php esc_html_e( 'Type', 'easyReservations' ); ?></th>
					<th><?php esc_html_e( 'Default', 'easyReservations' ); ?></th>
				</tr>
				</thead>
				<tbody>
				<tr attr="resources">
					<td style="text-align:center;"><span class="dashicons dashicons-admin-home"></td>
					<td>
						<strong><?php esc_html_e( 'Resources', 'easyReservations' ); ?></strong><br><i><?php esc_html_e( 'Select field for resource', 'easyReservations' ); ?></i>
					</td>
					<td>&#10008;</td>
				</tr>
				<tr attr="adults">
					<td style="text-align:center;"><span class="dashicons dashicons-admin-users"></span></td>
					<td>
						<strong><?php esc_html_e( 'Adults', 'easyReservations' ); ?></strong><br><i><?php esc_html_e( 'Select field for adults', 'easyReservations' ); ?></i>
					</td>
					<td>1</td>
				</tr>
				<tr attr="children">
					<td style="text-align:center;"><span class="dashicons dashicons-universal-access"></span></td>
					<td>
						<strong><?php esc_html_e( 'Children', 'easyReservations' ); ?></strong><br><i><?php esc_html_e( 'Select field for children', 'easyReservations' ); ?></i>
					</td>
					<td>0</td>
				</tr>
				</tbody>
			</table>
		</div>
		<h3><?php esc_html_e( 'Special fields', 'easyReservations' ); ?></h3>
		<div class="table">
			<table class="formtable">
				<thead>
				<tr>
					<th></th>
					<th><?php esc_html_e( 'Type', 'easyReservations' ); ?></th>
				</tr>
				</thead>
				<tbody>
				<tr attr="hidden">
					<td style="text-align:center;"><span class="dashicons dashicons-lock"></span></td>
					<td>
						<strong><?php esc_html_e( 'Hidden', 'easyReservations' ); ?></strong><br><i><?php esc_html_e( 'Dictate information and/or hide it from guest', 'easyReservations' ); ?></i>
					</td>
				</tr>
				<tr attr="custom">
					<td style="text-align:center;"><span class="dashicons dashicons-tag"></span></td>
					<td>
						<strong><?php esc_html_e( 'Custom', 'easyReservations' ); ?></strong><br><i><?php esc_html_e( 'Custom form elements to get more information', 'easyReservations' ); ?></i>
					</td>
				</tr>
				<?php do_action( 'easyreservations_form_settings_list' ); ?>
				</tbody>
			</table>
		</div>
		<h3><?php esc_html_e( 'Format', 'easyReservations' ); ?></h3>
		<div class="table">
			<table class="formtable">
				<tbody>
				<tr bttr="label">
					<td><strong><?php esc_html_e( 'Label', 'easyReservations' ); ?>
						<tag>&lt;label&gt;</tag>
					</strong><br><i><?php esc_html_e( 'Used for description of tags. Should be before the content wrapper.', 'easyReservations' ); ?></i>
					</td>
				</tr>
				<tr bttr="div">
					<td><strong><?php esc_html_e( 'Content wrapper', 'easyReservations' ); ?>
						<tag>&lt;div class="content"&gt;</tag>
						<br><i></strong><?php esc_html_e( 'Wrapper around content. Should be around fields and text.', 'easyReservations' ); ?></i>
					</td>
				</tr>
				<tr bttr="row">
					<td><strong><?php esc_html_e( 'Row', 'easyReservations' ); ?>
						<tag>&lt;div class="row"&gt;</tag>
						<br><i></strong><?php esc_html_e( 'Wrapper for multiple elements in one row. Should be inside the content wrapper and around any form elements and/or text. It may be necessary to define their width\'s.', 'easyReservations' ); ?></i>
					</td>
				</tr>
				<tr bttr="small">
					<td><strong><?php esc_html_e( 'Small', 'easyReservations' ); ?>
						<tag>&lt;small&gt;</tag>
						<br><i></strong><?php esc_html_e( 'A caption for the form element. Should be in the content wrapper.', 'easyReservations' ); ?></i>
					</td>
				</tr>
				<tr bttr="b">
					<td><strong><?php esc_html_e( 'Bold', 'easyReservations' ); ?>
						<tag>&lt;strong&gt;</tag>
					</strong><br><i><?php esc_html_e( 'Bold text', 'easyReservations' ); ?></i></td>
				</tr>
				<tr bttr="i">
					<td><strong><?php esc_html_e( 'Italic', 'easyReservations' ); ?>
						<tag>&lt;i&gt;</tag>
					</strong><br><i><?php esc_html_e( 'Italic text', 'easyReservations' ); ?></i></td>
				</tr>
				<tr bttr="h1">
					<td><strong><?php esc_html_e( 'Headline', 'easyReservations' ); ?>
						<tag>&lt;h1&gt;</tag>
					</strong><br><i><?php esc_html_e( 'Big headline', 'easyReservations' ); ?></i></td>
				</tr>
				<tr bttr="h2">
					<td><strong><?php esc_html_e( 'Sub-headline', 'easyReservations' ); ?>
						<tag>&lt;h2&gt;</tag>
					</strong><br><i><?php esc_html_e( 'Smaller headline to divide the form.', 'easyReservations' ); ?></i>
					</td>
				</tr>
				</tbody>
			</table>
		</div>
	</div>
</div>
<div class="easy-ui" style="">
	<?php if ( $current_form_name == 'default-widget' || $current_form_name == 'default-search-bar' ): ?>
	<i style="margin:5px;"><?php esc_html_e( 'Default widget and search bar template cannot be edited so you always have them as reference', 'easyReservations' ); ?>.</i>
	<?php else: ?>
	<a href="javascript:submitForm();" class="button-primary" style="margin:5px;"><?php esc_html_e( 'Submit', 'easyReservations' ); ?></a>
	<a href="javascript:resetToDefault();" class="button  grey" style="margin:5px 5px 5px 0;"><?php esc_html_e( 'Default', 'easyReservations' ); ?></a>
	<?php if ( $current_form_name !== '' && $current_form_name !== 'default-widget' && $current_form_name !== 'default-search-bar' && $current_form_name !== 'checkout' ): ?>
	<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=er-settings&tab=form&delete-form=' . $current_form_name ), 'easy-delete-form' ) ); ?>" class="button  grey" style="margin:5px 5px 5px 0;"><?php esc_html_e( 'Delete', 'easyReservations' ); ?></a>
	<?php endif; ?>
	<?php endif; ?>
</div>
<form id="easy_form" method="post">
	<?php wp_nonce_field( 'easyreservations-settings' ); ?>
	<input type="hidden" name="save" value="reservations_form_settings">
	<input type="hidden" name="action" value="reservations_form_settings">
	<input type="hidden" name="reservations_form_content" id="reservations_form_content" value="">
</form>

<script type="text/javascript">
	function submitForm() {
		jQuery( '#reservations_form_content' ).val( jQuery( '#form_container' ).html() );
		jQuery( '#easy_form' ).trigger( 'submit' );
	}

	function resetToDefault() {
		var Default = '<?php echo str_replace( array( "\n", "\r" ), array(
			"\\n",
			"\\r"
		), wp_kses_post( er_form_get_default( $current_form_name ) ) ); ?>';

		jQuery( '#form_container' ).html( htmlForTextWithEmbeddedNewlines( Default ) );
	}

	function generateHiddenOptions( tag ) {
		var value = '<h4><?php echo addslashes( esc_html__( 'Type', 'easyReservations' ) ); ?></h4><p><select id="hiddentype" name="1" onchange="changeHiddenOption()">';
		jQuery.each( {
			xxx:                "<?php echo addslashes( esc_html__( 'Type', 'easyReservations' ) ); ?>",
			resource:           "<?php echo addslashes( esc_html__( 'Resource', 'easyReservations' ) ); ?>",
			arrival:               "<?php echo addslashes( esc_html__( 'Arrival date', 'easyReservations' ) ); ?>",
			"arrival_hour":     "<?php echo addslashes( esc_html__( 'Arrival hour', 'easyReservations' ) ); ?>",
			"arrival_minute":   "<?php echo addslashes( __( 'Arrival minute', 'easyReservations' ) ); ?>",
			departure:                 "<?php echo addslashes( esc_html__( 'Departure date', 'easyReservations' ) ); ?>",
			"departure_hour":   "<?php echo addslashes( esc_html__( 'Departure hour', 'easyReservations' ) ); ?>",
			"departure_minute": "<?php echo addslashes( esc_html__( 'Departure minute', 'easyReservations' ) ); ?>",
			units:              "<?php echo addslashes( esc_html__( 'Billing units', 'easyReservations' ) ); ?>",
			adults:             "<?php echo addslashes( esc_html__( 'Adults', 'easyReservations' ) ); ?>",
			children:           "<?php echo addslashes( esc_html__( 'Children', 'easyReservations' ) ); ?>"
		}, function( ok, ov ) {
			var selected = '';
			if ( tag && tag[ 1 ] == ok ) {
				selected = 'selected="selected"';
			}
			value += '<option value="' + ok + '" ' + selected + '>' + ov + '</option>';
		} );
		value += '</select></p><span id="the_hidden_value">';
		if ( tag ) {
			value += changeHiddenOption( tag, tag[ 1 ] );
		}
		value += '</span><label class="wrapper"><input type="checkbox" name="display"> <?php echo addslashes( esc_html__( 'Display value', 'easyReservations' ) ); ?></label>';
		return value;
	}

	function changeHiddenOption( tag, typ ) {
		var type = jQuery( '#hiddentype' ).val();
		if ( typ ) {
			type = typ;
		}
		var field = false;
		if ( type == 'resource' ) {
			if ( !tag || !tag[ 2 ] ) {
				tag = { 2: '' };
			}
			field = generateResourceSelect( tag[ 2 ], '2' );
		} else if ( type == "arrival" || type == "departure" ) {
			if ( !tag || !tag[ 2 ] ) {
				tag = { 2: '<?php echo esc_html( er_date_format() ); ?>' };
			}
			field = '<input type="text" name="2" value="' + tag[ 2 ] + '">'
		} else if ( type == "arrival_hour" || type == "departure_hour" ) {
			if ( !tag || !tag[ 2 ] ) {
				tag = { 2: 12 };
			}
			field = '<select name="2">' + generateOptions( '0-23', tag[ 2 ] ) + '</select>'
		} else if ( type == "arrival_minute" || type == "departure_minute" ) {
			if ( !tag || !tag[ 2 ] ) {
				tag = { 2: 30 };
			}
			field = '<select name="2">' + generateOptions( '0-59', tag[ 2 ] ) + '</select>'
		} else if ( type == "adults" || type == "units" ) {
			if ( !tag || !tag[ 2 ] ) {
				tag = { 2: 2 };
			}
			field = '<select name="2">' + generateOptions( '1-100', tag[ 2 ] ) + '</select>'
		} else if ( type == "children" ) {
			if ( !tag || !tag[ 2 ] ) {
				tag = { 2: 1 };
			}
			field = '<select name="2">' + generateOptions( '0-100', tag[ 2 ] ) + '</select>'
		}
		if ( field ) {
			field = '<h4><?php echo addslashes( esc_html__( 'Value', 'easyReservations' ) ); ?></h4><p>' + field + '</p>'
			if ( typ ) {
				return field;
			} else {
				jQuery( '#the_hidden_value' ).html( field );
			}
		}
	}

	function resourceSelect( tag ) {
		if ( !tag ) {
			tag = { value: '' };
		} else if ( !tag[ 'value' ] ) {
			tag[ 'value' ] = '';
		}
		return generateResourceSelect( tag[ 'value' ], 'value' );
	}

	function generateResourceSelect( sel, name ) {
		var value = '<select name="' + name + '">';
		jQuery.each( er_both_params.resources, function( k, v ) {
			var selected = '';
			if ( sel && sel == k ) {
				selected = 'selected="selected"';
			}
			value += '<option value="' + k + '" ' + selected + '>' + v[ 'post_title' ] + '</option>';
		} );
		return value + '</select>';
	}

	function daysCheckboxes( sel ) {
		if ( !sel ) {
			sel = { value: '' };
		}
		var days = <?php echo wp_json_encode( er_date_get_label( 0, 3 ) ); ?>;
		var value = '';
		var selected_days = false;
		if ( sel[ 'days' ] ) {
			selected_days = sel[ 'days' ].split( ',' );
		}

		jQuery.each( days, function( k, v ) {
			var selected = '';
			if ( ( selected_days && jQuery.inArray( "" + ( k + 1 ), selected_days ) > -1 ) || !selected_days ) {
				selected = 'checked="checked"';
			}
			value += '<label class="wrapper"><input type="checkbox" class="not" value="' + ( k + 1 ) + '" name="day-' + ( k + 1 ) + '" ' + selected + '>' + v + '</label> ';
		} );
		return value;
	}

	function generateDaysCheckboxes() {
		var tag = '';
		jQuery.each( [1, 2, 3, 4, 5, 6, 7], function( k, v ) {
			if ( jQuery( 'input[name="day-' + v + '"]' ).is( ':checked' ) ) {
				tag += v + ',';
			}
		} );
		if ( tag === '' ) {
			return 'days="" ';
		}
		tag = tag.substr( 0, tag.length - 1 );
		if ( tag == '1,2,3,4,5,6,7' ) {
			tag = '';
		}
		if ( tag !== '' ) {
			tag = 'days="' + tag + '" ';
		}

		return tag;
	}

	function customRequired( tag ) {
		var sel = '', checked = '';
		if ( tag && tag[ Object.keys( tag )[ Object.keys( tag ).length - 1 ] ] ) {
			sel = tag[ Object.keys( tag )[ Object.keys( tag ).length - 1 ] ];
		}
		if ( sel == '*' ) {
			checked = ' checked="checked"';
		}
		return '<input type="checkbox" name="*" value="*"' + checked + '> <?php echo addslashes( esc_html__( 'Required', 'easyReservations' ) ); ?><br>';
	}

	function generateTimeSelection( tag ) {
		var value = '<a href="javascript:" onclick="generateTimeOptions()">Add time range</a>';
		if ( tag && tag[ 'range' ] ) {
			var times = tag[ 'range' ].split( ';' );
			jQuery.each( times, function( k, v ) {
				if ( v && v != '' ) {
					var fromto = v.split( '-' );
					value += generateTimeOptions( fromto[ 0 ], fromto[ 1 ], true );
				}
			} );
		} else {
			value += '<span class="timerange"></span>';
		}
		return value;
	}

	function generateTimeOptions( sel, val, doreturn ) {
		var value = '<p style="padding:0;margin:0;" class="timerange"><select class="not" name="range-from[]">';
		jQuery.each(<?php echo wp_json_encode( array(
			'00',
			'01',
			'02',
			'03',
			'04',
			'05',
			'06',
			'07',
			'08',
			'09',
			10,
			11,
			12,
			13,
			14,
			15,
			16,
			17,
			18,
			19,
			20,
			21,
			22,
			23
		) ); ?>, function( k, v ) {
			var selected = '';
			if ( sel && sel == k ) {
				selected = 'selected="selected"';
			}
			value += '<option value="' + k + '" ' + selected + '>' + v + '</option>';
		} );

		if ( !val ) {
			val = '';
		}
		value += '</select> - <select class="not" name="range-to[]">';
		jQuery.each(<?php echo wp_json_encode( array(
			'00',
			'01',
			'02',
			'03',
			'04',
			'05',
			'06',
			'07',
			'08',
			'09',
			10,
			11,
			12,
			13,
			14,
			15,
			16,
			17,
			18,
			19,
			20,
			21,
			22,
			23
		) ); ?>, function( k, v ) {
			var selected = '';
			if ( val && val == k ) {
				selected = 'selected="selected"';
			}
			value += '<option value="' + k + '" ' + selected + '>' + v + '</option>';
		} );

		value += '</select><a href="#" onclick="this.parentNode.parentNode.removeChild(this.parentNode);"  class="dashicons dashicons-no"></a></p>';

		if ( doreturn ) {
			return value;
		} else {
			jQuery( '.timerange:last' ).after( value );
		}
	}

	function addTimeRangeToTag() {
		var codefields = document.getElementsByName( 'range-from[]' );
		var transfields = document.getElementsByName( 'range-to[]' );
		var tag = '';

		if ( codefields.length >= 1 ) {
			for ( var i = 0; i < codefields.length; i++ ) {
				tag += codefields[ i ].value + '-' + transfields[ i ].value + ';';
			}
		}
		if ( tag != '' ) {
			tag = 'range="' + tag + '""';
		}
		return tag;
	}

	var current_form = '<?php echo esc_attr( $current_form_name ); ?>',
		style        = {
			title: '<?php echo addslashes( esc_html__( 'Style', 'easyReservations' ) ); ?>',
			input: 'text'
		},
		title        = {
			title: '<?php echo addslashes( esc_html__( 'Title', 'easyReservations' ) ); ?>',
			input: 'text'
		},
		timerange    = {
			title: '<?php echo addslashes( esc_html__( 'Time range', 'easyReservations' ) ); ?>',
			input: generateTimeSelection
		},
		disabled     = {
			title:   '<?php echo addslashes( esc_html__( 'Disabled', 'easyReservations' ) ); ?>',
			input:   'check',
			default: 'disabled'
		},
		fields       = {
			error:              {
				name:    '<?php echo addslashes( esc_html__( 'Errors', 'easyReservations' ) ); ?>',
				desc:    '<?php echo addslashes( esc_html__( 'Shows the warning messages in form. Is required for the multiple reservations form function.', 'easyReservations' ) ); ?>',
				options: {
					error_title:   {
						title:   '<?php echo addslashes( esc_html__( 'Title', 'easyReservations' ) ); ?>',
						input:   'text',
						default: 'Errors found in the form'
					},
					error_message: {
						title:   '<?php echo addslashes( esc_html__( 'Message', 'easyReservations' ) ); ?>',
						input:   'textarea',
						default: 'There is a problem with the form, please check and correct the following:'
					},
					style:         style,
					title:         title
				}
			},
			"date":             {
				name:    '<?php echo addslashes( esc_html__( 'Date', 'easyReservations' ) ); ?>',
				desc:    '<?php echo addslashes( esc_html__( 'For resources with slots and resources that get reserved on a daily basis.', 'easyReservations' ) ); ?>',
				options: {
					departure:       {
						title:   '<?php echo addslashes( esc_html__( 'Departure is selectable', 'easyReservations' ) ); ?>',
						input:   'check',
						checked: 'true',
						default: 'true'
					},
					time:            {
						title:   '<?php echo addslashes( esc_html__( 'Time is selectable', 'easyReservations' ) ); ?>',
						input:   'check',
						checked: 'true',
						default: 'true'
					},
					price:            {
						title:   '<?php echo addslashes( esc_html__( 'Display price', 'easyReservations' ) ); ?>',
						input:   'check',
						checked: 'true',
						default: 'true'
					},
					resource:        {
						title: '<?php echo addslashes( esc_html__( 'Default resource', 'easyReservations' ) ); ?>',
						input: resourceSelect
					},
					arrivalHour:     {
						title:   '<?php echo addslashes( esc_html__( 'Default arrival hour', 'easyReservations' ) ); ?>',
						input:   'select',
						options: '0-23',
						default: '12'
					},
					arrivalMinute:   {
						title:   '<?php echo addslashes( esc_html__( 'Default arrival minute', 'easyReservations' ) ); ?>',
						input:   'select',
						options: '0-59',
						default: '0'
					},
					departureHour:   {
						title:   '<?php echo addslashes( esc_html__( 'Default departure hour', 'easyReservations' ) ); ?>',
						input:   'select',
						options: '0-23',
						default: '12'
					},
					departureMinute: {
						title:   '<?php echo addslashes( esc_html__( 'Default departure minute', 'easyReservations' ) ); ?>',
						input:   'select',
						options: '0-59',
						default: '0'
					},
				}
			},
			"date-from":        {
				name:     '<?php echo addslashes( esc_html__( 'Arrival date', 'easyReservations' ) ); ?>',
				desc:     '<?php echo addslashes( esc_html__( 'Is required in any form.', 'easyReservations' ) ); ?>',
				generate: generateDaysCheckboxes,
				options:  {
					value:    {
						title:   '<?php echo addslashes( esc_html__( 'Value', 'easyReservations' ) ); ?>',
						input:   'text',
						default: '+14'
					},
					days:     {
						title: '<?php echo addslashes( esc_html__( 'Selectable days', 'easyReservations' ) ); ?>',
						input: daysCheckboxes
					},
					min:      {
						title:   '<?php echo addslashes( esc_html__( 'Earliest selectable date in days (0=now)', 'easyReservations' ) ); ?>',
						input:   'amount',
						default: '0'
					},
					max:      {
						title:   '<?php echo addslashes( esc_html__( 'Latest selectable date in days (0=endless)', 'easyReservations' ) ); ?>',
						input:   'amount',
						default: '0'
					},
					style:    style,
					title:    title,
					disabled: disabled
				}
			},
			"date-to":          {
				name:    '<?php echo addslashes( esc_html__( 'Departure date', 'easyReservations' ) ); ?>',
				desc:    '<?php echo addslashes( esc_html__( 'Field with datepicker for the departure date. Can be replaced by billing units selection or deleted so that every reservation lasts one billing unit.', 'easyReservations' ) ); ?>',
				options: {
					value:    {
						title:   '<?php echo addslashes( esc_html__( 'Value', 'easyReservations' ) ); ?>',
						input:   'text',
						default: '+21'
					},
					style:    style,
					title:    title,
					disabled: disabled
				}
			},
			"arrival_hour":     {
				name:     '<?php echo addslashes( esc_html__( 'Arrival hour', 'easyReservations' ) ); ?>',
				desc:     '<?php echo addslashes( esc_html__( 'Select for arrival hour. Can be replaced by a hidden field and defaults to 12:00 if not in form.', 'easyReservations' ) ); ?>',
				generate: addTimeRangeToTag,
				options:  {
					value:    {
						title:   '<?php echo addslashes( esc_html__( 'Selected', 'easyReservations' ) ); ?>',
						input:   'select',
						options: '0-23',
						default: '12'
					},
					range:    timerange,
					style:    style,
					title:    title,
					disabled: disabled
				}
			},
			"departure_hour":   {
				name:     '<?php echo addslashes( esc_html__( 'Departure hour', 'easyReservations' ) ); ?>',
				desc:     '<?php echo addslashes( esc_html__( 'Select for departure hour. Can be replaced by a hidden field and defaults to 12:00 if not in form.', 'easyReservations' ) ); ?>',
				generate: addTimeRangeToTag,
				options:  {
					value:    {
						title:   '<?php echo addslashes( esc_html__( 'Selected', 'easyReservations' ) ); ?>',
						input:   'select',
						options: '0-23',
						default: '12'
					},
					range:    timerange,
					style:    style,
					title:    title,
					disabled: disabled
				}
			},
			"arrival_minute":   {
				name:    '<?php echo addslashes( esc_html__( 'Arrival minute', 'easyReservations' ) ); ?>',
				desc:    '<?php echo addslashes( esc_html__( 'Select for arrival minute.', 'easyReservations' ) ); ?>',
				options: {
					value:     {
						title:   '<?php echo addslashes( esc_html__( 'Selected', 'easyReservations' ) ); ?>',
						input:   'select',
						options: '0-59',
						default: '0'
					},
					increment: {
						title:   '<?php echo addslashes( esc_html__( 'Increment', 'easyReservations' ) ); ?>',
						input:   'select',
						options: { 1: '1', 5: '5', 10: '10', 15: '15', 30: '30' },
						default: '1'
					},
					style:     style,
					title:     title,
					disabled:  disabled
				}
			},
			"departure_minute": {
				name:    '<?php echo addslashes( esc_html__( 'Departure minute', 'easyReservations' ) ); ?>',
				desc:    '<?php echo addslashes( esc_html__( 'Select for departure minute.', 'easyReservations' ) ); ?>',
				options: {
					value:     {
						title:   '<?php echo addslashes( esc_html__( 'Selected', 'easyReservations' ) ); ?>',
						input:   'select',
						options: '0-59',
						default: '0'
					},
					increment: {
						title:   '<?php echo addslashes( esc_html__( 'Increment', 'easyReservations' ) ); ?>',
						input:   'select',
						options: { 1: '1', 5: '5', 10: '10', 15: '15', 30: '30' },
						default: '1'
					},
					style:     style,
					title:     title,
					disabled:  disabled
				}
			},
			units:              {
				name:    '<?php echo addslashes( ucfirst( esc_html__( 'billing units', 'easyReservations' ) ) ); ?>',
				desc:    '<?php echo addslashes( esc_html__( 'Select of billing units to define the duration of stay. Can be replaced by departure date field or defaults to one billing unit if not in form.', 'easyReservations' ) ); ?>',
				options: {
					1:        {
						title:   '<?php echo addslashes( esc_html__( 'Min', 'easyReservations' ) ); ?>',
						input:   'select',
						options: '1-100',
						default: '1'
					},
					2:        {
						title:   '<?php echo addslashes( esc_html__( 'Max', 'easyReservations' ) ); ?>',
						input:   'select',
						options: '1-100',
						default: '10'
					},
					value:    {
						title:   '<?php echo addslashes( esc_html__( 'Selected', 'easyReservations' ) ); ?>',
						input:   'select',
						options: '1-100',
						default: '7'
					},
					style:    style,
					title:    title,
					disabled: disabled
				}
			},
			resources:          {
				name:    '<?php echo addslashes( esc_html__( 'Resources', 'easyReservations' ) ); ?>',
				desc:    '<?php echo addslashes( esc_html__( 'Select of resources. Is required and can only be replaced by hidden field. You can exclude or include resources by entering comma separated IDs.', 'easyReservations' ) ); ?>',
				options: {
					value:    {
						title: '<?php echo addslashes( esc_html__( 'Selected', 'easyReservations' ) ); ?>',
						input: resourceSelect
					},
					exclude:  {
						title:   '<?php echo addslashes( esc_html__( 'Exclude', 'easyReservations' ) ); ?>',
						input:   'text',
						default: ''
					},
					include:  {
						title:   '<?php echo addslashes( esc_html__( 'Include', 'easyReservations' ) ); ?>',
						input:   'text',
						default: ''
					},
					style:    style,
					title:    title,
					disabled: disabled
				}
			},
			adults:             {
				name:    '<?php echo addslashes( esc_html__( 'Adults', 'easyReservations' ) ); ?>',
				desc:    '<?php echo addslashes( esc_html__( 'Select of adults. Is required and can only be replaced by hidden field.', 'easyReservations' ) ); ?>',
				options: {
					1:        {
						title:   '<?php echo addslashes( esc_html__( 'Min', 'easyReservations' ) ); ?>',
						input:   'select',
						options: '1-100',
						default: '1'
					},
					2:        {
						title:   '<?php echo addslashes( esc_html__( 'Max', 'easyReservations' ) ); ?>',
						input:   'select',
						options: '1-100',
						default: '10'
					},
					value:    {
						title:   '<?php echo addslashes( esc_html__( 'Selected', 'easyReservations' ) ); ?>',
						input:   'select',
						options: '1-100',
						default: '3'
					},
					style:    style,
					title:    title,
					disabled: disabled
				}
			},
			children:           {
				name:    '<?php echo addslashes( esc_html__( 'Children', 'easyReservations' ) ); ?>',
				desc:    '<?php echo addslashes( esc_html__( 'Select of children. Can be replaced by hidden field or deleted.', 'easyReservations' ) ); ?>',
				options: {
					1:        {
						title:   '<?php echo addslashes( esc_html__( 'Min', 'easyReservations' ) ); ?>',
						input:   'select',
						options: '0-100',
						default: '0'
					},
					2:        {
						title:   '<?php echo addslashes( esc_html__( 'Max', 'easyReservations' ) ); ?>',
						input:   'select',
						options: '1-100',
						default: '10'
					},
					value:    {
						title:   '<?php echo addslashes( esc_html__( 'Selected', 'easyReservations' ) ); ?>',
						input:   'select',
						options: '0-100',
						default: '0'
					},
					style:    style,
					title:    title,
					disabled: disabled
				}
			},
			hidden:             {
				name:    '<?php echo addslashes( esc_html__( 'Hidden', 'easyReservations' ) ); ?>',
				desc:    '<?php echo addslashes( esc_html__( 'Date and information fields can be replaced by hidden fields to force the selection without the guest choosing or seeing it. They are helpful for special offers or forms for just one resource.', 'easyReservations' ) ); ?>',
				options: generateHiddenOptions
			},
			"show_price":       {
				name:    '<?php echo addslashes( esc_html__( 'Display price live', 'easyReservations' ) ); ?>',
				desc:    '<?php echo addslashes( esc_html__( 'Display price as of current selection.', 'easyReservations' ) ); ?>',
				options: {
					before: {
						title:   '<?php echo addslashes( esc_html__( 'Text before price', 'easyReservations' ) ); ?>',
						input:   'text',
						default: 'Price:'
					},
					style:  style,
					title:  title
				}
			},
			submit:             {
				name:    '<?php echo addslashes( esc_html__( 'Submit', 'easyReservations' ) ); ?>',
				desc:    '<?php echo addslashes( esc_html__( 'Button to submit the form', 'easyReservations' ) ); ?>',
				options: {
					value: {
						title:   '<?php echo addslashes( esc_html__( 'Value', 'easyReservations' ) ); ?>',
						input:   'text',
						default: 'Submit'
					},
					style: style,
					title: title
				}
			},
			custom:             {
				name:     '<?php echo addslashes( esc_html__( 'Custom', 'easyReservations' ) ); ?>',
				desc:     '<?php echo addslashes( sprintf( esc_html__( 'Can be any form element, can have an impact on the price and are used to get more information. Define them %s first', 'easyReservations' ), '<a href="' . admin_url( 'admin.php?page=er-settings&tab=custom' ) . '">here</a>' ) ); ?>',
				checkout: 'both',
				options:  {
					id:    {
						title: '<?php echo addslashes( esc_html__( 'Select field', 'easyReservations' ) ); ?>',
						input: 'select',
						options: <?php echo wp_json_encode( isset( $custom_fields_array ) ? $custom_fields_array : array() ); ?>
					},
					style: style,
					title: title
				}
			}
		};
	jQuery( document ).ready( function( $ ) {
		$.each( fields, function( k, v ) {
			var remove = false;
			if ( v[ 'checkout' ] ) {
				if ( current_form !== 'checkout' && v[ 'checkout' ] !== 'both' ) {
					remove = true;
				}
			} else {
				if ( current_form === 'checkout' ) {
					remove = true;
				}
			}
			if ( remove ) {
				jQuery( 'tr[attr=' + k + ']' ).remove();
			}
		} );
		$( 'table.formtable tbody' ).each( function( _, e ) {
			if ( $( e ).children().length === 0 ) {
				var ele = $( e ).parent().parent();
				ele.prev().remove();
				ele.remove();
			}
		} );
		$( '#accordion' ).accordion( {
			heightStyle: "content",
			autoHeight:  false,
			icons:       { "header": "ui-icon-plus", "activeHeader": "ui-icon-minus" }
		} );
	} );

	<?php do_action( 'easyreservations_form_settings_js' ); ?>
</script>