var picu = picu || {};

picu.StatusBarView = Backbone.View.extend({

	model: picu.singleImage,

	template: _.template( jQuery( "#picu-status-bar" ).html() ),

	tagName: 'header',

	className: 'picu-status-bar',

	initialize: function( options ) {
		this.collection = options.collection;
		this.appstate = options.model;

		this.update();
		this.listenTo( this.collection, 'change', function() {
			this.update();
		} );

		// Key bindings
		_.bindAll( this , 'keyAction' );
		$( document ).on( 'keydown', this.keyAction );
	},

	events: {
		'click .picu-save': 'saveSelection',
		'click .picu-filter-selected': 'filterSelected',
		'click .picu-filter-unselected': 'filterUnselected',
		'click .picu-filter-reset': 'filterReset',
		'click .picu-grid-size__toggle': 'toggleGridSizeList',
		'click .picu-grid-size__switch': 'switchGridSize',
		'click .picu-selection-alert': 'toggleRestrictionAlert',
		'keydown': 'keyAction'
	},

	update: function() {
		var all = this.collection.length;
		var selected = this.collection.where({selected: true}).length;
		var restrictionWarning = this.restrictionWarning();
		var animation = 'animation-off';
		if ( selected > 1 ) {
			this.appstate.animation = false;
			
		}
		if ( this.appstate.animation != false ) {
			animation = '';
		}
		var statusbar = this.template({all: all, selected: selected, appstate: this.appstate, zip: this.appstate.get( 'zip' ), selection_restriction: this.appstate.get('selection_restriction'), restriction_warning: restrictionWarning, animation: animation });
		this.$el.html( statusbar );
	},

	filterSelected: function() {
		$( '.picu-error' ).remove();

		this.appstate.set( 'filter', 'selected' );
		$( 'body' ).removeClass( 'filter-unselected' ).addClass( 'filter-selected' );

		if ( this.collection.where({selected: true}).length <= 0 ) {
			$( '.picu-gallery' ).append('<div class="picu-error"><div class="error-inner"><h2>' + this.appstate.get( 'error_msg_filter_selected' ) + '</h2><p><a class="error-filter-reset" href="#index"><svg viewBox="0 0 100 100"><use xlink:href="#icon_close"></use></svg>' + this.appstate.get( 'reset_filter_msg' ) + '</span></p></div></div>');
		}

		picu.GalleryView.prototype.lazyLoad();
	},

	filterUnselected: function() {
		$( '.picu-error' ).remove();

		this.appstate.set( 'filter', 'unselected' );
		$( 'body' ).removeClass( 'filter-selected' ).addClass( 'filter-unselected' );

		if ( this.collection.where({selected: true}).length >= this.collection.length ) {
			$( '.picu-gallery' ).append('<div class="picu-error"><div class="error-inner"><h2>' + this.appstate.get( 'error_msg_filter_unselected' ) + '</h2><p><a class="error-filter-reset" href="#index"><svg viewBox="0 0 100 100"><use xlink:href="#icon_close"></use></svg>' + this.appstate.get( 'reset_filter_msg' ) + '</span></p></div></div>');
		}

		picu.GalleryView.prototype.lazyLoad();
	},

	filterReset: function() {
		this.appstate.unset( 'filter' );
		$( 'body' ).removeClass( 'filter-selected filter-unselected' );
		$( '.picu-error' ).remove();

		picu.GalleryView.prototype.lazyLoad();
	},

	saveSelection: function() {
		// Check if the client needs to register first
		var router = new Backbone.Router();
		var temp = jQuery.parseJSON( appstate );
		if ( temp.ident == null ) {
			router.navigate( 'register', {trigger: true} );
			return;
		}
		// Hide save button, show spinner
		$( '.picu-save' ).hide();
		$( '<div class="picu-saving">loading</div>' ).insertBefore( '.picu-save' );

		var selection = _.map( this.collection.where({selected: true}), function( s ){ return s.attributes.imageID; });

		var allMarkers = {};

		var temp = this.collection.map( function( model ){
			var id = model.get( 'imageID' );
			var markers = model.get( 'markers' );

			if ( '' != markers && null != markers ) {
				if ( 0 < Object.keys( markers ).length ) {
					allMarkers['id_'+id] = markers;
				}
			}
			markers = '';
		});

		// Send AJAX request
		$.post( this.appstate.get( 'ajaxurl' ), {

			action: 'picu_send_selection',
			security: this.appstate.get( 'nonce' ),
			postid: this.appstate.get( 'postid' ),
			ident: this.appstate.get( 'ident' ),
			selection: selection,
			markers: allMarkers,
			intent: 'temp'

		}, function( response ) {

			// Display response as overlay
			var overlayclass = '';
			if ( response.success == true ) {
				overlayclass = ' success';
			} else {
				overlayclass = ' fail';
			}

			$( '.picu-collection' ).append('<div class="overlay'+ overlayclass +'"><div class="message"><p>' + response.data.message + '</p><p><a class="picu-button small primary js-close-message">' + response.data.button_text + '</a></p></div></div>');

			// Remove spinner, show save button
			$( '.picu-saving' ).remove();
			$( '.picu-save' ).show();

		}).fail( function() {
			// Ajax fail
			$( '.picu-collection' ).append('<div class="overlay fail"><div class="message"><p>Error: Request failed.<br />Do you have a working internet connection?</p><p><a class="picu-button small primary js-close-message" href="#">OK</a></p></div></div>');

			// Remove spinner, show save button
			$( '.loading' ).remove();
			$( '.picu-save' ).show();
		});

	},

	toggleGridSizeList: function( e ) {
		var minWidth = document.querySelector( '.picu-grid-size__toggle' ).offsetWidth + 'px';
		var gridSizeList = document.querySelector( '.picu-grid-size__list' );
		gridSizeList.style.minWidth =  minWidth;
		gridSizeList.classList.toggle( 'is-visible' );
	},

	switchGridSize: function( e ) {
		var size = e.target.id.substr( 10 );
		document.querySelector( 'body' ).classList.remove( 'thumbsize-small' );
		document.querySelector( 'body' ).classList.remove( 'thumbsize-medium' );
		document.querySelector( 'body' ).classList.remove( 'thumbsize-large' );
		document.querySelector( 'body' ).classList.add( 'thumbsize-' + size );
		document.querySelector( '.picu-grid-size__list' ).classList.remove( 'is-visible' );
		picu.GalleryView.prototype.lazyLoad();
	},

	toggleRestrictionAlert: function( e ) {
		document.querySelector( '.picu-selection-alert__explanation' ).classList.toggle( 'is-visible' );
	},

	keyAction: function( e ) {
		// ESC key
		if ( e.keyCode == 27 ) {
			e.preventDefault();
			document.querySelector( '.picu-grid-size__list' ).classList.remove( 'is-visible' );

			var selectionAlert = document.querySelector( '.picu-selection-alert__explanation' );
			if ( selectionAlert != null ) {
				selectionAlert.classList.remove( 'is-visible' );
			}
		}
	},

	restrictionWarning: function() {
		if ( typeof this.appstate.attributes.selection_restriction !== 'undefined' ) {
			var restriction = this.appstate.attributes.selection_restriction.restriction;
			var from = this.appstate.attributes.selection_restriction.from;
			var to = this.appstate.attributes.selection_restriction.to;
			var num = this.collection.where({selected: true}).length;
		}

		if ( num == 0 ) {
			return false;
		}
		else if ( ( restriction == 'at least' && num < from ) || ( restriction == 'a maximum of' && num > from ) || ( restriction == 'exactly' && num != from ) || ( restriction == 'in the range of' && ( num < from || num > to ) ) ) {
			return true;
		}

		return false;
	},

	remove: function() {
		// Unbind keydown
		$( document ).off( 'keydown', this.keyAction );

		// Completely remove this view
		Backbone.View.prototype.remove.call( this );
	}

});