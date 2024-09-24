<?php
/**
 * Email Functions
 *
 * @since 1.7.0
 */

defined( 'ABSPATH' ) || exit;


/**
 * Messaging Logic
 *
 * Handles which messages and notifications get sent,
 * when a picu collection is saved
 *
 * @param int $post_id The collection id
 * @param object $post The post object
 * @since 1.7.0
 */
function picu_messaging_logic( $post_id, $post ) {
	
	// Check if intent is sending an email (button is clicked)
	if ( ! isset( $_POST['picu_sendmail'] ) )
		return $post_id;
	
	// Check if share method is set to "send-mail"
	if ( 'picu-send-email' != get_post_meta( $post_id, '_picu_collection_share_method', true ) )
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
		
	// Abort if autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		return $post_id;

	// Abort if the user doesn't have permissions
	if ( ! current_user_can( 'edit_post', $post_id ) )
		return $post_id;

	// Abort if there are no proof images, but the intent is proofing
	if ( isset( $_POST['picu_gallery_ids'] ) AND empty( $_POST['picu_gallery_ids'] ) ) {
		return $post_id;
	}
	
	// Abort if there are no delivery images, but the intent is delivery – and the delivery option is upload
	if ( isset( $_POST['delivery_image_ids'] ) AND empty( $_POST['delivery_image_ids'] ) AND $_POST['picu_delivery_option'] == 'upload' ) {
		return $post_id;
	}

	// Abort sending if there are error notifications
	$notifications = get_option( '_' . get_current_user_id() . '_picu_notifications' );

	if ( is_array( $notifications ) ) {
		foreach( $notifications as $notification ) {
			if ( strpos( $notification['type'], 'error' ) )  {
				return $post_id;
			}
		}
	}
	
	// Do stuff depending on the post status
	switch ( $post->post_status ) {
		case 'draft':
			picu_mail_proofing( $post_id, $post );
			break;
		case 'sent':
			break;
		case 'approved':
			// do stuff ?
			break;
		case 'delivery-draft':
			picu_mail_delivery( $post_id, $post );
			break;
		case 'delivered':
			// do stuff
			break;
		default:
			// do default stuff
	}
}

add_action( 'save_post_picu_collection', 'picu_messaging_logic', 10, 2 );


/**
 * Wrapper function for sending a new collection to client(s)
 *
 * @since 1.7.0
 *
 * @param int $post_id The collection id
 * @param object $post The post object
 * @param bool $preview Wether the function should used for preview
 */
function picu_mail_proofing( $post_id, $post, $preview = false ) {
	// Setup error tracking
	$send_error = false;

	if ( picu_is_multi_collection( $post_id ) ) {
		$collection_hashes = get_post_meta( $post_id, '_picu_collection_hashes', true );

		foreach ( $collection_hashes as $hash => $hash_fields ) {
			$to_address = $hash_fields['email'];
			if ( !empty( $hash_fields['email'] ) ) {
				$sent = picu_send_proofing_email( $to_address, $post, $hash, $preview );
			}

			if ( $preview === true ) {
				return $sent;
			}

			if ( $sent !== true ) {
				$send_error = true;
			}
		}
	}
	else {
		$to_address = sanitize_email( $_POST['picu_collection_email_address'] );
		$sent = picu_send_proofing_email( $to_address, $post, '', $preview );

		if ( $preview === true ) {
			return $sent;
		}

		if ( $sent !== true ) {
			$send_error = true;
		}
	}

	if ( $send_error === true ) {
		// If an error occured, save that in the collection history
		picu_update_collection_history( $post_id, 'error-sending-email', $to_address );

		// Add error notification
		/* translators: Admin (error) notification */
		picu_add_notification( 'picu_mail_error', 'notice notice-error is-dismissible', __( 'There was an error sending the email.', 'picu' ) );
		return false;
	}

	picu_update_post_status( $post_id, 'sent' );
	picu_update_collection_history( $post_id, 'sent', picu_get_collection_emails( $post_id ) );

	/* translators: Admin (success) notification */
	picu_add_notification( 'picu_mail_sent', 'notice notice-success is-dismissible', __( 'The collection was sent to the client.', 'picu' ) );

	return true;
}


/**
 * Send a new collection email
 *
 * @since 2.2.0
 *
 * @param string $to_address The recipient email address
 * @param object $post The collection post object
 * @param string $ident The ident parameter
 * @param bool $preview Wether the function should used for preview
 * @return bool|string Whether sending was successful or not, or the email preview
 */
