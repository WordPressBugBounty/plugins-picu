var picu = picu || {};

picu.SendView = Backbone.View.extend({

	model: picu.appState,
	tagName: 'div',
	className: 'picu-modal',
	id: 'picu-send',

	template: _.template( jQuery( "#picu-send-selection" ).html() ),

	initialize: function( options ) {
		this.collection = options.collection;
		this.router = options.router;
		this.appstate = JSON.parse( appstate );

		// Key bindings
		_.bindAll( this , 'keyAction' );
		$( document ).on( 'keydown', this.keyAction);
	},

	render: function() {
		var imagecount = this.collection.length;
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

		var sendSelectionTemplate = this.template({selected: selected, imagecount: imagecount, title: this.model.get( 'title' ), comments: numberOfComments});
		this.$el.html( sendSelectionTemplate );
		return this;

	},

	events: {
		'click #picu-send-button': 'sendSelection',
		'keydown': 'keyAction'
	},

	sendSelection: function( e ) {

		e.preventDefault();

		// Get imageID from models in the collection where selected is true
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

		// Get values from approval form
		var values = {};
		var fields = document.querySelectorAll( '[name^=picu-approval-form]' );
		_.each( fields, function( e ) {
			// Get the title from selectbox option, not just the value
			var title = e.querySelectorAll( "[selected]" );
			if ( typeof title[0] !== 'undefined' ) {
				title[0].innerText;
				values[e.id] = { value:e.value, label:e.labels[0].innerText, title:title[0].innerText }
			}
			else {
				values[e.id] = { value:e.value, label:e.labels[0].innerText }
			}
		});

		$( '<div class="loading">loading</div>' ).insertBefore( '#picu-send-button' );
		$( '#picu-send-button' ).hide();

		var current = function successCallback() {}
		current.model = this.model;
		current.router = this.router;

		//Send AJAX request
		$.post( this.model.get( 'ajaxurl' ), {

			action: 'picu_send_selection',
			security: this.model.get( 'nonce' ),
			postid: this.model.get( 'postid' ),
			ident: this.model.get( 'ident' ),
			selection: selection,
			markers: allMarkers,
			approval_fields: values,
			intent: 'approve'

		}, function( response ) {

			if ( response.success == true ) {

				// Set poststatus to approved
				current.model.set({'poststatus': 'approved'});
				picu.poststatus = 'approved';

				// On success, show approved view
				location.href = "#approved";

			}
			else {
				// Show error message
				$( '.picu-collection' ).append('<div class="overlay fail"><div class="message"><p>' + response.data.message + '</p><p><a class="picu-button small primary js-close-message" href="#">OK</a></p></div></div>');
				$( '.loading' ).remove();
				$( '#picu-send-button' ).show();
			}

		}).fail( function() {
			// Ajax fail
			$( '.picu-collection' ).append('<div class="overlay fail"><div class="message"><p>' + this.appstate.request_failed_error + '</p><p><a class="picu-button small primary js-close-message" href="#">OK</a></p></div></div>');
		});

	},

	keyAction: function( e ) {

		// ESC key
		if ( e.keyCode == 27 ) {
			e.preventDefault();
			this.router.navigate('index', {trigger: true} );
		}
	},

	remove: function() {
		// Unbind keydown
		$( document ).off( 'keydown', this.keyAction );
		// Remove yourself
        $( '#picu-send' ).remove();
	}

});