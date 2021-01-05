/*global er_single_resource_params, PhotoSwipe, PhotoSwipeUI_Default */
jQuery( function( $ ) {

	// er_single_resource_params is required to continue.
	if ( typeof er_single_resource_params === 'undefined' ) {
		return false;
	}

	/**
	 * Product gallery class.
	 */
	var ProductGallery = function( $target, args ) {
		this.$target = $target;
		this.$images = $( '.easyreservations-resource-gallery__image', $target );

		// No images? Abort.
		if ( 0 === this.$images.length ) {
			this.$target.css( 'opacity', 1 );
			return;
		}

		// Make this object available.
		$target.data( 'resource_gallery', this );

		// Pick functionality to initialize...
		this.flexslider_enabled = typeof $.fn.flexslider === 'function' && er_single_resource_params.flexslider_enabled;
		this.zoom_enabled = typeof $.fn.zoom === 'function' && er_single_resource_params.zoom_enabled;
		this.photoswipe_enabled = typeof PhotoSwipe !== 'undefined' && er_single_resource_params.photoswipe_enabled;

		// ...also taking args into account.
		if ( args ) {
			this.flexslider_enabled = false === args.flexslider_enabled ? false : this.flexslider_enabled;
			this.zoom_enabled = false === args.zoom_enabled ? false : this.zoom_enabled;
			this.photoswipe_enabled = false === args.photoswipe_enabled ? false : this.photoswipe_enabled;
		}

		// ...and what is in the gallery.
		if ( 1 === this.$images.length ) {
			this.flexslider_enabled = false;
		}

		// Bind functions to this.
		this.initFlexslider = this.initFlexslider.bind( this );
		this.initZoom = this.initZoom.bind( this );
		this.initZoomForTarget = this.initZoomForTarget.bind( this );
		this.initPhotoswipe = this.initPhotoswipe.bind( this );
		this.onResetSlidePosition = this.onResetSlidePosition.bind( this );
		this.getGalleryItems = this.getGalleryItems.bind( this );
		this.openPhotoswipe = this.openPhotoswipe.bind( this );

		if ( this.flexslider_enabled ) {
			this.initFlexslider( args.flexslider );
			$target.on( 'easyreservations_gallery_reset_slide_position', this.onResetSlidePosition );
		} else {
			this.$target.css( 'opacity', 1 );
		}

		if ( this.zoom_enabled ) {
			this.initZoom();
			$target.on( 'easyreservations_gallery_init_zoom', this.initZoom );
		}

		if ( this.photoswipe_enabled ) {
			this.initPhotoswipe();
		}
	};

	/**
	 * Initialize flexSlider.
	 */
	ProductGallery.prototype.initFlexslider = function( args ) {
		var $target = this.$target,
			gallery = this;

		var options = $.extend( {
			selector: '.easyreservations-resource-gallery__wrapper > .easyreservations-resource-gallery__image',
			start: function() {
				$target.css( 'opacity', 1 );
			},
			after: function( slider ) {
				gallery.initZoomForTarget( gallery.$images.eq( slider.currentSlide ) );
			}
		}, args );

		$target.flexslider( options );

		// Trigger resize after main image loads to ensure correct gallery size.
		$( '.easyreservations-resource-gallery__wrapper .easyreservations-resource-gallery__image:eq(0) .wp-post-image' ).one( 'load', function() {
			var $image = $( this );

			if ( $image ) {
				setTimeout( function() {
					var setHeight = $image.closest( '.easyreservations-resource-gallery__image' ).height();
					var $viewport = $image.closest( '.flex-viewport' );

					if ( setHeight && $viewport ) {
						$viewport.height( setHeight );
					}
				}, 100 );
			}
		} ).each( function() {
			if ( this.complete ) {
				$( this ).trigger( 'load' );
			}
		} );
	};

	/**
	 * Init zoom.
	 */
	ProductGallery.prototype.initZoom = function() {
		this.initZoomForTarget( this.$images.first() );
	};

	/**
	 * Init zoom.
	 */
	ProductGallery.prototype.initZoomForTarget = function( zoomTarget ) {
		if ( ! this.zoom_enabled ) {
			return false;
		}

		var galleryWidth = this.$target.width(),
			zoomEnabled = false;

		$( zoomTarget ).each( function( index, target ) {
			var image = $( target ).find( 'img' );

			if ( image.data( 'large_image_width' ) > galleryWidth ) {
				zoomEnabled = true;
				return false;
			}
		} );

		// But only zoom if the img is larger than its container.
		if ( zoomEnabled ) {
			var zoom_options = $.extend( {
				touch: false
			}, er_single_resource_params.zoom_options );

			if ( 'ontouchstart' in document.documentElement ) {
				zoom_options.on = 'click';
			}

			zoomTarget.trigger( 'zoom.destroy' );
			zoomTarget.zoom( zoom_options );

			setTimeout( function() {
				if ( zoomTarget.find( ':hover' ).length ) {
					zoomTarget.trigger( 'mouseover' );
				}
			}, 100 );
		}
	};

	/**
	 * Init PhotoSwipe.
	 */
	ProductGallery.prototype.initPhotoswipe = function() {
		if ( this.zoom_enabled && this.$images.length > 0 ) {
			this.$target.prepend( '<a href="#" class="easyreservations-resource-gallery__trigger">🔍</a>' );
			this.$target.on( 'click', '.easyreservations-resource-gallery__trigger', this.openPhotoswipe );
			this.$target.on( 'click', '.easyreservations-resource-gallery__image a', function( e ) {
				e.preventDefault();
			} );

			// If flexslider is disabled, gallery images also need to trigger photoswipe on click.
			if ( ! this.flexslider_enabled ) {
				this.$target.on( 'click', '.easyreservations-resource-gallery__image a', this.openPhotoswipe );
			}
		} else {
			this.$target.on( 'click', '.easyreservations-resource-gallery__image a', this.openPhotoswipe );
		}
	};

	/**
	 * Reset slide position to 0.
	 */
	ProductGallery.prototype.onResetSlidePosition = function() {
		this.$target.flexslider( 0 );
	};

	/**
	 * Get resource gallery image items.
	 */
	ProductGallery.prototype.getGalleryItems = function() {
		var $slides = this.$images,
			items = [];

		if ( $slides.length > 0 ) {
			$slides.each( function( i, el ) {
				var img = $( el ).find( 'img' );

				if ( img.length ) {
					var large_image_src = img.attr( 'data-large_image' ),
						large_image_w = img.attr( 'data-large_image_width' ),
						large_image_h = img.attr( 'data-large_image_height' ),
						alt = img.attr( 'alt' ),
						item = {
							alt: alt,
							src: large_image_src,
							w: large_image_w,
							h: large_image_h,
							title: img.attr( 'data-caption' ) ? img.attr( 'data-caption' ) : img.attr( 'title' ),
						};
					items.push( item );
				}
			} );
		}

		return items;
	};

	/**
	 * Open photoswipe modal.
	 */
	ProductGallery.prototype.openPhotoswipe = function( e ) {
		e.preventDefault();

		var pswpElement = $( '.pswp' )[ 0 ],
			items = this.getGalleryItems(),
			eventTarget = $( e.target ),
			clicked;

		if ( eventTarget.is( '.easyreservations-resource-gallery__trigger' ) || eventTarget.is( '.easyreservations-resource-gallery__trigger img' ) ) {
			clicked = this.$target.find( '.flex-active-slide' );
		} else {
			clicked = eventTarget.closest( '.easyreservations-resource-gallery__image' );
		}

		var options = $.extend( {
			index: $( clicked ).index(),
			addCaptionHTMLFn: function( item, captionEl ) {
				if ( ! item.title ) {
					captionEl.children[ 0 ].textContent = '';
					return false;
				}
				captionEl.children[ 0 ].textContent = item.title;
				return true;
			}
		}, er_single_resource_params.photoswipe_options );

		// Initializes and opens PhotoSwipe.
		var photoswipe = new PhotoSwipe( pswpElement, PhotoSwipeUI_Default, items, options );
		photoswipe.init();
	};

	/**
	 * Function to call er_resource_gallery on jquery selector.
	 */
	$.fn.er_resource_gallery = function( args ) {
		new ProductGallery( this, args || er_single_resource_params );
		return this;
	};

	/*
	 * Initialize all galleries on page.
	 */
	$( '.easyreservations-resource-gallery' ).each( function() {

		$( this ).trigger( 'er-resource-gallery-before-init', [ this, er_single_resource_params ] );

		$( this ).er_resource_gallery( er_single_resource_params );

		$( this ).trigger( 'er-resource-gallery-after-init', [ this, er_single_resource_params ] );

	} );

	var resource_select = $( '.entry-summary  select#resource' );
	if ( resource_select.length > 0 ) {
		resource_select.closest( '.form-row' ).remove();
	}

	resource_select = $( '.entry-summary  #resource' );
	if ( resource_select.length === 0 ) {
		$( '<input>' ).attr( {
			type: 'hidden',
			id: 'resource',
			name: 'resource',
			value: er_single_resource_params.resource_id
		} ).appendTo( '.entry-summary form' );
	} else {
		resource_select.val( er_single_resource_params.resource_id );
	}
} );
