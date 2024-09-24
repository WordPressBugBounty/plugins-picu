var picu = picu || {};

picu.Router = Backbone.Router.extend({

	initialize: function( options ) {
		this.el = options.el;
		this.collection = options.collection;
		this.count = this.collection.length;
		this.appstate = options.appstate;
		this.history = [];
		this.listenTo( this, 'route', function ( name, args ) {
			this.history.push({
				name : name,
				args : args,
			});
		});
	},

	routes: {
		'': 'index',
		'index': 'index',
		'collection-info': 'collectionInfo',
		'send': 'send',
		'approved': 'approved',
		'register': 'register',
		':number': 'picuLightbox'
	},

	index: function() {
		// Make page scrollable
		$( 'html' ).removeClass( 'static' );

		// Check if the GalleryView already exists and if a modal is set
		if ( this.view && this.view instanceof picu.GalleryView && this.modal ) {
			// Clear modal
			this.modal.remove();
			delete this.modal;
		}

		// Load the GalleryView
		else {
			// Empty .picu-collection
			this.el.empty();

			if ( this.appstate.get( 'poststatus' ) == 'delivered' || this.appstate.get( 'poststatus' ) == 'delivery-draft' ) {

				// New DeliveryView
				var delivery = this.loadView( new picu.DeliveryView( this.collection, this.appstate ) );

				// Append the new DelvieryView to .picu-collection
				this.el.append( delivery.render().el );
			}
			else {
				// New GalleryView
				var gallery = this.loadView( new picu.GalleryView( this.collection, this.appstate ) );

				// Append the new GalleryView to .picu-collection
				this.el.append( gallery.render().el );

				// Check if the user looked at a specific image in the Lightbox before
				if ( this.currentImage ) {
					// Scroll to the element that was opened in lightbox before
					// and hightlight the image as an indictor to the viewer
					$( 'html, body' ).animate( { scrollTop: $( '#picu-image-'+ this.currentImage ).offset().top }, 0 );
					$( '#picu-image-'+ this.currentImage ).addClass( 'flash' );
					delete this.currentImage;
					delete this.scrollposition;
				}
			}
		}
	},

	picuLightbox: function( number ) {

		// Redirect to index for delivery collections
		if ( this.appstate.get( 'poststatus' ) == 'delivered' || this.appstate.get( 'poststatus' ) == 'delivery-draft' ) {
			// Navigate back to the index
			this.navigate( 'index', {trigger: true} );
			return;
		}

		// Empty .picu-collection
		this.el.empty();

		// Get scroll position:
		this.scrollposition = $(window).scrollTop();

		// Check if number even exists, if not send the user to the index
		if ( number <= this.count ) {
			// Create new lightbox, pass the image number and the whole collection to the view
			var lightbox = this.loadView( new picu.LightboxView( number, this.collection, this.appstate, this ) );
			// Append the new LightboxView to .picu-collection
			this.el.append( lightbox.render().el );
			// Set "global" variable to keep track of the current image
			this.currentImage = number;
		}
		else {
			this.navigate( 'index', {trigger: true} );
		}
	},

	collectionInfo: function() {

		// Redirect to index for delivery collections
		if ( this.appstate.get( 'poststatus' ) == 'delivered' || this.appstate.get( 'poststatus' ) == 'delivery-draft' ) {
			// Navigate back to the index
			this.navigate( 'index', {trigger: true} );
			return;
		}

		$( 'html' ).addClass( 'static' );

		// New CollectionInfo Modal
		var view = new picu.CollectionInfo({collection: this.collection, appstate: this.appstate, router: this});

		// Append the view to .picu-collection
		this.el.append( view.render().el );

		// Set "global" variable to keep track of the current modal
		this.modal = view;
	},

	send: function() {

		// Redirect to index for delivery collections
		if ( this.appstate.get( 'poststatus' ) == 'delivered' || this.appstate.get( 'poststatus' ) == 'delivery-draft' ) {
			// Navigate back to the index
			this.navigate( 'index', {trigger: true} );
			return;
		}

		$( 'html' ).addClass( 'static' );

		// Check if selection restriction is active
		if ( typeof this.appstate.attributes.selection_restriction !== 'undefined' ) {
			var restriction = this.appstate.attributes.selection_restriction.restriction;
			var from = this.appstate.attributes.selection_restriction.from;
			var to = this.appstate.attributes.selection_restriction.to;
			var num = this.collection.where({selected: true}).length;
		}

		// Prevent the view from being displayed when collection is already approved
		if ( this.appstate.attributes.poststatus == 'approved' ) {
			// Navigate back to the index
			this.navigate( 'index', {trigger: true} );

			// Tell the user that the collection is already approved
			this.el.append('<div class="overlay fail"><div class="message"><p>' + this.appstate.get( 'already_approved_msg' ) + '</p><p><a class="picu-button small primary js-close-message">OK</a></p></div></div>');
			return;
		}

		// If selection is restricted, but the target is not valid
		if ( ( restriction == 'at least' && num < from ) || ( restriction == 'a maximum of' && num > from ) || ( restriction == 'exactly' && num != from ) || ( restriction == 'in the range of' && ( num < from || num > to ) ) ) {
			this.navigate( 'index', {trigger: true} );
			this.el.append('<div class="overlay fail"><div class="message"><p>' + this.appstate.attributes.selection_restriction.selection_info + '</p><p><a class="picu-button small primary js-close-message">OK</a></p></div></div>');
			return;
		}

		// Check that at leat one image is selected
		else if ( this.collection.where({selected: true}).length > 0  ) {
			// New send view
			var view = new picu.SendView({model: this.appstate, collection: this.collection, router: this});

			// Append the view to .picu-collection
			this.el.append( view.render().el );

			// Set "global" variable to keep track of the current modal
			this.modal = view;
		}

		// Navigate back to the index
		else {
			this.navigate( 'index', {trigger: true} );
			// Tell the user that he/she has to select at least one image
			this.el.append('<div class="overlay fail"><div class="message"><p>' + this.appstate.get( 'select_at_least_one_image_msg' ) + '</p><p><a class="picu-button small primary js-close-message">OK</a></p></div></div>');
			return;
		}
	},

	approved: function() {

		// Check if the collection is really approved
		if ( this.appstate.attributes.poststatus != 'approved' ) {
			// Navigate back to the index
			this.navigate( 'index', {trigger: true} );
		}
		else {
			var approved = new picu.ApprovedView({title: this.appstate.get( 'title' ), history: history});
			this.el.empty();
			this.el.append( approved.render().el );
		}
	},

	register: function() {
		// Return to index if collection is closed or delivery
		if ( this.appstate.get( 'poststatus' ) == 'approved' || this.appstate.get( 'poststatus' ) == 'delivered' || this.appstate.get( 'poststatus' ) == 'delivery-draft' ) {
			this.navigate( 'index', {trigger: true} );
			return;
		}

		// Display error message and return to previous route, if the status is draft
		if ( this.appstate.get( 'poststatus' ) == 'draft' ) {
			if ( this.history[this.history.length - 1].name == 'picuLightbox' ) {
				this.navigate( '#' + this.history[this.history.length - 1].args[0], {trigger: true} );
			}
			else {
				this.navigate( 'index', {trigger: true} );
			}

			this.el.append('<div class="overlay fail"><div class="message"><p>' + this.appstate.get( 'still_draft_msg' ) + '</p><p><a class="picu-button small primary js-close-message">OK</a></p></div></div>');
			return;
		}

		var registration = new picu.RegistrationView({title: this.appstate.get( 'title' ), router: this, appstate: this.appstate});
		this.el.append( registration.render().el );
	},

	// Check if a view already exists, and removes it correctly before it creates a new instance
	loadView: function( view ) {

		// If a view is already set…
		if ( this.view ) {
			// …remove it!
			this.view.remove();
		}

		// Set "global" variable to keep track of the current view
		this.view = view;
		return this.view;
	}

});