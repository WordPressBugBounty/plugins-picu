var picu = picu || {};

picu.RegistrationView = Backbone.View.extend({
	tagName: 'div',
	className: 'picu-modal',
	id: 'picu-registration',

	template: _.template( jQuery( "#picu-registration" ).html() ),

	initialize: function( options ) {
		this.title = options.title;
		this.router = options.router;
		this.appstate = options.appstate;

		// Key bindings
		_.bindAll( this , 'keyAction' );
		$( document ).on( 'keydown', this.keyAction);
	},

	render: function() {
		var registrationTemplate = this.template({title: this.title});
		this.$el.html( registrationTemplate );
		return this;
	},

	events: {
		'keydown': 'keyAction',
		'click .picu-register': 'register',
		'click .picu-close-modal': 'closeRegistration',
	},

	keyAction: function( e ) {
		// ESC key
		if ( e.keyCode == 27 ) {
			e.preventDefault();
			this.router.navigate('index', {trigger: true} );
		}
	},

	register: function( e ) {
		e.preventDefault();

		// Get values from registration form
		var values = {};
		var fields = document.querySelectorAll( '[name^=picu-registration-form]' );
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

		//Send AJAX request
		$.post( this.appstate.get( 'ajaxurl' ), {
			action: 'picu_register',
			security: this.appstate.get( 'nonce' ),
			postid: this.appstate.get( 'postid' ),
			registration_fields: values,
			intent: 'register'
		}, function( response ) {
			if ( response.success == true ) {
				// Display the verification notice
				if ( response.data.verification == 1 ) {
					$( '.picu-registration-before' ).hide();
					$( '.picu-registration-after' ).show();
					return;
				}
				// Open collection with ident directly
				else if ( response.data.ident != null ) {
					// Redirect to collection URL with the new ident param
					window.location.replace( window.location.href.split('#')[0] + '?ident=' + response.data.ident );
				}
			}
			else {
				// Display error message
				$( '.picu-collection' ).append('<div class="overlay fail"><div class="message"><p>' + response.data.message + '</p><p><a class="picu-button small primary js-close-message" href="#">' + response.data.button_text + '</a></p></div></div>');
			}

		}).fail( function( response ) {
			// Ajax fail
			$( '.picu-collection' ).append('<div class="overlay fail"><div class="message"><p>' + this.appstate.request_failed_error + '</p><p><a class="picu-button small primary js-close-message" href="#">OK</a></p></div></div>');
		});
	},

	closeRegistration: function( e ) {
		e.preventDefault();
		// Go back to the last opened lightbox view, if registration was triggered from there
		// Make sure there is "enough history"
		if ( this.router.history != null && this.router.history.length >= 2 ) {
			// Check if the last view was the lightbox
			if ( this.router.history[this.router.history.length - 2].name == 'picuLightbox') {
				var imgNum = '#' + this.router.history[this.router.history.length - 2].args[0];
				this.router.navigate( imgNum, {trigger: true} );
				return;
			}
		}

		this.router.navigate( '#index', {trigger: true} );
		this.remove();
	},

	remove: function() {
		// Unbind keydown
		$( document ).off( 'keydown', this.keyAction );
		// Remove yourself
		$( '#picu-registration' ).remove();
	}
});