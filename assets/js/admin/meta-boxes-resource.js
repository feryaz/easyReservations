/*global woocommerce_admin_meta_boxes */

jQuery( function( $ ) {

	// Catalog Visibility.
	$( '#catalog-visibility' ).find( '.edit-catalog-visibility' ).click( function() {
		if ( $( '#catalog-visibility-select' ).is( ':hidden' ) ) {
			$( '#catalog-visibility-select' ).slideDown( 'fast' ).css( 'display', 'block' );
		}
		return false;
	} );
	$( '#catalog-visibility' ).find( '.save-post-visibility' ).click( function() {
		$( '#catalog-visibility-select' ).slideUp( 'fast' );

		var label = $( 'input[name=_visibility]:checked' ).attr( 'data-label' );

		if ( $( 'input[name=_featured]' ).is( ':checked' ) ) {
			//label = label + ', ' + woocommerce_admin_meta_boxes.featured_label;
			$( 'input[name=_featured]' ).attr( 'checked', 'checked' );
			$( '#resource_featured' ).val( 'yes' );
		} else {
			$( '#resource_featured' ).val( 'no' );
		}

		$( '#resource_visibility' ).val( $( 'input[name=_visibility]:checked' ).val() );
		$( '.edit-catalog-visibility' ).text( label );
		return false;
	} );
	$( '#catalog-visibility' ).find( '.cancel-post-visibility' ).click( function() {
		$( '#catalog-visibility-select' ).slideUp( 'fast' );
		$( '#catalog-visibility' ).find( '.edit-catalog-visibility' ).show();

		var current_visibility = $( '#current_visibility' ).val();
		var current_featured = $( '#current_featured' ).val();

		$( 'input[name=_visibility]' ).removeAttr( 'checked' );
		$( 'input[name=_visibility][value=' + current_visibility + ']' ).attr( 'checked', 'checked' );

		var label = $( 'input[name=_visibility]:checked' ).attr( 'data-label' );

		if ( 'yes' === current_featured ) {
			//label = label + ', ' + woocommerce_admin_meta_boxes.featured_label;
			$( 'input[name=_featured]' ).attr( 'checked', 'checked' );
		} else {
			$( 'input[name=_featured]' ).removeAttr( 'checked' );
		}

		$( '.edit-catalog-visibility' ).text( label );
		return false;
	} );

	// resource gallery file uploads.
	var resource_gallery_frame;
	var $image_gallery_ids = $( '#resource_image_gallery' );
	var $resource_images = $( '#resource_images_container' ).find( 'ul.resource_images' );

	$( '.add_resource_images' ).on( 'click', 'a', function( event ) {
		var $el = $( this );

		event.preventDefault();

		// If the media frame already exists, reopen it.
		if ( resource_gallery_frame ) {
			resource_gallery_frame.open();
			return;
		}

		// Create the media frame.
		resource_gallery_frame = wp.media.frames.resource_gallery = wp.media( {
			// Set the title of the modal.
			title: $el.data( 'choose' ),
			button: {
				text: $el.data( 'update' )
			},
			states: [
				new wp.media.controller.Library( {
					title: $el.data( 'choose' ),
					filterable: 'all',
					multiple: true
				} )
			]
		} );

		// When an image is selected, run a callback.
		resource_gallery_frame.on( 'select', function() {
			var selection = resource_gallery_frame.state().get( 'selection' );
			var attachment_ids = $image_gallery_ids.val();

			selection.map( function( attachment ) {
				attachment = attachment.toJSON();

				if ( attachment.id ) {
					attachment_ids = attachment_ids ? attachment_ids + ',' + attachment.id : attachment.id;
					var attachment_image = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;

					$resource_images.append(
						'<li class="image" data-attachment_id="' + attachment.id + '"><img src="' + attachment_image +
						'" /><ul class="actions"><li><a href="#" class="delete" title="' + $el.data( 'delete' ) + '">' +
						$el.data( 'text' ) + '</a></li></ul></li>'
					);
				}
			} );

			$image_gallery_ids.val( attachment_ids );
		} );

		// Finally, open the modal.
		resource_gallery_frame.open();
	} );

	// Image ordering.
	$resource_images.sortable( {
		items: 'li.image',
		cursor: 'move',
		scrollSensitivity: 40,
		forcePlaceholderSize: true,
		forceHelperSize: false,
		helper: 'clone',
		opacity: 0.65,
		placeholder: 'er-metabox-sortable-placeholder',
		start: function( event, ui ) {
			ui.item.css( 'background-color', '#f6f6f6' );
		},
		stop: function( event, ui ) {
			ui.item.removeAttr( 'style' );
		},
		update: function() {
			var attachment_ids = '';

			$( '#resource_images_container' ).find( 'ul li.image' ).css( 'cursor', 'default' ).each( function() {
				var attachment_id = $( this ).attr( 'data-attachment_id' );
				attachment_ids = attachment_ids + attachment_id + ',';
			} );

			$image_gallery_ids.val( attachment_ids );
		}
	} );

	// Remove images.
	$( '#resource_images_container' ).on( 'click', 'a.delete', function() {
		$( this ).closest( 'li.image' ).remove();

		var attachment_ids = '';

		$( '#resource_images_container' ).find( 'ul li.image' ).css( 'cursor', 'default' ).each( function() {
			var attachment_id = $( this ).attr( 'data-attachment_id' );
			attachment_ids = attachment_ids + attachment_id + ',';
		} );

		$image_gallery_ids.val( attachment_ids );

		// Remove any lingering tooltips.
		$( '#tiptip_holder' ).removeAttr( 'style' );
		$( '#tiptip_arrow' ).removeAttr( 'style' );

		return false;
	} );

	$( document ).ready( function() {
		let blockLoaded = false;
		let blockLoadedInterval = setInterval( function() {
			if ( $( '.edit-post-post-visibility' ).length > 0 ) {
				//Actual functions goes here
				$( '#catalog-visibility' ).children().insertAfter( $( '.edit-post-post-visibility' ) );
				blockLoaded = true;
			}
			if ( blockLoaded ) {
				clearInterval( blockLoadedInterval );
			}
		}, 500 );

	} );

	wp.data.dispatch( 'core/edit-post' ).removeEditorPanel( 'discussion-panel' );
} );
