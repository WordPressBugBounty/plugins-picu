var picu = picu || {};

picu.LightboxView = Backbone.View.extend({

    tagName: 'div',
    className: 'picu-lightbox',

    template: _.template( jQuery( "#picu-lightbox" ).html() ),

    initialize: function( number, collection, appstate, router ) {
        this.number = ( parseInt(number) - 1 );
        this.collection = collection;
        this.appstate = appstate;
        this.max = collection.length;
        this.router = router;
        this.current = ( parseInt(number) );

        // Get model from collection based on the number
        this.model = collection.models[this.number];

        // Render on change
        this.listenTo( this.model, 'change', this.render );

        // Key bindings
        _.bindAll( this , 'keyAction' );
        $( document ).on( 'keydown', this.keyAction);

    },

    render: function() {

        var lightboxTemplate = this.template( this.model.attributes, this.current );

        this.$el.html( lightboxTemplate );
        return this;
    },

    events: {
        'click .picu-lightbox-select': 'toggleImageSelection',
        'click .picu-lightbox-next': 'nextImage',
        'click .picu-lightbox-prev': 'previousImage',
        'keydown': 'keyAction'
    },

	nextImage: function( e ) {
		e.preventDefault();

		// If a filter is active
		if ( this.appstate.get( 'filter' ) == 'selected' || this.appstate.get( 'filter' ) == 'unselected' ) {

			if ( this.appstate.get( 'filter' ) == 'selected' ) {
				var filter = true;
			}
			if ( this.appstate.get( 'filter' ) == 'unselected' ) {
				var filter = false;
			}

			// Get current image number
			var currentImage = this.current;

			// Find all images that follow the current image and correspond to our filter
			var filteredCollection = this.collection.filter( function( model ) {
				return (
					model.get( 'number' ) > currentImage &&
					model.get( 'selected' ) == filter
				)
			});

			// If there is no image following the current image, start from the beginning
			if ( filteredCollection.length < 1 ) {
				filteredCollection = this.collection.filter( function( model ) {
					return model.get( 'selected' ) == filter
				});
			}

			// If there is still no image, just use the current one
			if ( filteredCollection.length < 1 ) {
				var nextImage = this.current;
			}
			// Otherwise define the next image
			else {
				var nextImage = filteredCollection[0].get( 'number' );
			}

			// Set global image counter and jump to that image
			this.current = nextImage;
			this.router.navigate( this.current.toString(), {trigger: true} );

		}
		else {

			if ( this.current >= this.max ) {
				this.current = 1;
				this.router.navigate( '1', {trigger: true} );
			}
			else {
				this.current++;
				this.router.navigate( this.current.toString(), {trigger: true} );
			}
		}
	},

	previousImage: function( e ) {
		e.preventDefault();

		// If a filter is active
		if ( this.appstate.get( 'filter' ) == 'selected' || this.appstate.get( 'filter' ) == 'unselected' ) {

			if ( this.appstate.get( 'filter' ) == 'selected' ) {
				var filter = true;
			}
			if ( this.appstate.get( 'filter' ) == 'unselected' ) {
				var filter = false;
			}

			// Get current image number
			var currentImage = this.current;

			// Find all images that come before the current image and correspond to our filter
			var filteredCollection = this.collection.filter( function( model ) {
				return (
					model.get( 'number') < currentImage &&
					model.get( 'selected' ) == filter
				)
			});

			// If there is no image before the current image, jump to the end
			if ( filteredCollection.length < 1 ) {
				filteredCollection = this.collection.filter( function( model ) {
					return model.get( 'selected') == filter
				});
			}

			// If there is still no image, just use the current one
			if ( filteredCollection.length < 1 ) {
				var previousImage = this.current;
			}
			// Get the previous image, which is the last entry in the filteredCollection
			else {
				var previousImage = filteredCollection.slice(-1)[0].get( 'number' );
			}

			// Set global image counter and jump to that image
			this.current = previousImage;
			this.router.navigate( this.current.toString(), {trigger: true} );

		}
		else {

			if ( this.current <= 1 ) {
				this.current = this.max;
				this.router.navigate( this.current.toString(), {trigger: true} );
			}
			else {
				this.current--;
				this.router.navigate( this.current.toString() , {trigger: true} );
			}
		}
	},

	toggleImageSelection: function() {
		picu.saveSelection( this );
		picu.EventBus.trigger( 'save:now', this );
	},

	keyAction: function( e ) {
        // ESC key
        if ( e.keyCode == 27 ) {
            e.preventDefault();
            this.router.navigate('index', {trigger: true} );
        }
		// Do not capture any other keys, if registration is open
		if ( this.router.history[this.router.history.length - 1].name == 'register' ) {
			return;
		}
        // left arrow key
        if ( e.keyCode == 37 ) {
            e.preventDefault();
            this.previousImage( e );
        }
        // right arrow key
        if ( e.keyCode == 39 ) {
            e.preventDefault();
            this.nextImage( e );
        }
        // enter, p or space key
        if ( 'approved' != this.appstate.get( 'poststatus' ) ) {
            if ( e.keyCode == 13 || e.keyCode == 80 || e.keyCode == 32 ) {
                e.preventDefault();
                this.toggleImageSelection();
            }
        }
    },

    remove: function() {
        // Unbind keydown
        $( document ).off( 'keydown', this.keyAction );
    }

});