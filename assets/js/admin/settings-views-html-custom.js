var optTempID = 1,
	ifTempID = 1,
	first = true,
	added = {};

function custom_edit( id ) {
	if ( htmlSettingsCustomLocalizeScript.all_custom_fields && htmlSettingsCustomLocalizeScript.all_custom_fields[ id ] ) {
		var field = htmlSettingsCustomLocalizeScript.all_custom_fields[ id ];
		jQuery( '#custom_name' ).val( field[ 'title' ] );
		if ( field[ 'price' ] ) {
			jQuery( '#custom_price_field' ).prop( 'checked', 'checked' );
		}
		custom_type_select( field[ 'type' ] );
		custom_field_extras( field );
		custom_field_value( field );
		jQuery( '*[id^=clauses_sortable]' ).sortable();
		jQuery( '#options_sortable' ).sortable();
		jQuery( '#mainform' ).append( '<input type="hidden" name="custom_id" id="custom_id" value="' + id + '">' );
	}
}

function custom_type_select( sel ) {
	var options = {};
	options[ 'x' ] = '--';
	if ( ! sel ) {
		sel = jQuery( '#custom_field_type' ).val();
	}
	if ( ! jQuery( '#custom_price_field' ).prop( 'checked' ) ) {
		options[ 'text' ] = 'Text field';
		options[ 'area' ] = 'Text area';
	}
	options[ 'slider' ] = 'Slider';
	options[ 'number' ] = 'Number';
	options[ 'check' ] = 'Checkbox';
	options[ 'select' ] = 'Select field';
	options[ 'radio' ] = 'Radio buttons';
	jQuery( '#custom_field_type' ).html( generateOptions( options, sel ) );
}

function custom_field_extras( sel ) {
	var value = '';
	if ( sel ) {
		value = sel[ "unused" ];
	}
	var html = '<label class="in-hierarchy" title="Content in emails if custom field wasn\'t chosen." style="margin-bottom:4px;width:104px;display: inline-block;">Unused </label><input type="text" name="custom_field_unused" value="' + value + '">';
	var selected = '';
	if ( sel && sel[ 'required' ] ) {
		selected = ' checked="checked"';
	}
	html += '<p><label class="wrapper"><input type="checkbox" id="custom_field_required" name="custom_field_required"' + selected + '>Required</label></p>';
	selected = '';
	if ( sel && sel[ 'admin' ] ) {
		selected = ' checked="checked"';
	}
	//if(jQuery('#custom_price_field').prop('checked') )
	jQuery( '#custom_field_extras' ).html( html );
}

function custom_field_value( sel ) {
	var type = jQuery( '#custom_field_type' ).val();
	jQuery( '#custom_value_tr' ).remove();
	if ( type !== 'x' ) {
		var options = '',
			value = '';
		if ( type == 'text' || type == 'area' ) {
			if ( ! sel ) {
				sel = new Array();
				sel[ "value" ] = "";
			}
			options = '<ul><li class="sortable">Value <input type="text" name="custom_field_value" value="' + sel[ "value" ] + '"></li></ul>';
		} else {
			if ( type == 'select' || type == 'radio' || type == 'check' ) {
				options += '<strong>Options</strong> <a id="add_new_custom" onclick="add_new_option()" style="font-size:14px;" class="dashicons dashicons-plus-alt"></a>';
			}
			options += '<ul id="options_sortable">';
			options += custom_generate_option( sel );
			options += '</ul>';
		}
		jQuery( '#custom_type_tr' ).after( '<tr id="custom_value_tr"><td colspan="2">' + options + '</td></tr>' );
		jQuery( document.body ).trigger( 'init_tooltips' );
	}
}

