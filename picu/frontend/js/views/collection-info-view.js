var picu = picu || {};

picu.CollectionInfo = Backbone.View.extend({

	tagName: 'div',
	className: 'picu-modal',
	id: 'picu-info-view',

	template: _.template( jQuery( "#picu-info-view" ).html() ),

	initialize: function( options ) {
		this.collection = options.collection;
		this.appstate = options.appstate;
		this.router = options.router;

		// Key bindings
		_.bindAll( this , 'keyAction' );
		$( document ).on( 'keydown', this.keyAction);

	},

	render: function() {
		var all = this.collection.length;
		var selected = this.collection.where({selected: true}).length;
		var numberOfComments = 0;
		this.collection.filter( function( model ) {
			if ( '' != model.get('markers') && null != model.get('markers') ) {
				var temp = Object.keys( model.get('markers') ).length;
				if ( temp > 0 ) {
					numberOfComments = numberOfComments + temp;
				}
			}
		});

		var welcomeTemplate = this.template({appstate: this.appstate, title: this.appstate.get( 'title' ), date: this.appstate.get( 'date'), imagecount: all, selected: selected, description: this.appstate.get( 'description' ), comments: numberOfComments});
		this.$el.html( welcomeTemplate );
		return this;
	},

	events: {
		'keydown': 'keyAction'
	},

	keyAction: function( e ) {

		// Enter or ESC key
		if ( e.keyCode == 13 || e.keyCode == 27 ) {
			e.preventDefault();
			this.router.navigate('index', {trigger: true} );
		}
	},

	remove: function() {
		// Unbind keydown
		$( document ).off( 'keydown', this.keyAction );
		// Remove yourself
		$( '#picu-info-view' ).remove();
	}

});