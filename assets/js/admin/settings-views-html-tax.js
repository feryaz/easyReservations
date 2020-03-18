/* global Backbone, htmlSettingsTaxLocalizeScript */

/**
 * Used by easyReservations/includes/admin/settings/views/html-settings-tax.php
 */
( function( $, data, wp ) {
	$( function() {
		if ( ! String.prototype.trim ) {
			String.prototype.trim = function() {
				return this.replace( /^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g, '' );
			};
		}

		const rowTemplate = wp.template( 'er-tax-table-row' ),
			rowTemplateEmpty = wp.template( 'er-tax-table-row-empty' ),
			paginationTemplate = wp.template( 'er-tax-table-pagination' ),
			$table = $( '.er_tax_rates' ),
			$tbody = $( '#rates' ),
			$pagination = $( '#rates-pagination' ),
			$searchField = $( '#rates-search .er-tax-rates-search-field' ),
			$submit = $( '.button-primary[name=save]' ),
			ERTaxTableModelConstructor = Backbone.Model.extend( {
				changes: {},
				setRateAttribute: function( rateID, attribute, value ) {
					const rates = _.indexBy( this.get( 'rates' ), 'id' ),
						changes = {};

					if ( rates[ rateID ][ attribute ] !== value ) {
						changes[ rateID ] = {};
						changes[ rateID ][ attribute ] = value;
						rates[ rateID ][ attribute ] = value;
					}

					this.logChanges( changes );
				},
				logChanges: function( changedRows ) {
					const changes = this.changes || {};

					_.each( changedRows, function( row, id ) {
						changes[ id ] = _.extend( changes[ id ] || {
							id: id,
						}, row );
					} );

					this.changes = changes;
					this.trigger( 'change:rates' );
				},
				getFilteredRates: function() {
					const search = $searchField.val().toLowerCase();
					let rates = this.get( 'rates' );

					if ( search.length ) {
						rates = _.filter( rates, function( rate ) {
							const searchText = _.toArray( rate ).join( ' ' ).toLowerCase();
							return ( -1 !== searchText.indexOf( search ) );
						} );
					}

					rates = _.sortBy( rates, function( rate ) {
						return parseInt( rate.order, 10 );
					} );

					return rates;
				},
				block: function() {
					$table.block( {
						message: null,
						overlayCSS: {
							background: '#fff',
							opacity: 0.6,
						},
					} );
				},
				unblock: function() {
					$table.unblock();
				},
				save: function() {
				},
			} ),
			ERTaxTableViewConstructor = Backbone.View.extend( {
				rowTemplate: rowTemplate,
				per_page: data.limit,
				page: data.page,
				initialize: function() {
					const qtyPages = Math.ceil( _.toArray( this.model.get( 'rates' ) ).length / this.per_page );

					this.qty_pages = 0 === qtyPages ? 1 : qtyPages;
					this.page = this.sanitizePage( data.page );

					this.listenTo( this.model, 'change:rates', this.setUnloadConfirmation );
					this.listenTo( this.model, 'saved:rates', this.clearUnloadConfirmation );
					$tbody.on( 'change autocompletechange', ':input', { view: this }, this.updateModelOnChange );
					$searchField.on( 'keyup search', { view: this }, this.onSearchField );
					$pagination.on( 'click', 'a', { view: this }, this.onPageChange );
					$pagination.on( 'change', 'input', { view: this }, this.onPageChange );
					$( window ).on( 'beforeunload', { view: this }, this.unloadConfirmation );
					$submit.on( 'click', { view: this }, this.onSubmit );

					// Can bind these directly to the buttons, as they won't get overwritten.
					$table.find( '.insert' ).on( 'click', { view: this }, this.onAddNewRow );
					$table.find( '.remove_tax_rates' ).on( 'click', { view: this }, this.onDeleteRow );
					$table.find( '.export' ).on( 'click', { view: this }, this.onExport );
				},
				render: function() {
					const rates = this.model.getFilteredRates(),
						qtyRates = _.size( rates ),
						qtyPages = Math.ceil( qtyRates / this.per_page ),
						firstIndex = 0 === qtyRates ? 0 : this.per_page * ( this.page - 1 ),
						lastIndex = this.per_page * this.page,
						pagedRates = _.toArray( rates ).slice( firstIndex, lastIndex ),
						view = this;

					// Blank out the contents.
					this.$el.empty();

					if ( pagedRates.length ) {
						// Populate $tbody with the current page of results.
						$.each( pagedRates, function( id, rowData ) {
							view.$el.append( view.rowTemplate( rowData ) );
						} );
					} else {
						view.$el.append( rowTemplateEmpty() );
					}

					if ( qtyPages > 1 ) {
						// We've now displayed our initial page, time to render the pagination box.
						$pagination.html( paginationTemplate( {
							qty_rates: qtyRates,
							current_page: this.page,
							qty_pages: qtyPages,
						} ) );
					} else {
						$pagination.empty();
						view.page = 1;
					}
				},
				updateUrl: function() {
					if ( ! window.history.replaceState ) {
						return;
					}

					const search = $searchField.val();
					let url = data.base_url;

					if ( 1 < this.page ) {
						url += '&p=' + encodeURIComponent( this.page );
					}

					if ( search.length ) {
						url += '&s=' + encodeURIComponent( search );
					}

					window.history.replaceState( {}, '', url );
				},
				onSubmit: function( event ) {
					event.data.view.clearUnloadConfirmation();
				},
				onAddNewRow: function( event ) {
					const view = event.data.view,
						model = view.model,
						rates = _.indexBy( model.get( 'rates' ), 'id' ),
						changes = {},
						size = _.size( rates ),
						newRow = _.extend( {}, data.default_rate, {
							id: 'new-' + size + '-' + Date.now(),
							newRow: true,
						} ),
						$current = $tbody.children( '.current' );

					if ( $current.length ) {
						const currentId = $current.last().data( 'id' );
						const currentOrder = parseInt( rates[ currentId ].order, 10 );
						newRow.order = 1 + currentOrder;

						const ratesToReorder = _.filter( rates, function( rate ) {
							return parseInt( rate.order, 10 ) > currentOrder;
						} );

						_.map( ratesToReorder, function( rate ) {
							rate.order++;
							changes[ rate.id ] = _.extend( changes[ rate.id ] || {}, { order: rate.order } );
							return rate;
						} );
					} else {
						newRow.order = 1 + _.max(
							_.pluck( rates, 'order' ),
							function( val ) {
								// Cast them all to integers, because strings compare funky. Sighhh.
								return parseInt( val, 10 );
							}
						);
						// Move the last page
						view.page = view.qty_pages;
					}

					rates[ newRow.id ] = newRow;
					changes[ newRow.id ] = newRow;

					model.set( 'rates', rates );
					model.logChanges( changes );

					view.render();
				},
				onDeleteRow: function( event ) {
					const view = event.data.view,
						model = view.model,
						rates = _.indexBy( model.get( 'rates' ), 'id' ),
						changes = {},
						$current = $tbody.children( '.current' );

					event.preventDefault();

					if ( $current ) {
						$current.each( function() {
							let currentId = $( this ).data( 'id' );

							delete rates[ currentId ];

							changes[ currentId ] = _.extend( changes[ currentId ] || {}, { deleted: 'deleted' } );
						} );

						model.set( 'rates', rates );
						model.logChanges( changes );

						view.render();
					} else {
						window.alert( data.strings.no_rows_selected );
					}
				},
				onSearchField: function( event ) {
					event.data.view.updateUrl();
					event.data.view.render();
				},
				onPageChange: function( event ) {
					const $target = $( event.currentTarget );

					event.preventDefault();
					event.data.view.page = $target.data( 'goto' ) ? $target.data( 'goto' ) : $target.val();
					event.data.view.render();
					event.data.view.updateUrl();
				},
				onExport: function( event ) {
					let csvData = 'data:application/csv;charset=utf-8,' + data.strings.csv_data_cols.join( ',' ) + '\n';

					$.each( event.data.view.model.getFilteredRates(), function( id, rowData ) {
						let row = rowData.country + ',';
						row += rowData.state + ',';
						row += ( rowData.postcode ? rowData.postcode.join( '; ' ) : '' ) + ',';
						row += ( rowData.city ? rowData.city.join( '; ' ) : '' ) + ',';
						row += rowData.tax_rate + ',';
						row += rowData.name + ',';
						row += rowData.priority + ',';
						row += rowData.compound + ',';
						row += data.current_class;

						csvData += row + '\n';
					} );

					$( this ).attr( 'href', encodeURI( csvData ) );

					return true;
				},
				setUnloadConfirmation: function() {
					this.needsUnloadConfirm = true;
				},
				clearUnloadConfirmation: function() {
					this.needsUnloadConfirm = false;
				},
				unloadConfirmation: function( event ) {
					if ( event.data.view.needsUnloadConfirm ) {
						event.returnValue = data.strings.unload_confirmation_msg;
						window.event.returnValue = data.strings.unload_confirmation_msg;
						return data.strings.unload_confirmation_msg;
					}
				},
				updateModelOnChange: function( event ) {
					const model = event.data.view.model,
						$target = $( event.target ),
						id = $target.closest( 'tr' ).data( 'id' ),
						attribute = $target.data( 'attribute' );

					let val = $target.val();

					if ( 'compound' === attribute || 'flat' === attribute ) {
						if ( $target.is( ':checked' ) ) {
							val = 1;
						} else {
							val = 0;
						}
					}

					model.setRateAttribute( id, attribute, val );
				},
				sanitizePage: function( pageNum ) {
					pageNum = parseInt( pageNum, 10 );
					if ( pageNum < 1 ) {
						pageNum = 1;
					} else if ( pageNum > this.qty_pages ) {
						pageNum = this.qty_pages;
					}
					return pageNum;
				},
			} ),
			ERTaxTableModelInstance = new ERTaxTableModelConstructor( {
				rates: data.rates,
			} ),
			ERTaxTableInstance = new ERTaxTableViewConstructor( {
				model: ERTaxTableModelInstance,
				el: '#rates',
			} );

		ERTaxTableInstance.render();
	} );
}( jQuery, htmlSettingsTaxLocalizeScript, wp ) );
