<?php
/**
 * Plugin Name: picu
 * Plugin URI: https://picu.io/
 * Description: Send a collection of photographs to your client for approval.
 * Version: 2.3.8
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Haptiq
 * Author URI: https://picu.io/
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: picu
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
 * Include functions for picu
 *
 * @since 0.2.0
 */
if ( ! function_exists( 'picu_setup' ) ) {

	function picu_setup() {

		// Define plugin version
		define( 'PICU_VERSION', '2.3.8' );

		// Define path for this plugin
		define( 'PICU_PATH', plugin_dir_path(__FILE__) );

		// Define URL for this plugin
		define( 'PICU_URL', plugin_dir_url(__FILE__) );

		// Define picu upload dir
		$upload_dir = wp_upload_dir();
		define( 'PICU_UPLOAD_DIR', $upload_dir['basedir'] . '/picu' );

		// Define telemetry URL
		define( 'PICU_TELEMETRY_URL', 'https://picu.io/wp-json/picu-telemetry/v1/' );

		// Include functions to render admin menu and settings page
		require PICU_PATH . 'backend/includes/picu-settings.php';

		// Include functions to render add-ons page
		require PICU_PATH . 'backend/includes/picu-addons-page.php';

		// Include function for registering custom post type collection
		require PICU_PATH . 'backend/includes/picu-cpt-collection.php';

		// Include welcome screen
		require PICU_PATH . 'backend/includes/picu-welcome-screen.php';

		// Include functions for admin notices and error messages
		require PICU_PATH . 'backend/includes/picu-admin-notices.php';

		// Everything that doesn't fit anywhere else...
		require PICU_PATH . 'backend/includes/picu-helper.php';

		// Include custom metabox and our custom edit screen
		require PICU_PATH . 'backend/includes/picu-edit-collection.php';

		// Picu media handling
		require PICU_PATH . 'backend/includes/picu-media.php';

		// Handle ajax requests
		require PICU_PATH . 'backend/includes/picu-ajax.php';

		// Fix compatibility issues with third parties
		require PICU_PATH . 'backend/includes/picu-compatibility.php';
		
		// Picu Email sending class
		require PICU_PATH . 'backend/includes/emails/class-picu-emails.php';
		
		// Picu Email sending functions
		require PICU_PATH . 'backend/includes/emails/picu-emails.php';

		// Include template redirection etc. for collections
		require PICU_PATH . 'frontend/includes/picu-template-functions.php';
		
		// Deprecated functions, filters and hooks
		require PICU_PATH . 'backend/includes/deprecated.php';

		// Handle picu telemetry
		require PICU_PATH . 'backend/includes/picu-telemetry.php';

		// Autoload classes installed via composer (emogrify)
		require PICU_PATH . 'vendor/autoload.php';

		// Add picu debug info to Site Health screen
		require PICU_PATH . 'backend/includes/picu-site-health.php';

		// Check the settings version, run upgrader
		$settings_version = get_option( 'picu_settings_version' );
		// Version upgrade is needed
		if ( empty( $settings_version ) ) {
			picu_settings_upgrade();
		}
	}
}

add_action( 'after_setup_theme', 'picu_setup' );


/**
 * Run upgrades after update
 *
 * Since 2.3.0
 */
function picu_upgrade() {
	$settings_version = get_option( 'picu_settings_version' );

	if ( version_compare( $settings_version, PICU_VERSION, '<' ) ) {
		picu_collections_upgrade();
	}
}

// This needs to run after `init` so everything we need is in place!
add_action( 'init', 'picu_upgrade', 11 );


/**
 * Set transient to display welcome screen on activation
 *
 * @since 0.7.0
 */
function picu_activate_welcome_screen() {
	// Set transient for redirect to activation screen
	set_transient( '_picu_welcome_screen_activation_redirect', true, 30 );
}

register_activation_hook( __FILE__, 'picu_activate_welcome_screen' );


/**
 * Flush rewrite rules on plugin activation/deactivation
 *
 * @since 0.7.0
 */
