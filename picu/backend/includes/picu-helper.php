<?php
/**
 * Picu Helper functions
 *
 * @since 0.5.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
 * Add additional body classes for admin screens.
 *
 * @since 0.3.2
 */
function picu_admin_body_class( $admin_body_class ) {

	// Get current admin screen
	$current_screen = get_current_screen();

	// Check if we are on a 'post.php' page
	if ( $current_screen->base == 'post' ) {
		// Add new class to the array;
		$admin_body_class .= ' post-status-' . get_post_status();
	}

	// return the array
	return $admin_body_class;

}

add_filter( 'admin_body_class', 'picu_admin_body_class' );


/**
 * Make our picu_theme option overridable by URL parameter 'theme'
 * 
 * @since 2.4.0
 * 
 * @param mixed $theme_option The 'picu_theme' option from the DB
 * @return mixed $theme The filtered value
 */
function picu_filter_theme_option( $theme_option ) {

	$theme_parameter = ! empty( $_GET['theme'] ) ? $_GET['theme'] : false;

	switch( $theme_parameter ) {
		case 'light':
			$theme = 'light';
			break;
		case 'dark':
			$theme = 'dark';
			break;
		default:
			$theme = $theme_option;
	}

	return $theme;

}

add_filter( 'option_picu_theme', 'picu_filter_theme_option' );


/**
 * Change the post_status.
 *
 * @since 0.3.0
 * 
 * @param int $post_id The collection post ID
 * @param string $status The collection status
 */
function picu_update_post_status( $post_id, $status ) {
	if ( $status != 'delivered' && $status != 'delivery-draft' && $status != 'sent' && $status != 'approved' && $status != 'expired' && $status != 'draft' ) {
		return $post_id;
	}

	$post_contents = array(
		'ID' => $post_id,
		'post_status' => $status
	);

	// Remove our mail and save function so we don't get trapped in a loop
	remove_action( 'save_post_picu_collection', 'picu_collection_publish' );
	remove_action( 'save_post_picu_collection', 'picu_messaging_logic' );

	// Update the post, which calls save_post again
	wp_update_post( $post_contents );

	// Re-add the function for mail and save after save_post has fired
	add_action( 'save_post_picu_collection', 'picu_collection_publish', 10, 2 );
	add_action( 'save_post_picu_collection', 'picu_messaging_logic', 10, 2 );
}


/**
 * Publish collection - When using "copy link & send manually" share option.
 *
 * @since 1.7.0
 *
 * @param int $post_id The collection post ID
 * @param object $post The post object
 * @return int The collection post ID
 */
function picu_collection_publish( $post_id, $post ) {
	// Only go ahead if our button was clicked
	if ( ! isset( $_POST['picu_sendmail'] ) )
		return $post_id;

	// Check if nonce is set
	if ( ! isset( $_POST['picu_collection_metabox_nonce'] ) )
		return $post_id;

	// Verify nonce
	if ( ! wp_verify_nonce( $_POST['picu_collection_metabox_nonce'], 'picu_collection_metabox' ) )
		return $post_id;

	// Abort if no title is set
	if ( ! $post->post_title )
		return $post_id;

	// Abort if there are no proof images, but the intent is proofing
	if ( isset( $_POST['picu_gallery_ids'] ) AND empty( $_POST['picu_gallery_ids'] ) ) {
		return $post_id;
	}

	// Abort if there are no delivery images, but the intent is delivery – and the delivery option is upload
	if ( isset( $_POST['delivery_image_ids'] ) AND empty( $_POST['delivery_image_ids'] ) AND $_POST['picu_delivery_option'] == 'upload' ) {
		return $post_id;
	}

	// Abort if autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		return $post_id;

	// Abort if the user doesn't have permissions
	if ( ! current_user_can( 'edit_post', $post_id ) )
		return $post_id;

	// Abort sending if there are error notifications
	$notifications = get_option( '_' . get_current_user_id() . '_picu_notifications' );

	if ( is_array( $notifications ) ) {
		foreach( $notifications as $notification ) {
			if ( strpos( $notification['type'], 'error' ) )  {
				return $post_id;
			}
		}
	}

	// Only send mail, if this method is selected
	if ( $_POST['picu_collection_share_method'] == 'picu-copy-link' ) {

		if ( $post->post_status == 'delivery-draft' ) {
			// Set the post status to "sent"
			picu_update_post_status( $post_id, 'delivered' );
			// Update collection history
			picu_update_collection_history( $post_id, 'delivery-published' );

			// Add success notification
			picu_add_notification( 'picu_mail_sent', 'notice notice-success is-dismissible', __( 'Your delivery is ready! Make sure to send the link to your client:', 'picu' ) . ' <input type="text" value="' . get_draft_permalink( $post_id ) . '" />' );
		}
		else {
			// Set the post status to "sent"
			picu_update_post_status( $post_id, 'sent' );
			// Update collection history
			picu_update_collection_history( $post_id, 'published' );

			// Add default client, if none exists
			$create_default_client = apply_filters( 'picu_create_default_client', true, $post_id );

			if ( $create_default_client && ! picu_collection_has_ident( $post_id ) ) {
				picu_add_client_to_hashes( $post_id, __( 'Client', 'picu') );
			}

			// Add success notification
			picu_add_notification( 'picu_mail_sent', 'notice notice-success is-dismissible', __( 'The collection is ready! Make sure to send the link to your client:', 'picu' ) . ' <input type="text" value="' . get_draft_permalink( $post_id ) . '" />' );
		}
	}
}

add_action( 'save_post_picu_collection', 'picu_collection_publish', 10, 2 );


/**
 * Update picu collection history.
 *
 * @since 0.9.4
 * @since 2.3.5 Added $meta param.
 *
 * @param $post_id
 * @param $event, string - sent, reopened, approved
 * @param $data, string or array - additional data
 * @param array $meta Additional data about the history event.
 */
function picu_update_collection_history( $post_id, $event, $data = NULL, $meta = [] ) {
	// Load existing history
	$existing_history = get_post_meta( $post_id, '_picu_collection_history', true );

	// Create new history array
	$time = time();
	if ( is_array( $existing_history ) && array_key_exists( $time, $existing_history ) ) {
		$time++;
	}
	$new_history["$time"] = array(
		'event' => $event,
		'data' => $data
	);

	if ( ! is_array( $meta ) ) {
		$meta = [ $meta ];
	}

	if ( ! empty( $meta ) ) {
		$new_history["$time"]['meta'] = $meta;
	}

	// Merge arrays
	if ( is_array( $existing_history ) ) {
		$history = $existing_history + $new_history; // Using +, because `array_merge()` will reindex
	}
	else {
		$history = $new_history;
	}

	// Save updated history
	update_post_meta( $post_id, '_picu_collection_history', $history );

	// Prevent infinite loop
	remove_action( 'save_post_picu_collection', 'picu_collection_publish' );
	remove_action( 'save_post_picu_collection', 'picu_messaging_logic' );

	// Update modified time
	$date = date( 'Y-m-d H:i:s', time() );
	wp_update_post( [
		'ID' => $post_id,
		'post_modified' => $date,
		'post_modified_gmt' => get_gmt_from_date( $date ),
	] );
}


/**
 * Get picu collection history event time.
 *
 * @since 0.9.4
 *
 * @param $post_id
 * @param $event, string - sent, reopened, approved
 */
