<?php
/**
 * Picu add-ons page
 *
 * @since 0.7.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


function picu_load_add_ons_page() {
?>
<div class="wrap picu-add-ons-wrap">

	<h1><?php _e ( 'picu Pro', 'picu' ); ?></h1>
	<hr class="wp-header-end" />

<?php

	// Load and display notifications
	$notifications = get_option( '_' . get_current_user_id() . '_picu_notifications' );

	if ( isset( $notifications ) AND is_array( $notifications ) ) {
		foreach( $notifications as $notification ) {
			echo '<div class="' . $notification['type'] . '"><p>' . $notification['message'] . '</p></div>';
		}

		// Delete notifications
		delete_option( '_' . get_current_user_id() . '_picu_notifications' );
	}

	// Load installed picu add-ons that need activation
	$picu_addons = array();
	$picu_addons = apply_filters( 'picu_addons', $picu_addons );

	// Load licensing information
	$licenses = get_option( 'picu_addon_licenses' );

?>

	<h2></h2>

	<div class="picu-tab-content js-picu-tab-content is-active" id="picu-add-ons-tab">
		<div class="picu-add-ons">


			<div class="picu-add-on-full">
				<div class="picu-add-on-inner js-picu-add-on-inner">
					<h2 class="picu-add-on-title"><a href="https://picu.io/pro/?utm_source=picu_plugin&utm_medium=pro_modules"><?php /* translators: Add-on page headline */ _e( 'Upgrade to picu Pro', 'picu' ); ?></a></h2>
					<div class="picu-pro-description">
						<p class="picu-add-on-subtitle"><?php /* translators: Text on add-on pages */ _e( 'Features for professionals.', 'picu' ); ?></p>
						<p><?php _e( 'Elevate your Online Proofing Workflow with picu Pro. Add your own branding, gather feedback with comments and markers on single images or add the possibility to download images from a collection.', 'picu' ); ?></p>
						<p style="text-align: center;">
							<a class="button button-primary" href="https://picu.io/pro/?utm_source=picu_plugin&utm_medium=pro_modules"><?php _e( 'Learn more', 'picu' ); ?></a>
						</p>
					</div>
				</div>
			</div><!-- .picu-add-on -->

			<div class="picu-add-on">
				<div class="picu-add-on-inner js-picu-add-on-inner">
					<h2><a href="https://picu.io/pro/multi-client-support/?utm_source=picu_plugin&utm_medium=pro_modules"><?php _e( 'Multi Client Support', 'picu' ); ?></a></h2>
					<a href="https://picu.io/pro/multi-client-support/?utm_source=picu_plugin&utm_medium=pro_modules"><img src="<?php echo PICU_URL; ?>/backend/images/add-ons/add-on-mutli-client-support-icon.png" alt="" /></a>
					<div class="picu-add-on-description">
						<p class="picu-add-on-subtitle"><?php _e( 'Send collections to multiple recipients', 'picu' ); ?></p>
						<p><?php _e( 'Send your collections to multiple recipients at once and receive individual selections from each of them.', 'picu' ); ?></p>
						<?php _e( '<ul>
							<li>Enter multiple email addresses</li>
							<li>See the status for each recipient</li>
							<li>Receive individual selections from each of the recipients</li>
						</ul>', 'picu' ); ?>
						<p style="text-align: center;">
							<a class="button" href="https://picu.io/pro/multi-client-support/?utm_source=picu_plugin&utm_medium=pro_modules"><?php _e( 'Learn more', 'picu' ); ?></a>
						</p>
					</div>
				</div>
			</div><!-- .picu-add-on -->

			<div class="picu-add-on">
				<div class="picu-add-on-inner js-picu-add-on-inner">
					<h2><a href="https://picu.io/pro/brand-and-customize/?utm_source=picu_plugin&utm_medium=pro_modules"><?php _e( 'Brand &amp; Customize', 'picu' ); ?></a></h2>
					<a href="https://picu.io/pro/brand-and-customize/?utm_source=picu_plugin&utm_medium=pro_modules"><img src="<?php echo PICU_URL; ?>/backend/images/add-ons/add-on-brand-customize-icon.png" alt="" /></a>
					<div class="picu-add-on-description">
						<p class="picu-add-on-subtitle"><?php _e( 'Make picu your own.', 'picu' ); ?></p>
						<p><?php _e( 'Your Branding is essential to your business. Add your own logo, adjust colors, and even use Google or Adobe (Typekit) Fonts to make picu your own.', 'picu' ); ?></p>
						<?php _e( '<ul>
							<li>Custom Logo</li>
							<li>Google & Adobe (Typekit) Fonts</li>
							<li>Adjust Layout</li>
							<li>Show/hide filenames</li>
						</ul>', 'picu' ); ?>
						<p style="text-align: center;">
							<a class="button" href="https://picu.io/pro/brand-and-customize/?utm_source=picu_plugin&utm_medium=pro_modules"><?php _e( 'Learn more', 'picu' ); ?></a>
						</p>
					</div>
				</div>
			</div><!-- .picu-add-on -->

			<div class="picu-add-on">
				<div class="picu-add-on-inner js-picu-add-on-inner">
					<h2><a href="https://picu.io/pro/mark-and-comment/?utm_source=picu_plugin&utm_medium=pro_modules"><?php _e( 'Mark &amp; Comment', 'picu' ); ?></a></h2>
					<a href="https://picu.io/pro/mark-and-comment/?utm_source=picu_plugin&utm_medium=pro_modules"><img src="<?php echo PICU_URL; ?>/backend/images/add-ons/add-on-mark-comment-icon.png" alt="" /></a>
					<div class="picu-add-on-description">
						<p class="picu-add-on-subtitle"><?php _e( 'Elevate your communication workflow and gather feedback like never before.', 'picu' ); ?></p>
						<p><?php _e( 'Sometimes, a selection is not enough and you want to gather detailed feedback and post-production instructions on individual images.', 'picu' ); ?></p>
						<?php _e( '<ul>
							<li>Commenting on individual images</li>
							<li>Mark certain areas of an image</li>
							<li>Get all client feedback in one place</li>
						</ul>', 'picu' ); ?>
						<p style="text-align: center;">
							<a class="button" href="https://picu.io/pro/mark-and-comment/?utm_source=picu_plugin&utm_medium=pro_modules"><?php _e( 'Learn more', 'picu' ); ?></a>
						</p>
					</div>
				</div>
			</div><!-- .picu-add-on -->

			<div class="picu-add-on">
				<div class="picu-add-on-inner js-picu-add-on-inner">
					<h2><a href="https://picu.io/pro/selection-options/?utm_source=picu_plugin&utm_medium=pro_modules"><?php _e( 'Selection Options', 'picu' ); ?></a></h2>
					<a href="https://picu.io/pro/selection-options/?utm_source=picu_plugin&utm_medium=pro_modules"><img src="<?php echo PICU_URL; ?>/backend/images/add-ons/add-on-selection-options-icon.png" alt="" /></a>
					<div class="picu-add-on-description">
						<p class="picu-add-on-subtitle"><?php _e( 'Set a selection goal.', 'picu' ); ?></p>
						<p><?php _e( 'Define a selection goal for your online proofing galleries, that your clients need to fulfill.', 'picu' ); ?></p>
						<?php _e( '<ul>
							<li>Custom selection goals</li>
							<li>Client notification with selection goals</li>
							<li>Enforce selection goals</li>
						</ul>', 'picu' ); ?>
						<p style="text-align: center;">
							<a class="button" href="https://picu.io/pro/selection-options/?utm_source=picu_plugin&utm_medium=pro_modules"><?php _e( 'Learn more', 'picu' ); ?></a>
						</p>
					</div>
				</div>
			</div><!-- .picu-add-on -->

			<div class="picu-add-on">
				<div class="picu-add-on-inner js-picu-add-on-inner">
					<h2><a href="https://picu.io/pro/download/?utm_source=picu_plugin&utm_medium=pro_modules"><?php _e( 'Download', 'picu' ); ?></a></h2>
					<a href="https://picu.io/pro/download/?utm_source=picu_plugin&utm_medium=pro_modules"><img src="<?php echo PICU_URL; ?>/backend/images/add-ons/add-on-download-icon.png" alt="" /></a>
					<div class="picu-add-on-description">
						<p class="picu-add-on-subtitle"><?php _e( 'Let your clients download images from a collection.', 'picu' ); ?></p>
						<p><?php _e( 'Enable to download a ZIP archive of all the images in a collection.', 'picu' ); ?></p>
						<?php _e( '<ul>
							<li>Enable ZIP Downloads per collection</li>
							<li>Comfortable Download Link</li>
						</ul>', 'picu' ); ?>
						<p style="text-align: center;">
							<a class="button" href="https://picu.io/pro/download/?utm_source=picu_plugin&utm_medium=pro_modules"><?php _e( 'Learn more', 'picu' ); ?></a>
						</p>
					</div>
				</div>
			</div><!-- .picu-add-on -->

			<div class="picu-add-on">
				<div class="picu-add-on-inner js-picu-add-on-inner">
					<h2><a href="https://picu.io/pro/import/?utm_source=picu_plugin&utm_medium=pro_modules"><?php _e( 'Import', 'picu' ); ?></a></h2>
					<a href="https://picu.io/pro/import/?utm_source=picu_plugin&utm_medium=pro_modules"><img src="<?php echo PICU_URL; ?>/backend/images/add-ons/add-on-import-icon.png" alt="" /></a>
					<div class="picu-add-on-description">
						<p class="picu-add-on-subtitle"><?php _e( 'Faster uploads for huge collections.', 'picu' ); ?></p>
						<p><?php _e( 'If you upload huge amounts of images, the default uploader can get a bit slow. Import lets you import images from a directory on your web server.', 'picu' ); ?></p>
						<?php _e( '<ul>
							<li>Direct Uploads to your webserver</li>
							<li>Choose directory to import</li>
							<li>Clean up after import</li>
						</ul>', 'picu' ); ?>
						<p style="text-align: center;">
							<a class="button" href="https://picu.io/pro/import/?utm_source=picu_plugin&utm_medium=pro_modules"><?php _e( 'Learn more', 'picu' ); ?></a>
						</p>
					</div>
				</div>
			</div><!-- .picu-add-on -->

			<div class="picu-add-on">
				<div class="picu-add-on-inner js-picu-add-on-inner">
					<h2><a href="https://picu.io/pro/delivery/?utm_source=picu_plugin&utm_medium=pro_modules"><?php _e( 'Delivery', 'picu' ); ?></a></h2>
					<a href="https://picu.io/pro/delivery/?utm_source=picu_plugin&utm_medium=pro_modules"><img src="<?php echo PICU_URL; ?>/backend/images/add-ons/add-on-delivery-icon.png" alt="" /></a>
					<div class="picu-add-on-description">
						<p class="picu-add-on-subtitle"><?php _e( 'Deliver final images to your clients', 'picu' ); ?></p>
						<p><?php _e( 'Deliver your final images either as a final step after the proofing process or by creating a separate delivery collection.', 'picu' ); ?></p>
						<?php _e( '<ul>
							<li>Upload images or specify an external link</li>
							<li>Single image or zip file download for your clients</li>
							<li>Keep track with the download history</li>
						</ul>', 'picu' ); ?>
						<p style="text-align: center;">
							<a class="button" href="https://picu.io/pro/delivery/?utm_source=picu_plugin&utm_medium=pro_modules"><?php _e( 'Learn more', 'picu' ); ?></a>
						</p>
					</div>
				</div>
			</div><!-- .picu-add-on -->

			<div class="picu-add-on">
				<div class="picu-add-on-inner js-picu-add-on-inner">
					<h2><a href="https://picu.io/pro/theft-protection/?utm_source=picu_plugin&utm_medium=pro_modules"><?php _e( 'Theft Protection', 'picu' ); ?></a></h2>
					<a href="https://picu.io/pro/theft-protection/?utm_source=picu_plugin&utm_medium=pro_modules"><img src="<?php echo PICU_URL; ?>/backend/images/add-ons/add-on-theft-protection-icon.png" alt="" /></a>
					<div class="picu-add-on-description">
						<p class="picu-add-on-subtitle"><?php _e( 'Protect your photographs from image theft.', 'picu' ); ?></p>
						<p><?php _e( 'There is nothing worse than seeing your work used without being payed. Theft Protection helps to prevent image theft from picu collections.', 'picu' ); ?></p>
						<ul>
							<li><?php _e( 'Watermarking', 'picu' ); ?></li>
							<li><?php _e( 'Disable Right Click', 'picu' ); ?></li>
						</ul>
						<p style="text-align: center;">
							<a class="button" href="https://picu.io/pro/theft-protection/?utm_source=picu_plugin&utm_medium=pro_modules"><?php _e( 'Learn more', 'picu' ); ?></a>
						</p>
					</div>
				</div>
			</div><!-- .picu-add-on -->

			<div class="picu-add-on">
				<div class="picu-add-on-inner idea js-picu-add-on-inner">
					<h3><?php _e( 'Ideas?', 'picu' ); ?></h3>
					<p><?php _e( 'Like to see a certain feature? We are always open to suggestions from our users. Tell us how we can improve your workflow.', 'picu' ); ?></p>
					<p><a class="button button-primary" href="https://picu.io/contact/?utm_source=picu_plugin&utm_medium=pro_modules"><?php _e( 'Contact us', 'picu' ); ?></a></p>
				</div>
			</div><!-- .picu-add-on -->

			<div class="picu-add-on">
				<div class="picu-add-on-inner idea js-picu-add-on-inner">
					<h3><?php _e( 'Stay up-to-date', 'picu' ); ?></h3>
					<p><?php _e( 'To get the latest updates and be notified when we release new picu Pro features, sign up for our newsletter.', 'picu' ); ?>
					<p><a class="button button-primary" href="https://picu.io/?utm_source=picu_plugin&utm_medium=pro_modules#newsletter"><?php _e( 'Subscribe', 'picu' ); ?></a></p>
				</div>
			</div><!-- .picu-add-on -->

		</div>
	</div>
</div><!-- .wrap -->

<?php }


/**
 * Change access depending on a predefined capability
 *
 * @since 1.1.0
 */
add_filter( 'option_page_capability_picu_addon_licenses', 'picu_capability' );


/**
 * Helper function to retrieve license info from picu.io
 *
 * @since 1.3.0
 */
function picu_check_license( $license_key, $add_on_name, $action ) {

	// Prepare request parameters
	$api_params = array(
		'edd_action'=> $action,
		'license' 	=> $license_key,
		'item_name' => urlencode( $add_on_name ),
		'url'       => home_url()
	);

	// Call our api
	$response = wp_remote_post( 'https://picu.io', array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

	// Make sure the response came back okay
	if ( is_wp_error( $response ) ) {
		return false;
	}

	// JSON decore the response
	$license_data = json_decode( wp_remote_retrieve_body( $response ) );

	// Save data into a transient, vaild for one week
	set_transient( str_replace( '__', '_', sanitize_key( str_replace( ' ', '_', $add_on_name ) ) ) . '_license_status', $license_data, WEEK_IN_SECONDS );

	return $response;

}


/**
 * Helper function to check the license status
 *
 * @since 1.3.0
 */
function picu_get_license_info( $license_key, $add_on_name ) {

	// Get infos from transient
	$license_status = get_transient( str_replace( '__', '_', sanitize_key( str_replace( ' ', '_', $add_on_name ) ) ) . '_license_status' );

	// If trasient doesn't exist, send request to picu.io
	if ( ! $license_status ) {
		$response = picu_check_license( $license_key, $add_on_name, 'check_license' );
		$license_status = json_decode( wp_remote_retrieve_body( $response ) );
	}

	return $license_status;
}