function custom_generate_option( sel ) {
	if ( ! sel ) {
		var sel = new Object();
		sel[ "type" ] = jQuery( '#custom_field_type' ).val();
		sel[ 'options' ] = new Object();
		sel[ 'options' ][ optTempID ] = new Object();
		sel[ 'options' ][ optTempID ][ "value" ] = "";
		if ( jQuery( '#custom_price_field' ).prop( 'checked' ) ) {
			sel[ 'options' ][ optTempID ][ "price" ] = 100;
		}
		if ( sel[ "type" ] == 'check' || sel[ "type" ] == 'check' ) {
			sel[ 'options' ][ optTempID ][ "checked" ] = false;
		}
	}
	var options = '';
	for ( var k in sel[ 'options' ] ) {
		var v = sel[ 'options' ][ k ];
		options += '<li class="sortable" id="option_' + k + '">';
		if ( sel[ 'type' ] == 'check' || sel[ 'type' ] == 'select' || sel[ 'type' ] == 'radio' ) {
			options += '<a style="float:right" onclick="delete_option(this)" class="dashicons dashicons-dismiss"></a>';
		}
		options += '<input type="hidden" name="id[]" value="' + k + '">';

		if ( sel[ 'type' ] == 'slider' ) {
			options += '<label class="in-hierarchy">Value</label><input type="number" name="value[]" value="' + v[ "value" ] + '"><br>';
			options += '<label class="in-hierarchy">Min</label><input type="number" name="min[]" value="' + v[ "min" ] + '"><br>';
			options += '<label class="in-hierarchy">Max</label><input type="number" name="max[]" value="' + v[ "max" ] + '"><br>';
			options += '<label class="in-hierarchy">Label</label><input type="text" name="label[]" value="' + v[ "label" ] + '"><br>';
			options += '<label class="in-hierarchy">Step</label><input type="number" name="step[]" value="' + v[ "step" ] + '">';
		} else {
			options += '<label class="in-hierarchy">Value</label><input type="text" name="value[]" value="' + v[ "value" ] + '">';
		}
		var checked = '';
		if ( jQuery( '#custom_price_field' ).is( ':checked' ) && ( sel[ 'type' ] == 'number' || sel[ 'type' ] == 'slider' ) ) {
			if ( v[ 'mode' ] ) {
				checked = ' checked="checked"';
			}
			options += '<br><label class="wrapper" style="margin-left:14px"><input type="checkbox" name="number-price[]" class="er_input_price" ' + checked + ' value="1">Multiply this options price by fields value </label>';
		} else if ( sel[ 'type' ] == 'check' || sel[ 'type' ] == 'select' || sel[ 'type' ] == 'radio' ) {
			if ( v[ 'checked' ] ) {
				checked = ' checked="checked"';
			}
			options += '<br><label class="in-hierarchy">Checked</label><label class="wrapper"><input type="checkbox" name="checked[]"' + checked + ' onchange="check_checkboxes(this)" value="1"></label>';
		} else {
			options += '<input type="hidden" name="checked[]" value="0">';
		}
		if ( jQuery( '#custom_price_field' ).is( ':checked' ) ) {
			options += '<br><label class="in-hierarchy">Price</label><span class="input-wrapper"><input type="text" name="price[]" class="er_input_price" value="' + v[ "price" ] + '" style="width:50px;text-align:right"><span class="input-box">' + htmlSettingsCustomLocalizeScript.currency + '</span></span>';
			options += '<br><strong>Conditions' + '</strong> <a id="add_if_clause" onclick="add_if_clause(\'' + k + '\');" style="font-size: 14px;" class="dashicons dashicons-plus-alt"></a><ul id="clauses_sortable' + k + '">';
			if ( v[ 'clauses' ] ) {
				for ( var clause in v[ 'clauses' ] ) {
					options += add_if_clause( k, v[ 'clauses' ][ clause ] );
				}
			}
			options += '</ul>';
		}
		options += '</li>';
	}
	return options;
}

function add_if_clause( id, sel ) {
	if ( sel ) {
		var c = sel;
	} else {
		var c = { price: '', cond: 1, operator: 'equal', type: 'units', mult: 'x' };
	}
	var clause = '<li class="sortable clause" id="if_clause_' + id + '_' + ifTempID + '">';
	clause += '<a style="float:right" onclick="delete_option(this)" class="dashicons dashicons-dismiss"></a>';
	clause += 'If <span class="select"><select name="if_cond_type[]" onchange="change_operator(this);">';
	var condition_types = {
		units: "Billing units",
		resource: "Resource",
		adult: "Adults",
		child: "Children",
		arrival: "Arrival",
		departure: "Departure",
		time: "Current date",
		arrival_every: "Arrival w/o year",
		departure_every: "Departure w/o year",
		time_every: "Current date w/o year",
		field: "Custom price field"
	};
	var type = jQuery( '#custom_field_type' ).val();
	if ( type == 'slider' || type == 'number' ) {
		condition_types[ 'value' ] = 'This field\'s value';
	}
	clause += generateOptions( condition_types, c[ 'type' ] );
	clause += '</select></span>';
	var selection = c[ 'price' ];
	clause += generate_if_clause_condition( c );
	if ( ! isNaN( parseFloat( c[ 'price' ] ) ) && isFinite( c[ 'price' ] ) ) {
		selection = 'price';
	}
	clause += '<span class="select"><select name="if_cond_happens[]" onchange="clause_happens_select(jQuery(this).val(),\'' + id + '\', ' + ifTempID + ', true)">' + generateOptions( {
		x: "--",
		and: "AND",
		or: "OR",
		price: "THEN"
	}, selection ) + '</select></span>';
	clause += clause_happens_select( c[ 'price' ], id, ifTempID, false, c[ 'mult' ] );
	clause += '<input type="hidden" name="if_option[]" value="' + id + '"></li>';
	ifTempID++;
	if ( ! sel ) {
		jQuery( '#clauses_sortable' + id ).append( clause ).sortable();
	} else {
		return clause;
	}
}

