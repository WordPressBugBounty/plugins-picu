<?php
/**
 * @since 0.3.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// No caching or minification for picu collections
if ( ! defined( 'DONOTCACHEPAGE' ) ) {
	define( 'DONOTCACHEPAGE', true );
}
if ( ! defined( 'DONOTCACHEDB' ) ) {
	define( 'DONOTCACHEDB', true );
}
if ( ! defined( 'DONOTMINIFY' ) ) {
	define( 'DONOTMINIFY', true );
}
if ( ! defined( 'DONOTCDN' ) ) {
	define( 'DONOTCDN', true );
}
if ( ! defined( 'DONOTCACHEOBJECT' ) ) {
	define( 'DONOTCACHEOBJECT', true );
}

picu_collection_bouncer();

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
	<head>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<meta name="robots" content="noindex, nofollow" />
	<?php
		if ( has_site_icon() ) {
			wp_site_icon();
		}
	?>
		<title><?php if ( post_password_required( $post ) AND $post->post_author != get_current_user_id() ) {
			_e( 'This collection is password protected.', 'picu' );
			} else { the_title(); } ?></title>
		<?php

			picu_load_styles();

			$custom_styles = '';
			$custom_styles = apply_filters( 'picu_custom_styles', $custom_styles );

			if ( !empty( $custom_styles) ) {
				echo '<style>' . $custom_styles . '</style>';
			}

		?>
	</head>
	<body<?php picu_body_classes(); ?>>
		<?php if ( post_password_required( $post) AND $post->post_author != get_current_user_id() ) { ?>
			<div class="picu-protected">
				<div class="picu-protected-inner">
				<?php
					$picu_password_box_content = '<h1>' . __( 'This collection is password protected.', 'picu' ) . '</h1>';
					$picu_password_box_content = apply_filters( 'picu_password_box_content', $picu_password_box_content, $post->ID );
					echo $picu_password_box_content;

					if ( isset( $_COOKIE['wp-postpass_' . COOKIEHASH] ) and $_COOKIE['wp-postpass_' . COOKIEHASH] != $post->post_password ) { ?>
						<p class="error-msg"><?php _e( 'Wrong password.', 'picu' ); ?></p>
				<?php } ?>
				<?php echo get_the_password_form(); ?>
				</div>
			</div><!-- .picu-protected -->
			<?php
		}
		else {
		?>

		<?php
			if ( file_exists( PICU_PATH . '/frontend/images/icons.svg' ) ) {
				include_once( PICU_PATH . '/frontend/images/icons.svg' );
			}
		?>
		<?php
			// Display picu admin bar
			if ( is_user_logged_in() AND current_user_can( picu_capability() ) AND apply_filters( 'picu_show_admin_bar', true ) ) {
				if ( $post->post_status == 'delivered' ) { ?>
					<div class="picu-admin-bar delivered">
						<div class="picu-admin-bar-status"><?php _e( 'Delivered', 'picu' ); ?></div>
						<div class="picu-admin-bar-message">
						<?php echo sprintf( __( 'This collection has been delivered to the client on <span class="date">%s</span>.', 'picu' ), wp_date( get_option( 'date_format' ), picu_get_collection_history_event_time( $post->ID, 'delivered' ) ) ); ?></div>
						<div class="picu-admin-bar-actions"><?php edit_post_link( __( 'View Download Stats', 'picu' ) ); ?></div>
					</div>
				<?php } elseif ( $post->post_status == 'delivery-draft' ) { ?>
					<div class="picu-admin-bar delivery-draft">
						<div class="picu-admin-bar-status"><?php _e( 'Delivery Draft', 'picu' ); ?></div>
						<div class="picu-admin-bar-message"><?php _e( 'This collection has not been sent to the client.', 'picu' ); ?></div>
						<div class="picu-admin-bar-actions"><?php edit_post_link( __( 'Edit', 'picu' ) ); ?></div>
					</div>
				<?php } elseif ( $post->post_status == 'sent' ) { ?>
					<div class="picu-admin-bar sent">
						<div class="picu-admin-bar-status"><?php _e( 'Waiting for approval', 'picu' ); ?></div>
						<div class="picu-admin-bar-message">
						<?php echo sprintf( __( 'This collection has been sent to the client on <span class="date">%s</span>.', 'picu' ), wp_date( get_option( 'date_format' ), picu_get_collection_history_event_time( $post->ID, 'sent' ) ) ); ?></div>
						<div class="picu-admin-bar-actions"><?php edit_post_link( __( 'Edit', 'picu' ) ); ?></div>	
					</div>
				<?php } elseif ( $post->post_status == 'approved' ) { ?>
					<div class="picu-admin-bar approved">
						<div class="picu-admin-bar-status"><?php _e( 'Approved', 'picu' ); ?></div>
						<div class="picu-admin-bar-message"><?php echo sprintf( __( 'The selection has been sent by the client on %s.', 'picu' ), wp_date( get_option( 'date_format' ), picu_get_collection_history_event_time( $post->ID, 'approved' ) ) ); ?></div>
						<div class="picu-admin-bar-actions"><?php edit_post_link( __( 'View Selection', 'picu' ) ); ?></div>
					</div>
				<?php } else { ?>
					<div class="picu-admin-bar draft">
						<div class="picu-admin-bar-status"><?php _e( 'Draft', 'picu' ); ?></div>
						<div class="picu-admin-bar-message"><?php _e( 'This collection has not been sent to the client.', 'picu' ); ?></div>
						<div class="picu-admin-bar-actions"><?php edit_post_link( __( 'Edit', 'picu' ) ); ?></div>	
					</div>
				<?php }
			}
		?>
		<header class="picu-header">
			<?php
				$picu_header = '<div class="picu-header-inner">';
				$picu_header .= '<div class="blog-name">' . get_bloginfo( 'name' ) . '</div>';
				$picu_header .= '<div class="picu-collection-title">'.  get_the_title( $post->ID ) . '</div>';
				$picu_header .= '</div>';

				$picu_header = apply_filters( 'picu_header', $picu_header, $post->ID );
				echo $picu_header;
			?>

			<?php if ( is_user_logged_in() ) {
				edit_post_link( __( 'Edit', 'picu' ), '<span class="edit-button">', '</span>', $post->ID );
			} ?>
		</header>

		<?php do_action( 'picu_before_collection_images' ); ?>

		<div class="picu-collection"></div>

		<?php
			// Load backbone templates
			$templates = picu_load_backbone_templates();

			foreach ( $templates as $template => $template_path ) {
				include_once( $template_path );
				echo "\n\n\t\t";
			}
		?>

		<script>
			var picu = picu || {};
		</script>

		<script src='<?php echo PICU_URL; ?>frontend/js/_vendor/jquery.min.js'></script>
		<script src='<?php echo PICU_URL; ?>frontend/js/_vendor/jquery.visible.js'></script>
		<script src='<?php echo PICU_URL; ?>frontend/js/_vendor/underscore.min.js'></script>
		<script src='<?php echo PICU_URL; ?>frontend/js/_vendor/backbone.min.js'></script>
		<script src='<?php echo PICU_URL; ?>frontend/js/_vendor/dateformat.min.js'></script>

		<script>
			_.templateSettings = {
				evaluate: /<[%@]([\s\S]+?)[%@]>/g,
				interpolate: /<[%@]=([\s\S]+?)[%@]>/g,
				escape: /<[%@]-([\s\S]+?)[%@]>/g
			};
		</script>

		<?php
			// Load collections, models & views
			$cmv = picu_load_cmv();

			foreach ( $cmv as $file_name => $file_path ) {
				echo '<script src=' . $file_path . '></script>' . "\n\t\t";
			}
		?>

		<script src='<?php echo PICU_URL; ?>frontend/js/router.js'></script>

		<script src='<?php echo PICU_URL; ?>frontend/js/picu-app.js'></script>
		<script src='<?php echo PICU_URL; ?>frontend/js/picu-ui-helpers.js'></script>

		<script>
			// Load collection data and app state
			var data = '<?php echo picu_get_images(); ?>';
			var appstate = '<?php echo picu_get_app_state(); ?>';

			// Booting up...
			$(function() { picu.boot( $( '.picu-collection' ), data, appstate ); });
		</script>

		<?php
			$custom_scripts = '';
			$custom_scripts = apply_filters( 'picu_custom_scripts', $custom_scripts );
			echo $custom_scripts;
		?>

		<?php } // post_password_required() ?>

		<?php
			if ( get_option( 'picu_picu_love' ) == 'on' ) { ?>
				<a class="picu-brand" href="https://picu.io/">powered by picu</a>
		<?php } ?>
	</body>
</html>