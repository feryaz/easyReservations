( function ( $, data ) {
	var tooltip                 = $( '.er-timeline-tooltip' ),
		timeline_container      = $( '.er-timeline' ),
		datepicker              = $( '#timeline-datepicker' ),
		sidebar                 = timeline_container.find( 'div.sidebar' ),
		timeline                = timeline_container.find( 'div.timeline' ),
		header                  = timeline_container.find( 'div.header' ),
		resources_tbody         = timeline_container.find( 'div.resources table tbody' ),
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
		edit_mode 				= false, //If we are in edit mode this is a backup of the reservation object to revert to
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
		first_hour              = data.first_hour, //First hour to display in timeline
		last_hour               = data.last_hour, //Last hour to display in timeline
		interval                = data.default_interval; //Default interval of timeline

	header
		.on( 'click', '.expand-sidebar', function () {
			sidebar.addClass( 'expanded' ).show();
			$( this )
				.removeClass( 'expand-sidebar' )
				.addClass( 'contract-sidebar' );
		} )
		.on( 'click', '.contract-sidebar', function () {
			sidebar.removeClass( 'expanded' ).hide( 300, 'linear' );
			$( this )
				.removeClass( 'contract-sidebar' )
				.addClass( 'expand-sidebar' );
		} )
		.on( 'click', '.hourly', function () {
			if ( !$( this ).hasClass( 'active' ) ) {
				header.find( '.daily' ).removeClass( 'active' );
				$( this ).addClass( 'active' );
				start = new Date( selected.getTime() );
				interval = "3600";
				er_timeline.init();
			}
		} )
		.on( 'click', '.daily', function () {
			if ( !$( this ).hasClass( 'active' ) ) {
				header.find( '.hourly' ).removeClass( 'active' );
				$( this ).addClass( 'active' );
				start = new Date( selected.getTime() );
				interval = "86400";
				er_timeline.init();
			}
		} )
		.on( 'click', '.pending', function () {
			er_sidebar.toggle_pending();
		} )
		.on( 'click', '.date', function () {
			er_sidebar.toggle_calendar();
		} )
		.on( 'click', '.today', function () {
			er_timeline.jump_to_date( today );
		} );

	datepicker.bind( 'change', function ( e ) {
		er_timeline.jump_to_date( $( this ).datepicker( "getDate" ) );
	} );

	thead_main
		.on( 'mousedown', 'th', function ( e ) {
			//Find out where the drag started
			scroll_drag = timeline.scrollLeft() + e.pageX;
		} );

	$( window )
		.mouseup( function () {
			thead_main.css( 'cursor', 'grab' );
			clearInterval( scroll_action );
			scroll_action = false;
			scroll_drag = false;
			er_timeline.clear_scroll_add_interval();
		} );

	timeline_container
		.mousemove( function ( e ) {
			//Store mouse positions
			mouse_pos_x = e.pageX;
			mouse_pos_y = e.pageY;

			//Handle drag scrolling in timeline header
			if ( scroll_drag && e.which === 1 ) {
				timeline.scrollLeft( scroll_drag - e.pageX < 1 ? 1 : scroll_drag - e.pageX );
				er_timeline.set_current_date();

				if ( scroll_add === false ) {
					thead_main.css( 'cursor', 'grabbing' );
					er_timeline.start_scroll_add_interval();
				}
			}

			//Handle hover
			if ( last_hover !== e.target ) {
				last_hover = e.target;

				//if( e.target.getAttribute( "data-space" ) ) console.log($(e.target).data('reservations'));
				if ( last_hover.getAttribute( "data-date" ) ) {
					er_timeline.highlight_current( new Date( parseInt( e.target.getAttribute( "data-date" ), 10 ) ) );
				}

				//If we hover over cell or reservation store top position to snap draggable to
				if ( last_hover.getAttribute( "data-space" ) ) {
					drag_snap_top = $( last_hover ).offset().top;
				} else if ( last_hover.getAttribute( "data-id" ) ) {
					drag_snap_top = $( last_hover ).parent().offset().top;
				}
			}
		} )
		.mouseleave( function () {
			thead_main.css( 'cursor', 'grab' );
			clearInterval( scroll_action );
			scroll_action = false;
			scroll_drag = false;
			er_timeline.clear_scroll_add_interval();
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

			if ( $( this ).hasClass( 'retracted' ) ) {
				$( this ).removeClass( 'retracted' );

				$( tbody[ tbody_index ] ).show();
				elem.show();
			} else {
				$( this ).addClass( 'retracted' );

				$( tbody[ tbody_index ] ).hide();
				elem.hide();
			}
		} )
		.on( 'click', '.reservation', function () {
			var id = parseInt( $( this ).attr( 'data-id' ), 10 );

			timeline.find( '.reservation.selected' ).removeClass( 'selected' );
			$( this ).addClass( 'selected' );

			er_sidebar.display_reservation( reservations[ id ] );
		} )
		.on( 'keydown', '.reservation .title', function ( e ) {
			if ( e.keyCode === 13 ) {
				var id = $( this ).parents( '.reservation' ).attr( 'data-id' );
				reservations[ id ].title = $( this ).html();
				$(this).blur();
				er_sidebar.display_reservation( reservations[ id ] );
				return false;
			}
		} );

	sidebar
		.on( 'click', '.allow-edit', function () {
			var id = parseInt( $( this ).attr( 'data-reservation-id' ), 10 );

			if ( edit_mode ) {
				er_timeline.update_reservation( id );
				er_sidebar.stop_edit( edit_mode.id );
			}

			edit_mode = JSON.parse( JSON.stringify( reservations[ id ] ) );

			sidebar.find( '> .reservation-details .edit-actions' ).show();
			er_timeline.reservation_allow_edit( timeline.find( '.reservation[data-id="' + id + '"]' ) );
			$( this ).html( data.i18n_stop_edit ).addClass( 'stop-edit' ).removeClass( 'allow-edit' );
		} )
		.on( 'click', '.stop-edit', function () {
			var id = parseInt( $( this ).attr( 'data-reservation-id' ), 10 );
			er_timeline.update_reservation( id );
			er_sidebar.stop_edit( id );
		} )
		.on( 'click', '.status', function () {
			if ( !$( this ).hasClass( 'reservation-status' ) ) {
				var id     = parseInt( $( this ).parent().parent().attr( 'data-reservation-id' ), 10 ),
					status = $( this ).attr( 'data-status' );

				reservations[ id ].status = status;
				timeline.find( '.reservation[data-id="' + id + '"]' ).removeClass( 'approved checked completed' ).addClass( status );

				er_sidebar.display_reservation( reservations[ id ] );
				er_timeline.update_reservation( id );
			}
		} )
		.on( 'click', '.snapping', function () {
			if ( $( this ).hasClass( 'enabled' ) ) {
				snapping_enabled = false;
				$( this ).removeClass( 'enabled' );
			} else {
				snapping_enabled = true;
				$( this ).addClass( 'enabled' );
			}
		} )
		.on( 'click', '.revert', function () {
			var id = parseInt( $( this ).attr( 'data-reservation-id' ), 10 );
			er_timeline.recursively_remove_reservation( reservations[ id ] );

			timeline.find( '.reservation[data-id="' + id + '"]' ).remove();

			edit_mode.changed = true;
			er_timeline.add_reservation( edit_mode );
			er_timeline.draw_reservations();

			er_sidebar.stop_edit( id );
		} );

	var er_sidebar = {

		/**
		 * Init sidebar
		 */
		init: function () {

			if ( data.pending && data.pending.length > 0 ) {
				header.find( '.pending' ).html( '<span>' + data.pending.length + '</span>' );

				var reservations_container = sidebar.find( '> .pending' ).find( '.reservations' );
				reservations_container.html( '' );

				$.each( data.pending, function ( index, reservation ) {
					var element          = $( '<div class="pending-reservation">' ),
						resource_id      = parseInt( reservation.resource, 10 ),
						found_free_space = false;

					reservation.arrival = new Date( reservation.arrival );
					reservation.departure = new Date( reservation.departure );

					if( !reservation.title ){
						reservation.title = 'No title';
					}

					element
						.html( '<span>#' + reservation.id + ' ' + reservation.title + '</span>' );

					element.bind( 'click', function () {
						er_timeline.jump_to_date( reservation.arrival );

						if ( resource_id > 0 ) {
							timeline_container.find( '.resource-handler:not([data-resource="' + reservation.resource + '"],.retracted)' ).click();
						}

						$.each( data.resources, function ( _, resource ) {
							if ( !found_free_space && ( resource_id === 0 || resource_id === resource.ID ) ) {
								if ( resource.availability_by === 'unit' ) {
									reservation.resource = resource.ID;
									reservation.space = 1;
									found_free_space = true;

									return false;
								} else {
									reservation.resource = resource.ID;

									for ( var i = 1; i <= resource.quantity; i++ ) {
										reservation.space = 1;

										//TODO this should happen after reservations are loaded
										if ( er_timeline.check_availability( reservation ) ) {
											found_free_space = true;
											break;
										}
									}
								}
							}
						} );

						if ( found_free_space ) {
							reservation.status = 'approved';

							er_timeline.add_reservation( reservation );
							er_timeline.draw_reservations();

							data.pending.splice( index );
							$( this ).remove();
							er_sidebar.init();
						}
					} );

					reservations_container.append( element );
				} );
			} else {
				header.find( '.pending' ).html( '' );

				sidebar.find( '> .pending' ).find( '.reservations' ).html( data.i18n_no_pending );
			}

			er_sidebar.display_calendar();
		},

		/**
		 * Is sidebar open
		 *
		 * @returns {boolean}
		 */
		is_open: function () {
			return sidebar.hasClass( 'expanded' );
		},

		/**
		 * Open sidebar
		 */
		open: function () {
			if ( !er_sidebar.is_open() ) {
				header.find( '.expand-sidebar' ).click();
			}
		},

		/**
		 * Close sidebar
		 */
		close: function () {
			if ( er_sidebar.is_open() ) {
				header.find( '.contract-sidebar' ).click();
			}
		},

		/**
		 * Toggle sidebar
		 *
		 * @return {boolean}
		 */
		toggle: function () {
			if ( er_sidebar.is_open() ) {
				header.find( '.contract-sidebar' ).click();
				return false;
			} else {
				header.find( '.expand-sidebar' ).click();
				return true;
			}
		},

		/**
		 * If calendar is open close sidebar, else display calendar and/or open sidebar
		 */
		toggle_calendar: function () {
			if ( !sidebar.find( '> .calendar' ).hasClass( 'visible' ) ) {
				er_sidebar.display_calendar();
				er_sidebar.open();
			} else {
				er_sidebar.toggle();
			}
		},

		/**
		 * If pending is open close sidebar, else display pending and/or open sidebar
		 */
		toggle_pending: function () {
			if( !sidebar.find( '> .pending' ).hasClass( 'visible' ) ){
				er_sidebar.display_pending();
				er_sidebar.open();
			} else {
				er_sidebar.toggle();
			}
		},

		/**
		 * Clear currently displayed section
		 */
		clear: function () {
			sidebar.find( '> .visible' ).hide().removeClass( 'visible' );
		},

		/**
		 * Display calendar
		 */
		display_calendar: function () {
			var container = sidebar.find( '> .calendar' );

			er_sidebar.clear();
			container.show().addClass( 'visible' );
		},

		/**
		 * Display pending
		 */
		display_pending: function () {
			var container = sidebar.find( '> .pending' );

			er_sidebar.clear();
			container.show().addClass( 'visible' );
		},

		/**
		 * Resets reservation details and unbinds reservation element
		 */
		stop_edit: function ( id ) {
			sidebar.find( '> .reservation-details .edit-actions' ).hide();
			sidebar.find( '> .reservation-details .stop-edit' ).html( data.i18n_allow_edit ).removeClass( 'stop-edit' ).addClass( 'allow-edit' );

			if( edit_mode ) {
				er_timeline.reservation_stop_edit( $( '.reservation[data-id="' + id + '"]' ) );
				edit_mode = false;
			}
		},

		/**
		 * Display reservation
		 *
		 * @param {Object} reservation
		 */
		display_reservation: function ( reservation ) {
			var container = sidebar.find( '> .reservation-details' ),
				container_header = container.find( '.reservation-header' ),
				resource = data.resources[ reservation.resource ];

			container_header.find( '.title' ).html( reservation.title );
			container_header.find( '.reservation-status' ).attr( 'class', 'reservation-status status-' + reservation.status );

			container.attr( 'data-reservation-id', reservation.id );

			container.find( '.reservation-preview' )
				.attr( 'data-reservation-id', reservation.id )
				.data( 'reservation-data', false );

			container.find( '.snapping' ).removeClass( 'enabled' );
			container.find( '.revert' ).attr( 'data-reservation-id', reservation.id );

			container.find( '.input-box.reservation-status' ).removeClass( 'reservation-status' );
			container.find( '.input-box.status-' + reservation.status ).addClass( 'reservation-status' );

			container.find( '.reservation-arrival' ).html( easyFormatDate( reservation.arrival, 'full' ) );
			container.find( '.reservation-departure' ).html( easyFormatDate( reservation.departure, 'full' ) );
			container.find( '.reservation-resource' ).html( resource.post_title );
			container.find( '.reservation-adults' ).html( reservation.adults );
			container.find( '.reservation-children' ).html( reservation.children );

			if( edit_mode && edit_mode.id === reservation.id ){
				container.find( '.edit-actions' ).show();
				container.find( '.allow-edit' )
					.html( data.i18n_stop_edit )
					.removeClass( 'allow-edit' )
					.addClass( 'stop-edit' );

				container.find( '.stop-edit' ).attr( 'data-reservation-id', reservation.id );
			} else {
				container.find( '.stop-edit' ).html( data.i18n_allow_edit ).removeClass( 'stop-edit' ).addClass( 'allow-edit' );
				container.find( '.allow-edit' ).attr( 'data-reservation-id', reservation.id );
				container.find( '.edit-actions' ).hide();
			}

			if( resource.availability_by !== 'unit' ){
				container.find( '.reservation-space' ).hide();
			} else {
				container.find( '.reservation-space' ).show().html( typeof resource.spaces[reservation.space] === 'undefined' ? reservation.space : resource.spaces[ reservation.space ] );
			}

			if( reservation.order_id === '0' ){
				container.find( '.reservation-order' ).html( data.i18n_no_order );
			} else {
				container.find( '.reservation-order' ).html( data.i18n_order.replace( "%s", '<a href="' + data.order_url.replace( "%s", reservation.order_id ) + '" target="_blank">#' + reservation.order_id + '</a>' ) );
			}

			if( snapping_enabled ){
				container.find( '.snapping' ).addClass( 'enabled' );
			}

			er_sidebar.clear();
			container.show().addClass( 'visible' );
			er_sidebar.open();
		},
	};

	var er_timeline = {

		/**
		 * Init timeline
		 */
		init: function () {
			today = new Date();
			reservations = [];
			selected = false;

			if ( interval === "86400" ) {
				today.setHours( 0, 0, 0, 0 );
				start.setHours( 0, 0, 0, 0 );
			} else {
				today.setHours( today.getHours(), 0, 0, 0 );

				if ( today.getDate() === start.getDate() && today.getMonth() === start.getMonth() && today.getFullYear() === start.getFullYear() ) {
					start.setHours( today.getHours(), 0, 0, 0 );
				} else {
					start.setHours( 0, 0, 0, 0 );
				}
			}

			table.find( 'td,th' ).remove();

			start = er_timeline.manipulate_date_without_offset( start, ( 15 * interval * 1000 ) * -1 );
			end = new Date( start.getTime() );
			last_query_end = new Date( start.getTime() );
			last_query_start = new Date( start.getTime() );

			for ( var i = 0; i < 50; i++ ) {
				end = er_timeline.manipulate_date_without_offset( end, interval * 1000 );
				er_timeline.generate_column( end, false );
			}

			er_timeline.load_remaining();

			timeline.scrollLeft(
				thead_main.find( 'th:nth-child(15)' ).offset().left - timeline.offset().left + timeline.scrollLeft() + 1
			);

			er_timeline.set_current_date();
			er_timeline.sync_cell_heights();
		},

		/**
		 * Add an amount in milliseconds to a data object. If there's an offset in timezones also adds that offset.
		 *
		 * @param {Date} date
		 * @param {int} amount
		 * @returns {Date}
		 */
		manipulate_date_without_offset: function ( date, amount ) {
			var new_date = new Date( date.getTime() + amount );

			if ( interval === "3600" ) {
				if ( new_date.getHours() < first_hour ) {
					if( amount >= 0 ){
						new_date = new Date( new_date.getTime() + ( first_hour * 3600000 - ( new_date.getHours() * 3600000 + new_date.getMinutes() * 60000 ) ) );
					} else {
						new_date = new Date( new_date.getTime() - 86400000 + ( new_date.getHours() * 3600000 + new_date.getMinutes() * 60000 ) - 86400000 + last_hour * 3600000 );
					}
				} else if ( new_date.getHours() > last_hour ) {
					if( amount >=  0 ){
						new_date = new Date( new_date.getTime() + 86400000 - ( new_date.getHours() * 3600000 + new_date.getMinutes() * 60000 ) + first_hour * 3600000 );
					} else {
						new_date.setHours( last_hour, 0, 0, 0);
					}
				}

				var total = new_date.getTime() - date.getTime();
				if( total > 86400000 ){
					var x = Math.floor( total/86400000 );
					new_date = new Date( new_date.getTime() - ( x * ( first_hour * 3600000 ) + x * ( 86400000 - last_hour * 3600000 ) ) );
				}
			}

			return new Date( new_date.getTime() + new_date.getTimezoneOffset() * 1000 * 60 - date.getTimezoneOffset() * 1000 * 60 );
		},

		/**
		 * Highlights currently hovered column
		 *
		 * @param {Date} date
		 */
		highlight_current: function ( date ) {
			table.find( 'td.hover, th.hover' ).removeClass( 'hover' );
			table.find( 'td[data-date="' + date.getTime() + '"], th[data-date="' + date.getTime() + '"]' ).addClass( 'hover' );
		},

		/**
		 * Set the second visible cell as current date
		 */
		set_current_date: function () {
			var currently_selected = er_timeline.manipulate_date_without_offset( start, ( ( Math.round( timeline.scrollLeft() / cell_dimensions.width ) + 1 ) * interval * 1000 ) );

			if ( interval === "3600" ) {
				currently_selected.setHours( currently_selected.getHours(), 0, 0, 0 );
			} else {
				currently_selected.setHours( 0, 0, 0, 0 );
			}

			if ( !selected || currently_selected.getDate() !== selected.getDate() || currently_selected.getMonth() !== selected.getMonth() || currently_selected.getFullYear() !== selected.getFullYear() ) {
				selected = currently_selected;

				if ( interval === "3600" ) {
					header_date.html( selected.getDate() + ' ' + er_date_picker_params.month_names[ selected.getMonth() ] + ' ' + selected.getFullYear() );
				} else {
					header_date.html( er_date_picker_params.month_names[ selected.getMonth() ] + ' ' + selected.getFullYear() );
				}

				thead_main.find( 'th.current' ).removeClass( 'current' );
				thead_main.find( 'th[data-date="' + selected.getTime() + '"]' ).addClass( 'current' );

				datepicker.datepicker( "setDate", selected );
			} else if ( interval === "3600" && currently_selected.getHours() !== selected.getHours() ) {
				//If only the hour changed in hourly view we don't change the header or datepicker
				selected = currently_selected;

				thead_main.find( 'th.current' ).removeClass( 'current' );
				thead_main.find( 'th[data-date="' + selected.getTime() + '"]' ).addClass( 'current' );
			}
		},

		/**
		 * Starts an interval to add new columns while we are scrolled all the left or right
		 */
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

		/**
		 * Clear the interval
		 */
		clear_scroll_add_interval: function () {
			if ( scroll_add !== false ) {
				clearInterval( scroll_add );
				scroll_add = false;
				er_timeline.load_remaining();
			}
		},

		/**
		 * Jumps the timeline to specified date. If date is out of timelines currently loaded data it just inits from there, else it adds the difference in columns.
		 */
		jump_to_date: function ( date ) {
			if ( date < start || date > end ) {
				start = date;
				er_timeline.init();
			} else {
				var days_between = ( selected.getTime() - date.getTime() + date.getTimezoneOffset() * 1000 * 60 - selected.getTimezoneOffset() * 1000 * 60 ) / ( interval * 1000 );

				for ( var i = 1; i <= Math.abs( days_between ); i++ ) {
					er_timeline.add_new_column( days_between > 0 );
				}

				er_timeline.set_current_date();
			}
		},

		/**
		 * Add new column, keeps track of time, checks if reservations need to be redrawn
		 *
		 * @param {boolean} at_start
		 */
		add_new_column: function ( at_start ) {
			if ( at_start ) {
				start = er_timeline.manipulate_date_without_offset( start, interval * 1000 * -1 );
				er_timeline.generate_column( start, true );

				table.find( 'th:last-child,td:last-child' ).remove();

				last_query_end = er_timeline.manipulate_date_without_offset( last_query_end, interval * 1000 * -1 );
				end = er_timeline.manipulate_date_without_offset( end, interval * 1000 * -1 );
			} else {
				end = er_timeline.manipulate_date_without_offset( end, interval * 1000 );

				er_timeline.generate_column( end, false );

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
									content;

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
									.addClass( parseInt( result, 10 ) === quantity ? 'unavailable' : '' );
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

		update_reservation: function( id ){
			var reservation = reservations[ id ];
			$.ajax( {
				url:     data.ajax_url,
				data:    {
					action:     'easyreservations_timeline_update_reservation',
					security:   data.nonce,
					id: id,
					arrival: easyFormatDate( reservation.arrival, 'full' ),
					departure: easyFormatDate( reservation.departure, 'full' ),
					status: reservation.status,
					resource: reservation.resource,
					space: reservation.space,
					title: reservation.title
				},
				type:    'POST',
				success: function ( response ) {
					if ( response.data ) {
					}

					if ( response.reservation ) {
						er_timeline.add_reservation( response.reservation );

						er_timeline.draw_reservations();
					}
				}
			} );
		},

		/**
		 * Draw reservations ordered by arrival until we have to remove others down the line, if so start process again
		 */
		draw_reservations: function () {
			var queue     = [],
				completed = true;

			$.each( reservations, function ( _, reservation ) {
				if ( reservation && reservation.changed && reservation.status !== 'pending' ) {
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
			if ( completed && queue.length > 0 ) {
				er_timeline.sync_cell_heights();
			}
		},

		/**
		 * Start the recursive removing process beginning from a reservation
		 *
		 * @param {object} reservation
		 */
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
		},

		/**
		 * Recursively removes reservation data from cells
		 *
		 * @param {jQuery} cell timeline cell to start at
		 * @param {int} depths to start at
		 * @param {int} id of object that gets removed to begin with
		 */
		recursively_remove_reservations: function ( cell, depths, id ) {
			var cell_reservations    = cell.data( 'reservations' ),
				new_cell_reservation = [],
				found_start          = false,
				max_depths           = 0;

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
				er_timeline.recursively_remove_reservations( cell.next(), found_start, id );
			}
		},

		/**
		 * Unbind jquery dom element from draggable, resizable and the title to not be contenteditable
		 *
		 * @param {jQuery} element
		 */
		reservation_stop_edit: function ( element ) {
			element
				.draggable( 'destroy' )
				.resizable( 'destroy' );

			element.find( '.title' ).attr( 'contenteditable', 'false' );
		},

		/**
		 * Bind jquery dom element to be draggable, resizable and the title to be contenteditable
		 *
		 * @param {jQuery} element
		 */
		reservation_allow_edit: function ( element ) {
			element
				.draggable( {
					snap:          snapping_enabled ? false : '.reservation',
					snapTolerance: 3,
					scroll:        false,
					helper:        "clone",
					appendTo:      ".timeline",
					stack:         '.reservation',
					scope:         'reservations',
					cancel:        '.title',
					revert:        function ( event, ui ) {
						if ( event )
							return event;

						//on older version of jQuery use $(this).data("draggable")
						//on 2.x versions of jQuery use $(this).data("ui-draggable")
						$( this ).data( "uiDraggable" ).originalPosition = {
							top:  drag_start_position.top - 1,
							left: drag_start_position.left
						};

						return !event;
					},

					start: function ( event, ui ) {
						drag_start_position = ui.originalPosition;
						drag_start_offset = ui.offset;
					},

					drag: function ( event, ui ) {
						var id             = parseInt( ui.helper.attr( 'data-id' ), 10 ),
							reservation    = reservations[ id ],
							difference     = interval / cell_dimensions.width * ( ui.position.left - drag_start_position.left ),
							mouse_position = mouse_pos_x - timeline.offset().left;

						if ( snapping_enabled ) {
							var step = Math.round( ( ui.position.left - drag_start_position.left ) / cell_dimensions.width );
							difference = step * interval;
							ui.position.left = drag_start_position.left + ( step * cell_dimensions.width );
						}

						tooltip
							.html(
								easyFormatTime( er_timeline.manipulate_date_without_offset( reservation.arrival, difference * 1000 ) ) + ' - ' +
								easyFormatTime( er_timeline.manipulate_date_without_offset( reservation.departure, difference * 1000 ) )
							)
							.css( {
								'top':     mouse_pos_y,
								'left':    Math.min( mouse_pos_x - 130, timeline.width() ),
								'display': 'block'
							} );

						if ( drag_snap_top !== false ) {
							ui.position.top = drag_snap_top - drag_start_offset.top + drag_start_position.top;
						}

						if ( ( mouse_position > 0 && mouse_position < 15 ) || timeline.width() - mouse_position < 15 ) {
							if ( scroll_action === false ) {
								scroll_action = setInterval( function () {
									if ( scroll_action !== false ) {
										var mouse_position = mouse_pos_x - timeline.offset().left;
										if ( scroll_add === false && ( ( mouse_position > 0 && mouse_position < 15 ) || timeline.width() - mouse_position < 15 ) ) {
											er_timeline.add_new_column( mouse_position < 15 );
											drag_start_position.left = drag_start_position.left + ( mouse_position < 15 ? cell_dimensions.width : cell_dimensions.width * -1 );
										}

										timeline.scrollLeft( Math.max( 1, timeline.scrollLeft() + ( mouse_position < 15 ? cell_dimensions.width * -1 : cell_dimensions.width ) ) );
										//timeline.animate( { scrollLeft: ( mouse_pos_x - timeline.offset().left < 10 ? '-=' : '+=' ) + cell_dimensions.width + 'px' }, 130 );
									}
								}, 130 );
							}
						} else if ( scroll_action !== false ) {
							clearInterval( scroll_action );
							scroll_action = false;
							er_timeline.load_remaining();
						}
					},

					stop: function () {
						tooltip.css( 'display', 'none' );
						if ( scroll_action !== false ) {
							clearInterval( scroll_action );
							scroll_action = false;
							er_timeline.load_remaining();
						}
					}
				} )
				.resizable( {
					handles:   'e, w',
					grid:      snapping_enabled ? [96, 26] : false,
					maxWidth:  cell_dimensions.width * 50,
					scroll:    0,
					minHeight: 0,
					minWidth:  4,
					start:     function ( event, ui ) {
						ui.originalElement.attr( 'style', 'left: ' + ui.originalElement.css( 'left' ) + ';top: ' + ui.originalElement.css( 'top' ) + ' !important;width: ' + ui.originalElement.css( 'width' ) );
					},
					resize:    function ( event, ui ) {
						var id                   = parseInt( ui.element.attr( 'data-id' ), 10 ),
							reservation          = reservations[ id ],
							arrival_difference   = interval / cell_dimensions.width * ( ui.position.left - ui.originalPosition.left ),
							departure_difference = interval / cell_dimensions.width * ( ui.size.width + 2 ),
							message;

						if ( ui.position.left - ui.originalPosition.left !== 0 ) {
							message = easyFormatTime( er_timeline.manipulate_date_without_offset( reservation.arrival, arrival_difference * 1000 ) );
						} else if ( ui.size.width - ui.originalSize.width !== 0 ) {
							message = easyFormatTime( er_timeline.manipulate_date_without_offset( reservation.arrival, departure_difference * 1000 ) );
						} else {
							message = easyFormatTime( er_timeline.manipulate_date_without_offset( reservation.arrival, arrival_difference * 1000 ) );
							message += ' - ';
							message += easyFormatTime( er_timeline.manipulate_date_without_offset( reservation.arrival, departure_difference * 1000 ) );
						}

						tooltip
							.html( message )
							.css( {
								'top':     mouse_pos_y,
								'left':    Math.min( mouse_pos_x - 130, timeline.width() ),
								'display': 'block'
							} );
					},
					stop:      function ( event, ui ) {
						var id                   = parseInt( ui.element.attr( 'data-id' ), 10 ),
							arrival_difference   = interval / cell_dimensions.width * ( ui.position.left - ui.originalPosition.left ),
							departure_difference = interval / cell_dimensions.width * ( ui.size.width + 2 ),
							reservation          = {
								id:        id,
								arrival:   er_timeline.manipulate_date_without_offset( reservations[ id ].arrival, arrival_difference * 1000 ),
								departure: er_timeline.manipulate_date_without_offset( reservations[ id ].arrival, ( arrival_difference * 1000 ) + ( departure_difference * 1000 ) ),
								resource:  reservations[ id ].resource,
								space:     reservations[ id ].space
							};

						if ( data.resources[ reservations[ id ].resource ].availability_by !== 'unit' || er_timeline.check_availability( reservation ) ) {
							er_timeline.recursively_remove_reservation( reservations[ id ] );

							reservations[ id ].arrival = reservation.arrival;
							reservations[ id ].departure = reservation.departure;
							reservations[ id ].changed = true;


							er_timeline.draw_reservations();
						} else {
							ui.helper.animate(
								{
									width: ui.originalSize.width,
									left:  ui.originalPosition.left
								},
								500,
								function () {
								}
							);
						}

						tooltip.css( 'display', 'none' );
					}
				} );

			element.find('.title').attr( 'contenteditable', 'true' );
		},

		/**
		 * Draw single reservation
		 *
		 * @param {object} reservation
		 * @returns {boolean}
		 */
		draw_reservation: function ( reservation ) {
			var id           = parseInt( reservation.id, 10 ),
				date         = new Date( reservation.arrival.getTime() ),
				end          = new Date( reservation.departure.getTime() ),
				element      = $( '<div class="reservation">' ),
				width        = er_timeline.manipulate_date_without_offset( reservation.arrival , reservation.departure.getTime() - reservation.arrival.getTime() ).getTime() - er_timeline.manipulate_date_without_offset( reservation.arrival, 0 ).getTime(), //TODO remove offset?
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
						element.css( 'left', ( ( ( er_timeline.manipulate_date_without_offset( reservation.arrival, 0 ) - date.getTime() ) / 1000 / interval * cell_dimensions.width ) - 1 ) + 'px' );

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
				reservations[ id ].changed = false;

				return true;
				//delete reservations[ id ];
			} else {
				if ( changed_any_reservation ) {
					//We changed other reservations and begin drawing from the first changed reservation by arrival again

					return false;
				} else {
					//We can draw this reservations now

					element
						.html( '<span class="wrapper"><span class="sticky"><span class="id">' + id + '</span><div class="title">' + reservation.title + '</div></span></span>' )
						.css( 'min-width', width_px + 'px' )
						.css( 'max-width', width_px + 'px' )
						.css( 'top', '0px' )
						.css( 'position', 'absolute' )
						.addClass( reservation.status )
						.attr( 'data-tip', reservation.id )
						.attr( 'data-id', reservation.id );

					var was_there = $( '.reservation[data-id="' + id + '"]' ).remove();

					if ( was_there.length > 0 ) {
						element.addClass( 'fade-in-fast' );
					}
					
					if( edit_mode && edit_mode.id === id ){
						er_sidebar.display_reservation( reservation );
						er_timeline.reservation_allow_edit( element );
						timeline.find( '.reservation.selected' ).removeClass( 'selected' );
						element.addClass( 'selected' );
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
		 * @param {object} reservation
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
		 * @param {date} date of cell to add
		 * @param {boolean} at_start wether to add it at start
		 */
		generate_column: function ( date, at_start ) {
			var header_main,
				header_class = '',
				tbody_number = 0,
				i            = 0,
				day          = date.getDay() === 0 ? 6 : date.getDay() - 1;

			if ( interval === "86400" ) {
				header_main = $( '<th><div class="date"><span>' + easyFormatDate( date, 'd' ) + '</span><div>' + er_date_picker_params.day_names_min[ day ] + '</div></div></th>' );

				if ( date.getDate() === 1 ) {
					header_main.append( $( '<div class="first-of-month"></div>' ) );
				}
			} else {
				var last_char   = er_both_params.time_format.charAt( er_both_params.time_format.length - 1 ),
					description = easyAddZero( date.getMinutes() );

				if ( last_char === 'a' ) {
					description = date.getHours() >= 12 ? 'pm' : 'am';
				} else if ( last_char === 'a' ) {
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
					real_today    = new Date(),
					difference;

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
					$( thead[ tbody_number ] ).find( 'tr' ).prepend( cell_header );
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
						$( tbody[ tbody_number ] ).find( 'tr:nth-child(' + i + ')' ).prepend( cell );
					} else {
						$( tbody[ tbody_number ] ).find( 'tr:nth-child(' + i + ')' ).append( cell );
					}

					cell
						.droppable( {
							scope:     "reservations", //we only accept reservations
							tolerance: "pointer", //targets the cell under the mouse
							drop:      function ( event, ui ) {
								var cell = $( this );

								if ( last_hover.getAttribute( "data-space" ) ) {
									cell = $( last_hover );
								}

								var id          = parseInt( ui.draggable.attr( 'data-id' ), 10 ),
									difference  = interval / cell_dimensions.width * ( ui.position.left - drag_start_position.left ),
									reservation = {
										id:        id,
										arrival:   er_timeline.manipulate_date_without_offset( reservations[ id ].arrival, difference * 1000 ),
										departure: er_timeline.manipulate_date_without_offset( reservations[ id ].departure, difference * 1000 ),
										resource:  parseInt( cell.attr( 'data-resource' ), 10 ),
										space:     parseInt( cell.attr( 'data-space' ), 10 )
									};

								if ( data.resources[ reservation.resource ].availability_by !== 'unit' || er_timeline.check_availability( reservation ) ) {
									er_timeline.recursively_remove_reservation( reservations[ id ] );

									reservations[ id ].arrival = reservation.arrival;
									reservations[ id ].departure = reservation.departure;
									reservations[ id ].resource = reservation.resource;
									reservations[ id ].space = reservation.space;
									reservations[ id ].changed = true;

									if ( reservations[ id ].status === 'pending' ) {
										reservations[ id ].status = 'approved';
									}

									ui.helper.remove();

									er_timeline.draw_reservations();
								} else {
								}


							},
							over:      function ( event, ui ) {
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

	timeline_container.insertAfter( 'hr.wp-header-end' );
	tooltip.insertAfter( 'hr.wp-header-end' );
	sidebar.hide();

	if ( interval === "86400" ) {
		today.setHours( 0, 0, 0, 0 );
		header.find( '.daily' ).addClass( 'active' );
	} else {
		today.setHours( today.getHours(), 0, 0, 0 );
		header.find( '.hourly' ).addClass( 'active' );
	}

	er_sidebar.init();
	er_timeline.init();
} )
( jQuery, er_timeline_params );