function generate_if_clause_condition( sel ) {
	var clause = '';
	if ( sel[ 'type' ] == 'field' ) {
		var select = generate_customs_select( sel[ 'operator' ] );
		if ( ! select ) {
			clause += '<b name="if_cond[]">Add price fields first</b>';
		} else {
			clause += ' <span class="select delete"><select name="if_cond_operator[]" onchange="change_condition_options(this)" class="tips" data-tip="Other price field that has to be selected">' + select[ 0 ] + '</select></span><span class="delete"> and option </span>';
			clause += ' <span class="select delete"><select name="if_cond[]" class="tips" data-tip="Price fields option that has to be selected">' + generate_customs_options_select( select[ 1 ], sel[ 'cond' ] ) + '</select></span><span class="delete"> are selected </span>';
		}
	} else if ( sel[ 'type' ] == 'resource' ) {
		clause += '<span class="select delete"><select name="if_cond_operator[]">' + generateOptions( {
			equal: "=",
			notequal: "!="
		}, sel[ 'operator' ] ) + '</select></span> ';
		clause += '<span class="select delete"><select name="if_cond[]" class="tips" data-tip="Resource that has to be selected">' + generateOptions( htmlSettingsCustomLocalizeScript.resources, sel[ 'cond' ] ) + '</select></span>';
	} else {
		clause += '<span class="select delete"><select name="if_cond_operator[]">' + generateOptions( {
			equal: "=",
			notequal: "!=",
			greater: ">",
			greaterequal: ">=",
			smaller: "<",
			smallerequal: "<="
		}, sel[ 'operator' ] ) + '</select></span> ';
		if ( sel[ 'type' ] == 'arrival' || sel[ 'type' ] == 'departure' || sel[ 'time' ] == 'time' ) {
			clause += '<input type="text" name="if_cond[]" onclick="generate_datepicker(this);" class="er-datepicker" value="' + sel[ 'cond' ] + '" style="width:100px;text-align:center"> ';
		} else if ( sel[ 'type' ] == 'arrival_every' || sel[ 'type' ] == 'departure_every' || sel[ 'type' ] == 'time_every' ) {
			clause += '<input type="text" name="if_cond[]" onclick="generate_datepicker(this);" data-format="mm.dd" class="er-datepicker" value="' + sel[ 'cond' ] + '" style="width:100px;text-align:center"> ';
		} else {
			clause += '<input type="text" name="if_cond[]" class="tips" data-tip="Number that has to be matched" value="' + sel[ 'cond' ] + '" style="width:50px;text-align:center"> ';
		}
	}
	return clause;
}

function generate_customs_select( sel ) {
	var options = {};
	for ( var key in htmlSettingsCustomLocalizeScript.all_custom_fields ) {
		if ( htmlSettingsCustomLocalizeScript.all_custom_fields[ key ][ 'price' ] ) {
			if ( ! sel || ( isNaN( parseFloat( sel ) ) && ! isFinite( sel ) ) ) {
				sel = key;
			}
			options[ key ] = htmlSettingsCustomLocalizeScript.all_custom_fields[ key ][ 'title' ];
		}
	}
	if ( options == {} ) {
		return false;
	} else {
		return [ generateOptions( options, sel ), sel ];
	}
}

function generate_customs_options_select( id, sel ) {
	var options = {};
	options[ 'any' ] = 'Any';
	if ( htmlSettingsCustomLocalizeScript.all_custom_fields[ id ] ) {
		for ( var key in htmlSettingsCustomLocalizeScript.all_custom_fields[ id ][ 'options' ] ) {
			options[ key ] = htmlSettingsCustomLocalizeScript.all_custom_fields[ id ][ 'options' ][ key ][ 'value' ];
		}
	}
	return generateOptions( options, sel );
}

