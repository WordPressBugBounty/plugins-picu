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
            var selected = '';
        }
        if ( this.model.get( 'focused' ) == true ) {
            var focused = ' focused';
        }
        else {
            var focused = '';
        }

        return 'picu-gallery-item' + selected + focused;
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
        'click .picu-imgbox': 'toggleFocus'
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
	}
});