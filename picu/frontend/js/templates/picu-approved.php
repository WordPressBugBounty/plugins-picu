<script id="picu-approved" type="text/template">
<?php
	$target_url = apply_filters( 'picu_redirect', esc_url( get_home_url() ) );
	$redirect_timer = apply_filters( 'picu_redirect_timer', 5 );
	if ( $redirect_timer == 'immediately' ) { ?>
		<@ window.location.href = '<?php echo $target_url; ?>'; @>
	<?php } else { ?>
		<div class="picu-modal-inner">
			<?php ob_start(); ?>
			<h1><?php _e( 'Thank you!', 'picu' ); ?></h1>
			<p><?php _e( 'The collection has been approved and the photographer has been notified.', 'picu' ); ?></p>
			<p><?php _e( 'You can now close this browser window.', 'picu' ); ?></p>
			<?php echo apply_filters( 'picu_approved_message', ob_get_clean() ); ?>
			<?php
				if ( is_numeric( $redirect_timer ) AND $redirect_timer > 0 ) { ?>
					<p><?php echo sprintf( __( 'You will be redirected in %s seconds.', 'picu' ), $redirect_timer ); ?></p>
					<@
						setTimeout( function(){ window.location = '<?php echo $target_url; ?>'; }, <?php echo intval( $redirect_timer ); ?> * 1000 );
					@>
			<?php } else { ?>
				<?php $ident = ( ! empty( $_GET['ident'] ) ) ? $_GET['ident'] : ''; ?>
				<a class="picu-close-modal" href="<?php echo get_permalink() . ( parse_url( get_permalink(), PHP_URL_QUERY ) ? '&' : '?' ) . 'ident=' . $ident; ?>"><svg viewBox="0 0 100 100"><use xlink:href="#icon_close"></use></svg><span><?php _e( 'close', 'picu' ); ?></span></a>
			<?php } ?>
		</div><!-- .picu-modal-inner -->
	<?php } ?>
</script>