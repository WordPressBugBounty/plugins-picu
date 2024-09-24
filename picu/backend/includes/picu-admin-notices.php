<?php
/**
 * Admin Notices and Error Messages
 *
 * @since 0.5.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
 * Add notification to be displayed on collection edit screen
 *
 * @since 0.9.6
 *
 * @param string $name		Name of the notification
 * @param string $type		Type of notification (use WordPress regular notification classes, eg. notice-error or notice-succes)
 * @param string $message	The message that should be displayed
 */
function picu_add_notification( $name, $type, $message ) {

	$notifications = get_option( '_' . get_current_user_id() . '_picu_notifications' );

	if ( ! is_array( $notifications ) ) {
		$notifications = [];
	}

	// Define notification type and message
	$notifications[$name] = array(
		'type' => $type,
		'message' => $message
	);

	// Store notification as an option
	update_option( '_' . get_current_user_id() . '_picu_notifications', $notifications, false );
}


/**
 * Check for errors when sending a collection to a client
 *
 * @since 0.5.0
 */
function picu_errors( $post_id, $post ) {

	// Abort if autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		return;

	// If picu notifications exist, set filter redirect to remove/add query args
	if ( get_option( '_' . get_current_user_id() . '_picu_notifications' ) ) {
		add_filter( 'redirect_post_location', 'picu_save_add_notification_arg' );
	}

	// Only check errors if we actually send the collection
	if ( ! isset( $_REQUEST['picu_sendmail'] ) ) {
		return;
	}

	// Check if title is missing. If so, add notification
	if ( ! $post->post_title ) {
		picu_add_notification( 'picu_title_missing', 'notice notice-error is-dismissible', __( 'Title is missing', 'picu' ) );
	}

	// Check if there are any images. If not, add notification
	if ( isset( $_REQUEST['picu_gallery_ids'] ) AND empty( $_REQUEST['picu_gallery_ids'] ) ) {
		picu_add_notification( 'picu_no_images', 'notice notice-error is-dismissible', __( 'No images in collection.', 'picu' ) );
	}

	// Check if there are any images. If not, add notification
	if ( empty( $_REQUEST['delivery_image_ids'] ) AND ! empty( $_REQUEST['picu_delivery_option'] ) AND $_REQUEST['picu_delivery_option'] == 'upload' ) {
		picu_add_notification( 'picu_no_images', 'notice notice-error is-dismissible', __( 'No images in delivery collection.', 'picu' ) );
	}	

	// Check which share method is selected, proceed from there
	if ( isset( $_POST['picu_collection_share_method'] ) AND 'picu-send-email' == $_POST['picu_collection_share_method'] ) {

		// Check if email address is missing. Add notification, if so
		if ( empty( $_REQUEST['picu_collection_email_address'] ) AND empty( $_REQUEST['picu_delivery_email_address'] ) ) {
			picu_add_notification( 'picu_email_missing', 'notice notice-error is-dismissible', __( 'Email address is missing.', 'picu' ) );
		}

		// Check if email address is actually an email address. If not add notification
		elseif ( isset( $_REQUEST['picu_collection_email_address'] ) ) {

			// Check if there are multiple mail addresses
			if ( strpos( $_POST['picu_collection_email_address'], ', ' ) ) {

				// Check if Pro
				if ( ! picu_is_pro_active() ) {
					/* translators: Opening and closing link tags */
					picu_add_notification( 'picu_multi_email_requires_pro', 'notice notice-error is-dismissible', sprintf( __( '%spicu Pro%s is required to send a collection to multiple clients.', 'picu' ), '<a href="https://picu.io/pro/" target="_blank">', '</a>' ) );
				}

				// Check them individually
				$email_addresses = explode( ', ', $_POST['picu_collection_email_address'] );

				if ( is_array( $email_addresses ) AND count( $email_addresses ) > 1 ) {
			
					$email_addresses = array_filter( $email_addresses, 'picu_validate_email_address' );

					if ( count( $email_addresses ) <= 1 ) {
						picu_add_notification( 'picu_email_invalid', 'notice notice-error is-dismissible', __( 'Invalid email address.', 'picu' ) );
					}
				}
			}
			elseif ( ! is_email( $_REQUEST['picu_collection_email_address'] ) ) {
				picu_add_notification( 'picu_email_invalid', 'notice notice-error is-dismissible', __( 'Invalid email address.', 'picu' ) );
			}
		}
		elseif ( isset( $_REQUEST['picu_delivery_email_address'] ) ) {

			// Check if there are multiple mail addresses
			if ( strpos( $_POST['picu_delivery_email_address'], ', ' ) ) {

				// Check them individually
				$email_addresses = explode( ', ', $_POST['picu_delivery_email_address'] );

				if ( is_array( $email_addresses ) AND count( $email_addresses ) > 1 ) {
			
					$email_addresses = array_filter( $email_addresses, 'picu_validate_email_address' );

					if ( count( $email_addresses ) <= 1 ) {
						picu_add_notification( 'picu_email_invalid', 'notice notice-error is-dismissible', __( 'Invalid email address.', 'picu' ) );
					}
				}
			}
			elseif ( ! is_email( $_REQUEST['picu_delivery_email_address'] ) ) {
				picu_add_notification( 'picu_email_invalid', 'notice notice-error is-dismissible', __( 'Invalid email address.', 'picu' ) );
			}
		}

		// Check if description is missing. If so, add notification
		if ( empty( $_REQUEST['picu_collection_description'] ) AND empty( $_REQUEST['picu_delivery_description'] ) ) {
			picu_add_notification( 'picu_description_missing', 'notice notice-error is-dismissible', __( 'Description is missing.', 'picu' ) );
		}
	}
}