function clause_happens_select( sel, opt_id, clause_id, append, mult ) {
	jQuery( '#if_clause_amount_' + opt_id + '_' + clause_id + ',#if_clause_mult_' + opt_id + '_' + clause_id + ',#delete_' + opt_id + '_' + clause_id ).remove();
	var content = '';
	if ( sel == 'price' || jQuery.isNumeric( sel ) ) {
		if ( sel == 'price' ) {
			sel = 0;
		}
		content += '<span class="delete" id="delete_' + opt_id + '_' + clause_id + '">';
		content += '<span class="input-wrapper delete"><input type="text" name="if_cond_amount[]" id="if_clause_amount_' + opt_id + '_' + clause_id + '" class="er_input_price" value="' + sel + '" style="width:55px;text-align:right"><span class="input-box">' + htmlSettingsCustomLocalizeScript.currency + '</span></span> per ';
		content += '<span class="select delete"><select name="if_cond_mult[]" id="if_clause_mult_' + opt_id + '_' + clause_id + '">' + generateOptions( {
			x: "--",
			price_pers: "Person",
			price_adul: "Adult",
			price_child: "Children",
			price_halfhour: "Half-hour",
			price_hour: "Hour",
			price_realday: "Day",
			price_night: "Night",
			price_week: "Week",
			price_month: "Month",
			price_day: "Billing unit",
			price_both: "Unit and person",
			price_day_adult: "Unit and adult",
			price_day_child: "Unit and children"
		}, mult ) + '</select></span></span>';
	} else if ( sel !== '' && sel !== 'x' ) {
		content = '<input type="hidden" name="if_cond_amount[]" id="if_clause_amount_' + opt_id + '_' + clause_id + '" class="er_input_price" value="0">';
		content += '<input type="hidden" name="if_cond_mult[]" id="if_clause_mult_' + opt_id + '_' + clause_id + '" value="x">';
		if ( append ) {
			if ( ! added[ opt_id + clause_id ] ) {
				add_if_clause( opt_id, false );
			}
			added[ opt_id + clause_id ] = 1;
		}
	}
	if ( append ) {
		jQuery( '#if_clause_' + opt_id + '_' + clause_id ).append( content );
	} else {
		return content;
	}
}

function add_new_option() {
	optTempID++;
	var html = custom_generate_option( false );
	jQuery( '#options_sortable' ).append( html ).sortable();
}

function delete_option( e ) {
	jQuery( e ).parent().remove();
}

custom_type_select( false );
custom_field_extras();

function generate_datepicker( e ) {
	jQuery( document ).trigger( 'er-init-datepicker' );
	jQuery( e ).datepicker( 'show' );
}

function change_operator( e ) {
	e = jQuery( e ).parent().parent();
	var operator = generate_if_clause_condition( {
		price: '',
		cond: '',
		operator: 'equal',
		type: e.find( '*[name="if_cond_type[]"]' ).val()
	} );
	e.find( '*[name="if_cond[]"], span.delete' ).remove();
	e.find( '*[name="if_cond_happens[]"]' ).prop( "selectedIndex", 0 );
	e.find( '*[name="if_cond_type[]"]' ).parent().after( operator );
	jQuery( document.body ).trigger( 'init_tooltips' );
}

function change_condition_options( e ) {
	e = jQuery( e ).parent();
	e.find( '*[name="if_cond[]"]' ).html( generate_customs_options_select( e.find( '*[name="if_cond_operator[]"]' ).val() ) );
}

function check_checkboxes( e ) {
	jQuery( 'input[type=checkbox][name="checked[]"]:checked' ).each( function( box ) {
		if ( e !== this ) {
			jQuery( this ).prop( "checked", false );
		}
	} );
}

jQuery( '#custom_field_type' ).on( 'change', function() {
	custom_field_value( false )
} );

jQuery( '#custom_price_field' ).on( 'click', function() {
	custom_type_select( false );
	custom_field_extras( false );
	custom_generate_option( false );
	custom_field_value( false );
} );

jQuery( '#custom_cancel' ).on( 'click', function() {
	jQuery( '#custom_id' ).remove();
} );

jQuery( '#mainform' ).on( 'submit', function() {
	jQuery( 'input[type=checkbox][name="checked[]"]:not(:checked)' ).prop( "value", "0" ).prop( "type", "hidden" );
} );