<?php
/**
 * picu welcome screen
 *
 * @since 0.7.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
 * Redirect to the picu welcome screen
 *
 * @since 0.5.0
 */
function picu_welcome_screen_activation_redirect() {

	// Only redirect if transient is set
	if ( ! get_transient( '_picu_welcome_screen_activation_redirect' ) ) {
		return;
	}

	// Delete the redirect transient
	delete_transient( '_picu_welcome_screen_activation_redirect' );

	// Don't redirect if activating from network, or bulk
	if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
		return;
	}

	// Redirect to bbPress about page
	wp_safe_redirect( add_query_arg( array( 'page' => 'picu-welcome-screen' ), admin_url( 'index.php' ) ) );

}

add_action( 'admin_init', 'picu_welcome_screen_activation_redirect' );


/**
 * Add welcome screen as dashboard page
 *
 * @since 0.7.0
 */
function picu_welcome_screen_page() {

	add_dashboard_page(
		'picu Welcome Screen',
		'picu Welcome Screen',
		'read',
		'picu-welcome-screen',
		'welcome_screen_content'
	);
}

add_action('admin_menu', 'picu_welcome_screen_page');


/**
 * Display the welcome screen
 *
 * @since 0.7.0
 */
function welcome_screen_content() {
?>
<div class="wrap">
	<div class="picu-welcome">
		<h2 class="picu-welcome-subtitle"></h2>
		<img class="picu-logo" src="<?php echo PICU_URL; ?>/frontend/images/picu-logo-grey.svg" alt="picu" />
		<div class="row header-row">
			<div class="column col-50">
				<h1><?php _e( 'Greetings, Photographer!', 'picu' ); ?></h1>
				<p class="thanks"><?php _e( 'Thank you for installing picu.', 'picu' ); ?></p>
				<p><?php _e( 'We work very hard to make your experience and that of your clients as smooth as possible and we hope using picu will transform the way you work and interact with your photo clients.', 'picu' ); ?></p>
				<p><?php _e( 'If you have feedback of any kind, please get in touch.<br />We\'d love to hear from you.', 'picu' ); ?></p>
				<p><?php _e( 'To get started, follow the steps below.', 'picu' ); ?></p>
			</div>
			<div class="column col-50"><img src="<?php echo PICU_URL; ?>/backend/images/picu-browser.jpg" alt="picu collection" /></div>
		</div>

		<div class="row row-white">
			<div class="column col-33 get-started">
				<h2><?php _e( 'Get started', 'picu' ); ?></h2>
				<p><?php _e( 'We suggest to take a look at the settings first – no worries, we kept them pretty simple. Start by selecting one of two themes:', 'picu' ); ?></p>
				<p class="alt"><a class="button" href="<?php echo get_admin_url(); ?>admin.php?page=picu-settings"><?php _e( 'Select picu theme now', 'picu' ); ?></a></p>
				<p><?php _e( 'Already have images prepared that you want to send to a client? Start creating your first collection, no instructions needed.', 'picu' ); ?></p>
				<p><a class="button button-primary" href="<?php echo get_admin_url(); ?>post-new.php?post_type=picu_collection"><?php _e( 'Create a collection', 'picu' ); ?></a></p>
			</div>

			<div class="column col-33">
				<h2><?php _e( 'Need help?', 'picu' ); ?></h2>
				<ul>
					<li><?php /* translators: %s = opening and closing link tags */ echo sprintf( __( 'Please take a look at the %sdocumentation%s first.', 'picu' ), '<a href="https://picu.io/docs/">', '</a>' ); ?></li>
					<li><?php /* translators: %s = opening and closing link tags */ echo sprintf( __( 'If you can\'t find the answer to your question, please use the official WordPress.org %ssupport forum%s.', 'picu' ), '<a href="https://wordpress.org/support/plugin/picu">', '</a>' ); ?></li>
					<p><?php /* translators: %s = opening and closing link tags */ echo sprintf( __( 'Pro customers may contact us via our %ssupport page%s.', 'picu' ), '<a href="https://picu.io/support/">', '</a>' ); ?></p>
				</ul>
				<hr />
				<h2>Stay up-to-date</h2>
				<p><?php echo /* translators: %s = opening and closing link tags */ sprintf( __( 'To get the latest updates and be notified when we release new picu Pro features, %ssign up for our newsletter%s.', 'picu' ), '<a href="https://picu.io/#newsletter">', '</a>' ); ?></p>
			</div>
			<?php picu_display_pro_metabox(); ?>
		</div>
	</div>
</div>
<?php
}


/**
 * Remove the welcome screen from the menu
 *
 * @since 0.7.0
 */
function welcome_screen_remove_menus() {
	remove_submenu_page( 'index.php', 'picu-welcome-screen' );
}

add_action( 'admin_head', 'welcome_screen_remove_menus' );