add_action( 'save_post_picu_collection', 'picu_errors', 10, 2 );


/**
 * Check if any notifications are set
 *
 * @since 1.0.0
 */
function picu_should_notification_be_displayed() {
	// If picu notifications exist, set filter redirect to remove/add query args
	if ( get_option( '_' . get_current_user_id() . '_picu_notifications' ) ) {
		add_filter( 'redirect_post_location', 'picu_save_add_notification_arg' );
	}
}

// Add-Ons should use a priority lower than 10
add_action( 'save_post_picu_collection', 'picu_should_notification_be_displayed', 99, 2 );


/**
 * Add a query arg if an error occured
 *
 * @since 0.5.0
 */
function picu_save_add_notification_arg( $location ) {

	// Remove $_GET['message'] (the standard "post saved" message)
	$location = remove_query_arg( 'message', $location );

	// Add our own query string to indicate a custom picu notification
	$location = add_query_arg( 'picu_notification', 1, $location );

	return $location;
}


/**
 * Display our admin notices and error messages
 *
 * @since 0.5.0
 */
function picu_display_admin_notices() {
	global $post;

	// Check if picu notification should be displayed
	if ( isset( $_GET['picu_notification'] ) && $_GET['picu_notification'] == 1 ) {

		// Get notification option
		$notifications = get_option( '_' . get_current_user_id() . '_picu_notifications' );

		// If there are notifications, display them
		if ( isset( $notifications ) AND is_array( $notifications ) ) {
			foreach( $notifications as $notification ) {
				echo '<div class="' . $notification['type'] . '"><p>' . $notification['message'] . '</p></div>';
			}

			// Delete notification option
			delete_option( '_' . get_current_user_id() . '_picu_notifications' );
		}
	}
}

add_action( 'admin_notices', 'picu_display_admin_notices', 1, 2 );


/**
 * Filter message texts for bulk editing of collections
 *
 * @since 0.5.0
 */
function picu_filter_bulk_messages( $bulk_messages, $bulk_counts ) {
	$bulk_messages['picu_collection'] = array(
		'updated'   => _n( '%s Collection saved.', '%s Collections saved.', $bulk_counts['updated'], 'picu' ),
		'locked'    => _n( '%s Collection not saved, somebody is editing it.', '%s Collections not saved, somebody is editing them.', $bulk_counts['locked'], 'picu' ),
		'deleted'   => _n( '%s Collection permanently deleted.', '%s Collections permanently deleted.', $bulk_counts['deleted'], 'picu' ),
		'trashed'   => _n( '%s Collection moved to the Trash.', '%s Collections moved to the Trash.', $bulk_counts['trashed'], 'picu' ),
		'untrashed' => _n( '%s Collection restored from the Trash.', '%s Collections restored from the Trash.', $bulk_counts['untrashed'], 'picu' ),
	);

	return $bulk_messages;
}

add_filter( 'bulk_post_updated_messages', 'picu_filter_bulk_messages', 10, 2 );


/**
 * Filter message texts for admin notices in collections
 *
 * @since 0.5.0
 */
function picu_filter_messages( $messages ) {

	global $post, $post_ID;

	$messages['picu_collection'] = array(
		0 => '', // Unused. Messages start at index 1.
		/* translators: %s = opening and closing link tags */
		1 => sprintf( __( 'Collection saved. %sView Collection%s', 'picu' ), '<a href="' . esc_url( get_permalink( $post_ID ) ) . '">', '</a>' ),
		2 => __( 'Custom field updated.', 'picu' ),
		3 => __( 'Custom field deleted.', 'picu' ),
		4 => __( 'Collection saved.', 'picu' ),
		5 => isset( $_GET['revision'] ) ? sprintf( __( 'Collection restored to revision from %s', 'picu' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		/* translators: %s = opening and closing link tags */
		6 => sprintf( __( 'Collection saved. %sView Collection%s', 'picu' ), '<a href="' . esc_url( get_permalink( $post_ID ) ) . '">', '</a>' ),
		7 => __( 'Collection saved.', 'picu' ),
		/* translators: %s = opening and closing link tags */
		8 => sprintf( __( 'Collection submitted. %sPreview Collection%s', 'picu' ), '<a target="_blank" href="' . esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) . '">', '</a>' ),
		/* translators: %2$s and %3$s = opening and closing link tags */
		9 => sprintf( __( 'Collection scheduled for: <strong>%1$s</strong>. %2$sPreview Collection%3$s', 'picu' ), date_i18n( __( 'M j, Y @ G:i', 'picu' ), strtotime( $post->post_date ) ), '<a target="_blank" href="' . esc_url( get_permalink( $post_ID ) ) . '">', '</a>' ),
		/* translators: %s = opening and closing link tags */
		10 => sprintf( __( 'Collection draft updated. %sPreview Collection%s', 'picu' ), '<a target="_blank" href="' . esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) . '">', '</a>' ),
			);

	return $messages;

}

add_filter( 'post_updated_messages', 'picu_filter_messages' );


/**
 * Check if is a valid email address
 * 
 * @param string email address
 * @return string/bool email address or false
 */
function picu_validate_email_address( $address ) { 
	if ( is_email( $address ) ) {
		return $address;
	} else {
		return false;
	}
}