function picu_flush_rewrites() {

	// Include custom post type registration
	include( plugin_dir_path(__FILE__) . 'backend/includes/picu-cpt-collection.php' );

	// Make sure our custom post types are defined first
	picu_register_cpt_collection();

	// Flush the rewrite rules
	flush_rewrite_rules();

}

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
register_activation_hook( __FILE__, 'picu_flush_rewrites' );


/**
 * Load some custom styling for our admin screens.
 *
 * @since 0.3.2
 */
function picu_admin_styles_scripts() {

	global $post;

	$current_screen = get_current_screen();

	// Prevent conflicts in case no current_screen is set
	if ( empty( $current_screen ) ) {
		return;
	}

	// Only load those styles on edit collection screens
	if ( is_admin() ) {
		wp_enqueue_style( 'picu-admin', PICU_URL . 'backend/css/picu-admin.css', false, filemtime( PICU_PATH . 'backend/css/picu-admin.css' ) );

		global $pagenow;
		if ( $current_screen->post_type == 'picu_collection' AND get_post_type() == 'picu_collection' AND $pagenow == 'post-new.php' || $pagenow == 'post.php' ) {
			// Enqueue wp.media scripts manually, because we don't load the default editor on collections
			// Post needs to be provided, or uploaded media will not get attached to the collection
			$args = array( 'post' => $post->ID );
			wp_enqueue_media( $args );
		}

		if ( $current_screen->base == 'picu_page_picu-design-appearance' ) {
			// Add the color picker css file
			wp_enqueue_style( 'wp-color-picker' );
		}

		// Make sure we are on the right screen
		if ( ( $current_screen->post_type == 'picu_collection' && get_post_type() == 'picu_collection' && $pagenow == 'post-new.php' || $pagenow == 'post.php' ) || ( $current_screen->base == 'dashboard_page_picu-welcome-screen' ) || ( $current_screen->post_type == 'picu_collection' AND get_post_type() == 'picu_collection' AND $pagenow == 'edit.php' ) || strpos( $current_screen->base, 'picu_page_picu-' ) !== false ) {

			// Enqueue media
			wp_enqueue_media();

			// Enqueue script
			wp_enqueue_script( 'picu-admin', PICU_URL . 'backend/js/picu-admin.min.js', array( 'jquery', 'jquery-ui-draggable', 'jquery-ui-sortable', 'underscore', 'backbone', 'wp-color-picker' ), filemtime( PICU_PATH . 'backend/js/picu-admin.min.js' ), true );

			$post_id = false;
			if ( isset( $post->ID ) AND ! empty( $post->ID ) ) {
				$post_id = $post->ID;
			}

			// Localize it
			$picu_localization_strings = array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'ajax_nonce' => wp_create_nonce( 'picu_ajax' ),
				'postID' => $post_id,
				'media_modal_title' => __( 'Upload Images', 'picu' ),
				'media_modal_button_insert_text' => __( 'Insert Images', 'picu' ),
				'sent_option_label' => __( 'Sent', 'picu' ),
				'approved_option_label' => __( 'Approved', 'picu' ),
				'expired_option_label' => __( 'Expired', 'picu' ),
				'button_text_publish' => __( 'Publish', 'picu' ),
				'button_text_send_to_client' => __( 'Send to Client', 'picu' ),
				'selection_table_no_match' => __( 'No images found', 'picu' ),
			);

			$picu_localization_strings = apply_filters( 'picu_localization_strings', $picu_localization_strings );

			wp_localize_script( 'picu-admin', 'picu_admin', $picu_localization_strings );
		}

	}

}

add_action( 'admin_enqueue_scripts', 'picu_admin_styles_scripts' );


/**
 * Add settings link in plugins overview
 *
 * @param array $actions An array of links
 * @since 1.0.0
 */
function picu_plugin_action_links( $actions ) {

	$action = sprintf( '<a href="%s">%s</a>', admin_url( 'admin.php?page=picu-settings' ), __( 'Settings', 'picu' ) );
	array_unshift( $actions, $action );

	return $actions;
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ) , 'picu_plugin_action_links', 10 );


/**
 * Custom capability to manage picu collections.
 *
 * Defaults to administrator privileges, can be filtered using 'picu_capability'.
 *
 * @since 1.1.0
 */
