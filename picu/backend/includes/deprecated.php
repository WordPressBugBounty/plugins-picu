<?php
/**
 * Deprecated functions
 *
 * @since 1.6.0
 */
defined( 'ABSPATH' ) || exit;


/**
 * Deprecated `picu_mail_subject` filter.
 *
 * @since 2.0.0
 */
function picu_mail_subject_deprecated( $subject, $mail_context, $post_id ) {
	return apply_filters_deprecated( 'picu_mail_subject', array( $subject, $mail_context, $post_id ), '2.0.0', 'picu_email_subject' );
}

add_filter( 'picu_mail_subject', 'picu_mail_subject_deprecated', 0, 3 );


/**
 * Deprecate `picu_email_from` filter
 *
 * @since 2.0.0
 */
function picu_email_from_deprecated( $from ) {
	if ( ! empty( $from['from_name'] ) ) {
		$from['from_name'] = apply_filters_deprecated( 'picu_email_from', $from['from_name'], '2.0.0', 'picu_email_from_name' );
	}

	if ( ! empty( $from['from_address'] ) ) {
		$from['from_address'] = apply_filters_deprecated( 'picu_email_from', $from['from_address'], '2.0.0', 'picu_email_from_address' );
	}

	return $from;
}

add_filter( 'picu_email_from', 'picu_email_from_deprecated', 0 );


/**
 * Display notice about switchung to the new Pro plugin.
 *
 * @since 1.6.4
 */
function picu_old_pro_notice() {
	// Check if any of the old Pro modules are active
	$picu_addons = [];
	$picu_addons = apply_filters( 'picu_addons', $picu_addons );
	if ( ! is_array( $picu_addons ) OR count( $picu_addons ) <= 0 ) {
		return;
	}

	/* translators: Admin notice */
	$notice = __( '🚨 <strong>Action required:</strong> The picu Pro modules you are using are not compatible with this version of picu. Please update to the latest version of picu Pro.', 'picu' ) . ' <a href="https://picu.io/docs/picu-2-update/">' . __( 'Learn more…', 'picu' ) . '</a>';
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

add_action( 'init', 'picu_old_pro_notice' );