function picu_get_collection_history_event_time( $post_id, $event ) {

	$picu_collection_history = get_post_meta( $post_id, '_picu_collection_history', false );

	// Check if history exists and if it contains anything
	if ( is_array( $picu_collection_history ) AND 0 < count( $picu_collection_history ) ) {

		// Get timestamps
		$keys = array_keys( $picu_collection_history[0] );

		// Check at which timestamp our event existing_history
		foreach( $picu_collection_history[0] as $key => $temp ) {

			// Check all events, get the most recent one
			if ( isset( $temp['event'] ) AND $event == $temp['event'] ) {
				$time = $key; // The final time will be the last $event in the history
			}
		}

		// Check if it is a valid timestamp
		if ( isset( $time ) AND is_numeric( $time ) ) {
			return $time;
		}
	}

	return false;
}


/**
 * Get last history event.
 *
 * @since 1.5.7
 *
 * @param int $post_id The collection post ID
 * @param int $time A timestamp
 */
function picu_get_last_history_event( $post_id, $time = null ) {

	$event = '';
	$picu_collection_history = get_post_meta( $post_id, '_picu_collection_history', true );

	if ( is_array( $picu_collection_history ) ) {

		if ( ! $time ) {
			end( $picu_collection_history );
			$time = key( $picu_collection_history );
		}

		if ( ! empty( $picu_collection_history[$time]['event'] ) ) {
			$event = $picu_collection_history[$time]['event'];
		}
	}

	if ( empty( $event ) ) {
		return 'last-modified';
	}

	return $event;
}


/**
 * Check if a collection has been approved/closed/expired in the past.
 *
 * @since 1.4.4
 *
 * @param int $collection_id The collection post ID
 *
 * @return bool Whether the collection has been approved/closed/expired before
 */
function picu_has_collection_been_closed( $collection_id ) {
	$events = [];
	$collection_history = get_post_meta( $collection_id, '_picu_collection_history', true );

	if ( is_array( $collection_history ) AND ! empty( $collection_history ) ) {
		foreach( $collection_history as $event ) {
			$events[] = $event['event'];
		}

		if ( array_intersect( [ 'approved', 'closed-manually', 'expired' ], $events ) ) {
			return true;
		}
	}

	return false;
}


/**
 * Display a nice name for a collection event.
 *
 * @since 2.2.0
 *
 * @param string $event The collection event
 * @return string The collection event nice name
 */
function picu_collection_event_prettify( $event ) {
	switch( $event ) {
		case 'sent':
			return __( 'Sent to client(s)', 'picu' );
			break;
		case 'sent-to-new-client':
			return __( 'Sent to additional client', 'picu' );
			break;
		case 'published':
			return __( 'Published', 'picu' );
			break;
		case 'new-client-registered':
			return __( 'New client registered', 'picu' );
			break;
		case 'removed-client':
			return __( 'Removed client', 'picu' );
			break;
		case 'approved':
			return __( 'Approved', 'picu' );
			break;
		case 'approved-by-client':
			return __( 'Approved by client', 'picu' );
			break;
		case 'reopened-for-client':
			return __( 'Reopened for client', 'picu' );
			break;
		case 'reopened':
			return __( 'Reopened', 'picu' );
			break;
		case 'reopened-to-draft':
			return __( 'Reverted to draft', 'picu' );
			break;
		case 'reopened-to-delivery-draft':
			return __( 'Reverted to delivery draft', 'picu' );
			break;
		case 'expired':
			return __( 'Expired', 'picu' );
			break;
		case 'closed-manually':
			return __( 'Closed manually', 'picu' );
			break;
		case 'preparing-delivery':
			$event = __( 'Preparing Delivery', 'picu' );
			break;	
		case 'delivered':
			$event = __( 'Delivered', 'picu' );
			break;
		case 'delivery-published':
			$event = __( 'Delivery published', 'picu' );
			break;
		case 'last-modified':
			return __( 'Last modified', 'picu' );
			break;
		case 'images-updated':
			return __( 'Images updated', 'picu' );
			break;
	}

	return $event;
}
 

/**
 * Get an array of collection image IDs.
 *
 * @since 1.10.0
 * @since 2.3.0 Switch between regular and delivery collections.
 *
 * @param int $collection_id The collection post ID
 * @return array The collection image IDs or an empty array
 */
function picu_get_collection_images( $collection_id ) {
	$type = 'gallery';
	if ( in_array( get_post_status( $collection_id ),  [ 'delivery-draft', 'delivered' ] ) ) {
		$type = 'delivery';
	}

	$images = explode( ',' , get_post_meta( $collection_id, '_picu_collection_' . $type . '_ids', true ) );

	if ( ! empty( $images[0] ) ) {
		return $images;
	}
	
	return [];
}


/**
 * Get the number of images in a collection.
 *
 * @since 1.6.2
 * @since 1.10.0 Using the new `picu_get_collection_images` function
 *
 * @param $collection_id The collection post ID
 * @return int Number of images in a collection
 */
function picu_get_collection_image_num( $collection_id ) {
	$images = picu_get_collection_images( $collection_id );

	if ( ! empty( $images[0] ) ) {
		return count( $images );
	}

	return 0;
}


/**
 * Get the number of selected images of a collection.
 *
 * @since 1.3.4
 *
 * @param $post_id - The collection post ID
 * @return int Number of selected images (at least once for multi client collections)
 */
function picu_get_selection_count( $post_id ) {

	// For multi client collections
	$picu_collection_hashes = get_post_meta( $post_id, '_picu_collection_hashes', true );

	if ( ! empty( $picu_collection_hashes ) ) {

		$image_ids = [];

		foreach( $picu_collection_hashes as $key => $hash ) {
			$selection = get_post_meta( $post_id, '_picu_collection_selection_' . $key, true );
			if ( ! empty( $selection['selection'] ) ) {
				$image_ids = array_merge( $image_ids, $selection['selection'] );
			}
		}

		return count( array_unique( $image_ids ) );
	}

	// For single collections
	else {
		$selection = get_post_meta( $post_id, '_picu_collection_selection', true );
		return ( isset( $selection['selection'] ) AND is_array( $selection['selection'] ) ) ? count( $selection['selection'] ) : 0;
	}
	
}


/**
 * Get selected images.
 *
 * @since 2.3.4
 *
 * @param int $post_id The collection post ID
 * @param string $ident Identification hash for a multi client collection
 * @param bool $all Return selected at least once or selected by all
 * @return array List of selected image IDs
 */
function picu_get_selected_images( $post_id, $ident = '', $all = false ) {
	// Prepare variable
	$selection_image_ids = [];

	// Get hashes
	$picu_collection_hashes = get_post_meta( $post_id, '_picu_collection_hashes', true );

	if ( empty( $picu_collection_hashes ) ) {
		return [];
	}

	// Check if we handle one client only
	if ( ! empty( $ident ) AND ( ! is_array( $picu_collection_hashes ) OR ! array_key_exists( $ident, $picu_collection_hashes ) ) ) {
		return [];
	}

	// Get ids for a certain identity
	// First, check if the identity exists
	if ( ! empty( $ident ) AND array_key_exists( $ident, $picu_collection_hashes ) ) {
		// Next, check if there is a selection for this identity
		$selection = get_post_meta( $post_id, '_picu_collection_selection_' . $ident, true );
		if ( ! empty( $selection['selection'] ) ) {
			// Fill filenames
			$selection_image_ids = array_merge( $selection_image_ids, $selection['selection'] );
		}
	}
	// Get ids for all identities
	else {
		// Get selected by all
		if ( $all == true ) {
			$start = true;

			foreach( $picu_collection_hashes as $key => $hash ) {
				$selection = get_post_meta( $post_id, '_picu_collection_selection_' . $key, true );

				if ( ! empty( $selection['selection'] ) ) {
					// Fill filenames
					if ( $start == true ) {
						$selection_image_ids = $selection['selection'];
						$start = false;
					}
					else {
						$selection_image_ids = array_intersect( $selection_image_ids, $selection['selection'] );
					}
				}
				
			}
		}
		// Get selected at least once
		else {
			// Iterate through hashes and get selections
			foreach( $picu_collection_hashes as $key => $hash ) {
				// Fill individual selections
				$selection = get_post_meta( $post_id, '_picu_collection_selection_' . $key, true );
				if ( ! empty( $selection['selection'] ) ) {
					// Fill filenames
					$selection_image_ids = array_merge( $selection_image_ids, $selection['selection'] );
				}
			}
		}
	}

	$selection_image_ids = array_unique( $selection_image_ids );

	return $selection_image_ids;
}