function picu_capability() {
	$picu_capability = 'manage_options';
	$picu_capability = apply_filters( 'picu_capability', $picu_capability );

	return $picu_capability;
}


/**
 * Redirect /picu to collection overview in WordPress admin
 *
 * @since 1.2.1
 */
function picu_redirect_to_overview() {
	if ( $_SERVER['REQUEST_URI'] == '/picu' OR $_SERVER['REQUEST_URI'] == '/picu/' ) {
		wp_redirect( admin_url() . 'edit.php?post_type=picu_collection' );
		exit;
	}
}

add_action( 'init', 'picu_redirect_to_overview' );


/**
 * Check picu Pro compatibility
 *
 * @since 2.0.0
 */
function picu_check_pro_compat() {
	if ( defined( 'PICU_PRO' ) && version_compare( PICU_PRO, '1.4.5' ) < 0 ) {
		/* translators: Admin notice, %s = opening and closing link tags */
		$notice = sprintf ( __( '🚨 <strong>Action required:</strong> The version of picu Pro you are using is not compatible with this version of picu. %sPlease update to the latest version of picu Pro%s.', 'picu' ), '<a href="' . admin_url( 'plugins.php?s=picu%20pro&plugin_status=all' ) . '">', '</a>' );
		$notice_type = 'error';

		// Display admin notice
		add_action( 'admin_notices', function() use ( $notice, $notice_type ) {
			?>
			<div class="picu-pro-module-notice notice notice-<?php echo $notice_type; ?>">
				<p><?php echo $notice; ?></p>
			</div>
			<?php
		});
	}
}

add_action( 'init', 'picu_check_pro_compat' );


/**
 * Add picu Pro upgrade box
 * 
 * @since 1.3.1
 */
function picu_add_pro_metabox() {
	if ( ! picu_is_pro_license_valid() ) {

		add_meta_box(
			'picu-pro-metabox',
			__( 'Get picu pro', 'picu' ),
			'picu_display_pro_metabox',
			'picu_collection',
			'side',
			'high'
		);
	}
}

add_action( 'add_meta_boxes', 'picu_add_pro_metabox' );


/**
 * Render the Pro metabox.
 *
 * @since 1.3.1
 * @since 2.0.0 New box design and content
 */
