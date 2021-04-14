/*global easyreservations_admin_meta_boxes */
jQuery( function( $ ) {
	$( '#catalog-visibility' ).find( '.edit-catalog-visibility' ).on( 'click', function() {
		if ( $( '#catalog-visibility-select' ).is( ':hidden' ) ) {
			$( '#catalog-visibility-select' ).slideDown( 'fast' ).css( 'display', 'block' );
		}
		return false;
	} );

	$( '#catalog-visibility' ).find( '.save-post-visibility' ).on( 'click', function() {
		$( '#catalog-visibility-select' ).slideUp( 'fast' );

		let label = $( 'input[name=_visibility]:checked' ).attr( 'data-label' );

		if ( $( 'input[name=_featured]' ).is( ':checked' ) ) {
			label = label + ', ' + easyreservations_admin_meta_boxes.featured_label;
			$( 'input[name=_featured]' ).attr( 'checked', 'checked' );
			$( '#resource_featured' ).val( 'yes' );
		} else {
			$( '#resource_featured' ).val( 'no' );
		}

		if ( $( 'input[name=_onsale]' ).is( ':checked' ) ) {
			label = label + ', ' + easyreservations_admin_meta_boxes.onsale_label;
			$( 'input[name=_onsale]' ).attr( 'checked', 'checked' );
			$( '#resource_onsale' ).val( 'yes' );
		} else {
			$( '#resource_onsale' ).val( 'no' );
		}

		$( '#resource_visibility' ).val( $( 'input[name=_visibility]:checked' ).val() );
		$( '.edit-catalog-visibility' ).text( label );
		return false;
	} );

	$( '#catalog-visibility' ).find( '.cancel-post-visibility' ).on( 'click', function() {
		$( '#catalog-visibility-select' ).slideUp( 'fast' );
		$( '#catalog-visibility' ).find( '.edit-catalog-visibility' ).show();

		$( 'input[name=_visibility]' ).removeAttr( 'checked' );
		$( 'input[name=_visibility][value=' + $( '#current_visibility' ).val() + ']' ).attr( 'checked', 'checked' );

		if ( 'yes' === $( '#current_featured' ).val() ) {
			$( 'input[name=_featured]' ).attr( 'checked', 'checked' );
		} else {
			$( 'input[name=_featured]' ).removeAttr( 'checked' );
		}

		if ( 'yes' === $( '#current_onsale' ).val() ) {
			$( 'input[name=_onsale]' ).attr( 'checked', 'checked' );
		} else {
			$( 'input[name=_onsale]' ).removeAttr( 'checked' );
		}

		return false;
	} );

	// resource gallery file uploads.
	let resourceGalleryFrame;
	const imageGalleryIds = $( '#resource_image_gallery' );
	const resourceImages = $( '#resource_images_container' ).find( 'ul.resource_images' );

	$( '.add_resource_images' ).on( 'click', 'a', function( event ) {
		event.preventDefault();

		// If the media frame already exists, reopen it.
		if ( resourceGalleryFrame ) {
			resourceGalleryFrame.open();
			return;
		}

		const $el = $( this );

		// Create the media frame.
		resourceGalleryFrame = wp.media.frames.resource_gallery = wp.media( {
			// Set the title of the modal.
			title: $el.data( 'choose' ),
			button: {
				text: $el.data( 'update' ),
			},
			states: [
				new wp.media.controller.Library( {
					title: $el.data( 'choose' ),
					filterable: 'all',
					multiple: true,
				} ),
			],
		} );

		// When an image is selected, run a callback.
		resourceGalleryFrame.on( 'select', function() {
			const selection = resourceGalleryFrame.state().get( 'selection' );
			let attachmentIds = imageGalleryIds.val();

			selection.map( function( attachment ) {
				attachment = attachment.toJSON();

				if ( attachment.id ) {
					attachmentIds = attachmentIds ? attachmentIds + ',' + attachment.id : attachment.id;

					resourceImages.append(
						'<li class="image" data-attachment_id="' + attachment.id + '"><img src="' + ( attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url ) +
						'" /><ul class="actions"><li><a href="#" class="delete" title="' + $el.data( 'delete' ) + '">' +
						$el.data( 'text' ) + '</a></li></ul></li>'
					);
				}

				return true;
			} );

			imageGalleryIds.val( attachmentIds );
		} );

		// Finally, open the modal.
		resourceGalleryFrame.open();
	} );

	// Image ordering.
	resourceImages.sortable( {
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
			let attachmentIds = '';

			$( '#resource_images_container' ).find( 'ul li.image' ).css( 'cursor', 'default' ).each( function() {
				attachmentIds = attachmentIds + $( this ).attr( 'data-attachment_id' ) + ',';
			} );

			imageGalleryIds.val( attachmentIds );
		},
	} );

	// Remove images.
	$( '#resource_images_container' ).on( 'click', 'a.delete', function() {
		$( this ).closest( 'li.image' ).remove();

		let attachmentIds = '';

		$( '#resource_images_container' ).find( 'ul li.image' ).css( 'cursor', 'default' ).each( function() {
			attachmentIds = attachmentIds + $( this ).attr( 'data-attachment_id' ) + ',';
		} );

		imageGalleryIds.val( attachmentIds );

		// Remove any lingering tooltips.
		$( '#tiptip_holder' ).removeAttr( 'style' );
		$( '#tiptip_arrow' ).removeAttr( 'style' );

		return false;
	} );

	$( document ).ready( function() {
		const blockLoadedInterval = setInterval( function() {
			if ( $( '.edit-post-post-visibility' ).length > 0 ) {
				//Actual functions goes here
				$( '#catalog-visibility' ).children().insertAfter( $( '.edit-post-post-visibility' ) );
				clearInterval( blockLoadedInterval );
			}
		}, 500 );
	} );

	if( wp.data.dispatch( 'core/edit-post' ) ){
		wp.data.dispatch( 'core/edit-post' ).removeEditorPanel( 'discussion-panel' );
	}
} );