/**
 * Get approved filenames.
 *
 * @since 1.5.0
 * @since 1.11.0 Add $specialchars param
 *
 * @param int $post_id The collection post ID
 * @param string $ident Identification hash for a multi client collection
 * @param bool $all Return selected at least once or selected by all
 * @param bool $convert Whether filename characters should be converted to HTML entities
 * @return string Filenames of approved images
 */
function picu_get_approved_filenames( $post_id, $ident = '', $all = false, $convert = true ) {
	$img_filenames = '';

	// Get selected images
	$selection_image_ids = picu_get_selected_images( $post_id, $ident, $all );

	// Get filenames
	$filename_separator = ( defined( 'PICU_FILENAME_SEPARATOR' ) ) ? PICU_FILENAME_SEPARATOR : ' ';
	$filename_separator = apply_filters( 'picu_filename_separator', $filename_separator );

	if ( ! empty( $selection_image_ids ) ) {
		// Loop through our IDs to get the filenames
		foreach ( $selection_image_ids as $selection_image_key => $selection_image_id ) {
			$img_filename = picu_get_image_filename( $selection_image_id );
			$img_filename = apply_filters( 'picu_approved_filename', $img_filename, $selection_image_id );

			if ( $selection_image_key !== array_key_last( $selection_image_ids ) ) {
				// Add filename to our string, separated by our separator
				$img_filenames .= $img_filename . $filename_separator;
			} else {
				// No separator after the last filename
				$img_filenames .= $img_filename;
			}
		}

		$img_filenames = trim( $img_filenames );
	}

	if ( $convert === true ) {
		$img_filenames = htmlspecialchars( $img_filenames );
	}

	return $img_filenames;
}


/**
 * Return image filename.
 *
 * @since 3.1.0
 *
 * @param int $attachment_id The attachment post ID
 * @return string The filename
 */
function picu_get_image_filename( $attachment_id ) {
	// Check for our custom post meta entry first
	$filename = get_post_meta( $attachment_id, '_picu_original_filename', true );
	// Remove 
	$filename = pathinfo( $filename, PATHINFO_FILENAME );

	// Fallback to the filename after being uploaded
	if ( empty( $filename ) ) {
		$attachment_src = wp_get_attachment_image_src( $attachment_id, 'full' );
		$filename = pathinfo( $attachment_src[0], PATHINFO_FILENAME );
	}

	return $filename;
}


/**
 * Create proof txt file.
 *
 * @since 1.5.0
 *
 * @param int $post_id The collection post ID
 * @param bool $save_file Whether the file or the path should be returned
 * @return string Either the file path or the file will be returned directly
 */
function picu_create_proof_file( $post_id, $save_file = false ) {
	// Get title
	$title = get_the_title( $post_id );

	// Get client email address
	$email = picu_get_collection_clients( $post_id );
	$email = implode( ', ', $email );

	// Get the last event
	$last_event = picu_get_last_history_event( $post_id );

	if ( $last_event == 'approved' || $last_event == 'approved-by-client' ) {
		// Get approval date
		$temp_approval_date = picu_get_collection_history_event_time( $post_id, 'approved' );
		if ( ! empty( $temp_approval_date ) ) {
			$approval_time = wp_date( get_option( 'date_format' ) . ', ' . get_option( 'time_format' ), $temp_approval_date );
		}
	}

	if ( $last_event == 'expired' ) {
		// Get expired date
		$temp_expired_date = picu_get_collection_history_event_time( $post_id, 'expired' );
		if ( ! empty( $temp_expired_date ) ) {
			$expired_time = wp_date( get_option( 'date_format' ) . ', ' . get_option( 'time_format' ), $temp_expired_date );
		}
	}
	
	if ( $last_event == 'closed-manually' ) {
		// Get closing date
		$temp_closed_date = picu_get_collection_history_event_time( $post_id, 'closed-manually' );
		if ( ! empty( $temp_closed_date ) ) {
			$closed_time = wp_date( get_option( 'date_format' ) . ', ' . get_option( 'time_format' ), $temp_closed_date );
		}
	}

	// Get image ids as an array
	$image_ids = picu_get_selected_images( $post_id, '', false );
	$approved_image_ids = picu_get_selected_images( $post_id, '', true );

	// Get filenames
	$img_filenames = picu_get_approved_filenames( $post_id, '', false, false );

	// Get hashes
	$picu_collection_hashes = get_post_meta( $post_id, '_picu_collection_hashes', true );

	$proof_file_content = '# ' . sprintf( __( 'Selection summary for "%s"', 'picu' ), $title );

	/* translators: %s: date and time */
	if ( ! empty( $approval_time ) ) {
	$proof_file_content .= "\n\n" . sprintf( __( 'Approved: %s', 'picu' ), $approval_time );
	}
	elseif ( ! empty( $expired_time ) ) {
		$proof_file_content .= "\n\n" . sprintf( __( 'Expired: %s', 'picu' ), $expired_time );
	}
	elseif ( ! empty( $closed_time ) ) {
		$proof_file_content .= "\n\n" . sprintf( __( 'Closed: %s', 'picu' ), $closed_time );
	}

	if ( ! empty( $email ) ) {
		/* translators: %s: email address */
		$proof_file_content .= "\n\n" . sprintf( __( 'Clients: %s','picu' ), $email );
	}

	/**
	 * Create and return file for multi client collections
	 */ 
	if ( ! empty( $picu_collection_hashes ) ) {

		$client_selections = [];
		$recipient_num = count( $picu_collection_hashes );

		// Iterate through multi clients
		foreach( $picu_collection_hashes as $key => $hash ) {

			$selection = get_post_meta( $post_id, '_picu_collection_selection_' . $key, true );

			$approval_fields = [];

			// Legacy: Add the old approval message to the approval fields array
			if ( ! empty( $selection['approval_message'] ) ) {
				$approval_fields['picu_approval_message'] = [
					'label' => __( 'The following comment was added on approval', 'picu' ),
					'value' => $selection['approval_message'],
				];
			}

			if ( ! empty( $selection['approval_fields'] ) ) {
				$approval_fields = $selection['approval_fields'];
			}

			$client_selection = get_post_meta( $post_id, '_picu_collection_selection_' . $key, true );
			if ( ! empty( $client_selection['time'] ) ) {
				$time = wp_date( get_option( 'date_format' ) . ', ' . get_option( 'time_format' ), $client_selection['time'] );
			}
			else {
				$time = false;
			}

			$client_selections[] = [
				'name' => $hash['name'],
				'email' => $hash['email'],
				'status' => $hash['status'],
				'time' => $time,
				'approval_fields' => $approval_fields,
				'filenames' => picu_get_approved_filenames( $post_id, $key, false, false ),
				'num' => count( picu_get_selected_images( $post_id, $key ) ),
			];

		}

		if ( $recipient_num > 1 ) {
	$proof_file_content .= '.

* * *

';

$proof_file_content .= '## ' . sprintf(
	/* translators: %d: number of selected image(s) */
	_n( 'Selected at least once (%d image):', 'Selected at least once (%d images):', count( $image_ids ), 'picu' ),
	count( $image_ids )
) .'

' . $img_filenames . '

* * *

## ' . sprintf(
	/* translators: %d: number of selected image(s) */
	_n( 'Selected by all (%d image):', 'Selected by all (%d images):', count( $approved_image_ids ), 'picu' ),
	count( $approved_image_ids )
) . '

' .  picu_get_approved_filenames( $post_id, $ident = '', true, false );
		}

		$proof_file_content .= '
';

		if ( ! empty( $client_selections ) ) {
			foreach( $client_selections as $client ) {
$proof_file_content .= '
* * *';

// Check whether the client has actually approved the selection
if ( $client['status'] == 'approved' ) {
$proof_file_content .= '

' . picu_combine_name_email( $client['name'], $client['email'] ) . ' approved the collection on ' . $client['time'] . '.';
}
else {
	$proof_file_content .= '

' . picu_combine_name_email( $client['name'], $client['email'] ). ' has not finally approved the collection.';
}

		if ( empty( $client['filenames'] ) ) {
			$proof_file_content .= '

' . __( 'No selected images.', 'picu' ) .'
';
		}
		else {
$proof_file_content .= '

';

$proof_file_content .= sprintf(
	/* translators: %d: number of selected image(s) */
	_n( 'Selected (%d image):', 'Selected (%d images):', $client['num'], 'picu' ),
	$client['num']
) . '

' . $client['filenames'] . '

'; }

				// Add custom approval fields
				if ( ! empty( $client['approval_fields'] ) ) {
$proof_file_content .= picu_custom_fields_in_proof_file( $client['approval_fields'] );
				}

			}
		}
	}

	/**
	 * Create and return file for single client collections
	 */ 
	else {

		// Get client comment
		$picu_collection_selection = get_post_meta( $post_id, '_picu_collection_selection', true );

$proof_file_content .= '.

* * *

';

		// Legacy: Add the old approval message to the approval fields array
		if ( ! empty( $picu_collection_selection['approval_message'] ) ) {
			$picu_collection_selection['approval_fields']['picu_approval_message'] = [
				'label' => __( 'The following comment was added on approval', 'picu' ),
				'value' => $picu_collection_selection['approval_message'],
			];
		}

		// Add custom approval fields
		if ( ! empty( $picu_collection_selection['approval_fields'] ) ) {
$proof_file_content .= picu_custom_fields_in_proof_file( $picu_collection_selection['approval_fields'] );
$proof_file_content .= '
* * *

';
		}

$proof_file_content .= __( 'Selected images', 'picu' ) .':

' . $img_filenames;

	}

	// Filter file content
	$proof_file_content = apply_filters( 'picu_proof_file_content', $proof_file_content, $post_id );

	// Filter and sanitize file name
	/* translators: File name (prefix) for approved collections */
	$proof_file_name = apply_filters( 'picu_proof_file_name', __( 'selection', 'picu' ) . '-' . sanitize_title( $title ) . '.txt', $post_id );
	$proof_file_name = sanitize_file_name( $proof_file_name );

	// Save the file
	if ( $save_file === true ) {
		$upload_dir = wp_get_upload_dir();
		$full_path = $upload_dir['basedir'] . '/picu/collections/' . $post_id . '/' . $proof_file_name;

		file_put_contents( $full_path, $proof_file_content );

		return $full_path;
	}

	// Open the file
	header( 'Content-Type: application/download' );
	header( 'Content-Disposition: attachment; filename="' . $proof_file_name . '"' );
	echo $proof_file_content;
	exit;
}


