var picu = picu || {};

picu.GalleryView.Item = Backbone.View.extend({

    model: picu.singleImage,

    template: _.template( jQuery( "#picu-gallery-item" ).html() ),

    tagName: 'div',

    className: function() {
        if ( this.model.get( 'selected' ) == true ) {
            var selected = ' selected';
        }
        else {
            var selected = ' unselected';
        }

		if ( this.model.get( 'stars' ) > 0 ) {
            var stars = ' stars-' + this.model.get( 'stars' );
        }
        else {
            var stars = ' stars-0';
        }

        if ( this.model.get( 'focused' ) == true ) {
            var focused = ' focused';
        }
        else {
            var focused = '';
        }

        return 'picu-gallery-item' + selected + stars + focused;
    },

    id: function() {
        return 'picu-image-' + this.model.get( 'number' );
    },

    initialize: function( options ) {
		this.appstate = options.appstate;
        this.listenTo( this.model, 'change', this.render );
    },

    render: function() {
        var singleImageTemplate = this.template( this.model.attributes );

        this.$el.removeClass( 'flash' );

        if ( this.model.get( 'lazyloaded' ) == true ) {
            this.$el.addClass( 'loaded' );
        }

        this.$el.html( singleImageTemplate );
        return this;
    },

    events: {
        'click label': 'toggleImageSelection',
        'click .picu-imgbox': 'toggleFocus',
		'click .picu-star-rating__rating': 'setStarRating',
		'click .picu-star-rating__list': 'removeStarRating',
    },

	toggleImageSelection: function() {
		// Check if the client needs to register first
		var router = new Backbone.Router();
		var temp = jQuery.parseJSON( appstate );
		if ( temp.ident == null ) {
			router.navigate( 'register', {trigger: true} );
			return;
		}
		picu.GalleryView.prototype.lazyLoad();
		picu.saveSelection( this );
		picu.EventBus.trigger( 'save:now', this );
	},

	setStarRating: function( e ) {
		e.preventDefault();

		// Check if the client needs to register first
		var router = new Backbone.Router();
		var temp = jQuery.parseJSON( appstate );
		if ( temp.ident == null ) {
			router.navigate( 'register', {trigger: true} );
			return;
		}

		// Only allow changing the rating if the collection is open
		if ( this.appstate.get( 'poststatus' ) != 'sent' ) {
			return;
		}

		var currentStars = this.model.get( 'stars' );
		var setStars = e.target.closest( '.picu-star-rating__rating' ).dataset.rating;
		var list = e.target.closest( '.picu-caption' ).querySelector( '.picu-star-rating__list' );
		

		if ( currentStars == setStars ) {
			this.model.set( 'stars', 0 );
			this.$el.removeClass( [ 'stars-0', 'stars-1', 'stars-2', 'stars-3', 'stars-4', 'stars-5' ] );
			this.$el.addClass( 'stars-0' );
		}
		else {
			this.model.set( 'stars', setStars );

			// Set the star's class
			this.$el.removeClass( [ 'stars-0', 'stars-1', 'stars-2', 'stars-3', 'stars-4', 'stars-5' ] );
			this.$el.addClass( 'stars-' + setStars );
		}

		// Save
		picu.EventBus.trigger( 'save:now', this );
	},

	removeStarRating: function( e ) {
		// Only allow changing the rating if the collection is open
		if ( this.appstate.get( 'poststatus' ) != 'sent' ) {
			return;
		}

		if ( e.target.classList.contains( 'picu-star-rating__list' ) ) {
			var currentStars = this.model.get( 'stars' );
			var list = e.target.closest( '.picu-caption' ).querySelector( '.picu-star-rating__list' );
			list.classList.remove( 'is-visible' );
			this.model.set( 'stars', 0 );
			this.$el.removeClass( [ 'stars-0', 'stars-1', 'stars-2', 'stars-3', 'stars-4', 'stars-5' ] );
			this.$el.addClass( 'stars-0' );
			picu.EventBus.trigger( 'save:now', this );
		}
	}
});