function picu_display_pro_metabox() {
?>
	<div class="picu-pro-meta-box">
		<h2 class="picu-pro-meta-box__title"><?php echo __( 'Upgrade Your Proofing Workflow', 'picu' ); ?></h2>
		<div class="picu-pro-meta-box__content">
			<ul>
				<li><?php _e( 'Add personal branding', 'picu' ); ?></li>
				<li><?php _e( 'Send collections to multiple clients', 'picu' ); ?></li>
				<li><?php _e( 'Enable markers and comments on individual images', 'picu' ); ?></li>
				<li><?php _e( 'Many more professional features', 'picu' ); ?></li>
			</ul>
			<p class="picu-pro-meta-box__button-wrap"><a class="button button-primary picu-pro__button" href="https://go.picu.io/get-picu-pro" target="_blank"><?php _e( 'Get picu Pro', 'picu' ); ?> <svg style="transform: translateY(3px);"  xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#007791" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h13M12 5l7 7-7 7"/></svg></a></p>
			<div class="picu-pro-meta-box__reviews">
				<p>
					<svg width="16" height="16" viewBox="0 0 16 15" xmlns="http://www.w3.org/2000/svg"><path d="m6.75365082.46448007c.30569615-.61930676 1.1888161-.61930676 1.49451225 0l1.86507723 3.77985326 4.1748568.61109495c.6453032.09432073.9240557.84870204.5363234 1.33810964l-.0754029.08342717-3.0197773 2.94036824.7130072 4.15513057c.1106198.6449702-.5249381 1.144844-1.1119645.922308l-.0972521-.0438773-3.73279062-1.9635613-3.73145727 1.9635613c-.57918124.3045856-1.25104449-.1453116-1.22088044-.7723782l.01166386-.1060525.71167385-4.15513057-3.018444-2.94036824c-.46718009-.45503341-.25023224-1.22945432.35090953-1.39822842l.11001096-.02330839 4.17352351-.61109495z" fill="#ffd700" transform="translate(.5 .5)"/></svg>
					<svg width="16" height="16" viewBox="0 0 16 15" xmlns="http://www.w3.org/2000/svg"><path d="m6.75365082.46448007c.30569615-.61930676 1.1888161-.61930676 1.49451225 0l1.86507723 3.77985326 4.1748568.61109495c.6453032.09432073.9240557.84870204.5363234 1.33810964l-.0754029.08342717-3.0197773 2.94036824.7130072 4.15513057c.1106198.6449702-.5249381 1.144844-1.1119645.922308l-.0972521-.0438773-3.73279062-1.9635613-3.73145727 1.9635613c-.57918124.3045856-1.25104449-.1453116-1.22088044-.7723782l.01166386-.1060525.71167385-4.15513057-3.018444-2.94036824c-.46718009-.45503341-.25023224-1.22945432.35090953-1.39822842l.11001096-.02330839 4.17352351-.61109495z" fill="#ffd700" transform="translate(.5 .5)"/></svg>
					<svg width="16" height="16" viewBox="0 0 16 15" xmlns="http://www.w3.org/2000/svg"><path d="m6.75365082.46448007c.30569615-.61930676 1.1888161-.61930676 1.49451225 0l1.86507723 3.77985326 4.1748568.61109495c.6453032.09432073.9240557.84870204.5363234 1.33810964l-.0754029.08342717-3.0197773 2.94036824.7130072 4.15513057c.1106198.6449702-.5249381 1.144844-1.1119645.922308l-.0972521-.0438773-3.73279062-1.9635613-3.73145727 1.9635613c-.57918124.3045856-1.25104449-.1453116-1.22088044-.7723782l.01166386-.1060525.71167385-4.15513057-3.018444-2.94036824c-.46718009-.45503341-.25023224-1.22945432.35090953-1.39822842l.11001096-.02330839 4.17352351-.61109495z" fill="#ffd700" transform="translate(.5 .5)"/></svg>
					<svg width="16" height="16" viewBox="0 0 16 15" xmlns="http://www.w3.org/2000/svg"><path d="m6.75365082.46448007c.30569615-.61930676 1.1888161-.61930676 1.49451225 0l1.86507723 3.77985326 4.1748568.61109495c.6453032.09432073.9240557.84870204.5363234 1.33810964l-.0754029.08342717-3.0197773 2.94036824.7130072 4.15513057c.1106198.6449702-.5249381 1.144844-1.1119645.922308l-.0972521-.0438773-3.73279062-1.9635613-3.73145727 1.9635613c-.57918124.3045856-1.25104449-.1453116-1.22088044-.7723782l.01166386-.1060525.71167385-4.15513057-3.018444-2.94036824c-.46718009-.45503341-.25023224-1.22945432.35090953-1.39822842l.11001096-.02330839 4.17352351-.61109495z" fill="#ffd700" transform="translate(.5 .5)"/></svg>
					<svg width="16" height="16" viewBox="0 0 16 15" xmlns="http://www.w3.org/2000/svg"><g fill="none" transform="translate(.5 .5)"><path d="m6.75365082.46448007c.30569615-.61930676 1.1888161-.61930676 1.49451225 0l1.86507723 3.77985326 4.1748568.61109495c.6453032.09432073.9240557.84870204.5363234 1.33810964l-.0754029.08342717-3.0197773 2.94036824.7130072 4.15513057c.1106198.6449702-.5249381 1.144844-1.1119645.922308l-.0972521-.0438773-3.73279062-1.9635613-3.73145727 1.9635613c-.57918124.3045856-1.25104449-.1453116-1.22088044-.7723782l.01166386-.1060525.71167385-4.15513057-3.018444-2.94036824c-.46718009-.45503341-.25023224-1.22945432.35090953-1.39822842l.11001096-.02330839 4.17352351-.61109495z" fill="#fffdf0"/><path d="m7.50024028.00000364v12.28732966l-3.73145727 1.9635613c-.57918124.3045856-1.25104449-.1453116-1.22088044-.7723782l.01166386-.1060525.71167385-4.15513057-3.018444-2.94036824c-.46718009-.45503341-.25023224-1.22945432.35090953-1.39822842l.11001096-.02330839 4.17352351-.61109495 1.86641054-3.77985326c.13336744-.27018775.37733399-.42249948.63539651-.45693517z" fill="#ffd700"/></g></svg>
					<span class="picu-pro-meta-box__rating">4.7 / 5</span>
				</p>
				<p><a class="picu-pro-meta-box__link" href="https://go.picu.io/user-reviews" target="_blank"><?php _e( 'Check out picu user reviews', 'picu' ); ?></a></p>
			</div>
		</div>
	</div>
<?php
}


