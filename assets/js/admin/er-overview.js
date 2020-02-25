( function ( $, data ) {
	var tooltip                 = $( '.er-overview-tooltip' ),
		overview_container      = $( '.er-overview' ),
		datepicker              = $( '#overview-datepicker' ),
		timeline                = overview_container.find( 'div.timeline' ),
		header                  = overview_container.find( 'div.header' ),
		header_date             = header.find( '.date' ),
		table                   = timeline.find( 'table' ),
		thead_main              = timeline.find( 'thead tr' ),
		tbody                   = table.find( 'tbody' ),
		reservations            = [],
		today                   = new Date(),
		start                   = new Date(),
		end                     = false,
		drag_start_position     = false,
		drag_start_offset       = false,
		changed_any_reservation = false,
		mouse_pos_x             = 0,
		mouse_pos_y             = 0,
		snap_top                = false,
		scroll_start            = false,
		scroll_timeout          = false,
		cell_dimensions         = { height: 27, width: 96 },
		last_hover              = 0,
		last_query_start        = 0,
		last_query_end          = 0,
		interval                = data.default_interval;

	overview_container.insertAfter( 'hr.wp-header-end' );
	tooltip.insertAfter( 'hr.wp-header-end' );

	header.bind( 'click', function () {
		datepicker.datepicker( 'show' );
	} );

	datepicker.bind( 'change', function () {
		start = $( this ).datepicker( "getDate" );
		er_overview.init();
	} );

	timeline.find( 'thead' ).on( {
		'mousedown': function ( e ) {
			scroll_start = timeline.scrollLeft() + e.pageX;
		}
	} );


	$( window )
		.mouseup( function () {
			thead_main.css( 'cursor', 'grab' );
			clearInterval( scroll_start );
			scroll_start = false;
			er_overview.clear_scroll_timeout();
		} );

	overview_container
		.mousemove( function ( e ) {
			mouse_pos_x = e.pageX;
			mouse_pos_y = e.pageY;
			if ( scroll_start && $.isNumeric( scroll_start ) && e.which === 1 ) {
				timeline.scrollLeft( scroll_start - e.pageX < 1 ? 1 : scroll_start - e.pageX );

				if ( scroll_timeout !== false ) {
					thead_main.css( 'cursor', 'grabbing' );
					er_overview.scroll();
				}
			}

			if ( last_hover !== e.target ) {
				last_hover = e.target;
				if ( e.target.getAttribute( "data-date" ) ) {
					er_overview.highlight_current( new Date( parseInt( e.target.getAttribute( "data-date" ), 10 ) ) );
				}

				//if( e.target.getAttribute( "data-space" ) )console.log($(e.target).data('reservations'));
				if ( e.target.getAttribute( "data-space" ) || e.target.getAttribute( "data-id" ) ) {
					snap_top = $( e.target ).offset().top;
				}
			}
		} )
		.mouseleave( function () {
			//er_overview.slide_current();
			thead_main.css( 'cursor', 'grab' );
			clearInterval( scroll_start );
			scroll_start = false;
			er_overview.clear_scroll_timeout();
		} )
		.on( 'mousedown', '.next', function () {
			if ( scroll_start === false ) {
				scroll_start = setInterval( function () {
					if ( scroll_start !== false ) {
						timeline.scrollLeft( timeline.scrollLeft() + cell_dimensions.width );
						er_overview.scroll();
					}
				}, 100 );
			}
		} )
		.on( 'mousedown', '.prev', function () {
			if ( scroll_start === false ) {
				scroll_start = setInterval( function () {
					if ( scroll_start !== false ) {
						timeline.scrollLeft( timeline.scrollLeft() - cell_dimensions.width );
						er_overview.scroll();
					}
				}, 100 );
			}
		} );

	timeline.scroll( function () {
		if ( scroll_start !== false ) {
			thead_main.css( 'cursor', 'grabbing' );
			er_overview.scroll();
		}
	} );

	var er_overview = {
		init: function () {
			if ( interval === "86400" ) {
				start.setHours( 0, 0, 0, 0 );
			} else {
				start.setHours( start.getHours(), 0, 0, 0 );
			}

			timeline.scrollLeft( 1 );

			var date = start, i;

			er_overview.set_current_date( today );

			table.find( 'td,th' ).remove();
			date.setTime( date.getTime() - ( 10 * interval * 1000 ) );
			last_query_end = date.getTime();
			last_query_start = date.getTime();

			for ( i = 0; i < 50; i++ ) {
				end = new Date( date.getTime() + ( i * interval * 1000 ) );
				er_overview.generate_column( end );
			}

			er_overview.load_remaining();

			timeline.scrollLeft(
				thead_main.find( 'th:nth-child(9)' ).offset().left - timeline.offset().left + timeline.scrollLeft() + 1
			);

			//er_overview.slide_current();
		},

		slide_current: function ( one_less ) {
			var current_cell = thead_main.find( 'th:nth-child(' + ( Math.round( timeline.scrollLeft() / cell_dimensions.width ) + ( 3 ) ) + ')' );
			er_overview.highlight_current( new Date( current_cell.data( 'date' ) ) );
		},

		highlight_current: function ( date ) {
			er_overview.set_current_date( date );

			$( '.er-overview .current' ).removeClass( 'current' );
			$( '*[data-date="' + date.getTime() + '"]' ).addClass( 'current' );
		},

		set_current_date: function ( date ) {
			if ( interval === "3600" ) {
				header_date.html( date.getDate() + ' ' + er_date_picker_params.month_names[ date.getMonth() ] + ' ' + date.getFullYear() );
			} else {
				header_date.html( er_date_picker_params.month_names[ date.getMonth() ] + ' ' + date.getFullYear() );
			}

			datepicker.val( date.getDate() + '.' + ( date.getMonth() + 1 ) + '.' + date.getFullYear() );
		},

		scroll: function () {
			if ( scroll_timeout === false && ( timeline.scrollLeft() < 2 || timeline[ 0 ].scrollWidth - ( timeline.width() + timeline.scrollLeft() ) < 2 ) ) {
				scroll_timeout = setInterval( function () {
					if ( scroll_timeout !== false ) {
						if ( timeline.scrollLeft() < 2 ) {
							start.setTime( start.getTime() - ( interval * 1000 ) );
							er_overview.generate_column( start, true );
							er_overview.set_current_date( start );

							table.find( 'th:last-child,td:last-child' ).remove();
							last_query_end -= ( interval * 1000 );
							end.setTime( end.getTime() - ( interval * 1000 ) );
						} else if ( timeline[ 0 ].scrollWidth - ( timeline.width() + timeline.scrollLeft() ) < 2 ) {
							end.setTime( end.getTime() + ( interval * 1000 ) );
							er_overview.generate_column( end );
							er_overview.set_current_date( end );

							table.find( 'th:first-child,td:first-child' )
								.each(function(){
									var cell_reservations = $( this ).data( 'reservations' );
									if( cell_reservations && cell_reservations.length > 0 ){
										$.each( cell_reservations, function(_, reservation_id){
											if( reservations[ reservation_id ] && typeof reservations[ reservation_id ] !== 'undefined' ){
												reservations[ reservation_id ].changed = true;
											}
										} );
									}
								})
								.remove();
							last_query_start += ( interval * 1000 );
							start.setTime( start.getTime() + ( interval * 1000 ) );
						}
					}
				}, 35 );
			}
		},

		clear_scroll_timeout: function () {
			if ( scroll_timeout !== false ) {
				clearInterval( scroll_timeout );
				scroll_timeout = false;
				er_overview.load_remaining();
			}
		},

		load_remaining: function () {
			if ( last_query_start === 0 || last_query_start > start.getTime() ) {
				er_overview.load_data( start, new Date( last_query_start ) );
				last_query_start = start.getTime();
			} else if ( last_query_end < end.getTime() ) {
				er_overview.load_data( new Date( last_query_end ), new Date( end.getTime() + ( interval * 1000 ) ) );
				last_query_end = end.getTime();
			} else {

			}
		},

		load_data: function ( start, end ) {
			$.ajax( {
				url:     data.ajax_url,
				data:    {
					action:     'easyreservations_overview_data',
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

								$( 'td[data-date="' + ( date.getTime() + ( date.getTimezoneOffset() * 1000 * 60 ) ) + '"][data-resource="' + resource_id + '"]' )
									.removeClass( 'loading' )
									.addClass( cell_class );
								$( 'tr.header td[data-date="' + ( date.getTime() + ( date.getTimezoneOffset() * 1000 * 60 ) ) + '"][data-resource="' + resource_id + '"]' )
									.html( '<div style="pointer-events:none">' + content + '</div>' )
									.addClass( result == quantity ? 'unavailable' : '' );
							} );
						} );
					}

					if ( response.reservations ) {
						$.each( response.reservations, function ( _, reservation ) {
							reservation.changed = true;

							er_overview.add_reservation( reservation );
						} );

						er_overview.draw_reservations();
					}
				}
			} );
		},

		draw_reservations: function () {
			var queue = [];

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
					if( !er_overview.draw_reservation( reservations[ reservation_id ] ) ){
						er_overview.draw_reservations();
						return false;
					}
				}
			} );
		},

		recursively_remove_reservation: function ( reservation ) {
			var id   = parseInt( reservation.id, 10 ),
				date = new Date( reservation.arrival ),
				end  = new Date( reservation.departure );

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
					er_overview.recursively_remove_reservations( cell, reservation.depths, id );
				}
				date.setTime( date.getTime() + ( interval * 1000 ) );
			}

			return false;
		},

		recursively_remove_reservations: function ( cell, depths, id ) {
			var cell_reservations = cell.data( 'reservations' ),
				found_start       = false,
				max_depths 		  = 0;

			if ( cell_reservations.length > 0 ) {
				$.each( cell_reservations, function ( index, reservation_id ) {
					if ( reservation_id ) {
						if ( reservation_id === id || reservations[ reservation_id ].depths >= depths ) {
							if ( found_start === false ) {
								found_start = reservations[ reservation_id ].depths;
							}
							found_start = Math.min( found_start, reservations[ reservation_id ].depths );

							changed_any_reservation = true;
							reservations[ reservation_id ].changed = true;
							cell_reservations.splice( index, 1);
						} else {
							max_depths = Math.max( max_depths, reservations[ reservation_id ].depths );
						}
					}
				} );
			}

			cell.data( 'reservations', cell_reservations );
			cell.height( cell_dimensions.height + cell_dimensions.height * max_depths );

			if ( found_start !== false ) {
				return er_overview.recursively_remove_reservations( cell.next(), found_start, id );
			}

			return true;
		},

		draw_reservation: function ( reservation ) {
			var id                   = parseInt( reservation.id, 10 ),
				date                 = new Date( reservation.arrival ),
				end                  = new Date( reservation.departure ),
				element              = $( '<div class="reservation">' ),
				width                = ( reservation.departure - reservation.arrival ),
				width_px             = ( ( ( width / 1000 ) / interval ) * cell_dimensions.width ) - ( interval === "86400" ? 2 : 1 ),
				did_add              = false,
				depths_taken              = [],
				depths = 0;

			changed_any_reservation = false;

			$( '.reservation[data-id="' + id + '"]' ).remove();

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
					var cell_reservations = cell.data( 'reservations' );

					if ( cell_reservations.length > 0 ) {
						$.each( cell_reservations, function ( index, reservation_id ) {
							if ( reservation_id && reservation_id !== id ) {
								if ( reservations[ reservation_id ].arrival > reservation.arrival && reservations[ reservation_id ].departure > reservation.arrival ) {
									er_overview.recursively_remove_reservations( cell, depths, reservation_id );
								} else if ( reservations[ reservation_id ].departure <= reservation.arrival ) {
									console.log( 'could go in ' + reservations[ reservation_id ].depths + ' because of ' + reservation_id );

								} else {
									depths_taken[ reservations[ reservation_id ].depths ] = 1;
									console.log( id + ' cannot go in ' + reservations[ reservation_id ].depths + ' because of '+ reservation_id );
								}
							}
						} );
					}

					if ( did_add === false ) {
						//We will append the reservation to the first cell found, we set left but don't know the depths yet
						element.css( 'left', ( ( ( ( reservation.arrival - date.getTime() ) / 1000 ) / interval ) * cell_dimensions.width ) - 1 + 'px' );

						did_add = cell;
					}

					if( $.inArray( id, cell_reservations) < 0 ){
						cell_reservations.push( id );

						cell.data( 'reservations', cell_reservations );
					}
				}

				date.setTime( date.getTime() + ( interval * 1000 ) );
			}

			if ( did_add === false ) {
				delete reservations[ id ];
			} else {
				if( changed_any_reservation ){
					return false;
				} else {
					element
						.html( '#' + id + ' ' + reservation.title )
						.draggable( {
							snapTolerance: 3,
							appendTo:      "td.cell",
							containment:   tbody,
							revert:        'invalid',
							stack:         '.reservation',
							scope:         'reservations',
							snap:          'td.cell,.reservation',
							start:         function ( event, ui ) {
								drag_start_position = ui.originalPosition;
								drag_start_offset = ui.offset;
							},
							drag:          function ( event, ui ) {
								var id          = parseInt( ui.helper.attr( 'data-id' ), 10 ),
									reservation = reservations[ id ],
									difference  = interval / cell_dimensions.width * ( ui.position.left - ui.originalPosition.left );

								tooltip
									.html( easyFormatTime( new Date( reservation.arrival + ( difference * 1000 ) ) ) + ' - ' + easyFormatTime( new Date( reservation.departure + ( difference * 1000 ) ) ) )
									.css( {
										'top':  mouse_pos_y,
										'left': mouse_pos_x - 130
									} )
									.show();

								if ( snap_top !== false ) {
									ui.position.top = snap_top - drag_start_offset.top + drag_start_position.top;
								}
								//console.log( new Date( parseInt( ui.helper.attr('data-arrival' ), 10 ) + difference * 1000 ) );
							},
							stop:          function () {
								tooltip.hide();
							}
						} )
						.resizable( {
							containment: tbody,
							handles:     'e',
							maxWidth:    cell_dimensions.width * 50,
							minWidth:    4,
							start:       function ( event, ui ) {
								var id       = parseInt( ui.originalElement.attr( 'data-id' ), 10 ),
									original = $( this ),
									cell     = ui.originalElement.parent();

								while ( cell.length > 0 ) {
									var reservations_in_cell = cell.find( '.reservation' );
									cell = cell.next();

									if ( reservations_in_cell.length > 0 ) {
										$.each( reservations_in_cell, function ( _, element ) {
											var reservation_id = parseInt( $( element ).attr( 'data-id' ) );

											if ( reservation_id !== id ) {
												original.resizable( "option", "maxWidth", ( reservations[ reservation_id ].arrival - reservations[ id ].departure ) / ( interval * 1000 ) * cell_dimensions.width + ui.originalSize.width + 1 );

												cell = { length: 0 };
												return false;
											}
										} );
									}
								}

								ui.helper.attr( 'style', 'left: ' + ui.helper.css( 'left' ) );
							},
							resize:      function ( event, ui ) {
								var id          = parseInt( ui.element.attr( 'data-id' ), 10 ),
									reservation = reservations[ id ],
									difference  = interval / cell_dimensions.width * ( ui.size.width + 1 );

								tooltip
									.html( easyFormatTime( new Date( reservation.arrival + ( difference * 1000 ) ) ) )
									.css( {
										'top':  mouse_pos_y,
										'left': mouse_pos_x - 130
									} )
									.show();

							},
							stop:        function ( event, ui ) {
								var id          = parseInt( ui.element.attr( 'data-id' ), 10 ),
									reservation = reservations[ id ],
									difference  = interval / cell_dimensions.width * ( ui.size.width + 1 );

								reservation.departure = reservation.arrival + ( difference * 1000 );

								er_overview.draw_reservation( reservation );

								tooltip.hide();
							}
						} )
						.css( 'min-width', width_px + 'px' )
						.css( 'max-width', width_px + 'px' )
						.css( 'position', 'absolute' )
						.attr( 'data-tip', reservation.id )
						.attr( 'data-id', reservation.id );

					did_add.append( element );

					while( depths_taken[depths] === 1 ){
						depths++;
					}

					if ( depths > 0 ) {
						if( did_add.height() < cell_dimensions.height + cell_dimensions.height * depths ){
							did_add.height( cell_dimensions.height + cell_dimensions.height * depths );
						}
						element.css( 'top', cell_dimensions.height * depths + 'px' );
					}

					reservation.depths = depths;
					reservation.changed = false;

					console.log( 'did draw ' + id + 'at depths ' + depths );

					reservations[ id ] = reservation;
				}
			}

			return did_add;
		},

		add_reservation: function ( reservation ) {
			var id = parseInt( reservation.id, 10 );

			//if ( typeof reservations[ id ] === 'undefined' ) {
			if ( !$.isNumeric( reservation.arrival ) ) {
				reservation.id = id;
				reservation.arrival = new Date( reservation.arrival ).getTime();
				reservation.departure = new Date( reservation.departure ).getTime();
				reservation.resource = parseInt( reservation.resource, 10 );
				reservation.space = parseInt( reservation.space, 10 );
			}

			reservation.changed = true;

			reservations[ id ] = reservation;
		},

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

		generate_column: function ( date, left ) {
			var header_main  = '',
				header_class = '',
				child        = 1,
				i            = 0,
				day          = date.getDay() === 0 ? 6 : date.getDay() - 1;

			if ( interval === "86400" ) {
				header_main = $( '<th><div class="date"><div>' + easyFormatDate( date, 'd' ) + '</div><span>' + er_date_picker_params.day_names_min[ day ] + '</span></div></th>' );

				if ( date.getDate() === 1 ) {
					header_class = 'first';
				}
			} else {
				header_main = $( '<th>' + easyFormatDate( date, 'H' ) + '</th>' );

				if ( date.getHours() === 0 ) {
					header_class = 'first';
				}
			}

			if ( header_class === 'first' ) {
				header_main.append( $( '<div class="first-of-month"></div>' ) );
			}

			if ( date.getDate() === today.getDate() && date.getMonth() === today.getMonth() && date.getFullYear() === today.getFullYear() && ( interval === "86400" || date.getHours() === today.getHours() ) ) {
				var today_marker  = $( '<div class="today"></div>' ),
					today_overlay = $( '<div class="overlay"></div>' ),
					difference    = 0;

				if ( interval === "86400" ) {
					difference = cell_dimensions.width / 86400 * ( today.getHours() * 3600 + today.getMinutes() * 60 );
				} else {
					difference = cell_dimensions.width / 3600 * ( today.getMinutes() * 60 );
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
				.attr( 'data-date', date.getTime() + ( date.getTimezoneOffset() * 1000 * 60 ) + 3600000 );

			header_class += ' loading';

			if ( left ) {
				thead_main.prepend( header_main );
			} else {
				thead_main.append( header_main );
			}

			$.each( data.resources, function ( resource_id, resource ) {
				var cell_header = $( '<td class="resource"><div style="height:20px;pointer-events:none"></div></td>' )
					.addClass( header_class )
					.attr( 'data-resource', resource_id )
					.attr( 'data-date', date.getTime() + ( date.getTimezoneOffset() * 1000 * 60 ) + 3600000 );

				if ( left ) {
					tbody.find( 'tr:nth-child(' + child + ')' ).prepend( cell_header );
				} else {
					tbody.find( 'tr:nth-child(' + child + ')' ).append( cell_header );
				}

				for ( i = 1; i <= ( resource.availability_by === 'unit' ? resource.quantity : 1 ); i++ ) {
					var cell = $( '<td class="cell" style="height:' + cell_dimensions.height + 'px"></td>' )
						.addClass( header_class )
						.attr( 'data-resource', resource_id )
						.data( 'reservations', [] )
						.attr( 'data-space', i )
						.attr( 'data-date', date.getTime() + ( date.getTimezoneOffset() * 1000 * 60 ) + 3600000 );

					child++;

					if ( left ) {
						tbody.find( 'tr:nth-child(' + child + ')' ).prepend( cell );
					} else {
						tbody.find( 'tr:nth-child(' + child + ')' ).append( cell );
					}

					cell
						.droppable( {
							scope: "reservations",
							drop:  function ( event, ui ) {
								var id          = parseInt( ui.draggable.attr( 'data-id' ), 10 ),
									difference  = interval / cell_dimensions.width * ( ui.position.left - drag_start_position.left ),
									reservation = {
										id:        id,
										arrival:   reservations[ id ].arrival + ( difference * 1000 ),
										departure: reservations[ id ].departure + ( difference * 1000 ),
										resource:  parseInt( $( this ).attr( 'data-resource' ), 10 ),
										space:     parseInt( $( this ).attr( 'data-space' ), 10 )
									};

								if ( er_both_params.resources[ reservation.resource ].availability_by !== 'unit' || er_overview.check_availability( reservation ) ) {
									console.log( 'recursivly delete' );
									er_overview.recursively_remove_reservation( reservations[ id ] );
									reservations[ id ].arrival = reservation.arrival;
									reservations[ id ].departure = reservation.departure;
									reservations[ id ].resource = reservation.resource;
									reservations[ id ].space = reservation.space;
									reservations[ id ].changed = true;


									ui.draggable.remove();

									er_overview.draw_reservations();
								} else {
									ui.draggable.animate(
										drag_start_position,
										500,
										function() {
										}
									);
								}


							},
							over:  function ( event, ui ) {
								//ui.draggable.offset({ top: $( this ).offset().top, left: ui.draggable.offset().left } );
							}
						} );
				}
				child++;
			} );

			if ( left ) {
				if ( last_query_start === 0 || last_query_start - ( interval * 1000 * 10 ) > date.getTime() ) {
					er_overview.load_data( start, new Date( last_query_start ) );
					last_query_start = date.getTime();
				}
			} else {
				if ( last_query_end + ( interval * 1000 * 10 ) < date.getTime() ) {
					er_overview.load_data( new Date( last_query_end ), new Date( end.getTime() + ( interval * 1000 ) ) );
					last_query_end = date.getTime();
				}
			}
		}
	};

	er_overview.init();
} )
( jQuery, er_overview_params );