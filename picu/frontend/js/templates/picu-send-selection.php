<script id="picu-send-selection" type="text/template">
	<div class="picu-modal-inner">
		<h1><?php echo apply_filters( 'picu_approval_heading', __( 'Approve Collection', 'picu' ) ); ?>: <@= title @></h1>
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
		<div class="picu-approval-form">
			<p class="col-100"><label for="picu_approval_message"><?php _e( 'Anything else you want us to know?', 'picu' ); ?></label><textarea name="picu-approval-form[picu_approval_message]" id="picu_approval_message" placeholder="<?php __( 'Leave a comment…', 'picu' ); ?>"></textarea></p>
		</div>
		<?php
			$picu_approval_warning = '<p><strong>' . __( 'You are about to approve this collection.', 'picu' ) . '</strong><br />' . __( 'Please note, that you won\'t be able to make changes to your selection after that.', 'picu' ) . '</p>';
			echo apply_filters( 'picu_approval_warning', $picu_approval_warning );
		?>
		<a id="picu-send-button" class="picu-button primary" href="#send"><?php echo apply_filters( 'picu_approval_button_text', _x( 'approve selection', 'send selection button text', 'picu' ) ); ?></a>
		<a class="picu-close-modal" href="#index"><svg viewBox="0 0 100 100"><use xlink:href="#icon_close"></use></svg><span><?php _e( 'close', 'picu' ); ?></span></a>
	</div><!-- .picu-modal-inner -->
</script>