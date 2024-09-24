var picu = picu || {};

/**
 * Boot picu app
 */
picu.boot = function( container, data, appstate ) {

	container = $( container );

	/**
	 * Create a collection of images
	 * @param collection data
	 */
	try {
		var o = JSON.parse( data );
		if ( o && typeof o === "object" ) {
			var gallery = new picu.GalleryCollection( o );
		}
	}
	catch ( e ) {
		window.alert( "The following error occured:\n\n" + e.message + "\n\nPlease contact support at picu.io/support" );
		return false;
	}

	/**
	 * Create appState model
	 * @param all the data
	 */
	var appState = new picu.appState( jQuery.parseJSON( appstate ) );

	/**
	 * Create the router
	 *
	 * @param container (html element in which our app lives)
	 * @param collection of images
	 * @param nonce to verify any ajax requests, like save or send selection
	 * @param post id
	 * @param post status
	 * @param post title
	 * @param post description
	 * @param ajax url
	 */
	var router = new picu.Router({el: container, collection: gallery, appstate: appState })

	Backbone.history.start({pushState: false});
}


/**
 * Toggle model attribute selection
 *
 */
picu.saveSelection = function( image ) {
	// Do not change selection state if there is no ident
	var temp = JSON.parse( appstate );
	if ( temp.ident == null ) {
		return;
	}

	// Validation: Check that image.model is actually our backbone model
	if ( image.model instanceof picu.singleImage ) {

		// Set selected attribute
		image.model.set( 'selected', ! image.model.get( 'selected' ) );

		// Change class like we already do in the lightbox template
		if ( image.model.get( 'selected' ) == true ) {
			image.$el.addClass( 'selected' );
		}
		else {
			image.$el.removeClass( 'selected' );
		}
	}
}



/**
 * Event Bus extention
 *
 */
picu.EventBus = _.extend( {}, Backbone.Events );



/**
 * Save the collection
 *
 */
picu.doTheSave = function( image ) {
	// Do not save if the client needs to register first
	var router = new Backbone.Router();
	var temp = jQuery.parseJSON( appstate );
	if ( temp.ident == null ) {
		router.navigate( 'register', {trigger: true} );
		return;
	}

	// Hide save button, show spinner
	$( '.picu-save' ).addClass( 'hidden' );
	$( '<div class="picu-saving">saving</div>' ).insertBefore( '.picu-save' );

	// Get selection
	var selection = _.map( image.model.collection.where({selected: true}), function( s ){ return s.attributes.imageID; });

	// Gather all markers
	var allMarkers = {};

	var temp = image.model.collection.map( function( model ){
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
	$.post( image.appstate.get( 'ajaxurl' ), {

		action: 'picu_send_selection',
		security: image.appstate.get( 'nonce' ),
		postid: image.appstate.get( 'postid' ),
		ident: image.appstate.get( 'ident' ),
		selection: selection,
		markers: allMarkers,
		intent: 'temp'

	}, function( response ) {

		// Display overlay if saving failed
		var overlayclass = '';
		if ( response.success == true ) {
			overlayclass = ' success';
		} else {
			overlayclass = ' fail';
			$( '.picu-collection' ).append('<div class="overlay'+ overlayclass +'"><div class="message"><p>' + response.data.message + '</p><p><a class="picu-button small primary js-close-message">' + response.data.button_text + '</a></p></div></div>');
		}

		// Remove spinner, show save button
		$( '.picu-saving' ).remove();
		$( '.picu-save' ).removeClass( 'hidden' );

	}).fail( function() {
		// Ajax fail
		// TODO: Make translatable
		$( '.picu-collection' ).append('<div class="overlay fail"><div class="message"><p>Error: Request failed.<br />Do you have a working internet connection?</p><p><a class="picu-button small primary js-close-message" href="#">OK</a></p></div></div>');

		// Remove spinner, show save button
		$( '.loading' ).remove();
		$( '.picu-save' ).show();
	});

}

picu.EventBus.on( 'save:now', picu.doTheSave );


/**
 * Return the current date, formatted.
 *
 * @since 2.3.5
 *
 * @see https://stackoverflow.com/questions/3552461/how-do-i-format-a-date-in-javascript 
 *
 * @return string The formated date.
 */
picu.date = function( date ) {
	// TODO: Correct date for WordPress' timezone setting!
	// Get the language from the appstate
	lang = jQuery.parseJSON( appstate ).lang;

	// Set date to now, if it is not defined
	if ( date == undefined ) {
		date = new Date();
	}

	// Create date and format it
	let formattedDate =  new Date( date ).toLocaleDateString( lang, {
		year: 'numeric',
		month: 'long',
		day: '2-digit',
		hour: "2-digit",
		minute: "2-digit",
		hour12: false
	});

	return formattedDate;
}


/**
 * Polyfil: Add Object.keys support in older environments that do not natively support it
 *
 * @source From https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Object/keys
 */
if (!Object.keys) {
	Object.keys = (function() {
		'use strict';
		var hasOwnProperty = Object.prototype.hasOwnProperty, hasDontEnumBug = !({ toString: null }).propertyIsEnumerable('toString'), dontEnums = [
			'toString',
			'toLocaleString',
			'valueOf',
			'hasOwnProperty',
			'isPrototypeOf',
			'propertyIsEnumerable',
			'constructor'
		], dontEnumsLength = dontEnums.length;

		return function(obj) {
			if (typeof obj !== 'function' && (typeof obj !== 'object' || obj === null)) {
				throw new TypeError('Object.keys called on non-object');
			}

			var result = [], prop, i;
			for (prop in obj) {
				if (hasOwnProperty.call(obj, prop)) {
					result.push(prop);
				}
			}

			if (hasDontEnumBug) {
				for (i = 0; i < dontEnumsLength; i++) {
					if (hasOwnProperty.call(obj, dontEnums[i])) {
						result.push(dontEnums[i]);
					}
				}
			}
			return result;
		};
	}());
}