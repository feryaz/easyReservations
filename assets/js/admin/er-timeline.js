/* global moment, easyFormatDate, easyFormatTime, easyAddZero, er_timeline_params, er_both_params, er_date_picker_params */

( function( $, data ) {
	const tooltip = $( '.er-timeline-tooltip' ),
		master = $( '.er-timeline' ),
		datepicker = $( '#timeline-datepicker' ),
		sidebar = master.find( 'div.sidebar' ),
		timeline = master.find( 'div.timeline' ),
		header = master.find( 'div.header' ),
		resources = master.find( 'div.resources' ),
		resourcesVertical = resources.find( '.vertical-scroll' ),
		resourcesTbody = resources.find( 'table tbody' ),
		headerDate = header.find( '.date' ),
		tableVertical = timeline.find( 'div.vertical-scroll' ),
		tableHorizontal = tableVertical.find( 'div.horizontal-scroll' ),
		table = timeline.find( 'div.vertical-scroll table' ),
		scroller = timeline.find( 'div.horizontal-scroll' ),
		tableHeader = timeline.find( 'thead.main tr' ),
		thead = table.find( 'thead:not(.main)' ),
		tbody = table.find( 'tbody' ),
		cells = 60,
		cellDimensions = { height: 32, width: 96 },
		resourcesSort = Object.keys( data.resources );

	let reservations = [], //Reservation data
		selected = false, //Current time
		today = moment(), //Current time
		start = moment(), //Date of the first column in timeline
		end = false, //Date of the last column in timeline
		dragStartPosition = false, //Dragged reservations starting position
		dragStartOffset = false, //Dragged reservations starting offset
		dragSnapTop = false, //Tells the draggable reservation what top to snap to
		editMode = false, //If we are in edit mode this is a backup of the reservation object to revert to
		addMode = false, //If we are in add mode this is a string (reservation/resource/global)
		changedAnyReservation = false, //If any other reservations got changed while attempting to draw a reservation
		mousePosX = 0, //Mouse position x gets updated while hovering timeline
		mousePosY = 0, //Mouse position y gets updated while hovering timeline
		scrollDrag = false, //position from which scroll was started in timeline header
		scrollAction = false, //js interval that scrolls timeline
		scrollAdd = false, //js interval that adds new calendar columns based on scroll
		placeholder = false,
		lastHover = 0, //The DOM element that got hovered as last in timeline
		lastQueryStart = 0, //From when we last queried data at the start of the timeline
		lastQueryEnd = 0, //Until when we last queried data at the end of the timeline
		snappingEnabled = data.default_snapping === '1', //If the snapping mode is enabled for dragging and resizing
		interval = '86400', //Default interval of timeline
		intervalString = 'days';

	resourcesSort.sort( function( k, v ) {
		return data.resources[ k ].menu_order - data.resources[ v ].menu_order;
	} );

	header
		.on( 'click', '.expand-sidebar', function() {
			sidebar.addClass( 'expanded' ).show();
			$( this )
				.removeClass( 'expand-sidebar' )
				.addClass( 'contract-sidebar' );
		} )
		.on( 'click', '.contract-sidebar', function() {
			sidebar.removeClass( 'expanded' ).hide( 300, 'linear' );
			$( this )
				.removeClass( 'contract-sidebar' )
				.addClass( 'expand-sidebar' );
		} )
		.on( 'click', '.hourly', function() {
			if ( ! $( this ).hasClass( 'active' ) ) {
				timeline.addClass( 'hourly' );
				header.find( '.daily' ).removeClass( 'active' );
				$( this ).addClass( 'active' );
				start = moment( selected );
				interval = '3600';
				intervalString = 'hours';
				erTimeline.init();
			}
		} )
		.on( 'click', '.daily', function() {
			if ( ! $( this ).hasClass( 'active' ) ) {
				timeline.removeClass( 'hourly' );
				header.find( '.hourly' ).removeClass( 'active' );
				$( this ).addClass( 'active' );
				start = moment( selected );
				interval = '86400';
				intervalString = 'days';
				erTimeline.init();
			}
		} )
		.on( 'click', '.pending', function() {
			erSidebar.toggle_pending();
		} )
		.on( 'click', '.date', function() {
			erSidebar.toggle_calendar();
		} )
		.on( 'click', '.today', function() {
			erTimeline.jump_to_date( today );
		} )
		.on( 'click', 'a.start-add', function() {
			addMode = $( this ).attr( 'data-target' );
		} )
		.on( 'click', '.cancel-add', function() {
			addMode = false;
		} );

	datepicker.on( 'change', function( e ) {
		erTimeline.jump_to_date( moment( $( this ).datepicker( 'getDate' ) ) );
	} );

	tableHeader
		.on( 'mousedown', 'th', function( e ) {
			//Find out where the drag started
			scrollDrag = scroller.scrollLeft() + e.pageX;
		} );

	tableVertical.on( 'scroll', function() {
		resourcesVertical.css( 'margin-top', -$( this ).scrollTop() );
	} );

	$( window )
		.mouseup( function( e ) {
			tableHeader.css( 'cursor', 'grab' );
			clearInterval( scrollAction );
			scrollAction = false;
			scrollDrag = false;
			erTimeline.clear_scroll_add_interval();
			tooltip.css( 'display', 'none' );

			if ( placeholder ) {
				const title = prompt( data.i18n_enter_title, '' );

				if ( title !== null ) {
					const direction = placeholder.attr( 'data-direction' ),
						width = parseInt( placeholder.css( 'width' ), 10 ) / cellDimensions.width * interval,
						dateStart = moment( parseInt( placeholder.attr( 'data-start' ), 10 ) * 1000 ),
						dateEnd = direction === 'left' ? moment( dateStart ).subtract( width, 'seconds' ) : moment( dateStart ).add( width, 'seconds' ),
						extraData = {
							add: addMode,
							arrival: easyFormatDate( dateStart < dateEnd ? dateStart : dateEnd, 'full' ),
							departure: easyFormatDate( dateEnd > dateStart ? dateEnd : dateStart, 'full' ),
							resource: parseInt( placeholder.attr( 'data-resource' ), 10 ),
							space: parseInt( placeholder.attr( 'data-space' ), 10 ),
							title: title,
						};

					if ( interval === '86400' ) {
						dateStart.startOf( 'day' );
						dateEnd.startOf( 'day' );
					} else {
						dateStart.startOf( 'hour' );
						dateEnd.startOf( 'hour' );
					}

					if ( dateEnd > dateStart ) {
						dateEnd.add( 1, intervalString );
					} else {
						dateStart.subtract( 1, intervalString );
					}

					erTimeline.load_data(
						dateStart < dateEnd ? dateStart : dateEnd,
						dateEnd > dateStart ? dateEnd : dateStart,
						extraData
					);
				}

				placeholder.remove();
				placeholder = false;
				addMode = false;
			}
		} );

	master
		.on( 'click', '.resource-handler', function() {
			const elem = $( this ).parent().parent().parent().parent().next(),
				tbodyIndex = ( elem.index() / 2 ) - 0.5;

			if ( $( this ).hasClass( 'retracted' ) ) {
				$( this ).removeClass( 'retracted' );
				elem.removeClass( 'retracted' );

				$( tbody[ tbodyIndex ] ).removeClass( 'retracted' ).show();
				elem.show();
			} else {
				$( this ).addClass( 'retracted' );
				elem.addClass( 'retracted' );

				$( tbody[ tbodyIndex ] ).addClass( 'retracted' ).hide();
				elem.hide();
			}
		} )
		.on( 'mousedown', '.next', function() {
			if ( scrollAction === false ) {
				erTimeline.add_new_column( false );
				erTimeline.set_current_date();

				scrollAction = setInterval( function() {
					erTimeline.add_new_column( false );
					erTimeline.set_current_date();
				}, 100 );
			}
		} )
		.on( 'mousedown', '.prev', function() {
			if ( scrollAction === false ) {
				erTimeline.add_new_column( true );
				erTimeline.set_current_date();

				scrollAction = setInterval( function() {
					erTimeline.add_new_column( true );
					erTimeline.set_current_date();
				}, 100 );
			}
		} );

	timeline
		.mousemove( function( e ) {
			//Store mouse positions
			mousePosX = e.pageX;
			mousePosY = e.pageY;

			//Handle drag scrolling in timeline header
			if ( scrollDrag && e.which === 1 ) {
				scroller.scrollLeft( Math.min( scrollDrag - e.pageX < 1 ? 1 : scrollDrag - e.pageX, tableHeader.width() - timeline.width() - cellDimensions.width ) );
				erTimeline.set_current_date();

				if ( scrollAdd === false ) {
					tableHeader.css( 'cursor', 'grabbing' );
					erTimeline.start_scroll_add_interval();
				}
			}

			//Add tooltips mode & placeholder
			if ( placeholder ) {
				const pageX = -dragStartPosition.left + mousePosX - parseInt( placeholder.attr( 'data-pageX' ), 10 ),
					dateStart = moment( parseInt( placeholder.attr( 'data-start' ), 10 ) * 1000 ),
					difference = ( pageX ) / cellDimensions.width * interval;

				let tooltipFirst = '',
					tooltipSecond = '';

				if ( -dragStartPosition.left + pageX > 0 ) {
					placeholder
						.attr( 'data-direction', 'right' )
						.css( 'margin-left', dragStartPosition.left )
						.css( 'width', pageX + ( -dragStartPosition.left ) );

					tooltipFirst = easyFormatTime( dateStart );
					tooltipSecond = easyFormatTime( dateStart.add( difference, 'seconds' ) );
				} else {
					placeholder
						.attr( 'data-direction', 'left' )
						.css( 'margin-left', pageX )
						.css( 'width', ( -pageX ) + dragStartPosition.left );

					tooltipSecond = easyFormatTime( dateStart );
					tooltipFirst = easyFormatTime( dateStart.add( difference, 'seconds' ) );
				}

				tooltip
					.html(
						tooltipFirst + ' - ' +
						tooltipSecond
					)
					.css( {
						'top': mousePosY,
						'left': Math.min( mousePosX - 130, timeline.width() ),
						'display': 'block',
					} );

				erTimeline.scroll_dragging();
			} else if ( addMode && e.target.getAttribute( 'data-space' ) ) {
				tooltip
					.html(
						easyFormatTime( moment( start ).add( interval / cellDimensions.width * ( Math.floor( e.target.offsetLeft + e.offsetX ) + 1 ), 'seconds' ) )
					)
					.css( {
						top: mousePosY,
						left: Math.min( mousePosX - 130, timeline.width() ),
						display: 'block',
					} );
			}

			//Handle hover
			if ( lastHover !== e.target ) {
				lastHover = e.target;

				//if( e.target.getAttribute( "data-space" ) ) console.log($(e.target).data('reservations'));
				if ( lastHover.getAttribute( 'data-resource' ) ) {
					erTimeline.highlight_current( moment( parseInt( e.target.getAttribute( 'data-date' ), 10 ) * 1000 ), parseInt( e.target.getAttribute( 'data-resource' ), 10 ), parseInt( e.target.getAttribute( 'data-space' ), 10 ) );
				} else {
					tableHeader.find( 'th.hover' ).removeClass( 'hover' );
				}

				//If we hover over cell or reservation store top position to snap draggable to
				if ( lastHover.getAttribute( 'data-space' ) ) {
					dragSnapTop = $( lastHover ).offset().top;
				} else if ( lastHover.getAttribute( 'data-id' ) ) {
					dragSnapTop = $( lastHover ).parent().offset().top;
				}
			}
		} )
		.mouseleave( function() {
			tableHeader.css( 'cursor', 'grab' );
			tableHeader.find( 'th.hover' ).removeClass( 'hover' );
			resourcesTbody.find( 'td.hover' ).removeClass( 'hover' );
			clearInterval( scrollAction );
			scrollAction = false;
			scrollDrag = false;
			lastHover = false;
			tooltip.css( 'display', 'none' );
			erTimeline.clear_scroll_add_interval();

			if ( placeholder ) {
				placeholder.remove();
				placeholder = false;
			}
		} )
		.on( 'mousedown', '.cell', function( e ) {
			if ( addMode ) {
				const date = moment( parseInt( $( this ).attr( 'data-date' ), 10 ) * 1000 ).add( interval / ( cellDimensions.width ) * Math.floor( e.offsetX + 1 ), 'seconds' );
				let attach = this;

				placeholder = $( '<div class="placeholder">' );

				dragStartPosition = { top: 0, left: 0 };

				if ( addMode === 'resource' ) {
					attach = this.parentNode.parentNode;
				} else if ( addMode === 'global' ) {
					attach = this.parentNode.parentNode.parentNode;
				}

				placeholder
					.css( 'top', attach.offsetTop )
					.css( 'left', this.offsetLeft + e.offsetX + 1 )
					.css( 'height', $( attach ).height() )
					.attr( 'data-pageX', e.pageX )
					.attr( 'data-resource', $( this ).attr( 'data-resource' ) )
					.attr( 'data-space', $( this ).attr( 'data-space' ) )
					.attr( 'data-start', date.unix() );

				tableHorizontal.append( placeholder );
			}
		} )
		.on( 'click', '.reservation', function() {
			const id = parseInt( $( this ).attr( 'data-id' ), 10 );

			timeline.find( '.reservation.selected' ).removeClass( 'selected' );
			$( this ).addClass( 'selected' );

			erSidebar.draw_reservation( reservations[ id ] );
		} )
		.on( 'keydown', '.reservation .title', function( e ) {
			if ( e.keyCode === 13 ) {
				const id = $( this ).parents( '.reservation' ).attr( 'data-id' );
				reservations[ id ].title = $( this ).html();
				$( this ).trigger( 'blur' );
				erSidebar.draw_reservation( reservations[ id ] );
				return false;
			}
		} );

	sidebar
		.on( 'click', '.allow-edit', function() {
			const id = parseInt( $( this ).attr( 'data-reservation-id' ), 10 );

			if ( editMode ) {
				erTimeline.update_reservation( id );
				erSidebar.stop_edit( editMode.id );
			}

			erTimeline.set_element_as_droppable( table.find( 'td.cell' ) );
			editMode = JSON.parse( JSON.stringify( reservations[ id ] ) );
			addMode = false;

			sidebar.find( '> .reservation-details .edit-actions' ).show();
			erTimeline.reservation_allow_edit( table.find( '.reservation[data-id="' + id + '"]' ) );
			$( this ).html( data.i18n_stop_edit ).addClass( 'stop-edit' ).removeClass( 'allow-edit' );
		} )
		.on( 'click', '.stop-edit', function() {
			const id = parseInt( $( this ).attr( 'data-reservation-id' ), 10 );
			erTimeline.update_reservation( id );
			erSidebar.stop_edit( id );
		} )
		.on( 'click', '.status', function() {
			if ( ! $( this ).hasClass( 'reservation-status' ) ) {
				const id = parseInt( $( this ).parent().parent().attr( 'data-reservation-id' ), 10 ),
					status = $( this ).attr( 'data-status' );

				reservations[ id ].status = status;
				table.find( '.reservation[data-id="' + id + '"]' ).removeClass( 'approved checked completed' ).addClass( status );

				erSidebar.draw_reservation( reservations[ id ] );
				erTimeline.update_reservation( id );
			}
		} )
		.on( 'click', '.snapping', function() {
			if ( $( this ).hasClass( 'enabled' ) ) {
				snappingEnabled = false;
				$( this ).removeClass( 'enabled' );
			} else {
				snappingEnabled = true;
				$( this ).addClass( 'enabled' );
			}
		} )
		.on( 'click', '.revert', function() {
			const id = parseInt( $( this ).attr( 'data-reservation-id' ), 10 );
			erTimeline.recursively_remove_reservation( reservations[ id ] );

			table.find( '.reservation[data-id="' + id + '"]' ).remove();

			editMode.changed = true;
			erTimeline.add_reservation( editMode );
			erTimeline.draw_reservations();

			erSidebar.stop_edit( id );
		} );

	const erSidebar = {

		/**
		 * Init sidebar
		 */
		init: function() {

		},

		/**
		 * Is sidebar open
		 *
		 * @return {boolean}
		 */
		is_open: function() {
			return sidebar.hasClass( 'expanded' );
		},

		/**
		 * Open sidebar
		 */
		open: function() {
			if ( ! erSidebar.is_open() ) {
				header.find( '.expand-sidebar' ).trigger( 'click' );
			}
		},

		/**
		 * Close sidebar
		 */
		close: function() {
			if ( erSidebar.is_open() ) {
				header.find( '.contract-sidebar' ).trigger( 'click' );
			}
		},

		/**
		 * Toggle sidebar
		 *
		 * @return {boolean}
		 */
		toggle: function() {
			if ( erSidebar.is_open() ) {
				header.find( '.contract-sidebar' ).trigger( 'click' );
				return false;
			}
			header.find( '.expand-sidebar' ).trigger( 'click' );
			return true;
		},

		/**
		 * If calendar is open close sidebar, else display calendar and/or open sidebar
		 */
		toggle_calendar: function() {
			if ( ! sidebar.find( '> .calendar' ).hasClass( 'visible' ) ) {
				erSidebar.display_calendar();
				erSidebar.open();
			} else {
				erSidebar.toggle();
			}
		},

		/**
		 * If pending is open close sidebar, else display pending and/or open sidebar
		 */
		toggle_pending: function() {
			if ( ! sidebar.find( '> .pending' ).hasClass( 'visible' ) ) {
				erSidebar.display_pending();
				erSidebar.open();
			} else {
				erSidebar.toggle();
			}
		},

		/**
		 * Clear currently displayed section
		 */
		clear: function() {
			sidebar.find( '> .visible' ).hide().removeClass( 'visible' );
		},

		/**
		 * Display calendar
		 */
		display_calendar: function() {
			const container = sidebar.find( '> .calendar' );

			erSidebar.clear();
			container.show().addClass( 'visible' );
		},

		/**
		 * Display pending
		 */
		display_pending: function() {
			const container = sidebar.find( '> .pending' );

			erSidebar.clear();
			container.show().addClass( 'visible' );
		},

		/**
		 * Resets reservation details and unbinds reservation element
		 *
		 * @param id
		 */
		stop_edit: function( id ) {
			sidebar.find( '> .reservation-details .edit-actions' ).hide();
			sidebar.find( '> .reservation-details .stop-edit' ).html( data.i18n_allow_edit ).removeClass( 'stop-edit' ).addClass( 'allow-edit' );

			if ( editMode ) {
				erTimeline.reservation_stop_edit( table.find( '.reservation[data-id="' + id + '"]' ) );
				editMode = false;
			}
		},

		draw_today: function() {
			const arrivals = sidebar.find( '> .calendar .arrivals' ),
				departures = sidebar.find( '> .calendar .departures' ),
				lastChar = er_both_params.time_format.charAt( er_both_params.time_format.length - 1 );

			let date,
				add,
				same;

			arrivals.html( '' );
			departures.html( '' );

			$.each( reservations, function( _, reservation ) {
				if ( reservation ) {
					add = false;
					same = false;

					if ( reservation.arrival.date() === selected.date() && reservation.arrival.month() === selected.month() && reservation.arrival.year() === selected.year() ) {
						add = 'arrival';
						date = reservation.arrival;
						same = reservation.departure.date() === date.date() && reservation.departure.month() === date.month() && reservation.departure.year() === date.year();
					} else if ( reservation.departure.date() === selected.date() && reservation.departure.month() === selected.month() && reservation.departure.year() === selected.year() ) {
						add = 'departure';
						date = reservation.departure;
					}

					if ( add ) {
						const element = $( '<div class="today-reservation">' );

						element
							.attr( 'data-id', reservation.id )
							.append(
								'<span class="date">' +
								'<span class="hour">' + easyAddZero( date.hour() ) + '</span>' +
								'<span class="minute">' + easyAddZero( date.minute() ) + '</span>' +
								'<span class="ampm">' + ( lastChar === 'a' ? ( date.hour() >= 12 ? 'pm' : 'am' ) : ( lastChar === 'A' ? ( date.hour() >= 12 ? 'PM' : 'AM' ) : '' ) ) + '</span>' +
								'</span>'
							)
							.append(
								'<div>' +
								'<div class="title"><span class="id reservation-status background status-' + reservation.status + '">' + reservation.id + '</span>' + reservation.title + '</div>' +
								'<div class="resource">' + ( reservation.resource > 0 ? data.resources[ reservation.resource ].post_title : data.i18n_no_resource ) + '</div>' +
								'<div class="date"><span class="' + add + '"></span>' + ( same ? easyFormatTime( reservation.departure ) : easyFormatDate( add === 'arrival' ? reservation.departure : reservation.arrival, 'full' ) ) + '</div>' +
								'</div>'
							)
							.on( 'click', function() {
								const id = parseInt( $( this ).attr( 'data-id' ), 10 );
								table.find( '.reservation[data-id="' + id + '"]' ).trigger( 'click' );
							} );

						if ( add === 'arrival' ) {
							arrivals.append( element );
						} else {
							departures.append( element );
						}
					}
				}
			} );

			if ( arrivals.is( ':empty' ) ) {
				arrivals.html( '<div class="today-reservation">' + data.i18n_no_arrivals + '</div>' );
			}

			if ( departures.is( ':empty' ) ) {
				departures.html( '<div class="today-reservation">' + data.i18n_no_departures + '</div>' );
			}
		},

		draw_pending: function() {
			if ( data.pending && data.pending.length > 0 ) {
				const reservationsContainer = sidebar.find( '> .pending' ).find( '.reservations' );

				header.find( '.pending' ).html( '<span>' + data.pending.length + '</span>' );

				reservationsContainer.html( '' );

				$.each( data.pending, function( index, reservation ) {
					const element = $( '<div class="pending-reservation">' ),
						resourceId = parseInt( reservation.resource, 10 );

					let foundFreeSpace = false;

					reservation.id = parseInt( reservation.id, 10 );
					reservation.arrival = moment( reservation.arrival );
					reservation.departure = moment( reservation.departure );

					if ( ! reservation.title ) {
						reservation.title = 'No title';
					}

					element
						.html(
							'<span class="id">' + reservation.id + '</span><div>' +
							'<div class="title">' + reservation.title + '</div>' +
							'<div class="resource">' + ( reservation.resource > 0 ? data.resources[ reservation.resource ].post_title : data.i18n_no_resource ) + '</div>' +
							'<div class="date">' + easyFormatDate( reservation.arrival, 'full' ) + '</div>' +
							'<div class="date">' + easyFormatDate( reservation.departure, 'full' ) + '</div>' +
							'</div>'
						);

					element.on( 'click', function() {
						erTimeline.jump_to_date( reservation.arrival );

						if ( resourceId > 0 ) {
							resources.find( '.resource-handler:not([data-resource="' + reservation.resource + '"],.retracted),.resource-handler.retracted[data-resource="' + reservation.resource + '"]' ).trigger( 'click' );
						}

						$.each( resourcesSort, function( _, currentResourceID ) {
							if ( ! foundFreeSpace && ( resourceId === 0 || resourceId === parseInt( currentResourceID, 10 ) ) ) {
								reservation.resource = parseInt( currentResourceID, 10 );

								if ( data.resources[ currentResourceID ].availability_by !== 'unit' ) {
									reservation.space = 1;
									foundFreeSpace = true;

									return false;
								}


								for ( let i = 1; i <= data.resources[ currentResourceID ].quantity; i++ ) {
									reservation.space = i;

									//TODO this should happen after reservations are loaded
									if ( erTimeline.check_availability( reservation ) ) {
										foundFreeSpace = true;
										break;
									}
								}
							}
						} );

						if ( foundFreeSpace ) {
							if ( editMode ) {
								erTimeline.update_reservation( editMode.id );
								erSidebar.stop_edit( editMode.id );
							}

							editMode = JSON.parse( JSON.stringify( reservation ) );
							erTimeline.set_element_as_droppable( table.find( 'td.cell' ) );

							reservation.status = 'approved';

							erTimeline.add_reservation( reservation );
							erTimeline.draw_reservations();

							erSidebar.draw_reservation( reservation );

							data.pending.splice( index, 1 );
							$( this ).remove();
							erSidebar.draw_pending();
						}
					} );

					reservationsContainer.append( element );
				} );
			} else {
				header.find( '.pending' ).html( '' );

				sidebar.find( '> .pending' ).find( '.reservations' ).html( data.i18n_no_pending );
			}
		},

		/**
		 * Display reservation
		 *
		 * @param {Object} reservation
		 */
		draw_reservation: function( reservation ) {
			const container = sidebar.find( '> .reservation-details' ),
				containerHeader = container.find( 'h2' ),
				resource = data.resources[ reservation.resource ];

			containerHeader.find( '.title' ).html( reservation.title );
			containerHeader.find( '.reservation-status' ).attr( 'class', 'reservation-status status-' + reservation.status ).html( reservation.id );

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

			if ( editMode && editMode.id === reservation.id ) {
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

			if ( resource.availability_by !== 'unit' ) {
				container.find( '.reservation-space' ).hide();
			} else {
				container.find( '.reservation-space' ).show().html( typeof resource.spaces[ reservation.space ] === 'undefined' ? reservation.space : resource.spaces[ reservation.space ] );
			}

			if ( reservation.order_id === '0' ) {
				container.find( '.reservation-order' ).html( data.i18n_no_order );
			} else {
				container.find( '.reservation-order' ).html( data.i18n_order.replace( '%s', '<a href="' + data.order_url.replace( '%s', reservation.order_id ) + '" target="_blank">#' + reservation.order_id + '</a>' ) );
			}

			if ( snappingEnabled ) {
				container.find( '.snapping' ).addClass( 'enabled' );
			}

			erSidebar.clear();
			container.show().addClass( 'visible' );
			erSidebar.open();
		},
	};

	var erTimeline = {

		/**
		 * Init timeline
		 */
		init: function() {
			const height = ( $( window ).height() - resourcesVertical.offset().top - 5 ) / ( data.reservation_id > 0 ? 3 : 1 );
			resourcesVertical.css( 'max-height', height );
			tableVertical.css( 'max-height', height );

			today = moment();
			reservations = [];
			selected = false;

			if ( interval === '86400' ) {
				cellDimensions.width = 96;
				today.startOf( 'day' );
				start.startOf( 'day' );
			} else {
				cellDimensions.width = 48;
				today.startOf( 'hour' );

				if ( today.date() === start.date() && today.month() === start.month() && today.year() === start.year() ) {
					start.hours( today.hour() ).minutes( 0 ).seconds( 0 ).milliseconds( 0 );
				} else {
					start.startOf( 'day' );
				}
			}

			timeline.find( 'td,th' ).remove();

			start.subtract( 15, intervalString );
			end = moment( start );
			lastQueryEnd = moment( start );
			lastQueryStart = moment( start );

			for ( let i = 0; i < cells; i++ ) {
				erTimeline.generate_column( end, false );
				if ( i < cells - 1 ) {
					end.add( 1, intervalString );
				}
			}

			erTimeline.load_remaining();

			const scroll = tableHeader.find( 'th:nth-child(15)' ).offset().left - timeline.offset().left + scroller.scrollLeft() + 1;

			scroller.scrollLeft( scroll );

			erTimeline.set_current_date();
			erTimeline.sync_cell_heights();
		},

		/**
		 * Highlights currently hovered column
		 *
		 * @param {moment} date
		 * @param {number} resourceId
		 * @param {number} space
		 */
		highlight_current: function( date, resourceId, space ) {
			//table.find( 'td.hover, th.hover' ).removeClass( 'hover' );
			//table.find( 'td[data-date="' + date.getTime() + '"], th[data-date="' + date.getTime() + '"]' ).addClass( 'hover' );
			tableHeader.find( 'th.hover' ).removeClass( 'hover' );
			tableHeader.find( 'th[data-date="' + date.unix() + '"]' ).addClass( 'hover' );
			resourcesTbody.find( 'td.hover, th.hover' ).removeClass( 'hover' );

			if ( space ) {
				resourcesTbody.find( 'td[data-resource="' + resourceId + '"][data-space="' + space + '"]' ).addClass( 'hover' );
			} else {
				resourcesTbody.find( 'th[data-resource="' + resourceId + '"]' ).addClass( 'hover' );
			}
		},

		/**
		 * Set the second visible cell as current date
		 */
		set_current_date: function() {
			const currentlySelected = moment( start ).add( Math.round( scroller.scrollLeft() / cellDimensions.width ) + 1, intervalString );

			if ( interval === '3600' ) {
				currentlySelected.startOf( 'hour' );
			} else {
				currentlySelected.startOf( 'day' );
			}

			if ( ! selected || currentlySelected.date() !== selected.date() || currentlySelected.month() !== selected.month() || currentlySelected.year() !== selected.year() ) {
				selected = currentlySelected;

				if ( interval === '3600' ) {
					headerDate.html( selected.date() + ' ' + er_date_picker_params.month_names[ selected.month() ] + ' ' + selected.year() );
				} else {
					headerDate.html( er_date_picker_params.month_names[ selected.month() ] + ' ' + selected.year() );
				}

				timeline.find( 'th.current,td.current' ).removeClass( 'current' );
				timeline.find( 'th[data-date="' + selected.unix() + '"],td[data-date="' + selected.unix() + '"]' ).addClass( 'current' );

				datepicker.datepicker( 'setDate', new Date( selected.format( 'YYYY-MM-DDTHH:mm:ssZ' ) ) );
				erSidebar.draw_today();
			} else if ( interval === '3600' && currentlySelected.hour() !== selected.hour() ) {
				//If only the hour changed in hourly view we don't change the header or datepicker
				selected = currentlySelected;

				timeline.find( 'th.current,td.current' ).removeClass( 'current' );
				timeline.find( 'th[data-date="' + selected.unix() + '"],td[data-date="' + selected.unix() + '"]' ).addClass( 'current' );
			}
		},

		scroll_dragging: function() {
			const mousePosTop = mousePosY - tableVertical.offset().top,
				mousePosLeft = mousePosX - timeline.offset().left,
				maxHeight = tableVertical.height();

			if ( ( mousePosLeft > 0 && mousePosLeft < cellDimensions.width / 2 ) || timeline.width() - mousePosLeft < cellDimensions.width / 2 ) {
				if ( scrollAction === false ) {
					scrollAction = setInterval( function() {
						const mousePosition = mousePosX - timeline.offset().left;

						if ( ( mousePosition > 0 && mousePosition < cellDimensions.width / 2 ) || timeline.width() - mousePosition < cellDimensions.width / 2 ) {
							dragStartPosition.left = dragStartPosition.left + ( mousePosition < cellDimensions.width / 2 ? cellDimensions.width : cellDimensions.width * -1 );
							erTimeline.add_new_column( mousePosition < cellDimensions.width / 2 );
							erTimeline.set_current_date();
						}
					}, 100 );
				}
			} else if ( mousePosTop > 0 && mousePosTop < 20 ) {
				if ( scrollAction === false ) {
					scrollAction = setInterval( function() {
						if ( scrollAction !== false ) {
							tableVertical.scrollTop( Math.max( 0, tableVertical.scrollTop() - 4 ) );
						}
					}, 1 );
				}
			} else if ( maxHeight - mousePosTop < 20 && mousePosTop <= maxHeight ) {
				if ( scrollAction === false ) {
					scrollAction = setInterval( function() {
						if ( scrollAction !== false ) {
							tableVertical.scrollTop( Math.min( maxHeight, tableVertical.scrollTop() + 4 ) );
						}
					}, 1 );
				}
			} else if ( scrollAction !== false ) {
				clearInterval( scrollAction );
				scrollAction = false;
				erTimeline.load_remaining();
			}
		},

		/**
		 * Starts an interval to add new columns while we are scrolled all the left or right
		 */
		start_scroll_add_interval: function() {
			if ( scrollAdd === false && ( scroller.scrollLeft() < 2 || tableHeader.width() - ( timeline.width() + scroller.scrollLeft() ) < 5 + cellDimensions.width * 2 ) ) {
				scrollAdd = setInterval( function() {
					if ( scrollAdd !== false && ( scroller.scrollLeft() < 2 || tableHeader.width() - ( timeline.width() + scroller.scrollLeft() ) < 5 + cellDimensions.width * 2 ) ) {
						erTimeline.add_new_column( scroller.scrollLeft() < 2 );
						erTimeline.set_current_date();
					}
				}, 45 );
			}
		},

		/**
		 * Clear the interval
		 */
		clear_scroll_add_interval: function() {
			if ( scrollAdd !== false ) {
				clearInterval( scrollAdd );
				scrollAdd = false;
				erTimeline.load_remaining();
			}
		},

		/**
		 * Jumps the timeline to specified date. If date is out of timelines currently loaded data it just inits from there, else it adds the difference in columns.
		 *
		 * @param {moment} date
		 */
		jump_to_date: function( date ) {
			if ( date < start || date > end ) {
				start = date;
				erTimeline.init();
			} else {
				const daysBetween = selected.diff( date ) / ( interval * 1000 );

				for ( let i = 1; i <= Math.abs( daysBetween ); i++ ) {
					erTimeline.add_new_column( daysBetween > 0 );
				}

				erTimeline.set_current_date();
			}
		},

		/**
		 * Add new column, keeps track of time, checks if reservations need to be redrawn
		 *
		 * @param {boolean} past
		 */
		add_new_column: function( past ) {
			if ( past ) {
				start.subtract( 1, intervalString );

				erTimeline.generate_column( start, true );

				timeline.find( 'th:last-child,td:last-child' ).remove();

				lastQueryEnd.subtract( 1, intervalString );
				end.subtract( 1, intervalString );
			} else {
				end.add( 1, intervalString );

				erTimeline.generate_column( end, false );

				tableHeader.find( 'th:first-child' ).remove();
				table.find( 'th:first-child' ).remove();
				table.find( 'td:first-child' )
					.each( function() {
						const cellReservations = $( this ).data( 'reservations' );
						if ( cellReservations && cellReservations.length > 0 ) {
							$.each( cellReservations, function( _, reservationId ) {
								if ( reservations[ reservationId ] && typeof reservations[ reservationId ] !== 'undefined' ) {
									reservations[ reservationId ].changed = true;
								}
							} );
						}
					} )
					.remove();

				lastQueryStart.add( 1, intervalString );
				start.add( 1, intervalString );
				erTimeline.draw_reservations();
			}

			erTimeline.sync_cell_heights();
		},

		/**
		 * Load remaining data
		 */
		load_remaining: function() {
			if ( lastQueryStart === 0 || lastQueryStart > start ) {
				erTimeline.load_data( start, lastQueryStart );
				lastQueryStart = moment( start );
			} else if ( lastQueryEnd < end ) {
				const nextQueryEnd = moment( end ).add( 1, intervalString );
				erTimeline.load_data( lastQueryEnd, nextQueryEnd );
				lastQueryEnd = nextQueryEnd;
			} else {

			}
		},

		/**
		 * Load data from database
		 *
		 * @param {moment} from
		 * @param {moment} until
		 * @param {Object} extraData
		 */
		load_data: function( from, until, extraData ) {
			$.ajax( {
				url: data.ajax_url,
				data: $.extend( {
					action: 'easyreservations_timeline_data',
					security: data.nonce,
					start: from.date() + '.' + ( from.month() + 1 ) + '.' + from.year(),
					start_hour: from.hour(),
					end: until.date() + '.' + ( until.month() + 1 ) + '.' + until.year(),
					end_hour: until.hour(),
					interval: interval,
				}, extraData ),
				type: 'POST',
				success: function( response ) {
					if ( response.data ) {
						$.each( response.data, function( resourceId, dataArray ) {
							const quantity = data.resources[ resourceId ].quantity;
							$.each( dataArray, function( timestamp, result ) {
								const date = moment( timestamp );
								let cellClass = '',
									content;

								if ( result < 0 ) {
									cellClass = 'unavailable';
									content = 0;
								} else {
									content = quantity - result;
								}

								tbody.find( 'td[data-date="' + ( date.unix() ) + '"][data-resource="' + resourceId + '"]' )
									.removeClass( 'loading' )
									.addClass( cellClass );
								thead.find( 'th[data-date="' + ( date.unix() ) + '"][data-resource="' + resourceId + '"] div.count' )
									.html( '<span>' + content + '</span>' )
									.addClass( parseInt( result, 10 ) === quantity ? 'unavailable' : '' );
							} );
						} );
					}

					if ( response.reservations ) {
						$.each( response.reservations, function( _, reservation ) {
							erTimeline.add_reservation( reservation, true );
						} );

						erTimeline.draw_reservations();
					}

					if ( response.message ) {
						alert( response.message );
					}
				},
			} );
		},

		update_reservation: function( id ) {
			const reservation = reservations[ id ];
			$.ajax( {
				url: data.ajax_url,
				data: {
					action: 'easyreservations_timeline_update_reservation',
					security: data.nonce,
					id: id,
					arrival: easyFormatDate( reservation.arrival, 'full' ),
					departure: easyFormatDate( reservation.departure, 'full' ),
					status: reservation.status,
					resource: reservation.resource,
					space: reservation.space,
					adults: reservation.adults,
					children: reservation.children,
					title: reservation.title,
				},
				type: 'POST',
				success: function( response ) {
					if ( response.reservation ) {
						reservations[ id ].arrival = moment( response.reservation.arrival.date );
						reservations[ id ].departure = moment( response.reservation.departure.date );
						reservations[ id ].adults = parseInt( response.reservation.adults, 10 );
						reservations[ id ].children = parseInt( response.reservation.children, 10 );
						reservations[ id ].resource = parseInt( response.reservation.resource_id, 10 );
						reservations[ id ].space = parseInt( response.reservation.space, 10 );
						reservations[ id ].order_id = parseInt( response.reservation.order_id, 10 );
						reservations[ id ].changed = true;
						erTimeline.draw_reservations();
					}

					if ( response.message ) {
						alert( response.message );
					}
				},
			} );
		},

		/**
		 * Draw reservations ordered by arrival until we have to remove others down the line, if so start process again
		 */
		draw_reservations: function() {
			const queue = [];
			let completed = true;

			$.each( reservations, function( _, reservation ) {
				if ( reservation && reservation.changed && reservation.status !== 'pending' ) {
					queue.push( reservation.id );
				}
			} );

			queue.sort( function( a, b ) {
				return reservations[ a ].arrival < reservations[ b ].arrival ? -1 : 1;
			} );

			$.each( queue, function( _, reservationId ) {
				if ( reservationId ) {
					if ( ! erTimeline.draw_reservation( reservations[ reservationId ] ) ) {
						erTimeline.draw_reservations();
						completed = false;
						return false;
					}
				}
			} );

			//If there was something to draw and we did draw it all.
			if ( completed && queue.length > 0 ) {
				erTimeline.sync_cell_heights();
			}
		},

		/**
		 * Start the recursive removing process beginning from a reservation
		 *
		 * @param {Object} reservation
		 */
		recursively_remove_reservation: function( reservation ) {
			const id = parseInt( reservation.id, 10 ),
				date = moment( reservation.arrival ),
				until = moment( reservation.departure );

			if ( interval === '86400' ) {
				date.startOf( 'day' );
				until.startOf( 'day' );
			} else {
				date.startOf( 'hour' );
				until.startOf( 'hour' );
			}

			while ( date <= until ) {
				const cell = $( 'td[data-date="' + ( date.unix() ) + '"][data-resource="' + reservation.resource + '"][data-space="' + reservation.space + '"]' );

				if ( cell.length > 0 ) {
					erTimeline.recursively_remove_reservations( cell, reservation.depths, id );
				}

				date.add( 1, intervalString );
			}
		},

		/**
		 * Recursively removes reservation data from cells
		 *
		 * @param {jQuery} cell timeline cell to start at
		 * @param {int} depths to start at
		 * @param {int} id of object that gets removed to begin with
		 */
		recursively_remove_reservations: function( cell, depths, id ) {
			const cellReservations = cell.data( 'reservations' ),
				newCellReservations = [];

			let foundStart = false,
				maxDepths = 0;

			if ( cellReservations && cellReservations.length > 0 ) {
				$.each( cellReservations, function( index, reservationId ) {
					if ( reservationId ) {
						if ( reservationId === id || reservations[ reservationId ].depths >= depths ) {
							if ( foundStart === false ) {
								foundStart = reservations[ reservationId ].depths;
							}
							foundStart = Math.min( foundStart, reservations[ reservationId ].depths );

							changedAnyReservation = true;
							reservations[ reservationId ].changed = true;
						} else {
							newCellReservations.push( reservationId );
							maxDepths = Math.max( maxDepths, reservations[ reservationId ].depths );
						}
					}
				} );
			}

			cell.data( 'reservations', newCellReservations );

			if ( foundStart !== false ) {
				erTimeline.recursively_remove_reservations( cell.next(), foundStart, id );
			}
		},

		/**
		 * Unbind jquery dom element from draggable, resizable and the title to not be contenteditable
		 *
		 * @param {jQuery} element
		 */
		reservation_stop_edit: function( element ) {
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
		reservation_allow_edit: function( element ) {
			element
				.draggable( {
					snap: snappingEnabled ? false : '.reservation',
					snapTolerance: 3,
					scroll: false,
					helper: 'clone',
					appendTo: '.timeline',
					scope: 'reservations',
					cancel: '.title',
					revert: function( event, ui ) {
						if ( event ) {
							return event;
						}

						//on older version of jQuery use $(this).data("draggable")
						//on 2.x versions of jQuery use $(this).data("ui-draggable")
						$( this ).data( 'uiDraggable' ).originalPosition = {
							top: dragStartPosition.top - 1,
							left: dragStartPosition.left,
						};

						return ! event;
					},

					start: function( event, ui ) {
						dragStartPosition = ui.originalPosition;
						dragStartOffset = ui.offset;
					},

					drag: function( event, ui ) {
						const id = parseInt( ui.helper.attr( 'data-id' ), 10 ),
							reservation = reservations[ id ];
						let difference = interval / cellDimensions.width * ( ui.position.left - dragStartPosition.left );

						if ( snappingEnabled ) {
							const step = Math.round( ( ui.position.left - dragStartPosition.left ) / cellDimensions.width );
							difference = step * interval;
							ui.position.left = dragStartPosition.left + ( step * cellDimensions.width );
						}

						tooltip
							.html(
								easyFormatTime( moment( reservation.arrival ).add( difference, 'seconds' ) ) + ' - ' +
								easyFormatTime( moment( reservation.departure ).add( difference, 'seconds' ) )
							)
							.css( {
								top: mousePosY,
								left: Math.min( mousePosX - 130, timeline.width() ),
								display: 'block',
							} );

						if ( dragSnapTop !== false ) {
							ui.position.top = dragSnapTop - dragStartOffset.top + dragStartPosition.top;
						}

						erTimeline.scroll_dragging();
					},

					stop: function() {
						tooltip.css( 'display', 'none' );
						if ( scrollAction !== false ) {
							clearInterval( scrollAction );
							scrollAction = false;
							erTimeline.load_remaining();
						}
					},
				} )
				.resizable( {
					handles: 'e, w',
					grid: snappingEnabled ? [ cellDimensions.width, 26 ] : false,
					minHeight: 0,
					minWidth: 4,
					start: function( event, ui ) {
						ui.originalElement.attr( 'style', 'left: ' + ui.originalElement.css( 'left' ) + ';top: ' + ui.originalElement.css( 'top' ) + ' !important;width: ' + ui.originalElement.css( 'width' ) );
					},
					resize: function( event, ui ) {
						const id = parseInt( ui.element.attr( 'data-id' ), 10 ),
							reservation = reservations[ id ],
							arrivalDifference = interval / cellDimensions.width * ( ui.position.left - ui.originalPosition.left ),
							departureDifference = interval / cellDimensions.width * ( ui.size.width );

						let message;

						if ( ui.position.left - ui.originalPosition.left !== 0 ) {
							message = easyFormatTime( moment( reservation.arrival ).add( arrivalDifference, 'seconds' ) );
						} else if ( ui.size.width - ui.originalSize.width !== 0 ) {
							message = easyFormatTime( moment( reservation.arrival ).add( departureDifference, 'seconds' ) );
						} else {
							message = easyFormatTime( moment( reservation.arrival ).add( arrivalDifference, 'seconds' ) );
							message += ' - ';
							message += easyFormatTime( moment( reservation.arrival ).add( departureDifference, 'seconds' ) );
						}

						tooltip
							.html( message )
							.css( {
								'top': mousePosY,
								'left': Math.min( mousePosX - 130, timeline.width() ),
								'display': 'block',
							} );
					},
					stop: function( event, ui ) {
						const id = parseInt( ui.element.attr( 'data-id' ), 10 ),
							arrivalDifference = interval / cellDimensions.width * ( ui.position.left - ui.originalPosition.left ),
							departureDifference = interval / cellDimensions.width * ( ui.size.width ),
							reservation = {
								id: id,
								arrival: moment( reservations[ id ].arrival ).add( arrivalDifference, 'seconds' ),
								departure: moment( reservations[ id ].arrival ).add( arrivalDifference + departureDifference, 'seconds' ),
								resource: reservations[ id ].resource,
								space: reservations[ id ].space,
							};

						if ( data.resources[ reservations[ id ].resource ].availability_by !== 'unit' || erTimeline.check_availability( reservation ) ) {
							erTimeline.recursively_remove_reservation( reservations[ id ] );

							reservations[ id ].arrival = reservation.arrival;
							reservations[ id ].departure = reservation.departure;
							reservations[ id ].changed = true;

							erTimeline.draw_reservations();
						} else {
							ui.helper.animate(
								{
									width: ui.originalSize.width,
									left: ui.originalPosition.left,
								},
								500,
								function() {
								}
							);
						}

						tooltip.css( 'display', 'none' );
					},
				} );

			element.find( '.title' ).attr( 'contenteditable', 'true' );
		},

		/**
		 * Draw single reservation
		 *
		 * @param {Object} reservation
		 * @return {boolean} didAdd
		 */
		draw_reservation: function( reservation ) {
			const id = parseInt( reservation.id, 10 ),
				date = moment( reservation.arrival ),
				until = moment( reservation.departure ),
				element = $( '<div class="reservation">' ),
				depthsTaken = [],
				width = ( ( ( until.diff( date ) / 1000 ) / interval ) * cellDimensions.width );

			let didAdd = false,
				depths = 0;

			changedAnyReservation = false;

			if ( interval === '86400' ) {
				date.startOf( 'day' );
				until.startOf( 'day' );
			} else {
				date.startOf( 'hour' );
				until.startOf( 'hour' );
			}

			while ( date <= until ) {
				//Check each existing cell that is in this reservations duration
				const cell = $( 'td[data-date="' + ( date.unix() ) + '"][data-resource="' + reservation.resource + '"][data-space="' + reservation.space + '"]' );

				if ( cell && cell.length > 0 ) {
					const cellReservations = cell.data( 'reservations' );

					if ( cellReservations.length > 0 ) {
						//Go through existing reservation in the cell and check if to redraw them or if to close depths for this reservation
						$.each( cellReservations, function( index, reservationId ) {
							if ( reservationId && reservationId !== id ) {
								if ( reservations[ reservationId ].arrival > reservation.arrival && reservations[ reservationId ].arrival < reservation.departure && reservations[ reservationId ].departure > reservation.arrival ) {
									erTimeline.recursively_remove_reservations( cell, depths, reservationId );
								} else if ( reservations[ reservationId ].departure <= reservation.arrival || reservations[ reservationId ].arrival >= reservation.departure ) {
									//console.log( 'could go in ' + reservations[ reservationId ].depths + ' because of ' + reservationId );
								} else {
									depthsTaken[ reservations[ reservationId ].depths ] = 1;
									//console.log( id + ' cannot go in ' + reservations[ reservationId ].depths + ' because of ' + reservationId );
								}
							}
						} );
					}

					if ( didAdd === false ) {
						//We will append the reservation to the first cell found, we set left but don't know the depths yet
						element.css( 'left', ( ( reservation.arrival.diff( date ) / 1000 / interval * cellDimensions.width ) - 1 ) + 'px' );

						didAdd = cell;
					}

					if ( $.inArray( id, cellReservations ) < 0 ) {
						cellReservations.push( id );

						cell.data( 'reservations', cellReservations );
					}
				}

				date.add( 1, intervalString );
			}

			if ( didAdd === false ) {
				//No cell to put reservation in found
				reservations[ id ].changed = false;

				return true;
				//delete reservations[ id ];
			}

			if ( changedAnyReservation ) {
				//We changed other reservations and begin drawing from the first changed reservation by arrival again
				return false;
			}

			//We can draw this reservations now
			element
				.html( '<span class="wrapper"><span class="sticky"><span class="id">' + id + '</span><div class="title">' + reservation.title + '</div></span></span>' )
				.css( 'min-width', width + 'px' )
				.css( 'max-width', width + 'px' )
				.css( 'top', '0px' )
				.css( 'position', 'absolute' )
				.addClass( reservation.status )
				.attr( 'data-tip', reservation.id )
				.attr( 'data-id', reservation.id );

			const existed = table.find( '.reservation[data-id="' + id + '"]' ).remove();

			if ( existed.length > 0 ) {
				//element.addClass( 'fade-in-fast' );
				element.addClass( 'fade-in-fast' );
			} else if ( reservation.fresh ) {
				delete reservation.fresh;
			} else {
				element.addClass( 'no-animation' );
			}

			if ( editMode && editMode.id === id ) {
				erSidebar.draw_reservation( reservation );
				erTimeline.reservation_allow_edit( element );
				timeline.find( '.reservation.selected' ).removeClass( 'selected' );
				element.addClass( 'selected' );
			}

			didAdd.append( element );

			while ( depthsTaken[ depths ] === 1 ) {
				depths++;
			}

			if ( depths > 0 ) {
				if ( didAdd.height() < cellDimensions.height + ( cellDimensions.height - 3 ) * depths ) {
					didAdd.height( cellDimensions.height + ( cellDimensions.height - 3 ) * depths );
				}
				element.css( 'top', ( cellDimensions.height - 3 ) * depths + 'px' );
			}

			reservation.depths = depths;
			reservation.changed = false;
			//console.log( 'did draw ' + id + 'at depths ' + depths );

			reservations[ id ] = reservation;

			return didAdd;
		},

		/**
		 * Add reservation to array
		 *
		 * @param {Object} reservation
		 * @param {boolean} changed
		 */
		add_reservation: function( reservation, changed ) {
			const id = parseInt( reservation.id, 10 );

			reservation.id = id;
			reservation.arrival = moment( reservation.arrival );
			reservation.departure = moment( reservation.departure );
			reservation.resource = parseInt( reservation.resource, 10 );
			reservation.space = parseInt( reservation.space, 10 );

			if ( data.resources[ reservation.resource ] && data.resources[ reservation.resource ].availability_by !== 'unit' ) {
				reservation.space = 1;
			}

			//TODO add check if it needs to be redrawn
			if ( typeof reservations[ id ] === 'undefined' ) {
				reservation.changed = true;
				reservation.fresh = true;
			} else {
				reservation.changed = changed ? true : reservations[ id ].changed;
				reservation.depths = reservations[ id ].depths;
			}

			reservations[ id ] = reservation;
		},

		/**
		 * Checks reservation against other loaded reservations
		 *
		 * @param {Object} reservationToCheck
		 * @return {boolean} available
		 */
		check_availability: function( reservationToCheck ) {
			const id = parseInt( reservationToCheck.id, 10 );
			let available = true;

			$.each( reservations, function( _, reservation ) {
				if ( reservation && reservation.resource === reservationToCheck.resource && reservation.space === reservationToCheck.space && reservation.id !== id && (
					reservationToCheck.arrival < reservation.departure && reservationToCheck.departure > reservation.arrival
				) ) {
					available = false;
					return false;
				}
			} );

			console.log( available );

			return available;
		},

		/**
		 * Keeps the resources cells as high as the biggest cell is
		 */
		sync_cell_heights: function() {
			let tbodyIndex,
				trIndex;
			resourcesTbody.each( function( _, resourceTbody ) {
				tbodyIndex = ( $( resourceTbody ).index() / 2 ) - 0.5;
				$( resourceTbody ).children().each( function( __, tr ) {
					trIndex = $( tr ).index();
					$( tr ).height( $( tbody[ tbodyIndex ] ).children().eq( $( tr ).index() ).height() );
				} );
			} );
		},

		/**
		 * Set element(s) to be droppable for reservations
		 *
		 * @param {jQuery} element
		 */
		set_element_as_droppable: function( element ) {
			element
				.droppable( {
					scope: 'reservations', //we only accept reservations
					tolerance: 'pointer', //targets the cell under the mouse
					drop: function( event, ui ) {
						let $this = $( this );

						if ( lastHover && lastHover.getAttribute( 'data-space' ) ) {
							$this = $( lastHover );
						}

						const id = parseInt( ui.draggable.attr( 'data-id' ), 10 ),
							difference = interval / cellDimensions.width * ( ui.position.left - dragStartPosition.left ),
							reservation = {
								id: id,
								arrival: moment( reservations[ id ].arrival ).add( difference, 'seconds' ),
								departure: moment( reservations[ id ].departure ).add( difference, 'seconds' ),
								resource: parseInt( $this.attr( 'data-resource' ), 10 ),
								space: parseInt( $this.attr( 'data-space' ), 10 ),
							};

						if ( data.resources[ reservation.resource ].availability_by !== 'unit' || erTimeline.check_availability( reservation ) ) {
							erTimeline.recursively_remove_reservation( reservations[ id ] );

							reservations[ id ].arrival = reservation.arrival;
							reservations[ id ].departure = reservation.departure;
							reservations[ id ].resource = reservation.resource;
							reservations[ id ].space = reservation.space;
							reservations[ id ].changed = true;

							if ( reservations[ id ].status === 'pending' ) {
								reservations[ id ].status = 'approved';
							}

							ui.helper.remove();

							erTimeline.draw_reservations();
						}
					},
				} );
		},

		/**
		 * Generate and appends calendar column
		 *
		 * @param {moment} date of cell to add
		 * @param {boolean} past wether to add it at start
		 */
		generate_column: function( date, past ) {
			const day = date.day() === 0 ? 6 : date.day() - 1;
			let headerMain,
				headerClass = '',
				tbodyNumber = 0,
				i = 0,
				todayMarker = false;

			if ( interval === '86400' ) {
				headerMain = $( '<th><div class="date"><div>' + easyFormatDate( date, 'd' ) + '<span>' + er_date_picker_params.day_names_min[ day ] + '</span></div></div><div class="marker"></div></th>' );

				if ( date.date() === 1 ) {
					headerClass = 'first';
					headerMain.append( $( '<div class="first">' + er_date_picker_params.month_names[ date.month() ] + '</div>' ) );
				}
			} else {
				const lastChar = er_both_params.time_format.charAt( er_both_params.time_format.length - 1 );
				let description = '00';

				if ( lastChar === 'a' ) {
					description = date.hours() >= 12 ? 'pm' : 'am';
				} else if ( lastChar === 'A' ) {
					description = date.hours() >= 12 ? 'PM' : 'AM';
				}

				headerMain = $( '<th><div class="date"><div>' + easyFormatDate( date, 'H' ) + '<span>' + description + '</span></div></div><div class="marker"></div></th>' );

				if ( date.hours() === 0 ) {
					headerClass = 'first';
					headerMain.append( $( '<div class="first">' + date.date() + ' ' + er_date_picker_params.day_names[ day ] + '</div>' ) );
				}
			}

			if ( date.date() === today.date() && date.month() === today.month() && date.year() === today.year() && ( interval === '86400' || date.hour() === today.hour() ) ) {
				const realToday = moment(),
					todayOverlay = $( '<div class="overlay"></div>' );

				let difference;

				todayMarker = $( '<div class="today"></div>' );

				if ( interval === '86400' ) {
					difference = ( cellDimensions.width / 86400 * ( ( realToday.hour() * 3600 ) + ( realToday.minute() * 60 ) ) ) - 1;
				} else {
					difference = ( cellDimensions.width / 3600 * ( realToday.minute() * 60 ) ) - 1;
				}

				todayMarker.css( 'left', difference );

				todayOverlay
					.css( 'left', difference )
					.css( 'width', difference )
					.css( 'margin-left', -difference );

				headerMain
					.append( todayMarker )
					.append( todayOverlay );

				headerClass += ' today';
			} else if ( date < today ) {
				headerClass += ' past';
			}

			if ( ( date.day() === 0 || date.day() === 6 ) ) {
				headerClass += ' weekend';
			}

			headerMain
				.addClass( headerClass )
				.attr( 'data-date', date.unix() );

			headerClass += ' loading';

			if ( past ) {
				tableHeader.prepend( headerMain );
			} else {
				tableHeader.append( headerMain );
			}

			$.each( resourcesSort, function( _, resourceId ) {
				const cellHeader = $( '<th><div class="count"></div></th>' )
					.addClass( headerClass )
					.attr( 'data-resource', resourceId )
					.attr( 'data-date', date.unix() );

				if ( past ) {
					$( thead[ tbodyNumber ] ).find( 'tr' ).prepend( cellHeader );
				} else {
					$( thead[ tbodyNumber ] ).find( 'tr' ).append( cellHeader );
				}

				for ( i = 1; i <= ( data.resources[ resourceId ].availability_by === 'unit' ? data.resources[ resourceId ].quantity : 1 ); i++ ) {
					const cell = $( '<td class="cell"></td>' )
						.addClass( headerClass )
						.attr( 'data-resource', resourceId )
						.data( 'reservations', [] )
						.attr( 'data-space', i )
						.attr( 'data-date', date.unix() );

					if ( todayMarker ) {
						cell.append( todayMarker.clone() );
						todayMarker = false;
					}

					if ( past ) {
						$( tbody[ tbodyNumber ] ).find( 'tr:nth-child(' + i + ')' ).prepend( cell );
					} else {
						$( tbody[ tbodyNumber ] ).find( 'tr:nth-child(' + i + ')' ).append( cell );
					}

					if ( editMode ) {
						erTimeline.set_element_as_droppable( cell );
					}
				}
				tbodyNumber++;
			} );

			if ( past ) {
				if ( lastQueryStart === 0 || lastQueryStart.valueOf() - ( interval * 1000 * 10 ) > date.valueOf() ) {
					erTimeline.load_data( start, lastQueryStart, {} );
					lastQueryStart = moment( start );
				}
			} else if ( lastQueryEnd.valueOf() + ( interval * 1000 * 10 ) < date.valueOf() ) {
				const nextQueryEnd = moment( end ).add( interval, 'seconds' );
				erTimeline.load_data( lastQueryEnd, nextQueryEnd, {} );
				lastQueryEnd = nextQueryEnd;
			}
		},
	};

	master.insertAfter( 'hr.wp-header-end' );
	tooltip.insertAfter( 'hr.wp-header-end' );
	sidebar.hide();

	if ( data.default_hourly === 'on' ) {
		interval = '3600';
		intervalString = 'hours';
	}

	if ( interval === '86400' ) {
		today.startOf( 'day' );
		header.find( '.daily' ).addClass( 'active' );
	} else {
		today.startOf( 'hour' );
		header.find( '.hourly' ).addClass( 'active' );
		timeline.addClass( 'hourly' );
		cellDimensions.width = 48;
	}

	//Async so other js does not have to wait
	setTimeout( function() {
		erSidebar.draw_pending();
		erSidebar.display_calendar();
		master.css( 'display', 'flex' );

		if ( data.reservation_arrival ) {
			erTimeline.jump_to_date( moment( data.reservation_arrival ) );
		} else {
			erTimeline.init();
		}
	}, 0 );

	if ( data.reservation_resource > 0 ) {
		resources.find( '.resource-handler:not([data-resource="' + data.reservation_resource + '"],.retracted),.resource-handler.retracted[data-resource="' + data.reservation_resource + '"]' ).trigger( 'click' );
	}
}( jQuery, er_timeline_params ) );