<?php
/**
 * picu front end template
 *
 * @since 0.3.0
 */
defined( 'ABSPATH' ) || exit;


/**
 * Return our picu template file
 *
 * @since 0.3.0
 * @since 1.7.0 Check if is singular template
 * 
 * @param string $template Path to the template file
 * @return string The filtered template path
 */
function picu_load_template( $template ) {

	if ( ! is_singular() ) {
		return $template;
	}

	global $post;

	if ( isset( $post ) AND $post->post_type == 'picu_collection' ) {
		if ( file_exists( PICU_PATH . 'frontend/picu-app.php' ) ) {
			$template = PICU_PATH . 'frontend/picu-app.php';
		}
	}

	return $template;
}

add_filter( 'template_include', 'picu_load_template', 99 );


/**
 * Returns email template preview
 * 
 * @since 1.7.0
 */

function picu_load_mail_preview( $single_template ) {
	
	global $post;

	if ( ! empty( $post ) ) {
		$post_id = $post->ID;

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $single_template;
		}

		$picu_action = isset( $_GET['picu-action'] ) ? $_GET['picu-action'] : false;
		
		if ( $picu_action && isset( $post ) && 'picu_collection' == $post->post_type ) {

			switch ( $picu_action ) {
				case 'mail-client':
					$single_template = picu_mail_proofing( $post_id, $post, true );
					break;
				case 'mail-approval':
					$single_template = picu_mail_approval( $post_id, '', [], true );
					break;
				case 'mail-expired':
					$single_template = picu_mail_expired( $post_id, true );
					break;
				case 'mail-delivery':
					$single_template = picu_mail_delivery( $post_id, $post, true );
					break;
				default:
					return $single_template;
			}

			echo $single_template;
			exit;
		}
	}

	return $single_template;
}

add_filter( 'template_include', 'picu_load_mail_preview', 99 );


/**
 * Gather picu body classes
 *
 * @since 0.7.3
 */
function get_picu_body_classes() {

	$picu_body_classes = array();

	// Add body class for lazyloading
	// Legacy M&C fix
	if ( defined( 'PICU_MARK_COMMENT_VERSION' ) AND version_compare( PICU_MARK_COMMENT_VERSION, '1.0.2' ) < 0 ) {
 
	} else {
		$picu_body_classes[] = 'lazyloading';
	}

	global $post;

	if ( ! empty( $post->ID ) ) {
		$picu_body_classes[] = 'collection-id-' . $post->ID;
	}

	$ident = '';
	if ( ! empty( $_GET['ident'] ) ) {
		$ident = sanitize_key( $_GET['ident'] );
	}

	$picu_body_classes[] = 'status-' . picu_get_collection_status( $post->ID, $ident );

	/**
	 * Add class, when user is logged in
	 */
	if ( is_user_logged_in() AND current_user_can( picu_capability() ) ) {
		$picu_body_classes[] = 'user-logged-in';
	}

	/**
	 * Add class, when picu admin bar is visible
	 */
	if (  apply_filters( 'picu_show_admin_bar', true ) ) {
		$picu_body_classes[] = 'has-picu-admin-bar';
	}

	/**
	 * Add filter to modify body classes
	 */
	$picu_body_classes = apply_filters( 'picu_body_classes', $picu_body_classes );

	return $picu_body_classes;

}


/**
 * Echo picu body classes
 *
 * @since 0.7.3
 */
function picu_body_classes() {

	$picu_body_classes = get_picu_body_classes();

	if ( count( $picu_body_classes ) > 0 ) {
		echo sprintf( ' class="%s"', implode( ' ', $picu_body_classes ) );
	}

}


/**
 * Retrieves a template part
 *
 * Taken from Easy Digital Downloads, who took it from bbPress
 *
 * @param string $slug
 * @param string $name Optional. Default null
 * @param bool   $load
 * @param array  $args
 * 
 * @return string
 *
 * @since 1.7.0
 *
 */
