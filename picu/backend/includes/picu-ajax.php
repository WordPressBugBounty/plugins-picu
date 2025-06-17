<?php
/**
 * Save Pro box and telemetry nag state.
 *
 * @since 0.4.0
 * @since 2.0.0 Moved frontend AJAX stuff to separate file: /frontend/includes/save-collection.php
 */
defined( 'ABSPATH' ) OR exit;


/**
 * Save visibility state of the picu pro meta box
 *
 * @since 1.3.1
 */
function picu_save_pro_box_state() {
	if ( ! check_ajax_referer( 'picu_ajax', 'security', false ) ) {
		picu_send_json( 'error', __( '<strong>Error:</strong> Nonce check failed.', 'picu' ) );
	}

	if ( isset( $_REQUEST['picu_hide_pro_box'] ) AND $_REQUEST['picu_hide_pro_box'] ==
'true' ) {
		set_transient( 'picu_pro_box_hidden_' . get_current_user_id(), true, YEAR_IN_SECONDS );
	}
	else {
		delete_transient( 'picu_pro_box_hidden_' . get_current_user_id() );
	}

	wp_send_json_success( $_REQUEST );

	exit;
}
add_action( 'wp_ajax_picu_save_pro_box_state', 'picu_save_pro_box_state' );


/**
 * Hide the telemetry nag notice for a while.
 *
 * @since 1.10.0
 */
function picu_save_telemetry_nag_state() {
	if ( ! check_ajax_referer( 'picu_ajax', 'security', false ) ) {
		picu_send_json( 'error', __( '<strong>Error:</strong> Nonce check failed.', 'picu' ) );
	}

	// Increase the telemetry nag
	$dismissed = get_option( 'picu_telemetry_nag', 0 ) + 1;
	update_option( 'picu_telemetry_nag', $dismissed, false );

	// Hide nag for one week
	set_transient( 'picu_telemetry_nag_' . get_current_user_id(), true, WEEK_IN_SECONDS );
	wp_send_json_success();
	exit;
}

add_action( 'wp_ajax_picu_save_telemetry_nag_state', 'picu_save_telemetry_nag_state' );