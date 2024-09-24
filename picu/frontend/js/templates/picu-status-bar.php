<script id="picu-status-bar" type="text/template">
	<?php
		$after_info_button = '';
		echo apply_filters( 'picu_frontend_after_info_button', $after_info_button );
	?>
	<div class="picu-display-filter">
		<a class="picu-filter-selected">
			<span class="picu-filter-icon"><svg viewBox="0 0 100 100"><use xlink:href="#icon_check"></use></svg></span>
			<span class="picu-filter-label"><?php _e( 'Selected', 'picu' ); ?></span>
		</a>
		<a class="picu-filter-unselected">
			<span class="picu-filter-icon"><svg viewBox="0 0 100 100"><use xlink:href="#icon_close"></use></svg></span>
			<span class="picu-filter-label"><?php _e( 'Unselected', 'picu' ); ?></span>
		</a>
		<a class="picu-filter-reset"><svg viewBox="0 0 100 100"><use xlink:href="#icon_close"></use></svg><span><?php _e( 'Reset filters', 'picu' ); ?></span></a>
	</div>
	<div class="picu-selection-count">
		<a class="picu-info-button" href="#collection-info" title="<?php _e( 'Show Information about this collection', 'picu' ); ?>"><?php _e( 'Show Information about this collection', 'picu' ); ?></a>
		<span class="picu-selected-num"><@= selected @></span> <span class="picu-total-num">/ <@= all @></span>
	</div>
	<@ if ( appstate.attributes.poststatus != 'approved' && appstate.attributes.poststatus != 'expired' ) { @>
	<div class="picu-collection-actions">
		<span class="picu-save"><span><?php _e( 'saved', 'picu' ); ?></span><svg viewBox="0 0 100 100"><use xlink:href="#icon_check"></use></svg></span>
		<a class="picu-button primary picu-pre-send" href="#send"><?php /* translators: Content inside the <span> is hidden on smaller screens */ echo apply_filters( 'picu_send_selection_button_text', __( 'Send<span> selection</span>…', 'picu' ) ); ?></a>
	</div>
	<@ } @>
</script>