function picu_get_template_part( $slug, $name, $load, $args ) {
	// Setup possible parts
	$templates = array();
	if ( isset( $name ) ) {
		$templates[] = $slug . '-' . $name . '.php';
	}
	$templates[] = $slug . '.php';

	// Return the part that is found
	return picu_locate_template( $templates, $load, false, $args );
}


/**
 * Retrieve the name of the highest priority template file that exists.
 *
 * @param string|array $template_names Template file(s) to search for, in order.
 * @param bool $load If true the template file will be loaded if it is found.
 * @param bool $require_once Whether to require_once or require. Default true.
 * 
 * @return string The template filename if one is located.
 * 
 * @since 1.7.0
 */
function picu_locate_template( $template_names, $load, $require_once, $args ) {
	// No file found yet
	$located = false;

	// Try to find a template file
	foreach ( (array) $template_names as $template_name ) {

		// Continue if template is empty
		if ( empty( $template_name ) )
			continue;

		// Trim off any slashes from the template name
		$template_name = ltrim( $template_name, '/' );

		// try locating this template file by looping through the template paths
		foreach( picu_get_template_paths() as $template_path ) {
			if ( file_exists( $template_path . $template_name ) ) {
				$located = $template_path . $template_name;
				break;
			}
		}

		if ( $located ) {
			break;
		}
	}

	if ( ( true == $load ) && ! empty( $located ) ) {
		load_template( $located, $require_once, $args );
	}

	return $located;
}


/**
 * Returns a list of paths to check for template locations
 *
 * @return mixed|void
 * 
 * @since 1.7.0
 */
function picu_get_template_paths() {

	$template_dir = 'picu';

	$file_paths = array(
		1 => trailingslashit( get_stylesheet_directory() ) . $template_dir, // Look in the active theme
		10 => trailingslashit( get_template_directory() ) . $template_dir, // Look in a possible parent theme
		100 => PICU_PATH . 'templates/', // Look in our plugin
	);

	// Sort file paths based on priority
	ksort( $file_paths, SORT_NUMERIC );

	return array_map( 'trailingslashit', $file_paths );
}


/**
 * Load backbone templates
 *
 * @since 0.7.2
 */
function picu_load_backbone_templates() {

	$templates = array(
		'collection-info'	=> PICU_PATH . 'frontend/js/templates/picu-collection-info.php',
		'status-bar'		=> PICU_PATH . 'frontend/js/templates/picu-status-bar.php',
		'gallery-item'		=> PICU_PATH . 'frontend/js/templates/picu-gallery-item.php',
		'lightbox'			=> PICU_PATH . 'frontend/js/templates/picu-lightbox.php',
		'send-selection'	=> PICU_PATH . 'frontend/js/templates/picu-send-selection.php',
		'approved'			=> PICU_PATH . 'frontend/js/templates/picu-approved.php',
		'register'			=> PICU_PATH . 'frontend/js/templates/picu-registration.php'
	);

	$templates = apply_filters( 'picu_load_backbone_templates', $templates );

	return $templates;
}


/**
 * Load collections, models & views
 *
 * @since 0.7.2
 */
