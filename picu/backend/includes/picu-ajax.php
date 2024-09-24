<?php
/**
 * Save selection from the client as post meta data
 * This function will be called from the front end (client view)
 *
 * @since 0.4.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
 * Save/approve a collection for a client.
 *
 * Save the selection, triggered via AJAX. Maybe set status of a client to approved, if all requirements are met.
 *
 * @since 0.4.0
 * @since 2.3.0 The collection status will no longer change, ident is required for saving
 */
function picu_send_selection() {
	// Nonce check!
	if ( ! check_ajax_referer( 'picu-ajax-security', 'security', false ) ) {
		picu_send_json( 'error', __( '<strong>Error:</strong> Nonce check failed.<br />Refresh your browser window.', 'picu' ) );
	}

	// Sanitize and validate post id
	$postid = sanitize_key( $_POST['postid'] );
	// Does this collection exist?
	if ( ! is_string( get_post_status( $postid ) ) ) {
		picu_send_json( 'error', __( 'Error: Post id is not set.', 'picu' ) );
	}
	
	// Post status needs to be 'publish' or 'sent'. In all other cases, a selection may not be saved
	if ( get_post_status( $postid ) == 'draft' ) {
		picu_send_json( 'error', __( 'This collection is still a draft. You can save it, once it is open for selections.', 'picu' ) );
	}

	if ( ! in_array( get_post_status( $postid ), [ 'publish', 'sent' ] ) ) {
		picu_send_json( 'error', __( 'Error: Collection is closed.', 'picu' ) );
	}

	// Allow for some additional validation
	do_action( 'picu_send_selection_validation', $_POST );

	// Check if ident parameter is set and if it exists in our collection
	if ( empty( $_POST['ident'] ) || ! picu_ident_exists( sanitize_key( $_POST['ident'] ), $postid ) ) {
		picu_send_json( 'error', __( 'Error: You are not authorized to change the selection.', 'picu' ) );
	}

	$ident = sanitize_key( $_POST['ident'] );

	// Sanitize selection
	if ( ! empty( $_POST['selection'] ) ) {
		$temp_selection = $_POST['selection'];
		$selection = array();
		foreach ( $temp_selection as $id ) {
			// Ids must be integer values, handing them over as strings
			$selection[] = strval( intval( $id ) );
		}
	}
	else {
		$selection = false;
	}

	// Sanitize markers
	if ( ! empty( $_POST['markers'] ) ) {
		$markers = $_POST['markers'];

		function picu_ajax_sanitize_comment( &$item, $key ) {
			if ( $key == 'comment' ) {
				$item = sanitize_text_field( $item );
			}
		}

		array_walk_recursive( $markers, 'picu_ajax_sanitize_comment' );
	}
	else {
		$markers = '';
	}

	// Prepare array, that is saved as post meta
	$save = array(
		'selection' => $selection,
		'time' => time(),
		'markers' => $markers
	);

	// Save approval message
	if ( ! empty( $_POST['approval_fields']['picu_approval_message']['value'] ) ) {
		$save['approval_fields']['picu_approval_message']['value'] = trim( implode( "\n", array_map( 'sanitize_text_field', explode( "\n", stripslashes( $_POST['approval_fields']['picu_approval_message']['value'] ) ) ) ) );
		$save['approval_fields']['picu_approval_message']['label'] = __( 'Message', 'picu' );
	}

	// Filter the $save array
	$save = apply_filters( 'picu_save_selection', $save, $_POST );

	// Construct approval message, add all of the approval fields
	$approval_message = '';
	if ( ! empty( $save['approval_fields'] ) ) {
		foreach( $save['approval_fields'] as $key => $value ) {
			if ( !empty( $value['value'] ) ) {
				$approval_message .= '<strong>' . $value['label'] ."</strong>\n";
				if ( ! empty( $value['title'] ) ) {
					$approval_message .= $value['title'];
				}
				else {
					$approval_message .= $value['value'];
				}
				$approval_message .= "\n\n";
			}
		}
	}

	// Save selection, send result back to the user
	$previous_save = get_post_meta( $postid, '_picu_collection_selection_' . $ident, true );
	$response = update_post_meta( $postid, '_picu_collection_selection_' . $ident, $save );

	// It worked
	if ( $response >= 1 ) {
		// Approve selection for client and send approval mail to photographer
		if ( is_array( $selection ) AND count( $selection ) > 0 AND isset( $_POST['intent'] ) AND $_POST['intent'] == 'approve' ) {
			$collection_hashes = get_post_meta( $postid, '_picu_collection_hashes', true );
			$collection_hashes[$ident]['status'] = 'approved';
			$collection_hashes[$ident]['time'] = time();
			
			update_post_meta( $postid, '_picu_collection_hashes', $collection_hashes );

			picu_update_collection_history( $postid, 'approved-by-client', picu_combine_name_email( $collection_hashes[$ident]['name'], $collection_hashes[$ident]['email'] ), [ 'approval_message' => $approval_message ] );

			// Add approval message to email args:
			$args = [];
			$args['approval_message'] = $approval_message;

			// Send email notification to the photographer
			picu_mail_approval( $postid, $ident, $args );

			do_action( 'picu_after_approval', $postid, $ident );

			picu_send_json( 'success', __( 'You successfully approved this collection.', 'picu' ) );
		}
		// Return error if intent was to approve but no images was selected
		elseif ( is_array( $selection ) AND count( $selection ) <= 0 AND isset( $_POST['intent'] ) AND $_POST['intent'] == 'approve' ) {
			picu_send_json( 'error', __( 'Please select at least one image.', 'picu' ) );

		}
		// Regular "saved was a success" message
		else {
			do_action( 'picu_after_saving_selection', $save, $previous_save, $postid, $ident );
			picu_send_json( 'success', __( 'Your selection was saved.', 'picu' ) );
		}

	}
	// It didn't work
	else {
		picu_send_json( 'error', __( 'Error. Your selection could not be saved.', 'picu' ) );
	}

	exit;
}

add_action( 'wp_ajax_picu_send_selection', 'picu_send_selection' );
add_action( 'wp_ajax_nopriv_picu_send_selection', 'picu_send_selection' );


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


/**
 * Sends a JSON response
 * 
 * @param string $type Whether to send a 'success' or an 'error'
 * @param string $message (optional) Message to be sent back with the JSON data, default: 'An error occured'
 * @param string $button_text (optional) Text for the button we use in the error message, default: 'OK'
 * 
 * @since 1.6.5
 */
function picu_send_json( $type, $message = null, $button_text = null ) {

	if ( ! isset ( $message ) ) {
		if ( $type == 'success' ) {
			$message = __( 'Success', 'picu' );
		}
		else {
			$message = __( 'An error occured', 'picu' );
		}
	}

	if ( ! isset ( $button_text ) ) {
		$button_text = __( 'OK', 'picu' );
	}

	$return = array(
		'message' => $message,
		'button_text' => $button_text
	);

	if ( $type == 'success' ) {
		wp_send_json_success( $return );
	}
	elseif ( $type == 'error' ) {
		wp_send_json_error( $return );
	}
	exit;

}