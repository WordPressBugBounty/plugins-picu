<?php
/**
 * Picu add-ons page
 *
 * @since 0.7.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


function picu_load_add_ons_page() {
?>
	<div class="picu-pro__head-wrapper">
		<span class="picu-pro__head-line">
			<img class="picu-pro__head-logo" src="<?php echo PICU_URL; ?>/backend/images/picu_logo_dark_w_o_text.png" />
			<?php _e( 'picu Pro', 'picu' ); ?>
		</span>
	</div>
	<div class="wrap pro-page__wrap">
		<h2 style="display: none;"><!-- h2 headline is necessary for WordPress to properly position admin notices --></h2>
		<div class="pro-page__wrap-inner">
			<section class="pro-page__header">
				<img class="pro-page__header-screenshot" src="<?php echo PICU_URL; ?>/backend/images/macbook-naxos-sunset.png" />
				<div class="pro-page__header-inner">
					<span class="pro-page__current-user-badge"><?php _e( 'You are already using the free version of picu', 'picu' ); ?></span>
					<h1><?php _e( 'Take Your Studio to the Next Level', 'picu' ); ?></h1>
					<p><?php _e( 'Professional features to transform your client workflow and grow your photography business', 'picu' ); ?></p>
				</div>
			</section>
		</div>

		<section class="pro-page__comparison">
			<div class="pro-page__comparison-grid">
				<div class="pro-page__comparison-problem-side">
					<h3><?php _e( 'Are you still struggling with...', 'picu' ); ?></h3>
					<ul>
						<li><?php _e( 'Sending clients to Dropbox or Google Drive', 'picu' ); ?></li>
						<li><?php _e( 'Vague feedback like "I like the third one"', 'picu' ); ?></li>
						<li><?php _e( 'Generic galleries that do not match your brand', 'picu' ); ?></li>
						<li><?php _e( 'Endless email chains trying to understand client needs', 'picu' ); ?></li>
						<li><?php _e( 'Manually uploading hundreds of images', 'picu' ); ?></li>
						<li><?php _e( 'Clients confused about how many photos to select', 'picu' ); ?></li>
					</ul>
				</div>
				<div class="pro_page__comparison-solution-side">
					<h3><?php _e( 'picu Pro solves this with...', 'picu' ); ?></h3>
					<ul>
						<li><?php _e( 'Branded galleries that keep clients on YOUR website', 'picu' ); ?></li>
						<li><?php _e( 'Pin-point comments directly on specific images', 'picu' ); ?></li>
						<li><?php _e( 'Custom logo, colors, and fonts matching your brand', 'picu' ); ?></li>
						<li><?php _e( 'Clear, organized feedback attached to each photo', 'picu' ); ?></li>
						<li><?php _e( 'Bulk import directly from your web server', 'picu' ); ?></li>
						<li><?php _e( 'Selection goals that guide client choices', 'picu' ); ?></li>
					</ul>
				</div>
			</div>
		</section>

		<section class="pro-page__features">
			<div class="pro-page__features__inner">
				<div class="pro-page__section-header">
					<h2><?php _e( 'Professional Features That Make the Difference', 'picu' ); ?></h2>
					<p><?php _e( 'Everything you need to run a professional photography business', 'picu' ); ?></p>
				</div>
				
				<div class="pro-page__features-grid">
					<div class="pro-page__feature">
						<div class="pro-page__feature-icon"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 10a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M6 4v4" /><path d="M6 12v8" /><path d="M10 16a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M12 4v10" /><path d="M12 18v2" /><path d="M16 7a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M18 4v1" /><path d="M18 9v11" /></svg></div>
						<h3><?php _e( 'Custom Branding', 'picu' ); ?></h3>
						<p><?php _e( 'Add your logo, choose your colors, and use custom fonts. Keep clients engaged with a professional, branded experience that reinforces your business identity.', 'picu' ); ?></p>
					</div>

					<div class="pro-page__feature">
						<div class="pro-page__feature-icon"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 5m0 3a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v8a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3z" /><path d="M3 10l18 0" /><path d="M7 15l.01 0" /><path d="M11 15l2 0" /></svg></div>
						<h3><?php _e( 'Sell Images', 'picu' ); ?></h3>
						<p><?php _e( 'Take your business to the next level by selling images right from your proofing galleries, and accept payments via Stripe or PayPal.', 'picu' ); ?></p>
					</div>
					
					<div class="pro-page__feature">
						<div class="pro-page__feature-icon"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 9h8" /><path d="M8 13h6" /><path d="M18 4a3 3 0 0 1 3 3v8a3 3 0 0 1 -3 3h-5l-5 3v-3h-2a3 3 0 0 1 -3 -3v-8a3 3 0 0 1 3 -3h12z" /></svg></div>
						<h3><?php _e( 'Direct Feedback', 'picu' ); ?></h3>
						<p><?php _e( 'Clients can add comments and markers directly on images, so you know exactly what they were thinking and get the feedback where it is most valuable to you.', 'picu' ); ?></p>
					</div>
					
					<div class="pro-page__feature">
						<div class="pro-page__feature-icon"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 13a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M8 21v-1a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v1" /><path d="M15 5a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M17 10h2a2 2 0 0 1 2 2v1" /><path d="M5 5a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M3 13v-1a2 2 0 0 1 2 -2h2" /></svg></div>
						<h3><?php _e( 'Multi-Client Support', 'picu' ); ?></h3>
						<p><?php _e( 'Send galleries to multiple clients simultaneously and receive individual selections from each. Perfect for wedding parties, corporate teams, or family groups.', 'picu' ); ?></p>
					</div>
					
					<div class="pro-page__feature">
						<div class="pro-page__feature-icon"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /><path d="M12 7a5 5 0 1 0 5 5" /><path d="M13 3.055a9 9 0 1 0 7.941 7.945" /><path d="M15 6v3h3l3 -3h-3v-3z" /><path d="M15 9l-3 3" /></svg></div>
						<h3><?php _e( 'Selection Goals', 'picu' ); ?></h3>
						<p><?php _e( 'Set minimum or maximum selection limits to guide your clients. No more confusion about how many photos to choose – they will know exactly what you need.', 'picu' ); ?></p>
					</div>
					
					<div class="pro-page__feature">
						<div class="pro-page__feature-icon"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 21l-8 -4.5v-9l8 -4.5l8 4.5v4.5" /><path d="M12 12l8 -4.5" /><path d="M12 12v9" /><path d="M12 12l-8 -4.5" /><path d="M15 18h7" /><path d="M19 15l3 3l-3 3" /></svg></div>
						<h3><?php _e( 'Final Delivery', 'picu' ); ?></h3>
						<p><?php _e( 'Complete the workflow by delivering final edited images through the same professional platform. Your clients get a seamless experience from proof to delivery.', 'picu' ); ?></p>
					</div>
					
					<div class="pro-page__feature">
						<div class="pro-page__feature-icon"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M5 13v-8a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2h-5.5m-9.5 -2h7m-3 -3l3 3l-3 3" /></svg></div>
						<h3><?php _e( 'Bulk Import', 'picu' ); ?></h3>
						<p><?php _e( 'Import hundreds of images directly from your web server. Skip the time-consuming manual uploads and get your galleries online faster than ever.', 'picu' ); ?></p>
					</div>
					
					<div class="pro-page__feature">
						<div class="pro-page__feature-icon"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M11.46 20.846a12 12 0 0 1 -7.96 -14.846a12 12 0 0 0 8.5 -3a12 12 0 0 0 8.5 3a12 12 0 0 1 -.09 7.06" /><path d="M15 19l2 2l4 -4" /></svg></div>
						<h3><?php _e( 'Image Protection', 'picu' ); ?></h3>
						<p><?php _e( 'Protect your work with watermarks and theft protection features. Keep your images secure during the proofing process.', 'picu' ); ?></p>
					</div>
					
					<div class="pro-page__feature">
						<div class="pro-page__feature-icon"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"  stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 17.75l-6.172 3.245l1.179 -6.873l-5 -4.867l6.9 -1l3.086 -6.253l3.086 6.253l6.9 1l-5 4.867l1.179 6.873z" /></svg></div>
						<h3><?php _e( 'Star Rating & Filtering', 'picu' ); ?></h3>
						<p><?php _e( 'Let clients rate images with stars and filter their favorites. Makes the selection process more intuitive and organized for complex galleries.', 'picu' ); ?></p>
					</div>
				</div>
			</div>
			<div class="pro-page__cta">
				<a class="picu-pro__cta-button" href="https://go.picu.io/all-pro-features" target="_blank"><?php _e( 'All Pro Features', 'picu' ); ?></a>
			</div>
		</section>

		<section class="pro-page__social-proof">
			<div class="pro-page__social-proof__inner">
				<div class="pro-page__section-header">
					<h2><?php _e( 'Join 2,000+ Professional Photographers', 'picu' ); ?></h2>
					<p><?php _e( 'See why photographers worldwide trust picu Pro for their business', 'picu' ); ?></p>
				</div>
				
				<div class="picu-pro__testimonials">
					<blockquote class="picu-pro__testimonial">
						<p class="picu-pro__testimonial-text">It changed the relationship with my clients. I’ve been using the plugin since 2018 and I recommend it.</p>
						<cite class="picu-pro__testimonial-author">Ivo Tavares<br>
							<em>Aveiro, Portugal</em>
						</cite>
					</blockquote>
					<blockquote class="picu-pro__testimonial">
						<p class="picu-pro__testimonial-text">It's truly transformed my business and saved me countless hours.</p>
						<cite class="picu-pro__testimonial-author">David G.<br>
							<em>Washington DC, USA</em>
						</cite>
					</blockquote>
					<blockquote class="picu-pro__testimonial">
						<p class="picu-pro__testimonial-text">The best plugin for a photographer's proofing process. Having the pro version is worth every penny, their support is on par with the quality of this plugin.</p>
						<cite class="picu-pro__testimonial-author">goody317<br>
							<em>Santander, Colombia</em>
						</cite>
					</blockquote>
				</div>
			</div>
		</section>

		<section class="picu-pro__pricing-section">
			<div class="picu-pro__pricing-section__inner">
				<h2><?php _e( 'Ready to Go Professional?', 'picu' ); ?></h2>
				<p><?php _e( 'Everything you need to level up your photography business', 'picu' ); ?></p>
				
				<div class="picu-pro__pricing-card">
					<ul class="picu-pro__pricing-card-checklist">
						<li><span><?php _e( 'All Pro features', 'picu' ); ?></span></li>
						<li><span><?php _e( 'Professional Support', 'picu' ); ?></span></li>
						<li><span><?php _e( '14-day money-back guarantee', 'picu' ); ?></span></li>
					</ul>
					<a class="picu-pro__cta-button" href="https://go.picu.io/pro-pricing-plans" target="_blank"><?php _e( 'Upgrade to Pro', 'picu' ); ?></a>
				</div>
			</div>
		</section>

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