function picu_load_cmv() {

	$cmv = array(
		'picu-state' =>				PICU_URL . 'frontend/js/models/picu-state.js?v=' . filemtime( PICU_PATH . 'frontend/js/models/picu-state.js' ),
		'collection-info-view' =>	PICU_URL . 'frontend/js/views/collection-info-view.js?v=' . filemtime( PICU_PATH . 'frontend/js/views/collection-info-view.js' ),
		'status-bar-view' =>		PICU_URL . 'frontend/js/views/status-bar-view.js?v=' . filemtime( PICU_PATH . 'frontend/js/views/status-bar-view.js' ),
		'single-image' =>			PICU_URL . 'frontend/js/models/single-image.js?v=' . filemtime( PICU_PATH . 'frontend/js/models/single-image.js' ),
		'picu-collection' =>		PICU_URL . 'frontend/js/collections/picu-collection.js?v=' . filemtime( PICU_PATH . 'frontend/js/collections/picu-collection.js' ),
		'gallery-view' =>			PICU_URL . 'frontend/js/views/gallery-view.js?v=' . filemtime( PICU_PATH . 'frontend/js/views/gallery-view.js' ),
		'single-image-view' =>		PICU_URL . 'frontend/js/views/single-image-view.js?v=' . filemtime( PICU_PATH . 'frontend/js/views/single-image-view.js' ),
		'lightbox-view' =>			PICU_URL . 'frontend/js/views/lightbox-view.js?v=' . filemtime( PICU_PATH . 'frontend/js/views/lightbox-view.js' ),
		'send-selection-view' =>	PICU_URL . 'frontend/js/views/send-selection-view.js?v=' . filemtime( PICU_PATH . 'frontend/js/views/send-selection-view.js' ),
		'approved-view' =>			PICU_URL . 'frontend/js/views/approved-view.js?v=' . filemtime( PICU_PATH . 'frontend/js/views/approved-view.js' ),
		'registration-view' =>		PICU_URL . 'frontend/js/views/registration-view.js?v=' . filemtime( PICU_PATH . 'frontend/js/views/registration-view.js' ),
	);

	$cmv = apply_filters( 'picu_load_cmv', $cmv );

	return $cmv;
}


/**
 * Load front end stylesheet
 *
 * @since 0.7.0
 */
function picu_load_styles() {

	$styles = array(
		'picu' => PICU_URL . 'frontend/css/picu-dark.css',
		'picu-print' => PICU_URL . 'frontend/css/picu-print.css',
	);

	$styles = apply_filters( 'picu_load_styles', $styles );

	$styles_output = '';

	foreach ( $styles as $name => $url ) {
		$styles_output .= "\n\t\t" . '<link href="' . $url . '?ver=' . filemtime( PICU_PATH . 'frontend/css/picu-dark.css' ) . '" rel="stylesheet" media="';
		if ( 'picu-print' == $name ) {
			$styles_output .= 'print';
		}
		else {
			$styles_output .= 'screen';
		}
		$styles_output .= '" />';
	}

	$styles_output = apply_filters( 'picu_styles_output', $styles_output );

	echo $styles_output . "\n";
}


/**
 * Load stylesheet depending on settings.
 *
 * @since 0.7.0
 *
 * @param string $styles The collection styles
 * @param string The filtered styles
 */
function picu_theme_options( $styles ) {
	if ( get_option( 'picu_theme' ) == 'light' ) {
		$styles['picu'] = PICU_URL . 'frontend/css/picu-light.css';
	}

	return $styles;
}

add_filter( 'picu_load_styles', 'picu_theme_options', 10, 1 );



/**
 * Get JSON formated image collection used in the front end
 *
 * @since 1.5.0
 *
 * @param $image_ids – the image ids; $post – the picu collection object
 * @return string, javascript objects, containing the image collection
 *
 */