/**
 * Add approval fields into the proof file.
 *
 * @since 1.6.5
 *
 * @param array $approval_fields The approval fields
 * @return string Approval fields with labels and values
 */
function picu_custom_fields_in_proof_file( $approval_fields ) {
	$text = '';
	$num = count( $approval_fields );
	$i = 1;
	foreach( $approval_fields as $field ) {
		if ( ! empty( $field['value'] ) ) {
			if ( $num == 1 ) {
				$text .= "\n";
			}
			$text .= $field['label'] . ":\n" . $field['value'] . "\n";
			if ( $num != $i ) {
				$text .= "\n";
			}
		}
		$i++;
	}

	return $text;
}


/**
 * Check whether an ident exists in a collection.
 *
 * @since 2.3.0
 *
 * @param string $ident The identification string
 * @param int $collection_id The collection post ID
 * @return bool Whether the ident exists
 */
function picu_ident_exists( $ident, $collection_id ) {
	$hashes = get_post_meta( $collection_id, '_picu_collection_hashes', true );
	if ( is_array( $hashes ) && array_key_exists( $ident, $hashes ) ) {
		return true;
	}

	return false;
}


/**
 * Check if hashes exist for a collection.
 *
 * @since 2.3.0
 *
 * @param int $collection_id The collection post ID.
 * @return bool Whether hashes exist for this collection.
 */
function picu_collection_has_ident( $collection_id ) {
	$hashes = get_post_meta( $collection_id, '_picu_collection_hashes', true );
	if ( ! empty( $hashes ) ) {
		return true;
	}

	return false;
}


/**
 * Get email address from ident parameter.
 *
 * @since 1.7.0
 *
 * @param int $collection_id The collection post ID
 * @param string $ident The identification string
 * @return string|bool Email address or false
 */
function picu_get_email_from_ident( $collection_id, $ident ) {
	$hashes = get_post_meta( $collection_id, '_picu_collection_hashes', true );

	if ( ! empty( $hashes[$ident]['email'] ) ) {
		$email = sanitize_email( $hashes[$ident]['email'] );
		return $email;
	}
	return false;
}


/**
 * Get status from ident parameter.
 *
 * @since 2.3.0
 *
 * @param int $collection_id The collection ID
 * @param string $ident The identification string
 * @return string|bool Status or false
 */
function picu_get_status_from_ident( $collection_id, $ident ) {
	$hashes = get_post_meta( $collection_id, '_picu_collection_hashes', true );

	if ( ! empty( $hashes[$ident]['status'] ) ) {
		$status = sanitize_text_field( $hashes[$ident]['status'] );
		return $status;
	}

	return false;
}


/**
 * Get name from ident parameter.
 *
 * @since 2.3.0
 *
 * @param int $collection_id The collection post ID
 * @param string $ident The identification string
 * @return string|bool Name or false
 */
function picu_get_name_from_ident( $collection_id, $ident ) {
	$hashes = get_post_meta( $collection_id, '_picu_collection_hashes', true );

	if ( ! empty( $hashes[$ident]['name'] ) ) {
		$email = sanitize_text_field( $hashes[$ident]['name'] );
		return $email;
	}

	return false;
}


/**
 * Get ident parameter from email address.
 *
 * @since 1.7.1
 *
 * @param int $collection_id The collection post ID
 * @param string $email Email address
 * @return string|bool The identification string or false
 */
function picu_get_ident_from_email( $collection_id, $email ) {
	$hashes = get_post_meta( $collection_id, '_picu_collection_hashes', true );

	if ( ! empty( $hashes ) && is_array( $hashes ) ) {
		foreach( $hashes as $ident => $hash ) {
			if ( $hash['email'] == $email ) {
				return $ident;
			}
		}
	}

	return false;
}


/**
 * Get email addresses for a collection.
 *
 * @since 1.7.5
 *
 * @param int $post_id The collection post ID
 * @return array The email addresses
 */
function picu_get_collection_emails( $post_id ) {
	$emails = [];

	$collection_hashes = get_post_meta( $post_id, '_picu_collection_hashes', true );

	if ( ! empty( $collection_hashes ) ) {
		foreach ( $collection_hashes as $hash => $hash_fields ) {
			$emails[] = $hash_fields['email'];
		}

		// Remove empty entries
		$emails = array_filter( $emails );
	}

	return $emails;
}


/**
 * Get all clients for a collection.
 *
 * @since 2.3.0
 *
 * @param int $post_id The collection post ID
 * @return array The clients (name + email)
 */
