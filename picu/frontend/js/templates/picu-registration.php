<script id="picu-registration" type="text/template">
	<div class="picu-modal-inner picu-modal-inner--narrow">
		<div class="picu-registration-before">
			<h1><?php _e( 'Before you start…', 'picu' ); ?></h1>
			<p><?php
				$email_required = get_option( 'picu_registration_email_required' );
				if ( $email_required == 'on' ) {
					$registration_intro = __( 'Please enter your name and email address to start selecting images.', 'picu' );
				}
				else {
					$registration_intro = __( 'Please enter your name to start selecting images.', 'picu' );
				}
				echo apply_filters( 'picu_register_intro', $registration_intro );
			?></p>
			<form class="picu-registration-form" method="post">
				<p class="col-100"><label for="name"><?php _e( 'Name', 'picu' ); ?></label> <input type="text" name="picu-registration-form['name']" id="name" /></p>
				<p class="col-100"><label for="email"><?php _e( 'Email', 'picu' ); if ( $email_required != 'on' ) { echo ' (' . __( 'optional', 'picu' ) . ')'; } ?></label> <input type="email" name="picu-registration-form['email']" id="email" /></p>
				<p class="col-100 align-center"><button class="picu-button primary picu-register"><?php _e( 'Continue', 'picu' ); ?></button></p>
			</form>
			<a class="picu-close-modal" href="#index"><svg viewBox="0 0 100 100"><use xlink:href="#icon_close"></use></svg><span><?php _e( 'close', 'picu' ); ?></span></a>
		</div>
		<div class="picu-registration-after" style="display: none;">
		<?php
			$output = '<h1>' . __( 'Thanks!', 'picu' ) . '</h1>';
			$output .= '<p>' . __( 'Please check your email inbox for your link to access the collection.', 'picu' ) . '</p>';
			$output .= '<p>' . __( 'You can safely close this window now.', 'picu' ) . '</p>';
			echo apply_filters( 'picu_registration_confirmation', $output );
			?>
		</div>
	</div><!-- .picu-modal-inner -->
</script>