function picu_get_image_collection( $image_ids, $post = '' ) {

	if ( empty( $post ) ) {
		$post = get_post();
	}

	// Set up attachment requests
	$orderby = 'post__in';
	$order = 'ASC';

	// Load attachments
	$_attachments = get_posts( array( 'include' => $image_ids, 'post_status' => 'any', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby ) );

	$attachments = array();
	foreach ( $_attachments as $key => $val ) {
		$attachments[$val->ID] = $_attachments[$key];
	}

	// If we don't get attachments, this is as far as we go
	if ( empty( $attachments ) ) {
		return '[ ]';
	}

	// Prepare backbone image collection
	$i = 0;
	$imgnum = 1;
	$image_collection = array();
	$orientation = '';

	// Create image objects
	foreach ( $attachments as $id => $attachment ) {

		// Calculate image orientiation
		$image_meta = wp_get_attachment_metadata( $id );

		if ( isset( $image_meta['height'], $image_meta['width'] ) ) {
			$orientation = ( $image_meta['height'] > $image_meta['width'] ) ? 'portrait' : 'landscape';
		}

		// Get image description
		if ( trim( $attachment->post_excerpt ) ) {
			$image_description = htmlspecialchars( $attachment->post_excerpt, ENT_QUOTES, 'UTF-8' );
			$image_description = preg_replace( "/\r|\n|\t/", "", $image_description );
		} else {
			$image_description = '';
		}

		// Get attachment URLs
		$image_name = wp_get_attachment_image_src( $attachment->ID, 'full' );
		$image_path = wp_get_attachment_image_src( $attachment->ID, 'picu-large' );
		$image_path_small = wp_get_attachment_image_src( $attachment->ID, 'picu-small' );

		if ( function_exists( 'wp_get_attachment_image_srcset' ) ) {
			$image_path_srcset = ( wp_get_attachment_image_srcset( $attachment->ID, 'picu-large' ) ) ?: '';
			$image_path_small_srcset = ( wp_get_attachment_image_srcset( $attachment->ID, 'picu-small' ) ) ?: '';
		}

		// Set size attribute, for use with srcset
		$size = apply_filters( 'picu_gallery_item_size_attr', '(min-width: 740px) 468px, 706px' );

		// Set lightbox size attribute; do not upscale the image: set natural image width as max
		if ( isset( $image_meta['width'] ) ) {
			$size_lightbox = $image_meta['width'] . 'px';
		}
		else {
			$size_lightbox = '100vw';
		}

		// Load selection
		$temp = [];
		if ( ! empty( $_GET['ident'] ) ) {
			$hashes = get_post_meta( get_the_ID(), '_picu_collection_hashes', true );

			if ( is_array( $hashes ) AND array_key_exists( $_GET['ident'], $hashes ) ) {
				$temp = get_post_meta( $post->ID, '_picu_collection_selection_' . $_GET['ident'], true );
			}
		}

		if ( isset( $temp['selection'] ) and is_array( $temp['selection'] ) ) {
			$selection = $temp['selection'];
			$selected = ( in_array( $attachment->ID, $selection ) ) ? true : false;
		} else {
			$selected = false;
		}

		// Get markers
		if ( isset( $temp['markers']['id_'.$id] ) AND ! empty( $temp['markers']['id_'.$id] ) ) {

			$markers = array();

			foreach( $temp['markers']['id_'.$id] as $key => $value ) {
				$markers[$key] = $value;
			}

			// See function below
			array_walk_recursive( $markers, 'picu_encode_marker_comment' );
		}
		else {
			$markers = '';
		}

		// Remove parameters from iamge name (eg. when Jetpacks photon is used)
		$image_title = strtok( basename( $image_name[0] ), '?' );

		// Legacy M&C fix for new way of displaying image name
		// Using a regular string for M&C version older than 1.0.2
		if ( defined( 'PICU_MARK_COMMENT_VERSION' ) AND version_compare( PICU_MARK_COMMENT_VERSION, '1.0.2' ) < 0 ) {
			$title = $image_title;
		}
		else {
			$title = array( 'number' => $imgnum, 'filename' => $image_title );
		}

		$current_image = array(
			'number' => $imgnum,
			'imageID' => $attachment->ID,
			'title' => $title,
			'description' => $image_description,
			'imagePath' => $image_path[0],
			'imagePath_small' => $image_path_small[0],
			'imagePath_original' => $image_name[0],
			'imagePath_srcset' => $image_path_srcset,
			'imagePath_small_srcset' => $image_path_small_srcset,
			'size' => $size,
			'size_lightbox' => $size_lightbox,
			'orientation' => $orientation,
			'selected' => $selected,
			'markers' => $markers
		);

		/*
		 * Add filter to modify each individual image
		 */
		$current_image = apply_filters( 'picu_single_image_data', $current_image, $post );

		$image_collection[] = array(
			'number' => $current_image['number'],
			'imageID' => $current_image['imageID'],
			'title' => $current_image['title'],
			'description' => $current_image['description'],
			'imagePath' => $current_image['imagePath'],
			'imagePath_small' => $current_image['imagePath_small'],
			'imagePath_original' => $current_image['imagePath_original'],
			'imagePath_srcset' => $current_image['imagePath_srcset'],
			'imagePath_small_srcset' => $current_image['imagePath_small_srcset'],
			'size' => $current_image['size'],
			'sizeLightbox' => $current_image['size_lightbox'],
			'orientation' => $current_image['orientation'],
			'selected' => $current_image['selected'],
			'markers' => $current_image['markers']
		);

		$imgnum++;
	}

	return $image_collection;

}