function picu_get_collection_clients( $post_id ) {
	$clients = [];

	$collection_hashes = get_post_meta( $post_id, '_picu_collection_hashes', true );
	foreach( $collection_hashes as $hash => $hash_fields ) {
		$clients[] = picu_combine_name_email( $hash_fields['name'], $hash_fields['email'] );
	}

	// Remove empty entries
	$clients = array_filter( $clients );

	return $clients;
}


/**
 * Check if all clients have approved their selections.
 *
 * @since 2.3.5
 *
 * @param int $collection_id The collection post ID.
 * @return bool Whether everyone has approved the collection.
 */
function picu_have_all_clients_approved( $collection_id ) {
	$hashes = get_post_meta( $collection_id, '_picu_collection_hashes', true );

	if ( is_array( $hashes ) && ! empty( $hashes ) ) {
		$all_approved = true;

		foreach( $hashes as $ident => $value ) {
			if ( picu_get_status_from_ident( $collection_id, $ident ) != 'approved' ) {
				$all_approved = false;
			}
		}

		return $all_approved;
	}

	return false;
}


/**
 * Update client email address history.
 *
 * @since 1.7.5
 *
 * @param string $mail_context Info about which email we are hooking into
 * @param int $post_id The collection post ID
 */
function picu_update_email_history( $mail_context, $post_id ) {

	// Allow users to turn email history saving off
	if ( ! apply_filters( 'picu_save_email_history', true ) ) {
		return;
	}

	// Only run this one time (see below)
	if ( did_action( 'picu_update_email_history_once' ) >= 1 ) {
		return;
	}

	if ( $mail_context == 'client_collection_new' ) {

		// Get email(s)
		$emails = picu_get_collection_emails( $post_id );

		// Get history
		$history = get_user_option( 'picu_email_history' );

		if ( empty( $history ) ) {
			$history = [];
		}

		if ( ! empty( $emails ) ) {

			foreach( $emails as $email ) {
				// Check if email is already in there, if so, add to the count
				$search = array_search( $email, array_column( $history, 'email' ) );
				if ( false !== $search ) {
					$history[$search]['uses']++;
				}
				else {
					// If it is a new address, add it to the array
					array_push( $history, [ 'email' => $email, 'uses' => 1 ] );
				}
			}

			// Sort array by uses
			usort( $history, function( $a, $b ) {
				return $b['uses'] <=> $a['uses'];
			});

			update_user_option( get_current_user_id(), 'picu_email_history', $history );
		}
	}
	
	// We need to allow the "parent" action (picu_after_email_sent) to run more than once.
	// Problem: It runs once per email address / email sent.
	// So we use this "helper" action to run this function only once.
	do_action( 'picu_update_email_history_once' );
}

add_action( 'picu_after_email_sent', 'picu_update_email_history', 10, 2 );


/**
 * The user's email history as a datalist.
 *
 * @since 1.7.5
 */
function picu_the_email_history_datalist() {

	// Allow users to turn email suggestions off
	if ( ! apply_filters( 'picu_use_email_history', true ) ) {
		return;
	}

	$history = get_user_option( 'picu_email_history' );
	if ( empty( $history ) ) {
		return;
	}

	$datalist = '<datalist id="email-history">';
	foreach( $history as $entry ) {
		$datalist .= '<option value="' . $entry['email'] . '">';
	}
	$datalist .= '</datalist>';

	/**
	 * Filters email history datalist output
	 *
	 * @param string $datalist The datalist that will be printed
	 * @param array $history The array containing the email history
	 * @since 1.7.5
	 */
	echo apply_filters( 'picu_email_history_list', $datalist, $history );
}


/**
 * Get the number of recipients for a collection.
 *
 * @since 2.1.0
 *
 * @param int $post_id The collection post ID
 * @return int The number of recipients
 */
function picu_get_recipients_num( $post_id ) {
	$hashes = get_post_meta( $post_id, '_picu_collection_hashes', true );
	if ( ! empty( $hashes ) AND count( $hashes ) > 0 ) {
		return count( $hashes );
	}

	return 0;
}


/**
 * Return the default expiration length.
 *
 * @since 2.0.0
 *
 * @return int The number of days
 */
function picu_expiration_length() {
	// Add filter to adjust the expiration length, defaults to 30 days
	$expiration_length = (int) apply_filters( 'picu_expiration_length', 30 );

	// Make sure the number is positive
	$expiration_length = abs( $expiration_length );

	// Make sure the number is at least 1
	if ( $expiration_length < 1 ) { $expiration_length = 1; }

	return $expiration_length;
}


/**
 * Calculate expiration time starting from now.
 *
 * @since 2.0.0
 *
 * @since int $expiration_length Number of days
 */
function picu_calculate_expiration_time() {
	// Get expiration length
	$expiration_length = picu_expiration_length();

	// Get the current timestamp
	$current_timestamp = ceil( time() / 300 ) * 300;

	// Calculate the timestamp for 30 days from now (30 days * 24 hours * 60 minutes * 60 seconds)
	$new_timestamp = $current_timestamp + ( $expiration_length * 24 * 60 * 60 );

	return $new_timestamp;
}


/**
 * Add action to expire collections
 *
 * @since 2.0.0
 */
add_action( 'picu_collection_checker', 'picu_expire_collections' );


/**
 * Adds a custom cron schedule for every 5 minutes.
 *
 * @since 2.0.0
 *
 * @param array $schedules An array of non-default cron schedules.
 * @return array Filtered array of non-default cron schedules.
 */
function picu_add_custom_cron_schedule( $schedules ) {
	$schedules[ 'every-5-minutes' ] = [
		'interval' => 300,
		'display' => __( 'Every 5 minutes', 'picu' )
	];
	return $schedules;
}

add_filter( 'cron_schedules', 'picu_add_custom_cron_schedule' );


/**
 * Schedule collection checker.
 *
 * @since 2.0.0
 */
if ( ! wp_next_scheduled( 'picu_collection_checker' ) ) {
	$now = time();
	$next = ceil( $now / 300 ) * 300; // We want exactly every five minutes. :)
	wp_schedule_event( $next, 'every-5-minutes', 'picu_collection_checker' );
}


/**
 * Expire collections.
 *
 * @since 2.0.0
 */
function picu_expire_collections() {
	// Get sent collections, where the expiration time is in the past
	$args = [
		'post_type' => 'picu_collection',
		'post_status' => [ 'sent' ],
		'posts_per_page' => -1,
		'fields' => 'ids',
		'meta_query' => [
			'expiration' => [
				'key' => '_picu_collection_expiration_time',
				'value' => time(),
				'compare' => '<',
			],
		],
	];

	$collections = get_posts( $args );

	if ( ! empty( $collections ) ) {
		foreach( $collections as $collection_id ) {
			// Set the status to expired
			picu_update_post_status( $collection_id, 'expired' );
			// Update collection history
			picu_update_collection_history( $collection_id, 'expired' );
			// Run action after a collection has expired
			do_action( 'picu_collection_has_expired', $collection_id );
		}
	}
}


/**
 * Maybe set individual clients to failed when collection expires.
 *
 * @since 2.2.0
 *
 * @param int $post_id The collection post ID
 * @param bool $preview Wether the function is used for preview
 */
function picu_collection_maybe_fail_clients( $collection_id ) {
	$clients = get_post_meta( $collection_id, '_picu_collection_hashes', true );
	foreach( $clients as $hash => $client ) {
		if ( $clients[$hash]['status'] == 'sent' ) {
			$clients[$hash]['status'] = 'failed';
		}
	}

	update_post_meta( $collection_id, '_picu_collection_hashes', $clients );
}

add_action( 'picu_collection_has_expired', 'picu_collection_maybe_fail_clients' );
add_action( 'picu_collection_has_closed', 'picu_collection_maybe_fail_clients' );

