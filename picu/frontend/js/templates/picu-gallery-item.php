<script id="picu-gallery-item" type="text/template">
    <figure class="picu-figure" tabindex="0">
        <div class="picu-imgbox <@= orientation @>">
            <div class="picu-imgbox-inner">
				<@ if ( lazyloaded == true ) { @>
				<a class="picu-imgbox-link" href='#<@= number @>' tabindex="-1"><img src="<@= imagePath_small @>" srcset="<@= imagePath_small_srcset @>" sizes="<@= size @>" /></a>
				<@ } else { @>
					<a class="picu-imgbox-link" href='#<@= number @>' tabindex="-1"><img class="lazy" src="<?php echo PICU_URL; ?>frontend/images/ripple-dark.gif" style="width: 20px; height: 20px;" /></a>
				<@ } @>
            </div>
        </div>
        <figcaption class="picu-caption">
            <div class="picu-img-title">
                <span class="picu-img-name" title="<@= Object.keys( title ).map( function( key ) { return title[key]; } ).join( ' ' ) @>"><@= Object.keys( title ).map( function( key ) { return '<span class="' + key + '">' + title[key] + '</span>'; } ).join( ' ' ) @>
                </span>
            </div>
			<@ if ( JSON.parse( appstate ).poststatus != 'approved' && JSON.parse( appstate ).poststatus != 'expired' ) { @>
            <div class="picu-select-item">
                <input type="checkbox" name="approved-<@= number @>" id="check<@= number @>" value="<@= imageID @>" tabindex="-1" /> <label for="check<@= number @>" tabindex="-1">
                    <svg viewBox="0 0 100 100"><use xlink:href="#icon_check"></use></svg>
                    <span class="picu-select-label"><?php _e( 'Select image', 'picu' ); ?> <@= number @></span>
                </label>
            </div>
            <@ } @>
        </figcaption>
    </figure><!-- .picu-figure -->
</script>