function picu_send_proofing_email( $to_address, $post, $ident = '', $preview = false ) {
	$mail = new Picu_Emails( $post->ID );
	$args = [];

	$args['to_address'] = $to_address;

	// Set context
	$args['mail_context'] = 'client_collection_new';

	// Add text part
	$args['mail_parts'] = [
		[
			'type' => 'text',
			'text' => $mail->text_to_html( get_post_meta( $post->ID, '_picu_collection_description', true ) )
		]
	];

	// Maybe include expiration part
	$args = picu_maybe_include_expiration_info( $args, $post );

	// Maybe include password part
	$args = picu_maybe_include_password( $args, $post );

	// Add collection link
	$args['mail_parts']['collection_link'] = [
		'type' => 'button',
		'text' => __( 'View Images', 'picu' ),
		'url' => ( ! empty( $ident ) ) ? esc_url( add_query_arg( 'ident', $ident, get_draft_permalink( $post->ID ) ) ) : esc_url( get_draft_permalink( $post->ID ) )
	];

	// Maybe add ident
	if ( ! empty( $ident ) ) {
		$args['ident'] = $ident;
	}

	$mail->setArgs( $args );

	if ( $preview === true ) {
		return $mail->build( $args );
	}

	if ( $mail->send() !== true ) {
		return false;
	}

	return true;
}


/**
 * Email delivery collection to client(s)
 *
 * @param int $post_id The collection id
 * @param object $post The post object
 * @param bool $preview Wether the function should used for preview
 * @since 1.7.0
 */
function picu_mail_delivery( $post_id, $post, $preview = false ) {
	
	if ( empty( $_POST['picu_delivery_email_address'] ) && $preview === false )
		return $post_id;

	if ( empty( $_POST['picu_delivery_description'] ) && $preview === false )
		return $post_id;

	$email_address = $_POST['picu_delivery_email_address'];

	$mail = new Picu_Emails( $post_id );

	$args = [];

	// Set context
	$args['mail_context'] = 'client_delivery_new';

	// Add text part
	$args['mail_parts']['message'] = [
		'type' => 'text',
		'text' => $mail->text_to_html( get_post_meta( $post_id, '_picu_delivery_description', true ) )
	];

	// Maybe include password
	$args = picu_maybe_include_password( $args, $post );

	// Add collection link
	$args['mail_parts']['button'] = [
		'type' => 'button',
		/* translators: Button text, used in an email */
		'text' => __( 'View Images', 'picu' ),
		'url' => esc_url( get_draft_permalink( $post_id ) )
	];

	// Setup error tracking
	$send_error = false;

	// Check if there are multiple mail addresses
	if ( strpos( $email_address, ', ' ) ) {
		$email_addresses = explode( ', ', $email_address );
		$email_addresses = array_filter( $email_addresses, 'sanitize_email' );
		foreach ( $email_addresses as $to_address ) {
			$args['to_address'] = $to_address;

			$mail->setArgs( $args );

			if ( $preview === true ) {
				return $mail->build( $args );
			}

			$mail->send();
		}
	}
	else {
		$args['to_address'] = sanitize_email( $email_address );

		$mail->setArgs( $args );

		if ( $preview === true ) {
			return $mail->build( $args );
		}

		$mail->send();
	}

	if ( $send_error === true ) {
		// If an error occured, save that in the collection history
		picu_update_collection_history( $post_id, 'error-sending-delivery-email', $email_address );

		// Add error notification
		/* translators: Admin (error) notification */
		picu_add_notification( 'picu_mail_error', 'notice notice-error is-dismissible', __( 'There was an error sending the delivery.', 'picu' ) );
		return false;
	}

	picu_update_post_status( $post_id, 'delivered' );
	picu_update_collection_history( $post_id, 'delivered', $args['to_address'] );

	/* translators: Admin (success) notification */
	picu_add_notification( 'picu_mail_sent', 'notice notice-success is-dismissible', __( 'The delivery collection was sent to the client.', 'picu' ) );

	return true;
}