/**
 * Add action to send reminders for collections with open/unapproved selections
 *
 * @since 2.0.0
 */
add_action( 'picu_collection_checker', 'picu_maybe_send_selection_reminder' );


/**
 * Decide whether to send a selection reminder.
 * 
 * @since 2.0.0
 */
function picu_maybe_send_selection_reminder() {
	if ( get_option( 'picu_send_reminder' ) != 'on' ) {
		return;
	}

	$args = [
		'post_type' => 'picu_collection',
		'post_status' => [ 'sent' ],
		'posts_per_page' => -1,
		'fields' => 'ids',
		// Check for both keys, to make sure the collection has recipients
		'meta_query' => [
			'has_selection' => [
				'compare_key' => 'LIKE',
				'key' => '_picu_collection_selection_',
			],
			'has_hashes' => [
				'key' => '_picu_collection_hashes',
				'compare' => 'EXISTS'
			]
		]
	];

	$collections = get_posts( $args );

	if ( ! empty( $collections ) ) {
		foreach( $collections as $collection_id ) {
			$recipients = get_post_meta( $collection_id, '_picu_collection_hashes', true );
			foreach( $recipients as $recipient_id => $recipient_data ) {
				// Get selection data for recipient
				$selection_data = get_post_meta( $collection_id, '_picu_collection_selection_' . $recipient_id, true );

				// Check if the recipient has any selection data and not yet approved the collection
				if ( ! empty( $selection_data ) && picu_get_status_from_ident( $collection_id, $recipient_id ) == 'sent' ) {
					// Skip, if a reminder has been sent before
					$reminder = get_post_meta( $collection_id, '_picu_collection_reminder_' . $recipient_id, true );
					if ( ! empty( $reminder ) ) {
						continue;
					}

					// Get time of the last selection update
					$time_difference = apply_filters( 'picu_selection_reminder_time_diff', 86400 );
					// Check if the selection update is more than the time difference
					if ( $selection_data['time'] < time() - $time_difference ) {
						// Send reminder
						do_action( 'picu_send_selection_reminder', $collection_id, $recipient_id, $recipient_data['email'] );
						// Set reminder post meta
						update_post_meta( $collection_id, '_picu_collection_reminder_' . $recipient_id, time() );
					}
				}
			}
		}
	}
}


/**
 * Combine client name and email address.
 *
 * @since 2.3.0
 *
 * @param string $name The client's name
 * @param string $email The client's email
 * @return string The nicely formatted name and email
 */
function picu_combine_name_email( $name, $email ) {
	$output = '';

	if ( ! empty( $name ) ) {
		$output .= $name;
	}
	
	if ( ! empty( $email ) ) {
		if ( ! empty( $name ) ) {
			$output .= ' ' . sprintf( __( '(%s)', 'picu' ), $email );
		}
		else {
			$output .= $email;
		}
	}

	return $output;
}


/**
 * Return client name initials.
 *
 * @since 2.3.0
 *
 * @param string $name The client's name
 * @return string The initials in uppercase
 */
function picu_get_client_initials( $name ) {
	$initials = substr( $name, 0, 2 );
	$initials = strtoupper( $initials );

	return $initials;
}


/**
 * Add new client to hashes.
 *
 * @since 2.3.0
 *
 * @param int $collection_id The collection post ID
 * @param string $name The client name
 * @param string $email The client email
 * @param array $args Additional data to save for the client
 * @return bool|string False or the new client's ident
 */
function picu_add_client_to_hashes( $collection_id, $name = '', $email = '', $args = [] ) {
	// We need at least either name or email
	if ( empty( $name ) && empty( $email ) ) {
		return false;
	}

	// Get Existing hashes
	$collection_hashes = get_post_meta( $collection_id, '_picu_collection_hashes', true );
	if ( empty( $collection_hashes ) ) {
		$collection_hashes = [];
	}

	// Add new client
	$hash = substr( md5( rand() ), 0, 10 );
	$collection_hashes[$hash] = [
		'name' => $name,
		'email' => sanitize_email( $email ),
		'status' => 'sent',
		'time' => time(),
	];

	// Add additional data, eg. 'time' or 'status'
	if ( ! empty( $args ) ) {
		foreach( $args as $key => $arg ) {
			$collection_hashes[$hash][$key] = $arg;
		}
	}

	// Update meta
	update_post_meta( $collection_id, '_picu_collection_hashes', $collection_hashes );

	return $hash;
}


/**
 * Update client selections, when collection images change.
 *
 * @since 2.3.0
 *
 * @param int $collection_id The collection post ID
 * @param string $gallery_ids The comma separated image IDs
 */
function picu_update_client_selections( $collection_id, $gallery_ids ) {
	// Get clients
	$picu_collection_hashes = get_post_meta( $collection_id, '_picu_collection_hashes', true );

	if ( empty( $picu_collection_hashes ) ) {
		return;
	}
	
	$gallery_ids = explode( ',', $gallery_ids );

	// Iterate through clients
	foreach( $picu_collection_hashes as $ident => $hash ) {
		$selection = get_post_meta( $collection_id, '_picu_collection_selection_' . $ident, true );
		if ( empty( $selection['selection'] ) ) {
			continue;
		}

		foreach( $selection['selection'] as $image_id ) {
			// Delete selection, if the image is no longer there
			if ( ! in_array( $image_id, $gallery_ids ) ) {
				if ( ( $k = array_search( $image_id, $selection['selection'] ) ) !== false ) {
					unset( $selection['selection'][$k] );
				}

				// Delete markers for non-existing images
				if ( isset( $selection['markers']['id_' . $image_id] ) ) {
					unset( $selection['markers']['id_' . $image_id] );
				}
			}
		}

		update_post_meta( $collection_id, '_picu_collection_selection_' . $ident, $selection );
	}
}


/**
 * Return the default client name.
 *
 * @since 2.3.0
 *
 * @return string The default client name.
 */
function picu_get_default_client_name() {
	$name = apply_filters( 'picu_default_client_name', __( 'Client', 'picu' ) );

	return $name;
}


/**
 * Get collection link.
 *
 * @since 2.4.0
 * 
 * @param string $ident The ident parameter
 * @param int $collection_id The collection post ID
 * @return string The collection link
 */
function picu_get_collection_link( $ident = '', $collection_id = null ) {
	return ( ! empty( $ident ) ) ? esc_url( add_query_arg( 'ident', $ident, get_the_permalink( $collection_id ) ) ) : get_the_permalink( $collection_id );
}


/**
 * Return a list of countries.
 *
 * @since 3.0.0
 *
 * @return array The list of countries.
 */