/**
 * Hide admin bar for now
 */
add_filter( 'picu_show_admin_bar', '__return_false' );


/**
 * Check if collection slug has changed
 * 
 * @since 1.5.0
 */
function picu_check_collection_slug() {

	// Only run if collection or 404
	if ( 'picu_collection' != get_post_type() AND ! is_404() ) {
		return;
	}

	// Only run, if pretty permalinks are active
	if ( ! get_option( 'permalink_structure' ) ) {
		return;
	}

	// Get current collection slug
	$post_type = get_post_type_object( 'picu_collection' );
	$post_type_slug = $post_type->rewrite['slug'];

	// Get saved slug. Set, if empty
	$saved_slug = get_transient( 'picu_collection_slug' );

	if ( empty( $saved_slug ) ) {
		set_transient( 'picu_collection_slug', $post_type_slug, 0 );
		return;
	}
	
	// Compare current with saved collection slug
	if ( $post_type_slug != $saved_slug ) {
		$old_picu_slugs = get_transient( 'picu_collection_old_slugs' );
		$old_picu_slugs[] = $saved_slug;
		set_transient( 'picu_collection_old_slugs', array_unique( $old_picu_slugs ) );
		set_transient( 'picu_collection_slug', $post_type_slug, 0 );
		flush_rewrite_rules( false );
	}
}

add_action( 'wp', 'picu_check_collection_slug' );


/**
 * Redirect when old collection base slug is used
 * 
 * @since 1.5.0
 */
function picu_redirect_from_old_slug() {

	// Only run, if this is a 404
	if ( ! is_404() ) {
		return;
	}

	// Only run, if pretty permalinks are active
	if ( ! get_option( 'permalink_structure' ) ) {
		return;
	}

	// Check if the old slug transient is set
	$old_picu_slugs = get_transient( 'picu_collection_old_slugs' );

	if ( empty( $old_picu_slugs ) ) {
		return;
	}

	// Get current url & path
	global $wp;
	$current_url = home_url( $wp->request );
	$url = parse_url( $current_url );
	$path = '';
	if ( ! empty( $url['path'] ) ) {
		$path = explode( '/', $url['path'] );
	}

	// Get current picu collection slug
	$post_type_object = get_post_type_object( 'picu_collection' );
	$current_slug = $post_type_object->rewrite['slug'];

	if ( empty( $path[1] ) OR ! in_array( $path[1], $old_picu_slugs ) OR $path[1] == $current_slug ) {
		return;
	}
	
	// Replace old slug with the new one
	$new_url = $url['scheme'] . '://' . $url['host'] . '/' . $current_slug . '/' . $path[2] . '/';

	// Get post type by url for the collection
	$post_type = get_post_type( url_to_postid( $new_url ) );

	// Redirect if the url is actually a collection
	if ( 'picu_collection' == $post_type ) {
		wp_redirect( trailingslashit( $new_url ), 301 );
	}
}

add_filter( 'wp', 'picu_redirect_from_old_slug' );


/**
 * Initialize proof file download
 * 
 * @since 1.5.0
 */
function picu_trigger_proof_file_download() {
	if ( ! empty( $_REQUEST['picu-download'] ) AND $_REQUEST['picu-download'] == 'picu-proof-file' ) {
		picu_create_proof_file( $_REQUEST['post'] );
		exit;
	}
}

