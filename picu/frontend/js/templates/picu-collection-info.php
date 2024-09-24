<script id="picu-info-view" type="text/template">
	<div class="picu-modal-inner">
		<h1><@= title @></h1>
		<div class="info-panel">
			<div class="panel-item">
				<div class="panel-value"><@= imagecount @></div>
				<div class="panel-label"><?php _e( 'Images', 'picu'); ?></div>
			</div>
			<div class="panel-item">
				<div class="panel-value"><@= selected @></div>
				<div class="panel-label"><?php _e( 'selected', 'picu'); ?></div>
			</div>
			<?php
				$panels = array();
				echo implode( '',  apply_filters( 'picu_info_view_panel_items', $panels ) );
			?>
		</div>
		<div class="description"><@= description @>
		<?php
			if ( get_post_status( get_the_ID() ) == 'expired' ) {
				echo '<p class="additional-info">' . __( '<em>Please Note:</em> This collection has expired. Therefore it is not possible to change your selection at this time.', 'picu' ). '</p>';
			}
			$expiration_time = get_post_meta( $post->ID, '_picu_collection_expiration_time', true );
			if ( ! empty( $expiration_time ) AND get_post_status() != 'expired' ) {
				$expiration_time = wp_date( get_option( 'date_format' ) . ', ' . get_option( 'time_format' ), $expiration_time );
				echo '<p class="additional-info">' . sprintf( __( '<em>Please Note:</em> This collection will expire on %s and you won\'t be able to make changes after that.', 'picu' ), $expiration_time ) . '</p>';
			}
		?>
		</div>
		<a class="picu-button primary picu-start-selection" href="#index">
		<@ if ( appstate.attributes.poststatus != 'approved' && appstate.attributes.poststatus != 'expired' ) { @>
		<?php _e( 'OK', 'picu' ); ?>
		<@ } else { @>
		<?php _e( 'View collection', 'picu' ); ?>
		<@ } @>
		</a>
		<a class="picu-close-modal" href="#index"><svg viewBox="0 0 100 100"><use xlink:href="#icon_close"></use></svg><span><?php _e( 'close', 'picu' ); ?></span></a>
	</div><!-- .picu-modal-inner -->
</script>