function picu_get_countries() {
	$countries =[
		'af' => __( 'Afghanistan', 'picu' ),
		'ax' => __( 'Åland Islands', 'picu' ),
		'al' => __( 'Albania', 'picu' ),
		'dz' => __( 'Algeria', 'picu' ),
		'as' => __( 'American Samoa', 'picu' ),
		'ad' => __( 'Andorra', 'picu' ),
		'ao' => __( 'Angola', 'picu' ),
		'ai' => __( 'Anguilla', 'picu' ),
		'aq' => __( 'Antarctica', 'picu' ),
		'ag' => __( 'Antigua and Barbuda', 'picu' ),
		'ar' => __( 'Argentina', 'picu' ),
		'am' => __( 'Armenia', 'picu' ),
		'aw' => __( 'Aruba', 'picu' ),
		'au' => __( 'Australia', 'picu' ),
		'at' => __( 'Austria', 'picu' ),
		'az' => __( 'Azerbaijan', 'picu' ),
		'bs' => __( 'Bahamas', 'picu' ),
		'bh' => __( 'Bahrain', 'picu' ),
		'bd' => __( 'Bangladesh', 'picu' ),
		'bb' => __( 'Barbados', 'picu' ),
		'by' => __( 'Belarus', 'picu' ),
		'be' => __( 'Belgium', 'picu' ),
		'bz' => __( 'Belize', 'picu' ),
		'bj' => __( 'Benin', 'picu' ),
		'bm' => __( 'Bermuda', 'picu' ),
		'bt' => __( 'Bhutan', 'picu' ),
		'bo' => __( 'Bolivia', 'picu' ),
		'bq' => __( 'Bonaire, Saint Eustatius and Saba', 'picu' ),
		'ba' => __( 'Bosnia and Herzegovina', 'picu' ),
		'bw' => __( 'Botswana', 'picu' ),
		'bv' => __( 'Bouvet Island', 'picu' ),
		'br' => __( 'Brazil', 'picu' ),
		'io' => __( 'British Indian Ocean Territory', 'picu' ),
		'bn' => __( 'Brunei', 'picu' ),
		'bg' => __( 'Bulgaria', 'picu' ),
		'bf' => __( 'Burkina Faso', 'picu' ),
		'bi' => __( 'Burundi', 'picu' ),
		'kh' => __( 'Cambodia', 'picu' ),
		'cm' => __( 'Cameroon', 'picu' ),
		'ca' => __( 'Canada', 'picu' ),
		'cv' => __( 'Cape Verde', 'picu' ),
		'ky' => __( 'Cayman Islands', 'picu' ),
		'cf' => __( 'Central African Republic', 'picu' ),
		'td' => __( 'Chad', 'picu' ),
		'cl' => __( 'Chile', 'picu' ),
		'cn' => __( 'China', 'picu' ),
		'cx' => __( 'Christmas Island', 'picu' ),
		'cc' => __( 'Cocos (Keeling) Islands', 'picu' ),
		'co' => __( 'Colombia', 'picu' ),
		'km' => __( 'Comoros', 'picu' ),
		'cg' => __( 'Congo (Brazzaville)', 'picu' ),
		'cd' => __( 'Congo (Kinshasa)', 'picu' ),
		'ck' => __( 'Cook Islands', 'picu' ),
		'cr' => __( 'Costa Rica', 'picu' ),
		'hr' => __( 'Croatia', 'picu' ),
		'cu' => __( 'Cuba', 'picu' ),
		'cw' => __( 'Cura&ccedil;ao', 'picu' ),
		'cy' => __( 'Cyprus', 'picu' ),
		'cz' => __( 'Czech Republic', 'picu' ),
		'dk' => __( 'Denmark', 'picu' ),
		'dj' => __( 'Djibouti', 'picu' ),
		'dm' => __( 'Dominica', 'picu' ),
		'do' => __( 'Dominican Republic', 'picu' ),
		'ec' => __( 'Ecuador', 'picu' ),
		'eg' => __( 'Egypt', 'picu' ),
		'sv' => __( 'El Salvador', 'picu' ),
		'gq' => __( 'Equatorial Guinea', 'picu' ),
		'er' => __( 'Eritrea', 'picu' ),
		'ee' => __( 'Estonia', 'picu' ),
		'sz' => __( 'Eswatini', 'picu' ),
		'et' => __( 'Ethiopia', 'picu' ),
		'fk' => __( 'Falkland Islands', 'picu' ),
		'fo' => __( 'Faroe Islands', 'picu' ),
		'fj' => __( 'Fiji', 'picu' ),
		'fi' => __( 'Finland', 'picu' ),
		'fr' => __( 'France', 'picu' ),
		'gf' => __( 'French Guiana', 'picu' ),
		'pf' => __( 'French Polynesia', 'picu' ),
		'tf' => __( 'French Southern Territories', 'picu' ),
		'ga' => __( 'Gabon', 'picu' ),
		'gm' => __( 'Gambia', 'picu' ),
		'ge' => __( 'Georgia', 'picu' ),
		'de' => __( 'Germany', 'picu' ),
		'gh' => __( 'Ghana', 'picu' ),
		'gi' => __( 'Gibraltar', 'picu' ),
		'gr' => __( 'Greece', 'picu' ),
		'gl' => __( 'Greenland', 'picu' ),
		'gd' => __( 'Grenada', 'picu' ),
		'gp' => __( 'Guadeloupe', 'picu' ),
		'gu' => __( 'Guam', 'picu' ),
		'gt' => __( 'Guatemala', 'picu' ),
		'gg' => __( 'Guernsey', 'picu' ),
		'gn' => __( 'Guinea', 'picu' ),
		'gw' => __( 'Guinea-Bissau', 'picu' ),
		'gy' => __( 'Guyana', 'picu' ),
		'ht' => __( 'Haiti', 'picu' ),
		'hm' => __( 'Heard Island and McDonald Islands', 'picu' ),
		'hn' => __( 'Honduras', 'picu' ),
		'hk' => __( 'Hong Kong', 'picu' ),
		'hu' => __( 'Hungary', 'picu' ),
		'is' => __( 'Iceland', 'picu' ),
		'in' => __( 'India', 'picu' ),
		'id' => __( 'Indonesia', 'picu' ),
		'ir' => __( 'Iran', 'picu' ),
		'iq' => __( 'Iraq', 'picu' ),
		'ie' => __( 'Ireland', 'picu' ),
		'im' => __( 'Isle of Man', 'picu' ),
		'il' => __( 'Israel', 'picu' ),
		'it' => __( 'Italy', 'picu' ),
		'ci' => __( 'Ivory Coast', 'picu' ),
		'jm' => __( 'Jamaica', 'picu' ),
		'jp' => __( 'Japan', 'picu' ),
		'je' => __( 'Jersey', 'picu' ),
		'jo' => __( 'Jordan', 'picu' ),
		'kz' => __( 'Kazakhstan', 'picu' ),
		'ke' => __( 'Kenya', 'picu' ),
		'ki' => __( 'Kiribati', 'picu' ),
		'kw' => __( 'Kuwait', 'picu' ),
		'kg' => __( 'Kyrgyzstan', 'picu' ),
		'la' => __( 'Laos', 'picu' ),
		'lv' => __( 'Latvia', 'picu' ),
		'lb' => __( 'Lebanon', 'picu' ),
		'ls' => __( 'Lesotho', 'picu' ),
		'lr' => __( 'Liberia', 'picu' ),
		'ly' => __( 'Libya', 'picu' ),
		'li' => __( 'Liechtenstein', 'picu' ),
		'lt' => __( 'Lithuania', 'picu' ),
		'lu' => __( 'Luxembourg', 'picu' ),
		'mo' => __( 'Macao', 'picu' ),
		'mg' => __( 'Madagascar', 'picu' ),
		'mw' => __( 'Malawi', 'picu' ),
		'my' => __( 'Malaysia', 'picu' ),
		'mv' => __( 'Maldives', 'picu' ),
		'ml' => __( 'Mali', 'picu' ),
		'mt' => __( 'Malta', 'picu' ),
		'mh' => __( 'Marshall Islands', 'picu' ),
		'mq' => __( 'Martinique', 'picu' ),
		'mr' => __( 'Mauritania', 'picu' ),
		'mu' => __( 'Mauritius', 'picu' ),
		'yt' => __( 'Mayotte', 'picu' ),
		'mx' => __( 'Mexico', 'picu' ),
		'fm' => __( 'Micronesia', 'picu' ),
		'md' => __( 'Moldova', 'picu' ),
		'mc' => __( 'Monaco', 'picu' ),
		'mn' => __( 'Mongolia', 'picu' ),
		'me' => __( 'Montenegro', 'picu' ),
		'ms' => __( 'Montserrat', 'picu' ),
		'ma' => __( 'Morocco', 'picu' ),
		'mz' => __( 'Mozambique', 'picu' ),
		'mm' => __( 'Myanmar', 'picu' ),
		'na' => __( 'Namibia', 'picu' ),
		'nr' => __( 'Nauru', 'picu' ),
		'np' => __( 'Nepal', 'picu' ),
		'nl' => __( 'Netherlands', 'picu' ),
		'nc' => __( 'New Caledonia', 'picu' ),
		'nz' => __( 'New Zealand', 'picu' ),
		'ni' => __( 'Nicaragua', 'picu' ),
		'ne' => __( 'Niger', 'picu' ),
		'ng' => __( 'Nigeria', 'picu' ),
		'nu' => __( 'Niue', 'picu' ),
		'nf' => __( 'Norfolk Island', 'picu' ),
		'mk' => __( 'North Macedonia', 'picu' ),
		'mp' => __( 'Northern Mariana Islands', 'picu' ),
		'kp' => __( 'North Korea', 'picu' ),
		'no' => __( 'Norway', 'picu' ),
		'om' => __( 'Oman', 'picu' ),
		'pk' => __( 'Pakistan', 'picu' ),
		'ps' => __( 'Palestinian Territory', 'picu' ),
		'pa' => __( 'Panama', 'picu' ),
		'pg' => __( 'Papua New Guinea', 'picu' ),
		'py' => __( 'Paraguay', 'picu' ),
		'pe' => __( 'Peru', 'picu' ),
		'ph' => __( 'Philippines', 'picu' ),
		'pn' => __( 'Pitcairn', 'picu' ),
		'pl' => __( 'Poland', 'picu' ),
		'pt' => __( 'Portugal', 'picu' ),
		'pr' => __( 'Puerto Rico', 'picu' ),
		'qa' => __( 'Qatar', 'picu' ),
		're' => __( 'Reunion', 'picu' ),
		'ro' => __( 'Romania', 'picu' ),
		'ru' => __( 'Russia', 'picu' ),
		'rw' => __( 'Rwanda', 'picu' ),
		'bl' => __( 'Saint Barth&eacute;lemy', 'picu' ),
		'sh' => __( 'Saint Helena', 'picu' ),
		'kn' => __( 'Saint Kitts and Nevis', 'picu' ),
		'lc' => __( 'Saint Lucia', 'picu' ),
		'mf' => __( 'Saint Martin (French part)', 'picu' ),
		'sx' => __( 'Saint Martin (Dutch part)', 'picu' ),
		'pm' => __( 'Saint Pierre and Miquelon', 'picu' ),
		'vc' => __( 'Saint Vincent and the Grenadines', 'picu' ),
		'ws' => __( 'Samoa', 'picu' ),
		'sm' => __( 'San Marino', 'picu' ),
		'st' => __( 'S&atilde;o Tom&eacute; and Pr&iacute;ncipe', 'picu' ),
		'sa' => __( 'Saudi Arabia', 'picu' ),
		'sn' => __( 'Senegal', 'picu' ),
		'rs' => __( 'Serbia', 'picu' ),
		'sc' => __( 'Seychelles', 'picu' ),
		'sl' => __( 'Sierra Leone', 'picu' ),
		'sg' => __( 'Singapore', 'picu' ),
		'sk' => __( 'Slovakia', 'picu' ),
		'si' => __( 'Slovenia', 'picu' ),
		'sb' => __( 'Solomon Islands', 'picu' ),
		'so' => __( 'Somalia', 'picu' ),
		'za' => __( 'South Africa', 'picu' ),
		'gs' => __( 'South Georgia/Sandwich Islands', 'picu' ),
		'kr' => __( 'South Korea', 'picu' ),
		'ss' => __( 'South Sudan', 'picu' ),
		'es' => __( 'Spain', 'picu' ),
		'lk' => __( 'Sri Lanka', 'picu' ),
		'sd' => __( 'Sudan', 'picu' ),
		'sr' => __( 'Suriname', 'picu' ),
		'sj' => __( 'Svalbard and Jan Mayen', 'picu' ),
		'se' => __( 'Sweden', 'picu' ),
		'ch' => __( 'Switzerland', 'picu' ),
		'sy' => __( 'Syria', 'picu' ),
		'tw' => __( 'Taiwan', 'picu' ),
		'tj' => __( 'Tajikistan', 'picu' ),
		'tz' => __( 'Tanzania', 'picu' ),
		'th' => __( 'Thailand', 'picu' ),
		'tl' => __( 'Timor-Leste', 'picu' ),
		'tg' => __( 'Togo', 'picu' ),
		'tk' => __( 'Tokelau', 'picu' ),
		'to' => __( 'Tonga', 'picu' ),
		'tt' => __( 'Trinidad and Tobago', 'picu' ),
		'tn' => __( 'Tunisia', 'picu' ),
		'tr' => __( 'Turkey', 'picu' ),
		'tm' => __( 'Turkmenistan', 'picu' ),
		'tc' => __( 'Turks and Caicos Islands', 'picu' ),
		'tv' => __( 'Tuvalu', 'picu' ),
		'ug' => __( 'Uganda', 'picu' ),
		'ua' => __( 'Ukraine', 'picu' ),
		'ae' => __( 'United Arab Emirates', 'picu' ),
		'gb' => __( 'United Kingdom (UK)', 'picu' ),
		'us' => __( 'United States (US)', 'picu' ),
		'um' => __( 'United States (US) Minor Outlying Islands', 'picu' ),
		'uy' => __( 'Uruguay', 'picu' ),
		'uz' => __( 'Uzbekistan', 'picu' ),
		'vu' => __( 'Vanuatu', 'picu' ),
		'va' => __( 'Vatican', 'picu' ),
		've' => __( 'Venezuela', 'picu' ),
		'vn' => __( 'Vietnam', 'picu' ),
		'vg' => __( 'Virgin Islands (British)', 'picu' ),
		'vi' => __( 'Virgin Islands (US)', 'picu' ),
		'wf' => __( 'Wallis and Futuna', 'picu' ),
		'eh' => __( 'Western Sahara', 'picu' ),
		'ye' => __( 'Yemen', 'picu' ),
		'zm' => __( 'Zambia', 'picu' ),
		'zw' => __( 'Zimbabwe', 'picu' )
	];

	natsort( $countries );

	$countries = apply_filters( 'picu_countries', $countries );

	return $countries;
}


