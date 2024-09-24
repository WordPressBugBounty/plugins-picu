jQuery(function($){
	$(document).ready(function() {

		$( '.picu-collection' ).on( 'click', '.js-close-message', function( e ) {
			e.preventDefault();
			$( '.overlay' ).remove();
		});

		$( 'body' ).on( 'click', function( e ) {
			var sizeList = document.querySelector( '.picu-grid-size__list' );
			if ( sizeList != null && ! e.target.classList.contains( 'picu-grid-size__toggle' ) ) {
				document.querySelector( '.picu-grid-size__list' ).classList.remove( 'is-visible' );
			}

			var selectionAlert = document.querySelector( '.picu-selection-alert__explanation' );
			if ( selectionAlert != null && ( e.target.closest( 'div' ) == null || ! e.target.closest( 'div' ).classList.contains( 'picu-selection-alert' ) ) ) {
				document.querySelector( '.picu-selection-alert__explanation' ).classList.remove( 'is-visible' );
			}
		});

	});
});