/**
 * Return JSON formated image collection
 *
 * @since 1.5.0
 *
 * @param $post – the picu collection object
 * @return string javascript object, containing the image collection
 *
 */

function picu_get_images( $post = '' ) {

	if ( empty( $post ) ) {
		$post = get_post();
	}

	// Get image IDs
	$include = get_post_meta( $post->ID, '_picu_collection_gallery_ids', true );
	$delivery_images = get_post_meta( $post->ID, '_picu_collection_delivery_ids', true );

	if ( ( 'delivered' == $post->post_status OR 'delivery-draft' == $post->post_status ) AND ! empty( $delivery_images ) ) {
		return json_encode( picu_get_image_collection( $delivery_images ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
	}
	elseif ( ! empty( $include ) AND 'delivered' != $post->post_status AND 'delivery-draft' != $post->post_status ) {
		return json_encode( picu_get_image_collection( $include ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
	}
	else {
		return '[ ]';
	}
}



/**
 * Helper function to add slashes and encode comments
 */
function picu_encode_marker_comment( &$item, $key ) {
	if ( $key == 'comment' ) {
		$item = addslashes( htmlspecialchars( $item, ENT_QUOTES, 'UTF-8' ) );
		$item = str_replace( '&amp;', '&', $item );
	}
}



/**
 * Create AppState JSON object
 *
 * @since 0.6.0
 *
 * @param $id, collection id
 * @return string, json
 *
 */

function picu_get_app_state() {

	$post = get_post();
	$id = $post->ID;

	$description = addslashes( wpautop( htmlspecialchars( get_post_meta( $id, '_picu_collection_description', true ), ENT_QUOTES, 'UTF-8' ) ) );
	$date = picu_datetime_escape( get_the_date( get_option( 'date_format' ), $id ) );
	$date_format = picu_datetime_escape( get_option( 'date_format' ) );
	$time_format = picu_datetime_escape( get_option( 'time_format' ) );

	$state = array(
		'version' => PICU_VERSION,
		'nonce' => wp_create_nonce( 'picu-ajax-security' ),
		'postid' => get_the_ID( $id ),
		'poststatus' => get_post_status( $id ),
		'title' => get_the_title( $id ),
		'date' => $date,
		'description' => preg_replace( "/\r|\n|\t/", "", $description ),
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
		'lang' => str_replace( '_', '-', get_locale() ),
		'utc_diff' => wp_date( 'Z' ) / 60,
		'date_format' => $date_format,
		'time_format' => $time_format,
		'error_msg_no_imgs' => __( '<h2>No images found</h2><p>It seems there are no images in this collection.</p>', 'picu' ),
		'error_msg_filter_selected' => __( 'You have not selected any images.', 'picu' ),
		'error_msg_filter_unselected' => __( 'You have no <em>unselected</em> images.', 'picu' ),
		'reset_filter_msg' => __( 'Reset filter to show all images', 'picu' ),
		'select_at_least_one_image_msg' => __( 'You have to select at least one image.', 'picu' ),
		'already_approved_msg' => __( 'This collection has already been approved.', 'picu' ),
		'expired_msg' => __( 'This collection has expired.', 'picu' ),
		'request_failed_error' => __( 'Error: Request failed.<br />Do you have a working internet connection?', 'picu' ),
		'still_draft_msg' => __( 'This collection is still a draft. You have to open it to select images.', 'picu' ),
	);

	// Add identifier
	$hashes = get_post_meta( get_the_ID(), '_picu_collection_hashes', true );

	if ( ! empty( $hashes ) AND ! empty( $_GET['ident'] ) ) {
		$hashes = get_post_meta( get_the_ID(), '_picu_collection_hashes', true );

		if ( array_key_exists( $_GET['ident'], $hashes ) ) {
			$state['ident'] = $_GET['ident'];

			if ( $hashes[$_GET['ident']]['status'] == 'approved' ) {
				$state['poststatus'] = 'approved';
			}

			// Use approved as status for "failed" until we have open/closed status
			if ( $hashes[$_GET['ident']]['status'] == 'failed' ) {
				$state['poststatus'] = 'approved';
			}
		}
	}

	/**
	 * Add filter to extend on app state parameters
	 */
	$state = apply_filters( 'picu_app_state', $state );

	$app_state = json_encode( $state, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );

	return $app_state;
}


/**
 * Escape a date/time format string.
 *
 * @since 2.3.7
 *
 * @see https://stackoverflow.com/questions/43003401/encoding-escaping-json-control-characters
 *
 * @param string $string Original string.
 * @return string Escaped string.
 */
function picu_datetime_escape( $format_string ) {
	$escaped = '';
	for ( $i = 0; $i < strlen( $format_string ); ++$i ) {
		$char = $format_string[$i];
		if ( ( $char === '\\' ) && ( $format_string[$i + 1] !== '"' ) ) {
			// Escape a backslash, but leave escaped double quotes intact
			$escaped .= '\\\\';
		}
		else {
			$escaped .= $char;
		}
	}

	return $escaped;
}


/**
 * Collection list shortcode
 *
 * @since 1.2.0
 *
 * @param array $atts Shortcode attributes
 * @param string $content Content between the shortcode tags; displayed when no collections are found
 *
 * @return string HTML output of the collection list
 *
 */
function picu_list_collections( $atts, $content = null ) {
	// Setup default arguments
	$args = array(
		'post_type' => 'picu_collection',
		'orderby' => 'date',
		'posts_per_page' => 1000
	);

	$status = [];

	if ( is_array( $atts ) AND array_key_exists( 'status', $atts ) ) {
		$status = explode( ',', $atts['status'] );
		// Only allow certain predefined status: sent, approved, expired, delivered
		$status = array_filter( $status, function( $item ) {
			if ( in_array( trim( $item ), [ 'open', 'closed', 'sent', 'approved', 'expired', 'delivered' ] ) ) {
				return true;
			}
			else {
				return false;
			}
		} );
	}

	// Check for IDs
	if ( isset( $atts['ids'] ) and ! empty( $atts['ids'] ) ) {
		$ids = explode( ',', $atts['ids'] );
		$ids = array_map( 'trim', $ids );
		$args['post__in'] = $ids;
	}

	// Check for email address
	if ( isset( $atts['email'] ) and ! empty( $atts['email'] ) ) {
		$email = sanitize_email( $atts['email'] );
	}

	// Check for current user
	if ( is_array( $atts ) AND in_array( 'current_user', $atts ) ) {
		$current_user = wp_get_current_user();
		if ( ! empty( $current_user->user_email ) ) {
			$email = $current_user->user_email;
		}
		else {
			$email = '🚫';
		}
	}

	// Fill meta query arg for email
	if ( ! empty( $email ) ) {
		$args['meta_query'] = array(
			'relation' => 'OR',
			array(
				'key' => '_picu_collection_hashes',
				'value' => $email,
				'compare' => 'LIKE'
			),
			array(
				'key' => '_picu_delivery_email_address',
				'value' => $email,
				'compare' => 'LIKE'
			),
		);
	}

	// If email parameter is not used, we filter by post_status
	if ( empty( $email ) && is_array( $status ) && ! empty( $status[0] ) ) {
		if ( $status == [ 'approved' ] || $status == [ 'expired' ] || $status == [ 'sent' ] || $status == [ 'delivered' ] ) {
			// Leave those for backwards compatibility
		}
		// Map new, outward facing statusses to the ones actually used under the hood
		elseif ( ! empty( array_intersect( $status, [ 'open', 'sent' ] ) ) ) {
			$status = 'sent';
		}
		elseif( ! empty( array_intersect( $status, [ 'closed', 'approved', 'expired' ] ) ) ) {
			$status = [ 'approved', 'expired' ];
		}
		elseif( ! empty( array_intersect( $status, [ 'delivered' ] ) ) ) {
			$status = [ 'delivered' ];
		}


		$args['post_status'] = $status;
	}
	// When filtering for a specific client, we need to prepare the status
	else {
		if ( $status == [ 'open' ] ) {
			$status = [ 'sent', 'publish' ];
		}
		elseif ( $status == [ 'closed' ] ) {
			$status = [ 'approved', 'failed' ];
		}
		elseif ( $status == [ 'expired' ] ) {
			$status = [ 'failed' ];
		}
		elseif ( in_array( 'open', $status ) ) {
			// Remove open, replace with sent
			if ( ( $key = array_search( 'open', $status ) ) !== false ) {
				unset( $status[$key] );
			}
			array_push( $status, 'sent' );
		}
		elseif ( in_array( 'closed', $status ) ) {
			// Remove closed, replace with approved and failed
			if ( ( $key = array_search( 'closed', $status ) ) !== false ) {
				unset( $status[$key] );
			}
			array_push( $status, 'approved', 'failed' );
		}
	}

	// Filter query args
	$args = apply_filters( 'picu_list_collections_args', $args );

	$query = new WP_Query( $args );

	if ( $query->have_posts() ) {
		$collection_list = '<ul class="picu-collection-list">';

		while ( $query->have_posts() ) {
			$query->the_post();

			// Filter, if email (or current_user) is set
			if ( ! empty( $email ) ) {
				// Get delivered collections: No ident here, so we go by regular post_status
				if ( in_array( 'delivered', $status ) && get_post_status() == 'delivered' ) {
					// Then check if the email address exists in the delivery email post meta
					$delivery_email = get_post_meta( get_the_ID(), '_picu_delivery_email_address', true );
					if ( strpos( $delivery_email, $email ) >= 0 ) {
						$collection_list .= '<li class="picu-status-' . get_post_status() . '"><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
					}
				}
				else {
					$ident = picu_get_ident_from_email( get_the_ID(), $email );

					// Only display the collection, if there is an ident 
					if ( ! empty( $ident ) ) {
						// Check status
						$client_status = picu_get_status_from_ident( get_the_ID(), $ident );

						// Display the collection, if status is not set, or if collection and user match
						if ( empty( $status ) || ! empty( array_intersect( $status, [ $client_status ] ) ) ) {
							// Display the collection with ident
							$collection_list .= '<li class="picu-status-' . get_post_status() . '"><a href="' . get_permalink() . '?ident=' . picu_get_ident_from_email( get_the_ID(), $email ) . '">' . get_the_title() . '</a></li>';
						}
					}
				}
			}
			// No specific user, so displaying collection without ident
			else {
				$collection_list .= '<li class="picu-status-' . get_post_status() . '"><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
			}
		}

		$collection_list .= '</ul>';
	}
	else {
		$collection_list = '<div class="picu-no-collections">' . $content . '</div>';
	}

	wp_reset_query();

	// Filter the collection list output
	$collection_list = apply_filters( 'picu_collection_list', $collection_list, $query, $atts, $content );

	return $collection_list;
}

add_shortcode( 'picu_list_collections', 'picu_list_collections' );


/**
 * Change the password form for picu collections
 *
 * @since 1.4.5
 *
 * @param $output
 * @return html
 *
 */
function picu_password_form( $output ) {

	global $post;

	if ( $post->post_type == 'picu_collection' ) {
		$label = 'pwbox-' . ( empty( $post->ID ) ? rand() : $post->ID );
		ob_start();
	?>
	<form action="<?php echo esc_url( site_url( 'wp-login.php?action=postpass', 'login_post' ) ); ?>" method="post">
		<?php wp_referer_field(); ?>
		<p><label for="<?php echo $label; ?>"><?php _e( 'To view this collection, enter the password below.', 'picu' ); ?></label> <input name="post_password" id="<?php echo $label; ?>" type="password" size="20" maxlength="20" /></p>
		<p><input class="picu-button primary" type="submit" name="Submit" value="<?php echo esc_attr__( 'Enter', 'picu' ); ?>" /></p>
	</form>
	<?php
		$output = ob_get_clean();
	}

	return $output;
}

add_filter( 'the_password_form', 'picu_password_form' );


/**
 * Get the collection status
 * 
 * @since 1.6.0
 * 
 * @param int $post_id The collection/post id
 * @param string $ident The ident parameter, if it is a multi-client collection
 * 
 * @return string The collection status
 */
function picu_get_collection_status( $post_id, $ident = '' ) {
	if ( ! empty( $ident ) ) {
		$hashes = get_post_meta( $post_id, '_picu_collection_hashes', true );
		if ( ! empty( $hashes[$ident] ) AND ! empty( $hashes[$ident]['status'] ) ) {
			return $hashes[$ident]['status'];
		}
	}

	return get_post_status( $post_id );
}


/**
 * Check if a collection is a multi-client collection
 *
 * @since 1.6.0
 *
 * @param int $post_id
 * @return bool
 *
 */
function picu_is_multi_collection( $post_id ) {

	$hashes = get_post_meta( $post_id, '_picu_collection_hashes', true );

	if ( ! empty( $hashes ) AND count( $hashes ) > 0 ) {
		return true;
	}
	
	return false;
}


/**
 * Only allow access to a multi-client collection if the identifier is present.
 *
 * @since 1.6.0
 *
 */
function picu_collection_bouncer() {
	// Allow entry if collection is delivery-draft or delivered
	$post_status = get_post_status( get_the_ID() );
	if ( $post_status == 'delivery-draft' OR $post_status == 'delivered' ) {
		// Redirect to URL without ident
		if ( ! empty( $_GET['ident'] ) ) {
			wp_redirect( get_the_permalink() );
			exit;
		}
		return true;
	}

	// If ident is present and valid, allow entry
	if ( ! empty( $_GET['ident'] ) && picu_ident_exists( $_GET['ident'], get_the_ID() ) ) {
		return true;
	}

	// External evaluation
	if ( apply_filters( 'picu_collection_bouncer', false, get_the_ID() ) == true ) {
		return true;
	}

	// If there is only one client, redirect to the collection with ident
	if ( empty( $_GET['ident'] ) && picu_collection_has_ident( get_the_ID() ) && picu_get_recipients_num( get_the_ID() ) == 1 ) {
		$hashes = get_post_meta( get_the_ID(), '_picu_collection_hashes', true );
		$url = get_the_permalink();
		$url = add_query_arg( 'ident', array_keys( $hashes )[0], $url );
		wp_redirect( $url );
		exit;
	}

	// Get some more collection data
	$selection = get_post_meta( get_the_ID(), '_picu_collection_selection', true );

	// Convert collection if send method is manual, there is no ident and the collection does not yet have an ident
	if ( empty( $_GET['ident'] ) && empty( $selection ) && picu_get_recipients_num( get_the_ID() ) === 0 && get_post_status() != 'draft' ) {
		// Prepare client data
		$name = picu_get_default_client_name();
		$email = get_post_meta( get_the_ID(), '_picu_collection_email_address', true );
		// Don't use default name, when email is available
		if ( ! empty( $email ) ) {
			$name = '';
		}

		// Automatically create ident and redirect
		$ident = picu_add_client_to_hashes( get_the_ID(), $name, $email );

		if ( ! empty( $ident ) ) {
			$url = get_the_permalink();
			$url = add_query_arg( 'ident', $ident, $url );
			wp_redirect( $url );
			exit;
		}
	}

	// Allow for preview, when collection is a draft
	if ( get_post_status() == 'draft' ) {
		return true;
	}

	// Deny everything else
	picu_send_404();
}


/**
 * Send 404 and show error page.
 * 
 * @since 2.3.0
 */
function picu_send_404() {
	status_header( 404 );
	nocache_headers();
	include( get_404_template() );
	die();
}