/**
 * Get country name by country code.
 *
 * @since 3.0.0
 *
 * @param string $code The country code.
 * @return string The country name or an empty string.
 */
function picu_get_country_name( $code ) {
	$country = '';
	$countries = picu_get_countries();
	if ( ! empty( $countries[$code] ) ) {
		$country = $countries[$code];
	}

	$country = apply_filters( 'picu_country', $country, $code );

	return $country;
}


/**
 * Render country select dropdown.
 *
 * @since 3.0.0
 *
 * @param string $selected Currently selected country code.
 * @param array $args Additional arguments (name, id, class).
 * @return string The country select menu HTML.
 */
function picu_country_select( $selected = '', $args = [] ) {
	$defaults = [
		'name' => 'country',
		'id' => 'country',
		'class' => 'picu-country-select',
	];

	$args = wp_parse_args( $args, $defaults );

	$output = sprintf(
		'<select name="%s" id="%s" class="%s">',
		esc_attr( $args['name'] ),
		esc_attr( $args['id'] ),
		esc_attr( $args['class'] )
	);

	$output .= sprintf(
		'<option value="">%s</option>',
		esc_html__( 'Select a country', 'picu' )
	);

	foreach ( picu_get_countries() as $code => $name ) {
		$output .= sprintf(
			'<option value="%s" %s>%s</option>',
			esc_attr( $code ),
			selected( $selected, $code, false ),
			esc_html( $name )
		);
	}

	$output .= '</select>';

	return $output;
}