add_action( 'init', 'picu_trigger_proof_file_download' );


/**
 * Display Pro hint between image upload and sharing options
 * 
 * @since 1.6.0
 */
function picu_display_pro_hint() {
	if ( ! picu_is_pro_active() ) {

		$pro_hints = [
			[
				/* translators: Opening and closing link tags */
				'text' => sprintf( __( '<strong>Get precise feedback</strong> &ndash; Learn %show to enable comments & markers%s on individual images.', 'picu' ), ' <a href="https://go.picu.io/get-feedback" target="_blank">', '</a>' ),
				'icon' => '🖍️'
			],
			[
				/* translators: Opening and closing link tags */
				'text' => sprintf( __( '<strong>Dealing with a lot of images?</strong> Learn %show to upload via FTP and import from your web server%s.', 'picu' ), '<a href="https://go.picu.io/lots-of-images" target="_blank">', '</a>' ),
				'icon' => '😱'
			],
			[
				/* translators: Opening and closing link tags */
				'text' => sprintf( __( '<strong>Protect your images</strong> &ndash; Learn %show to automatically add a watermark%s to your images.', 'picu' ), ' <a href="https://go.picu.io/protect-your-images" target="_blank">', '</a>' ),
				'icon' => '🔒'
			],
			[
				/* translators: Opening and closing link tags */
				'text' => sprintf( __( '<strong>Need more control?</strong> Learn %show to define the number of images%s your client needs to select.', 'picu' ), ' <a href="https://go.picu.io/more-control" target="_blank">', '</a>' ),
				'icon' => '🎚️'
			],
			[
				/* translators: Opening and closing link tags */
				'text' => sprintf( __( '<strong>Allow image downloads?</strong> Learn %show to enable image downloads%s for your collections.', 'picu' ), ' <a href="https://go.picu.io/allow-image-downloads" target="_blank">', '</a>' ),
				'icon' => '⬇️'
			],
			[
				/* translators: Opening and closing link tags */
				'text' => sprintf( __( '<strong>Done with post processing?</strong> Learn %show to deliver your final images%s to your clients.', 'picu' ), ' <a href="https://go.picu.io/done-post-processing" target="_blank">', '</a>' ),
				'icon' => '✅'
			]
		];

		// Randomize which pro hint to display
		$display_pro_hint = rand( 1, count( $pro_hints ) ) - 1;
	?>
	<div class="picu-pro-hint">
		<div class="picu-pro-hint-inner">
			<div class="picu-pro-hint-content">
				<span class="picu-pro-hint-icon"><?php echo $pro_hints[$display_pro_hint]['icon']; ?></span>
				<?php echo $pro_hints[$display_pro_hint]['text']; ?>
			</div>
			<div class="picu-pro-hint__badge">Pro</div>

		</div>
	</div>
	<?php
	}
}


/**
 * Determine if Pro is active and license is valid.
 *
 * @since 1.6.0
 * @since 1.9.0 Use function from Pro plugin, also checking license status
 * @since 2.0.1 No longer checking license status
 *
 * @return bool Whether Pro is active or not
 */
function picu_is_pro_active() {
	if ( is_plugin_active( 'picu-pro/picu-pro.php' ) ) {
		return true;
	}

	return false;
}


/**
 * Determine if there is a valid Pro license.
 *
 * @since 2.0.1
 *
 * @return bool Whether there is an active Pro license
 */
function picu_is_pro_license_valid() {
	$valid = false;

	if ( function_exists( 'picu_pro_get_license_status' ) ) {
		$license_status = picu_pro_get_license_status();
		if ( $license_status == 'valid' ) {
			$valid = true;
		}
	}

	return $valid;
}


/**
 * Never use "private" post status.
 *
 * @since 2.3.3
 *
 * @param array $data An array of slashed, sanitized, and processed post data
 * @return array Filtered post data
 */
function picu_remove_post_status_private( $data ) {
	if ( $data['post_type'] == 'picu_collection' && $data['post_status'] == 'private' ) {
		$data['post_status'] = 'approved';
	}

	return $data;
}

add_action( 'wp_insert_post_data', 'picu_remove_post_status_private', 10 );