/**
 * Send approval notification to the photographer
 *
 * @param int $post_id The collection id
 * @param string $ident The identifier for the client
 * @param array $args The arguments that make the email
 * @param bool $preview Wether the function is used for preview
 * @since 1.7.0
*/
function picu_mail_approval( $post_id, $ident, $args = [], $preview = false ) {
	$post = get_post( $post_id );
	$blog_url = parse_url( get_bloginfo( 'url' ) );
	$blog_url = $blog_url['host'];

	// Set context
	$args['mail_context'] = 'photographer_collection_approved';

	// Set ident
	if ( ! empty( $ident ) ) {
		$args['ident'] = $ident;
	}

	// Send mail to the collection author
	$to_address = get_the_author_meta( 'user_email', $post->post_author );

	// Override to_address by picu settings, possibly
	if ( ! empty( get_option( 'picu_notification_email' ) ) ) {
		$to_address = sanitize_email( get_option( 'picu_notification_email' ) );
	}
	// Possibility to change the email to_address
	$to_address = apply_filters( 'picu_approval_mail_recipient', $to_address, $post_id );
	
	/* translators: Email subject */
	$args['subject'] = sprintf( __( 'Collection "%s" approved' , 'picu' ), sanitize_text_field( $post->post_title ) );
	$args['subject'] = apply_filters( 'picu_approval_mail_subject', $args['subject'] );

	// Attach proof file
	$proof_file = picu_create_proof_file( $post_id, true );
	$attachments = [ $proof_file ];

	$defaults = [
		'to_address' => $to_address,
		'cc_address' => null,
		'subject' => get_the_title( $post_id ),
		'mail_parts' => [],
		'ident' => null,
		'approval_message' => '',
		'attachments' => $attachments,
	];

	$args = wp_parse_args( $args, $defaults );

	$mail = new Picu_Emails( $post_id );

	// Add mail parts
	$args['mail_parts'] = [];

	// Add text part
	$name = picu_get_name_from_ident( $post_id, $ident );
	$email = picu_get_email_from_ident( $post_id, $ident );
	$by = picu_combine_name_email( $name, $email );
	$text = $mail->text_to_html( sprintf( __( "Your collection \"%s\" has been approved by \"%s\".\n\n", 'picu' ), sanitize_text_field( $post->post_title ), $by ) );

	array_push( $args['mail_parts'],
		[
			'type' => 'text',
			/* translators: Text in an email */
			'text' => $text
		]
	);

	// Add approval message, if there is one
	$approval_message = $args['approval_message'];
	if ( ! empty( $approval_message ) ) {
		array_push( $args['mail_parts'],
			[
				'type' => 'text',
				'text' => $mail->text_to_html( sprintf( "\n\n%s\n\n", $approval_message ) )
			]
		);
	}
	unset( $args['approval_message'] );

	// Add link to the collection in the WordPress Admin
	array_push( $args['mail_parts'],
		[
			'type' => 'button',
			/* translators: Button text, used in an email */
			'text' => __( 'View Selection', 'picu' ),
			'url' => admin_url( 'post.php?post=' . $post_id . '&action=edit' ),
		]
	);

	// Set all the arguments
	$mail->setArgs( $args );

	if ( $preview === true ) {
		return $mail->build( $args );
	}

	$mail->send();

	// Delete proof file
	wp_delete_file( $proof_file );

	return true;
}


/**
 * Send email to the photographer after a collection has expired.
 *
 * @since 2.0.0
 *
 * @param int $post_id The collection post ID
 * @param bool $preview Wether the function is used for preview
 */
function picu_mail_expired( $post_id, $preview = false ) {
	$post = get_post( $post_id );
	$blog_url = parse_url( get_bloginfo( 'url' ) );
	$blog_url = $blog_url['host'];

	// Set context
	$args['mail_context'] = 'photographer_collection_expired';

	// Send mail to the collection author
	$to_address = get_the_author_meta( 'user_email', $post->post_author );

	// Override to_address by picu settings, possibly
	if ( ! empty( get_option( 'picu_notification_email' ) ) ) {
		$to_address = sanitize_email( get_option( 'picu_notification_email' ) );
	}
	// Possibility to change the email to_address
	$to_address = apply_filters( 'picu_expiration_mail_recipient', $to_address, $post_id );
	
	/* translators: Email subject */
	$args['subject'] = sprintf( __( 'Collection "%s" expired' , 'picu' ), sanitize_text_field( $post->post_title ) );
	$args['subject'] = apply_filters( 'picu_expiration_mail_subject', $args['subject'] );

	$defaults = [
		'to_address' => $to_address,
		'cc_address' => null,
		'subject' => get_the_title( $post_id ),
		'mail_parts' => []
	];

	$args = wp_parse_args( $args, $defaults );

	$mail = new Picu_Emails( $post_id );

	// Add text part
	array_push( $args['mail_parts'],
		[
			'type' => 'text',
			/* translators: Text in an email, %s = collection title */
			'text' => $mail->text_to_html( sprintf( __( "Your collection \"%s\" has expired.\n\n", 'picu' ), sanitize_text_field( $post->post_title ) ) )
		]
	);

	// Add link to the collection in the WordPress Admin
	array_push( $args['mail_parts'],
		[
			'type' => 'button',
			/* translators: Button text, used in an email */
			'text' => __( 'View Collection', 'picu' ),
			'url' => admin_url( 'post.php?post=' . $post_id . '&action=edit' ),
		]
	);

	// Set all the arguments and send the email
	$mail->setArgs( $args );

	if ( $preview === true ) {
		return $mail->build( $args );
	}

	$mail->send();
}

