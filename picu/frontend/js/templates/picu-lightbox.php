<script id="picu-lightbox" type="text/template">
    <div class="picu-lightbox-inner">
        <div class="picu-lightbox-image-container">
            <a href="#index"><img <@ if ( selected == true ) { print( 'class="selected" ') } @> src="<@= imagePath @>" srcset="<@= imagePath_srcset @>" sizes="<@= sizeLightbox @>" /></a>
        </div>
        <nav class="picu-lightbox-navigation">
            <a class="picu-lightbox-close" href="#index"><svg viewBox="0 0 100 100"><use xlink:href="#icon_close"></use></svg><span><?php _e( 'close lightbox', 'picu' ); ?></span></a>
            <span class="picu-img-name" title="<@= Object.keys(title).map( function( key ) { return title[key]; } ).join( ' ' ) @>"><@= Object.keys(title).map( function( key ) { return '<span class="' + key + '">' + title[key] +  '</span>'; } ).join( ' ' ) @></span>
            <a class="picu-lightbox-next"><svg viewBox="0 0 100 100"><use xlink:href="#icon_arrow_right"></use></svg><span><?php _e( 'next image', 'picu' ); ?></span></a>
            <a class="picu-lightbox-prev"><svg viewBox="0 0 100 100"><use xlink:href="#icon_arrow_left"></use></svg><span><?php _e( 'previous image', 'picu' ); ?></span></a>
            <@ if ( JSON.parse( appstate ).poststatus != 'approved' && JSON.parse( appstate ).poststatus != 'expired' ) { @>
            <a class="picu-lightbox-select<@ if ( selected == true ) { print( ' selected' ) } @>"><svg viewBox="0 0 100 100"><use xlink:href="#icon_check"></use></svg><span><?php _e( 'select image', 'picu' ); ?></span></a>
			<@ } @>
			<@ if ( JSON.parse( appstate ).is_zip_download_enabled ) { @>
			<a class="picu-download-button" href="<@= imagePath_original @>" download><span><?php _e( 'Download', 'picu' ); ?></span><svg viewBox="0 0 100 100"><use xlink:href="#icon-download"></use></svg></a>
			<@ } @>
        </nav>
    </div><!-- .picu-lightbox-inner -->
</script>