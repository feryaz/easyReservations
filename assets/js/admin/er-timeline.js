( function ( $, data ) {
	var tooltip                 = $( '.er-timeline-tooltip' ),
		timeline_container      = $( '.er-timeline' ),
		datepicker              = $( '#timeline-datepicker' ),
		sidebar                	= timeline_container.find( 'div.sidebar' ),
		timeline                = timeline_container.find( 'div.timeline' ),
		header                  = timeline_container.find( 'div.header' ),
		resources_tbody               = timeline_container.find( 'div.resources table tbody' ),
		header_date             = header.find( '.date' ),
		table                   = timeline.find( 'table' ),
		thead_main              = table.find( 'thead.main tr' ),
		thead                   = table.find( 'thead:not(.main)' ),
		tbody                   = table.find( 'tbody' ),
		reservations            = [], //Reservation data
		selected                = false, //Current time
		today                   = new Date(), //Current time
		start                   = new Date(), //Date of the first column in timeline
		end                     = false, //Date of the last column in timeline
		drag_start_position     = false, //Dragged reservations starting position
		drag_start_offset       = false, //Dragged reservations starting offset
		drag_snap_top           = false, //Tells the draggable reservation what top to snap to
		changed_any_reservation = false, //If any other reservations got changed while attempting to draw a reservation
		mouse_pos_x             = 0, //Mouse position x gets updated while hovering timeline
		mouse_pos_y             = 0, //Mouse position y gets updated while hovering timeline
		snapping_enabled        = true, //If the snapping mode is enabled for dragging and resizing
		scroll_drag             = false, //position from which scroll was started in timeline header
		scroll_action           = false, //js interval that scrolls timeline
		scroll_add              = false, //js interval that adds new calendar columns based on scroll
		cell_dimensions         = { height: 32, width: 96 },
		last_hover              = 0, //The DOM element that got hovered as last in timeline
		last_query_start        = 0, //From when we last queried data at the start of the timeline
		last_query_end          = 0, //Until when we last queried data at the end of the timeline
		interval                = data.default_interval; //Interval of timeline;

	timeline_container.insertAfter( 'hr.wp-header-end' );
	tooltip.insertAfter( 'hr.wp-header-end' );
	sidebar.hide();

	if( interval === "86400" ){
		today.setHours( 0, 0, 0, 0 );
		header.find( '.daily' ).addClass( 'active' );
	} else {
		today.setHours( today.getHours(), 0, 0, 0 );
		header.find( '.hourly' ).addClass( 'active' );
	}

	header
		.on( 'click', '.expand-sidebar', function () {
			sidebar.addClass('expanded').show();
			$(this)
				.removeClass('expand-sidebar')
				.addClass('contract-sidebar');
		} )
		.on( 'click', '.contract-sidebar', function () {
			sidebar.removeClass( 'expanded' ).hide( 300, 'linear');
			$(this)
				.removeClass('contract-sidebar')
				.addClass('expand-sidebar');
		} )
		.on( 'click', '.hourly', function () {
			if( !$(this).hasClass('active') ){
				header.find('.daily').removeClass('active');
				$( this ).addClass( 'active' );
				start = new Date( selected.getTime() );
				interval = "3600";
				er_timeline.init();
			}
		} )
		.on( 'click', '.daily', function () {
			if( !$(this).hasClass('active') ){
				header.find('.hourly').removeClass('active');
				$( this ).addClass( 'active' );
				start = new Date( selected.getTime() );
				interval = "86400";
				er_timeline.init();
			}
		} )
		.on( 'click', '.date', function () {
			er_sidebar.toggle();
		} )
		.on( 'click', '.today', function () {
			er_timeline.jump_to_date( today );
		} );

	datepicker.bind( 'change', function (e) {
		er_timeline.jump_to_date( $( this ).datepicker( "getDate" ) );
	} );

	thead_main
		.on( 'mousedown', 'th', function ( e ) {
			scroll_drag = timeline.scrollLeft() + e.pageX;
		} );

	$( window )
		.mouseup( function () {
			thead_main.css( 'cursor', 'grab' );
			clearInterval( scroll_action );
			scroll_action = false;
			scroll_drag = false;
			er_timeline.clear_scroll_timeout();
		} );

	timeline_container
		.mousemove( function ( e ) {
			mouse_pos_x = e.pageX;
			mouse_pos_y = e.pageY;
			if ( scroll_drag && e.which === 1 ) {
				timeline.scrollLeft( scroll_drag - e.pageX < 1 ? 1 : scroll_drag - e.pageX );
				er_timeline.set_current_date();

				if ( scroll_add === false ) {
					thead_main.css( 'cursor', 'grabbing' );
					er_timeline.start_scroll_add_interval();
				}
			}

			if ( last_hover !== e.target ) {
				last_hover = e.target;
				if ( last_hover.getAttribute( "data-date" ) ) {
					er_timeline.highlight_current( new Date( parseInt( e.target.getAttribute( "data-date" ), 10 ) ) );
				}

				//if( e.target.getAttribute( "data-space" ) ) console.log($(e.target).data('reservations'));
				if ( last_hover.getAttribute( "data-space" ) ) {
					drag_snap_top = $( last_hover ).offset().top;
				} else if( last_hover.getAttribute( "data-id" ) ) {
					drag_snap_top = $( last_hover ).parent().offset().top;
				}
			}
		} )
		.mouseleave( function () {
			thead_main.css( 'cursor', 'grab' );
			clearInterval( scroll_action );
			scroll_action = false;
			scroll_drag = false;
			er_timeline.clear_scroll_timeout();
		} )
		.on( 'mousedown', '.next', function () {
			if ( scroll_action === false ) {
				er_timeline.add_new_column();
				er_timeline.set_current_date();

				scroll_action = setInterval( function () {
					er_timeline.add_new_column();
					er_timeline.set_current_date();
				}, 100 );
			}
		} )
		.on( 'mousedown', '.prev', function () {
			if ( scroll_action === false ) {
				er_timeline.add_new_column( true );
				er_timeline.set_current_date();

				scroll_action = setInterval( function () {
						er_timeline.add_new_column( true );
						er_timeline.set_current_date();
				}, 100 );
			}
		} )
		.on( 'click', '.resource-handler', function () {
			var elem        = $( this ).parent().parent().parent().next(),
				tbody_index = elem.index() / 2 - 1;

			if( $(this).hasClass('retracted') ){
				$( this ).removeClass('retracted');

				$( tbody[ tbody_index ] ).show();
				elem.show();
			} else {
				$( this ).addClass( 'retracted' );

				$(tbody[ tbody_index ]).hide();
				elem.hide();
			}
		} )
		.on( 'click', '.reservation', function () {
			timeline.find( '.reservation.selected' ).removeClass( 'selected' );
			$( this ).addClass( 'selected' );
		} );

	timeline.scroll( function () {

		if ( scroll_action !== false ) {
			thead_main.css( 'cursor', 'grabbing' );
			//er_timeline.start_scroll_add_interval();
		}
	} );

	var er_sidebar = {
		is_open: function(){
			return sidebar.hasClass('expanded');
		},
		open: function(){
			if( !er_sidebar.is_open() ){
				header.find('.expand-sidebar').click();
			}
		},
		close: function(){
			if( er_sidebar.is_open() ){
				header.find('.contract-sidebar').click();
			}
		},
		toggle: function(){
			if( er_sidebar.is_open() ){
				header.find('.contract-sidebar').click();
			} else {
				header.find( '.expand-sidebar' ).click();
			}
		}
	};

	er_sidebar.open();

	var er_timeline = {
		init: function () {
			today = new Date();
			reservations = [];
			selected = false;

			if ( interval === "86400" ) {
				today.setHours( 0, 0, 0, 0 );
				start.setHours( 0, 0, 0, 0 );
			} else {
				today.setHours( today.getHours(), 0, 0, 0 );

				if( today.getDate() === start.getDate() && today.getMonth() === start.getMonth() && today.getFullYear() === start.getFullYear() ){
					start.setHours( today.getHours(), 0, 0, 0 );
				} else {
					start.setHours( 0, 0, 0, 0 );
				}
			}

			table.find( 'td,th' ).remove();

			start = er_timeline.manipulate_date_without_offset( start, ( 15 * interval * 1000 ) * -1 );
			last_query_end = new Date( start.getTime() );
			last_query_start = new Date( start.getTime() );

			for ( var i = 0; i < 50; i++ ) {
				end = er_timeline.manipulate_date_without_offset( start, i * interval * 1000 );
				er_timeline.generate_column( end );
			}

			er_timeline.load_remaining();

			timeline.scrollLeft(
				thead_main.find( 'th:nth-child(15)' ).offset().left - timeline.offset().left + timeline.scrollLeft() + 1
			);

			er_timeline.set_current_date();
		},

		remove_offset: function( date ){
			return new Date( date.getTime() - date.getTimezoneOffset() * 1000 * 60 );
		},

		manipulate_date_without_offset: function( date, amount ){
			var new_date = new Date( date.getTime() + amount );
			return new Date( new_date.getTime() + new_date.getTimezoneOffset() * 1000 * 60 - date.getTimezoneOffset() * 1000 * 60 );
		},

		highlight_current: function ( date ) {
			$( '.er-timeline .hover' ).removeClass( 'hover' );
			$( '*[data-date="' + date.getTime() + '"]' ).addClass( 'hover' );
		},

		set_current_date: function () {
			var currently_selected = er_timeline.manipulate_date_without_offset( start, ( ( timeline.scrollLeft() / cell_dimensions.width + 1 ) * interval * 1000 ) );

			if ( interval === "3600" ) {
				currently_selected.setHours( currently_selected.getHours(), 0, 0, 0 );
			} else {
				currently_selected.setHours( 0, 0, 0, 0 );
			}

			if( !selected || currently_selected.getDate() !== selected.getDate() || currently_selected.getMonth() !== selected.getMonth() || currently_selected.getFullYear() !== selected.getFullYear() ){
				selected = currently_selected;

				if ( interval === "3600" ) {
					header_date.html( selected.getDate() + ' ' + er_date_picker_params.month_names[ selected.getMonth() ] + ' ' + selected.getFullYear() );
				} else {
					header_date.html( er_date_picker_params.month_names[ selected.getMonth() ] + ' ' + selected.getFullYear() );
				}

				thead_main.find( 'th.current' ).removeClass( 'current' );
				thead_main.find( 'th[data-date="' + selected.getTime() + '"]' ).addClass( 'current' );

				datepicker.datepicker( "setDate", selected );
			} else if( interval === "3600" && currently_selected.getHours() !== selected.getHours() ){
				//If only the hour changed in hourly view we don't change the header or datepicker
				selected = currently_selected;

				thead_main.find( 'th.current' ).removeClass( 'current' )
				thead_main.find( 'th[data-date="' + selected.getTime() + '"]' ).addClass( 'current' );
			}
		},

		start_scroll_add_interval: function () {
			if ( scroll_add === false && ( timeline.scrollLeft() < 2 || timeline[ 0 ].scrollWidth - ( timeline.width() + timeline.scrollLeft() ) < 2 ) ) {
				scroll_add = setInterval( function () {
					if ( scroll_add !== false && ( timeline.scrollLeft() < 2 || timeline[ 0 ].scrollWidth - ( timeline.width() + timeline.scrollLeft() ) < 2 ) ) {
						er_timeline.add_new_column( timeline.scrollLeft() < 2 );
						er_timeline.set_current_date();
					}
				}, 38 );
			}
		},

		jump_to_date: function( date ){
			if( date < start || date > end ){
				start = date;
				er_timeline.init();
			} else {
				var days_between = ( selected.getTime() - date.getTime() + date.getTimezoneOffset() * 1000 * 60 - selected.getTimezoneOffset() * 1000 * 60 ) / ( interval * 1000 ),
					abs = Math.abs( days_between );

				for(var i = 1; i <= abs ; i++){
					er_timeline.add_new_column( days_between > 0 );
				}

				er_timeline.set_current_date();
			}
		},

		add_new_column: function ( at_start ) {
			if ( at_start ) {
				start = er_timeline.manipulate_date_without_offset( start, interval * 1000 * -1 );
				er_timeline.generate_column( start, true );

				table.find( 'th:last-child,td:last-child' ).remove();

				last_query_end = er_timeline.manipulate_date_without_offset( last_query_end, interval * 1000 * -1 );
				end = er_timeline.manipulate_date_without_offset( end, interval * 1000 * -1 );
			} else {
				end = er_timeline.manipulate_date_without_offset( end, interval * 1000 );

				er_timeline.generate_column( end );

				table.find( 'th:first-child' ).remove();
				table.find( 'td:first-child' )
					.each( function () {
						var cell_reservations = $( this ).data( 'reservations' );
						if ( cell_reservations && cell_reservations.length > 0 ) {
							$.each( cell_reservations, function ( _, reservation_id ) {
								if ( reservations[ reservation_id ] && typeof reservations[ reservation_id ] !== 'undefined' ) {
									reservations[ reservation_id ].changed = true;
								}
							} );
						}
					} )
					.remove();

				last_query_start = er_timeline.manipulate_date_without_offset( last_query_start, interval * 1000 );
				start = er_timeline.manipulate_date_without_offset( start, interval * 1000 );
			}

			er_timeline.sync_cell_heights();
		},

		clear_scroll_timeout: function () {
			if ( scroll_add !== false ) {
				clearInterval( scroll_add );
				scroll_add = false;
				er_timeline.load_remaining();
			}
		},

		/**
		 * Load remaining data
		 */
		load_remaining: function () {
			if ( last_query_start === 0 || last_query_start > start ) {
				er_timeline.load_data( start, last_query_start );
				last_query_start = new Date( start.getTime() );
			} else if ( last_query_end < end ) {
				var next_query_end = er_timeline.manipulate_date_without_offset( end, interval * 1000 );
				er_timeline.load_data( last_query_end, next_query_end );
				last_query_end = next_query_end;
			} else {

			}
		},

		/**
		 * Load data from database
		 *
		 * @param start Date
		 * @param end Date
		 */
		load_data: function ( start, end ) {
			$.ajax( {
				url:     data.ajax_url,
				data:    {
					action:     'easyreservations_timeline_data',
					security:   data.nonce,
					start:      start.getDate() + '.' + ( start.getMonth() + 1 ) + '.' + start.getFullYear(),
					start_hour: start.getHours(),
					end:        end.getDate() + '.' + ( end.getMonth() + 1 ) + '.' + end.getFullYear(),
					end_hour:   end.getHours(),
					interval:   interval
				},
				type:    'POST',
				success: function ( response ) {
					if ( response.data ) {
						$.each( response.data, function ( resource_id, data_array ) {
							var quantity = data.resources[ resource_id ].quantity;
							$.each( data_array, function ( timestamp, result ) {
								//$( 'td[data-date="' + ( parseInt( timestamp, 10 ) * 1000 ) + '"][data-resource="' + resource_id + '"]' ).css( 'background', '#ff0000' );
								var date       = new Date( parseInt( timestamp, 10 ) * 1000 ),
									cell_class = '',
									content    = '';

								if ( result < 0 ) {
									cell_class = 'unavailable';
									content = 0;
								} else {
									content = quantity - result;
								}

								tbody.find( 'td[data-date="' + ( date.getTime() + ( date.getTimezoneOffset() * 1000 * 60 ) ) + '"][data-resource="' + resource_id + '"]' )
									.removeClass( 'loading' )
									.addClass( cell_class );
								thead.find( 'th[data-date="' + ( date.getTime() + ( date.getTimezoneOffset() * 1000 * 60 ) ) + '"][data-resource="' + resource_id + '"]' )
									.html( '<div><span>' + content + '</span></div>' )
									.addClass( result == quantity ? 'unavailable' : '' );
							} );
						} );
					}

					if ( response.reservations ) {
						$.each( response.reservations, function ( _, reservation ) {
							er_timeline.add_reservation( reservation );
						} );

						er_timeline.draw_reservations();
					}
				}
			} );
		},

		/**
		 * Draw reservations ordered by arrival until we have to remove others down the line, if so start process again
		 */
		draw_reservations: function () {
			var queue = [],
				completed = true;

			$.each( reservations, function ( _, reservation ) {
				if ( reservation && reservation.changed ) {
					queue.push( reservation.id );
				}
			} );

			queue.sort( function ( a, b ) {
				return reservations[ a ].arrival < reservations[ b ].arrival ? -1 : 1;
			} );

			$.each( queue, function ( _, reservation_id ) {
				if ( reservation_id ) {
					if ( !er_timeline.draw_reservation( reservations[ reservation_id ] ) ) {
						er_timeline.draw_reservations();
						completed = false;
						return false;
					}
				}
			} );

			//If there was something to draw and we did draw it all.
			if( completed && queue.length > 0 ){
				er_timeline.sync_cell_heights();
			}
		},

		recursively_remove_reservation: function ( reservation ) {
			var id   = parseInt( reservation.id, 10 ),
				date = new Date( reservation.arrival.getTime() ),
				end  = new Date( reservation.departure.getTime() );

			if ( interval === "86400" ) {
				date.setHours( 0, 0, 0 );
				end.setHours( 0, 0, 0 );
			} else {
				date.setHours( date.getHours(), 0, 0 );
				end.setHours( end.getHours(), 0, 0 );
			}

			while ( date <= end ) {
				var cell = $( 'td[data-date="' + ( date.getTime() ) + '"][data-resource="' + reservation.resource + '"][data-space="' + reservation.space + '"]' );

				if ( cell.length > 0 ) {
					er_timeline.recursively_remove_reservations( cell, reservation.depths, id );
				}

				date = er_timeline.manipulate_date_without_offset( date, interval * 1000 );
			}

			return false;
		},

		recursively_remove_reservations: function ( cell, depths, id ) {
			var cell_reservations = cell.data( 'reservations' ),
				new_cell_reservation = [],
				found_start       = false,
				max_depths        = 0;

			if ( cell_reservations && cell_reservations.length > 0 ) {
				$.each( cell_reservations, function ( index, reservation_id ) {
					if ( reservation_id ) {
						if ( reservation_id === id || reservations[ reservation_id ].depths >= depths ) {
							if ( found_start === false ) {
								found_start = reservations[ reservation_id ].depths;
							}
							found_start = Math.min( found_start, reservations[ reservation_id ].depths );

							changed_any_reservation = true;
							reservations[ reservation_id ].changed = true;
						} else {
							new_cell_reservation.push( reservation_id );
							max_depths = Math.max( max_depths, reservations[ reservation_id ].depths );
						}
					}
				} );
			}

			cell.data( 'reservations', new_cell_reservation );

			if ( found_start !== false ) {
				return er_timeline.recursively_remove_reservations( cell.next(), found_start, id );
			}

			return true;
		},

		/**
		 * Draw single reservation
		 *
		 * @param reservation
		 * @returns {boolean}
		 */
		draw_reservation: function ( reservation ) {
			var id           = parseInt( reservation.id, 10 ),
				date         = new Date( reservation.arrival.getTime() ),
				end          = new Date( reservation.departure.getTime() ),
				element      = $( '<div class="reservation">' ),
				width        = ( reservation.departure.getTime() - reservation.arrival.getTime() ), //TODO remove offset?
				width_px     = ( ( ( width / 1000 ) / interval ) * cell_dimensions.width ) - 2,
				did_add      = false,
				depths_taken = [],
				depths       = 0;

			changed_any_reservation = false;

			if ( interval === "86400" ) {
				date.setHours( 0, 0, 0 );
				end.setHours( 0, 0, 0 );
			} else {
				date.setHours( date.getHours(), 0, 0 );
				end.setHours( end.getHours(), 0, 0 );
			}

			while ( date <= end ) {
				//Check each existing cell that is in this reservations duration
				var cell = $( 'td[data-date="' + ( date.getTime() ) + '"][data-resource="' + reservation.resource + '"][data-space="' + reservation.space + '"]' );

				if ( cell && cell.length > 0 ) {
					var cell_reservations = cell.data( 'reservations' );

					if ( cell_reservations.length > 0 ) {
						//Go through existing reservation in the cell and check if to redraw them or if to close depths for this reservation
						$.each( cell_reservations, function ( index, reservation_id ) {
							if ( reservation_id && reservation_id !== id ) {
								if ( reservations[ reservation_id ].arrival > reservation.arrival && reservations[ reservation_id ].arrival < reservation.departure && reservations[ reservation_id ].departure > reservation.arrival ) {
									er_timeline.recursively_remove_reservations( cell, depths, reservation_id );
								} else if ( reservations[ reservation_id ].departure <= reservation.arrival || reservations[ reservation_id ].arrival >= reservation.departure ) {
									//console.log( 'could go in ' + reservations[ reservation_id ].depths + ' because of ' + reservation_id );
								} else {
									depths_taken[ reservations[ reservation_id ].depths ] = 1;
									//console.log( id + ' cannot go in ' + reservations[ reservation_id ].depths + ' because of ' + reservation_id );
								}
							}
						} );
					}

					if ( did_add === false ) {
						//We will append the reservation to the first cell found, we set left but don't know the depths yet
						element.css( 'left', ( ( ( reservation.arrival.getTime() - date.getTime() ) / 1000 / interval * cell_dimensions.width ) - 1 ) + 'px' );

						did_add = cell;
					}

					if ( $.inArray( id, cell_reservations ) < 0 ) {
						cell_reservations.push( id );

						cell.data( 'reservations', cell_reservations );
					}
				}

				date = er_timeline.manipulate_date_without_offset( date, interval * 1000 );
			}

			if ( did_add === false ) {
				//No cell to put reservation in found
				return true;
				//delete reservations[ id ];
			} else {
				if ( changed_any_reservation ) {
					//We changed other reservations and begin drawing from the first changed reservation by arrival again

					return false;
				} else {
					//We can draw this reservations now

					element
						.html( '<span class="wrapper"><span>#' + id + ' ' + reservation.title + '</span></span>' )
						.draggable( {
							snap: snapping_enabled ? false : 'td.cell,.reservation',
							snapTolerance: 3,
							scroll: false,
							helper: "clone",
							appendTo: ".timeline",
							containment: table,
							revert: function ( event, ui ) {
								if( event )
									return event;
								// on older version of jQuery use "draggable"
								// $(this).data("draggable")
								// on 2.x versions of jQuery use "ui-draggable"
								// $(this).data("ui-draggable")
								if( $( this ).data( "uiDraggable" ) ){

								}
								$( this ).data( "uiDraggable" ).originalPosition = {
									top:  drag_start_position.top - 1,
									left: drag_start_position.left
								};
								// return boolean
								return !event;
								// that evaluate like this:
								// return event !== false ? false : true;
							},
							stack:         '.reservation',
							scope:         'reservations',
							start: function ( event, ui ) {
								drag_start_position = ui.originalPosition;
								drag_start_offset = ui.offset;
							},
							drag: function ( event, ui ) {
								var id          = parseInt( ui.helper.attr( 'data-id' ), 10 ),
									reservation = reservations[ id ],
									difference  = interval / cell_dimensions.width * ( ui.position.left - drag_start_position.left ),
									mouse_position = mouse_pos_x - timeline.offset().left;

								if ( snapping_enabled ) {
									var test = Math.round( ( ui.position.left - drag_start_position.left ) / cell_dimensions.width );
									difference = test * interval;
									ui.position.left = drag_start_position.left + ( test * cell_dimensions.width );
								}

								tooltip
									.html(
										easyFormatTime( er_timeline.manipulate_date_without_offset( reservation.arrival, difference * 1000 ) ) +
										' - ' +
										easyFormatTime( er_timeline.manipulate_date_without_offset( reservation.departure, difference * 1000 ) )
									)
									.css( {
										'top':  mouse_pos_y,
										'left': Math.min( mouse_pos_x - 130, timeline.width() )
									} )
									.show();

								if ( drag_snap_top !== false ) {
									ui.position.top = drag_snap_top - drag_start_offset.top + drag_start_position.top;
								}

								if( mouse_position < 10 || timeline.width() - mouse_position < 10 ){
									if ( scroll_action === false ) {
										scroll_action = setInterval( function () {
											if ( scroll_action !== false ) {
												if ( scroll_add === false && ( timeline.scrollLeft() < 2 || timeline[ 0 ].scrollWidth - ( timeline.width() + timeline.scrollLeft() ) < 2 ) ) {
													er_timeline.add_new_column( timeline.scrollLeft() < 2 );
													drag_start_position.left = drag_start_position.left + ( timeline.scrollLeft() < 2 ? cell_dimensions.width : cell_dimensions.width * -1 );
												}

												timeline.scrollLeft( Math.max( 1, timeline.scrollLeft() + ( mouse_pos_x - timeline.offset().left < 10 ? cell_dimensions.width * -1 : cell_dimensions.width ) ) );
												//timeline.animate( { scrollLeft: ( mouse_pos_x - timeline.offset().left < 10 ? '-=' : '+=' ) + cell_dimensions.width + 'px' }, 130 );
											}
										}, 130 );
									}
								} else if( scroll_action !== false ) {
									clearInterval( scroll_action );
									scroll_action = false;
									er_timeline.load_remaining();
								}
							},
							stop: function () {
								tooltip.hide();
								if ( scroll_action !== false ) {
									clearInterval( scroll_action );
									scroll_action = false;
									er_timeline.load_remaining();
								}
							}
						} )
						.resizable( {
							handles:     'e, w',
							grid: 		snapping_enabled ? [96, 28] : false,
							maxWidth:    cell_dimensions.width * 50,
							scroll: 0,
							minHeight: 0,
							minWidth:    4,
							start:       function ( event, ui ) {
								var id       = parseInt( ui.originalElement.attr( 'data-id' ), 10 ),
									cell     = ui.originalElement.parent(),
									direction_west = $( last_hover ).hasClass( 'ui-resizable-w' );

								console.log( ui.originalElement.css( 'top' ) );
								ui.originalElement.attr( 'style', 'left: ' + ui.originalElement.css( 'left' ) + ';top: ' + ui.originalElement.css( 'top' ) + ' !important;width: ' + ui.originalElement.css( 'width' ) );
							},
							resize:      function ( event, ui ) {
								var id          = parseInt( ui.element.attr( 'data-id' ), 10 ),
									reservation = reservations[ id ],
									arrival_difference   = interval / cell_dimensions.width * ( ui.position.left - ui.originalPosition.left ),
									departure_difference = interval / cell_dimensions.width * ( ui.size.width + 2 ),
									message = '';

								if( ui.position.left - ui.originalPosition.left !== 0 ){
									message = easyFormatTime( er_timeline.manipulate_date_without_offset( reservation.arrival, arrival_difference * 1000 ) );
								} else if( ui.size.width - ui.originalSize.width !== 0) {
									message = easyFormatTime( er_timeline.manipulate_date_without_offset( reservation.arrival, departure_difference * 1000 ) );
								} else {
									message = easyFormatTime( er_timeline.manipulate_date_without_offset( reservation.arrival, arrival_difference * 1000 ) );
									message += ' - ';
									message += easyFormatTime( er_timeline.manipulate_date_without_offset( reservation.arrival, departure_difference * 1000 ) );
								}

								tooltip
									.html( message )
									.css( {
										'top': mouse_pos_y,
										'left': Math.min( mouse_pos_x - 130, timeline.width() )
									} )
									.show();

							},
							stop:        function ( event, ui ) {
								var id                   = parseInt( ui.element.attr( 'data-id' ), 10 ),
									arrival_difference   = interval / cell_dimensions.width * ( ui.position.left - ui.originalPosition.left ),
									departure_difference = interval / cell_dimensions.width * ( ui.size.width + 2 ),
									reservation          = {
										id:        id,
										arrival: er_timeline.manipulate_date_without_offset( reservations[ id ].arrival, arrival_difference * 1000 ),
										departure: er_timeline.manipulate_date_without_offset( reservations[ id ].arrival, ( arrival_difference * 1000 ) + ( departure_difference * 1000 ) ),
										resource: 	reservations[ id ].resource,
										space: reservations[ id ].space
									};

								console.log( ui.size.width );
								console.log( departure_difference );
								console.log( reservation.arrival );
								console.log( reservation.departure );

								if ( er_both_params.resources[ reservations[ id ].resource ].availability_by !== 'unit' || er_timeline.check_availability( reservation ) ) {
									er_timeline.recursively_remove_reservation( reservations[ id ] );

									reservations[ id ].arrival = reservation.arrival;
									reservations[ id ].departure = reservation.departure;
									reservations[ id ].changed = true;


									er_timeline.draw_reservations();
								} else {
									ui.helper.animate(
										{
											width: ui.originalSize.width,
											left: ui.originalPosition.left
										},
										500,
										function () {
										}
									);
								}

								tooltip.hide();
							}
						} )
						.css( 'min-width', width_px + 'px' )
						.css( 'max-width', width_px + 'px' )
						.css( 'top', '0px' )
						.css( 'position', 'absolute' )
						.attr( 'data-tip', reservation.id )
						.attr( 'data-id', reservation.id );

					var was_there = $( '.reservation[data-id="' + id + '"]' ).remove();

					if( was_there.length > 0 ){
						element.addClass( 'fade-in-fast' );
					}

					did_add.append( element );

					while ( depths_taken[ depths ] === 1 ) {
						depths++;
					}

					if ( depths > 0 ) {
						if ( did_add.height() < cell_dimensions.height + ( cell_dimensions.height - 3 ) * depths ) {
							did_add.height( cell_dimensions.height + ( cell_dimensions.height - 3 ) * depths );
						}
						element.css( 'top', ( cell_dimensions.height - 3 ) * depths + 'px' );
					}

					reservation.depths = depths;
					reservation.changed = false;

					console.log( 'did draw ' + id + 'at depths ' + depths );

					reservations[ id ] = reservation;
				}
			}

			return did_add;
		},

		/**
		 * Add reservation to array
		 *
		 * @param reservation
		 */
		add_reservation: function ( reservation ) {
			var id = parseInt( reservation.id, 10 );

			//if ( typeof reservations[ id ] === 'undefined' ) {
			reservation.id = id;
			reservation.arrival = new Date( reservation.arrival );
			reservation.departure = new Date( reservation.departure );
			reservation.resource = parseInt( reservation.resource, 10 );
			reservation.space = parseInt( reservation.space, 10 );

			//TODO add check if it needs to be redrawn
			if ( typeof reservations[ id ] === 'undefined' ) {
				reservation.changed = true;
			} else {
				reservation.changed = reservations[ id ].changed;
				reservation.depths = reservations[ id ].depths;
			}

			reservations[ id ] = reservation;
		},

		/**
		 * Checks reservation against other loaded reservations
		 *
		 * @param to_check
		 * @returns {boolean}
		 */
		check_availability: function ( to_check ) {
			var id           = parseInt( to_check.id, 10 ),
				is_available = true;

			$.each( reservations, function ( _, reservation ) {
				if ( reservation && reservation.resource === to_check.resource && reservation.space === to_check.space && reservation.id !== id && (
					to_check.arrival < reservation.departure && to_check.departure > reservation.arrival
				) ) {
					is_available = false;
					return false;
				}
			} );

			return is_available;
		},

		/**
		 * Keeps the resources cells as high as the biggest cell is
		 */
		sync_cell_heights: function () {
			var tbody_index, tr_index;
			resources_tbody.each( function ( _, resource_tbody ) {
				tbody_index = $( resource_tbody ).index() / 2 - 1;
				$( resource_tbody ).children().each( function ( _, tr ) {
					tr_index = $( tr ).index();
					$( tr ).height( $( tbody[ tbody_index ] ).children().eq( $( tr ).index() ).height() );
				} );
			} );
		},

		/**
		 * Generate and appends calendar column
		 *
		 * @param date 		Date 	date of cell to add
		 * @param at_start 	bool 	wether to add it at start
		 */
		generate_column: function ( date, at_start ) {
			var header_main  = '',
				header_class = '',
				tbody_number        = 0,
				i            = 0,
				day          = date.getDay() === 0 ? 6 : date.getDay() - 1;

			if ( interval === "86400" ) {
				header_main = $( '<th><div class="date"><span>' + easyFormatDate( date, 'd' ) + '</span><div>' + er_date_picker_params.day_names_min[ day ] + '</div></div></th>' );

				if ( date.getDate() === 1 ) {
					header_main.append( $( '<div class="first-of-month"></div>' ) );
				}
			} else {
				var last_char = er_both_params.time_format.charAt( er_both_params.time_format.length - 1 ),
					description = easyAddZero( date.getMinutes() );

				if( last_char === 'a' ){
					description = date.getHours() >= 12 ? 'pm' : 'am';
				} else if ( last_char === 'a' ){
					description = date.getHours() >= 12 ? 'PM' : 'AM';
				}

				header_main = $( '<th><div class="date"><span>' + easyFormatDate( date, 'H' ) + '</span><div>' + description + '</div></div></th>' );

				if ( date.getHours() === 0 ) {
					header_main.append( $( '<div class="first-of-month"></div>' ) );
				}
			}

			if ( date.getDate() === today.getDate() && date.getMonth() === today.getMonth() && date.getFullYear() === today.getFullYear() && ( interval === "86400" || date.getHours() === today.getHours() ) ) {
				var today_marker  = $( '<div class="today"></div>' ),
					today_overlay = $( '<div class="overlay"></div>' ),
					difference    = 0,
					real_today	  = new Date();

				if ( interval === "86400" ) {
					difference = cell_dimensions.width / 86400 * ( real_today.getHours() * 3600 + real_today.getMinutes() * 60 ) - 1;
				} else {
					difference = cell_dimensions.width / 3600 * ( real_today.getMinutes() * 60 ) - 1;
				}

				today_marker
					.css( 'left', difference );
				today_overlay
					.css( 'left', difference )
					.css( 'width', difference )
					.css( 'margin-left', -difference );

				header_main
					.append( today_marker )
					.append( today_overlay );

				header_class += ' today';
			} else if ( date < today ) {
				header_class += ' past';
			}

			if ( interval === "86400" && ( date.getDay() === 0 || date.getDay() === 6 ) ) {
				header_class += ' weekend';
			}

			header_main
				.addClass( header_class )
				.attr( 'data-date', date.getTime() );

			header_class += ' loading';

			if ( at_start ) {
				thead_main.prepend( header_main );
			} else {
				thead_main.append( header_main );
			}

			$.each( data.resources, function ( resource_id, resource ) {
				var cell_header = $( '<th><div></div></th>' )
					.addClass( header_class )
					.attr( 'data-resource', resource_id )
					.attr( 'data-date', date.getTime() );

				if ( at_start ) {
					$( thead[ tbody_number ]).find( 'tr' ).prepend( cell_header );
				} else {
					$( thead[ tbody_number ] ).find( 'tr' ).append( cell_header );
				}

				for ( i = 1; i <= ( resource.availability_by === 'unit' ? resource.quantity : 1 ); i++ ) {
					var cell = $( '<td class="cell"></td>' )
						.addClass( header_class )
						.attr( 'data-resource', resource_id )
						.data( 'reservations', [] )
						.attr( 'data-space', i )
						.attr( 'data-date', date.getTime() );

					if ( at_start ) {
						$(tbody[ tbody_number ]).find( 'tr:nth-child(' + i + ')' ).prepend( cell );
					} else {
						$(tbody[ tbody_number ]).find( 'tr:nth-child(' + i + ')' ).append( cell );
					}

					cell
						.droppable( {
							scope: "reservations", //we only accept reservations
							tolerance: "pointer", //targets the cell under the mouse
							drop:  function ( event, ui ) {
								var cell = $( this );

								if( last_hover.getAttribute( "data-space" ) ){
									cell = $( last_hover );
								}

								var id          = parseInt( ui.draggable.attr( 'data-id' ), 10 ),
									difference  = interval / cell_dimensions.width * ( ui.position.left - drag_start_position.left ),
									reservation = {
										id:        id,
										arrival: er_timeline.manipulate_date_without_offset( reservations[ id ].arrival, difference * 1000 ),
										departure: er_timeline.manipulate_date_without_offset( reservations[ id ].departure, difference * 1000 ),
										resource:  parseInt( cell.attr( 'data-resource' ), 10 ),
										space:     parseInt( cell.attr( 'data-space' ), 10 )
									};

								if ( er_both_params.resources[ reservation.resource ].availability_by !== 'unit' || er_timeline.check_availability( reservation ) ) {
									er_timeline.recursively_remove_reservation( reservations[ id ] );

									reservations[ id ].arrival = reservation.arrival;
									reservations[ id ].departure = reservation.departure;
									reservations[ id ].resource = reservation.resource;
									reservations[ id ].space = reservation.space;
									reservations[ id ].changed = true;

									ui.helper.remove();

									er_timeline.draw_reservations();
								} else {
								}


							},
							over:  function ( event, ui ) {
							}
						} );
				}
				tbody_number++;
			} );

			if ( at_start ) {
				if ( last_query_start === 0 || last_query_start.getTime() - ( interval * 1000 * 10 ) > date.getTime() ) {
					er_timeline.load_data( start, last_query_start );
					last_query_start = new Date( start.getTime() );
				}
			} else {
				if ( last_query_end.getTime() + ( interval * 1000 * 10 ) < date.getTime() ) {
					var next_query_end = new Date( er_timeline.manipulate_date_without_offset( end, interval * 1000 ).getTime() );
					er_timeline.load_data( last_query_end, next_query_end );
					last_query_end = next_query_end;
				}
			}
		}
	};

	er_timeline.init();
} )
( jQuery, er_timeline_params );