add_action( 'picu_collection_has_expired', 'picu_mail_expired' );


/**
 * Send selection reminder to the client.
 *
 * @since 2.0.0
 *
 * @param int $post_id The collection post ID
 * @param string $ident Recipient identification parameter
 * @param string $to_address Recipient email address
 */
function picu_mail_reminder_selection( $post_id, $ident, $to_address ) {
	$post = get_post( $post_id );
	// Set context
	$args['mail_context'] = 'client_reminder';

	/* translators: Email subject */
	$args['subject'] = __( 'Please finish your selection' , 'picu' );
	$args['subject'] = apply_filters( 'picu_reminder_mail_subject', $args['subject'] );

	$defaults = [
		'to_address' => $to_address,
		'cc_address' => null,
		'subject' => get_the_title( $post_id ),
		'mail_parts' => [],
		'attachments' => []
	];

	$args = wp_parse_args( $args, $defaults );

	$mail = new Picu_Emails( $post_id );

	// Add text part
	array_push( $args['mail_parts'],
		[
			'type' => 'text',
			/* translators: Text in an email, %s = collection title */
			'text' => $mail->text_to_html( sprintf( __( "You recently started selecting images for the collection \"%s\".\n\nWe just wanted to remind you, that you still have to finally approve your selection.\n\n", 'picu' ), sanitize_text_field( $post->post_title ) ) )
		]
	);

	// Add link to the collection in the WordPress Admin
	array_push( $args['mail_parts'],
		[
			'type' => 'button',
			/* translators: Button text, used in an email */
			'text' => __( 'View Images', 'picu' ),
			'url' => isset( $ident ) ? esc_url( add_query_arg( 'ident', $ident, get_draft_permalink( $post_id ) ) ) : esc_url( get_draft_permalink( $post_id ) )
		]
	);

	// Set all the arguments and send the email
	$mail->setArgs( $args );
	$mail->send();
}

add_action( 'picu_send_selection_reminder', 'picu_mail_reminder_selection', 10, 3 );


/**
 * Maybe send the collection password with the email
 *
 * @param array $args All the arguments that make the email
 * @param object $collection The collection post object
 * @return array $args The maybe altered args array
 * @since 1.7.0
 */
function picu_maybe_include_password( array $args, $collection ) {

	if ( ! apply_filters( 'picu_send_password_in_email', true ) ) {
		return $args;
	}

	if ( get_option( 'picu_send_password' ) == 'on' ) {

		$password = $collection->post_password;

		if ( ! empty( $password ) ) {
			$args['mail_parts']['collection_password'] = [
				'type' => 'password',
				'password' => $password
			];
		}
	}

	return $args;
}


/**
 * Maybe send expiration info with the email.
 *
 * @since 2.0.0
 *
 * @param array $args All the arguments that make the email
 * @param object $collection The collection post object
 * @return array $args The (maybe) altered args array
 */
function picu_maybe_include_expiration_info( $args, $collection ) {
	if ( get_post_meta( $collection->ID, '_picu_collection_expiration', true ) != 'on' ) {
		return $args;
	}

	$expiration_time = get_post_meta( $collection->ID, '_picu_collection_expiration_time', true );

	if ( ! empty( $expiration_time ) ) {
		$expiration_time = wp_date( get_option( 'date_format' ) . ', ' . get_option( 'time_format' ), $expiration_time );

		$args['mail_parts']['expiration_notice'] = [
			'type' => 'text',
			'text' => sprintf( __( '<em>Please Note:</em> This collection will expire on %s and you won\'t be able to make changes after that.', 'picu' ), $expiration_time ),
			'class' => 'additional-info'
		];
	}

	return $args;
}