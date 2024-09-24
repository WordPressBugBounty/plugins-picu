<?php
/**
 * picu media handling
 *
 * @since 0.4.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
 * Delete all attached media when a collection is deleted
 *
 * @since 0.4.0
 */
function picu_delete_attached_media( $post_id ) {

	if ( 'picu_collection' != get_post_type( $post_id ) )
		return;

	$args = array(
		'post_type' => 'attachment',
		'posts_per_page' => -1,
		'post_status' => 'any',
		'post_parent' => $post_id
	);

	// Temporarily remove our own attachment filter so we actually get anything
	remove_action( 'pre_get_posts', 'picu_exclude_collection_images_from_library', 999 );

	$attachments = new WP_Query( $args );

	// Now that our query is finished, re-add our filter
	add_action( 'pre_get_posts', 'picu_exclude_collection_images_from_library', 999 );

	foreach ( $attachments->posts as $attachment ) {
		if ( false === wp_delete_attachment( $attachment->ID, true ) ) {
			// Log failure to delete attachment
		}
	}
}

add_action( 'before_delete_post', 'picu_delete_attached_media' );


/**
 * Remove a collection's upload folder, when the collection is removed
 *
 * @since 0.5.0
 */
function picu_delete_upload_folder( $post_id ) {

	// Stop if we are not deleting a collection
	if ( 'picu_collection' != get_post_type( $post_id ) )
		return;

	// Get upload directory for the collection
	$picu_upload_dir = PICU_UPLOAD_DIR . "/collections/$post_id";

	// Check if the path we get actually is a directory
	if ( is_dir( $picu_upload_dir ) ) {

		// Delete delivery sub directory
		if ( is_dir( $picu_upload_dir . '/delivery' ) ) {
			rmdir( $picu_upload_dir . '/delivery' );
		}

		// Get folder contents
		$folder_content = array_diff( scandir( $picu_upload_dir ), array( '..', '.' ) );	

		// Check if directory is empty
		if ( 0 == count( $folder_content ) ) {
			// Remove that directory
			rmdir( $picu_upload_dir );
		}
	}

}
add_action( 'deleted_post', 'picu_delete_upload_folder' );


/**
 * Exclude our attachments from media library
 *
 * Don't display images attached to a collection on any
 * queries other than our own.
 *
 * @since 0.5.0
 * @since 2.1.1 Simplified; only run, when attachments are queried
 *
 * @param object $query The WP_Query instance (passed by reference)
 */
function picu_exclude_collection_images_from_library( $query ) {
	// Stop if we are not on an admin panel
	if ( ! is_admin() ) {
		return;
	}

	// We only need this filter if attachments are queried
	if ( $query->get( 'post_type' ) != 'attachment' ) {
		return;
	}

	// Remove our action from pre_get_posts to avoid infinite loop
	remove_action( 'pre_get_posts', 'picu_exclude_collection_images_from_library', 999 );

	// Get the IDs of all picu collections
	$collections_query = new WP_Query(
		[
			'post_type' => 'picu_collection',
			'posts_per_page' => -1,
			'post_status' => [ 'any', 'trash' ],
			'fields' => 'ids'
		]
	);

	if ( empty( $collections_query->posts ) ) {
		return;
	}

	// Exclude attachments that have a picu collection as a parent
	$query->query_vars['post_parent__not_in'] = $collections_query->posts;

	return $query;
}

add_action( 'pre_get_posts', 'picu_exclude_collection_images_from_library', 999 );


/**
 * Fix the media count (dropdown)
 *
 * @since 0.5.0
 */
function picu_fix_media_count( $counts ) {

	global $pagenow;

	if ( 'upload.php' != $pagenow )
		return $counts;

	// Remove our action form pre_get_posts to avoid infinite loop
	// (WP_Query would also trigger pre_get_posts filter)
	remove_action( 'pre_get_posts', 'picu_exclude_collection_images_from_library', 999 );

	// Query all collections (CPT 'picu_collection')
	$collections_query = new WP_Query(
		array(
			'post_type' => 'picu_collection',
			'post_status' => array( 'any', 'trash' ),
			'posts_per_page' => -1,
			'fields' => 'ids'
		)
	);

	if ( empty( $collections_query->posts ) )
		return $counts;

	$attachment_query = new WP_Query(
		array(
			'post_type' => 'attachment',
			'posts_per_page' => -1,
			'post_status' => 'any',
			'post_parent__in' => $collections_query->posts,
			'fields' => 'ids'
		)
	);

	// Now that our query is finished, we re-add our function to pre_get_posts
	add_action( 'pre_get_posts', 'picu_exclude_collection_images_from_library', 999 );

	if ( ! empty( $counts->{'image/jpeg'} ) ) {
		$counts->{'image/jpeg'} = $counts->{'image/jpeg'} - $attachment_query->found_posts;
	}

	return $counts;
}

add_filter( 'wp_count_attachments', 'picu_fix_media_count' );


/**
 * Filter date dropdown in media library list view to
 * remove "empty" months, where there are only picu images.
 *
 * @param object $months The months drop-down query results
 * @return object The filtered months object
 * @since 1.8.0
 */
function picu_filter_media_library_list_dropdown( $months ) {
	global $current_screen;

	// Only filter the media library screen
	if ( ! isset( $current_screen->id ) || $current_screen->id !== 'upload' ) {
		return $months;
	}

	// Get the IDs of all picu collections
	$args = array(
		'post_type' => 'picu_collection',
		'fields' => 'ids',
		'post_status' => [ 'any', 'trash' ],
		'posts_per_page' => -1
	);
	$collection_ids = get_posts( $args );

	// Check if there are any collections
	if ( ! isset( $collection_ids ) ) {
		return $months;
	}

	foreach ( $months as $key => $month ) {
		$month_num = zeroise( $month->month, 2 );
		$year = $month->year;

		// Check if there is at least one attachment, which is not a child of a picu_collection
		$args = array(
			'post_type' => 'attachment',
			'post_status' => 'inherit',
			'year' => $year,
			'monthnum' => $month_num,
			'post_parent__not_in' => $collection_ids,
			'posts_per_page' => 1
		);
		$attachments = get_posts( $args );

		// If there is none, remove the month from the dropdown
		if ( empty( $attachments ) ) {
			unset( $months[ $key ] );
		}
	}

	return $months;
}

add_filter( 'months_dropdown_results', 'picu_filter_media_library_list_dropdown' );


/**
 * Filter date dropdown in media library grid view to
 * remove "empty" months, where there are only picu images.
 *
 * @param object $settings List of media view settings
 * @return object The filtered settings object
 * @since 1.8.0
 */
function picu_filter_media_library_grid_dropdown( $settings ) {

	// Get the IDs of all picu collections
	$args = array(
		'post_type' => 'picu_collection',
		'fields' => 'ids',
		'post_status' => [ 'any', 'trash' ],
		'posts_per_page' => -1
	);
	$collection_ids = get_posts( $args );

	foreach ( $settings['months'] as $key => $month ) {
		$month_num = zeroise( $month->month, 2 );
		$year = $month->year;

		// Check if there is at least one attachment, which is not a child of a picu_collection
		$args = array(
			'post_type' => 'attachment',
			'post_status' => 'inherit',
			'year' => $year,
			'monthnum' => $month_num,
			'post_parent__not_in' => $collection_ids,
			'posts_per_page' => 1
		);
		$attachments = get_posts( $args );

		// If there is none, remove the month from the dropdown
		if ( empty( $attachments ) ) {
			unset( $settings['months'][ $key ] );
		}
	}

	return $settings;
}

add_filter( 'media_view_settings', 'picu_filter_media_library_grid_dropdown' );


/**
 * Redirect picu attachment pages to homepage
 *
 * @since 1.1.0
 */

function picu_attachment_redirect() {
	global $post;

	if ( isset( $post->post_parent ) AND 'picu_collection' == get_post_type( $post->post_parent ) AND 'attachment' == $post->post_type ) {
		wp_redirect( get_bloginfo( 'url' ), 301 );
	}
}

add_action( 'template_redirect', 'picu_attachment_redirect' );


/**
 * Filter image upload directory
 *
 * @since 0.5.0
 */
function picu_custom_upload_dir( $path ) {

	// When uploading, the file gets sent to upload_async.php, so we need to take the $_POST query in order to be able to get the post ID we need.
	if ( ! isset( $_POST['post_id'] ) || $_POST['post_id'] < 0 ) {
		return $path;
	}

	if ( ! empty( $path['error'] ) ) {
		return $path;
	}

	$post_id = $_POST['post_id'];
	$post_type = get_post_type( $post_id );

	// Check if we are uploading from the user-edit.php page.
	if ( $post_type == 'picu_collection' ) {

		if ( 'delivery-draft' == get_post_status( $post_id ) ) {
			$customdir = '/picu/collections/' . $post_id . '/delivery';
		} else {
			$customdir = '/picu/collections/' . $post_id;
		}

		if ( ! empty( $path['subdir'] ) ) { // year/month sub directory
			$path['path'] = str_replace( $path['subdir'], $customdir, $path['path'] );
			$path['url'] = str_replace( $path['subdir'], $customdir, $path['url'] );
			$path['subdir'] = '';
		} else {
			$path['path'] = $path['path'] . $customdir;
			$path['url'] = $path['url'] . $customdir;
		}

		return $path;
	}

	// We are not uploading from a collection, so go ahead with the default path
	return $path;
}

function picu_upload_prefilter( $file ) {
	add_filter( 'upload_dir', 'picu_custom_upload_dir' );
	return $file;
}

add_filter( 'wp_handle_upload_prefilter', 'picu_upload_prefilter' );

function picu_upload_postfilter( $fileinfo ) {
	remove_filter( 'upload_dir', 'picu_custom_upload_dir' );
	return $fileinfo;
}

add_filter( 'wp_handle_upload', 'picu_upload_postfilter' );


/**
 * Register custom image sizes
 *
 * @since 0.5.0
 */
function picu_image_sizes() {

	add_image_size( 'picu-thumbnail', 180, 180, true );
	add_image_size( 'picu-small', 400, 400, false );
	add_image_size( 'picu-medium', 1000, 1000, false );

	// Large
	$picu_large_image_size = apply_filters( 'picu_large_image_size', array(
		'width' => 3000,
		'height' => 2000
	) );

	add_image_size( 'picu-large', $picu_large_image_size['width'], $picu_large_image_size['height'], false );

	// Sizes added here must be added to picu_image_sizes_filter(), too

}

add_action( 'init', 'picu_image_sizes' );


/**
 * Define, when to use which of our image sizes
 *
 * @param array $sizes Associative array of image sizes to be created
 * @param array $meta The image meta data: width, height, file, sizes, etc.
 * @param int $attachment_id The attachment post ID for the image
 * @return array $sizes Filtered image sizes to be created
 * @since 0.5.0
 * @since 1.7.6 Add picu context to images using post meta
 */
function picu_default_image_sizes_filter( $sizes, $meta, $attachment_id ) {

	// Get context (proofing, delivery)
	$picu_context = get_post_meta( $attachment_id, '_picu_context', true );

	if ( empty( $picu_context ) ) {

		// If no context was found, check if attachment is uploaded to a collection
		$parent_id = wp_get_post_parent_id( $attachment_id );
		$parent_post_type = get_post_type( $parent_id );

		if ( $parent_post_type == 'picu_collection' ) {

			// If parent is a collection, set context depending on post status
			$parent_post_status = get_post_status( $parent_id );

			switch ( $parent_post_status ) {
				case 'delivered':
				case 'delivery-draft':
					$picu_context = 'delivery';
					break;
				default: // use for approved, sent, publish or draft
					$picu_context = 'proofing';
			}

			// Store the context as post meta
			update_post_meta( $attachment_id, '_picu_context', $picu_context );
		}
	}

	// Define a list of sizes used for proofing collections
	$proofing_sizes = [
		'picu-thumbnail',
		'picu-small',
		'picu-medium',
		'picu-large'
	];

	// Define a list of sizes used for delivery collections
	$delivery_sizes = [
		'picu-thumbnail',
		'picu-small'
	];

	/**
	 * Set the $sizes array, depending on the context,
	 * no context means this attachment was not uploaded to a collection,
	 * for those, filter the array and return ALL BUT our custom sizes
	 */
	if ( $picu_context == 'delivery' ) {
		$sizes = apply_filters( 'picu_intermediate_image_sizes', array_intersect_key( $sizes, array_flip( $delivery_sizes ) ) );
	} elseif ( $picu_context == 'proofing' ) {
		$sizes = apply_filters( 'picu_intermediate_image_sizes', array_intersect_key( $sizes, array_flip( $proofing_sizes ) ) );
	} else {
		$sizes = array_diff_key( $sizes, array_flip( $proofing_sizes ) );
	}

	return $sizes;
}

add_filter( 'intermediate_image_sizes_advanced', 'picu_default_image_sizes_filter', 10, 3 );


/**
 * Add our own picu-thumbnail size as "thumbnail"
 * to the attachment metadata
 *
 * @since 0.5.0
 * @since 1.7.6 Use attachment ID to get the parent ID
 */
function picu_metadata_attachment( $metadata, $attachment_id ) {

	$parent_id = wp_get_post_parent_id( $attachment_id );

	if ( get_post_type( $parent_id ) == 'picu_collection' && ! empty( $metadata['sizes']['picu-thumbnail'] ) ) {
		$metadata['sizes']['thumbnail'] = array(
			'file' => $metadata['sizes']['picu-thumbnail']['file'],
			'width' => $metadata['sizes']['picu-thumbnail']['width'],
			'height' => $metadata['sizes']['picu-thumbnail']['height'],
			'mime-type' => $metadata['sizes']['picu-thumbnail']['mime-type']
		);
	}

	return $metadata;
}

add_filter( 'wp_generate_attachment_metadata', 'picu_metadata_attachment', 10, 2 );


/**
 * Disable big image size threshold as to not generate a "scaled" version of picu images
 *
 * @since 1.4.9
 */
function picu_disable_big_image_size_threshold( $threshold, $imagesize, $file, $attachment_id ) {

	// Get post parent id
	$post_parent_id = wp_get_post_parent_id( $attachment_id );

	// If post parent is a collection, do not generate scaled image
	if ( ! empty( $post_parent_id ) AND 'picu_collection' == get_post_type( $post_parent_id ) ) {
		return false;
	}
}

add_filter( 'big_image_size_threshold', 'picu_disable_big_image_size_threshold', 10, 4 );


/**
 * Enable custom image size picu-small to being used right after uploading an image
 * 
 * @since 1.6.1
 */
function picu_prepare_attachment_for_js( $response, $attachment, $meta ) {

	if ( isset( $meta['sizes']['picu-small'] ) ) {
		$attachment_url = wp_get_attachment_url( $attachment->ID );
		$base_url = str_replace( wp_basename( $attachment_url ), '', $attachment_url );
		$size_meta = $meta['sizes']['picu-small'];

		$response['sizes']['picu-small'] = array(
			'height' => $size_meta['height'],
			'width' => $size_meta['width'],
			'url'  => $base_url . $size_meta['file'],
			'orientation' => $size_meta['height'] > $size_meta['width'] ? 'portrait' : 'landscape',
		);
	}

	return $response;
}

add_filter ( 'wp_prepare_attachment_for_js', 'picu_prepare_attachment_for_js' , 10, 3 );


/**
 * Switch default image processor.
 *
 * @since 2.0.0
 *
 * @param array $editors The image processors
 * @return array $editors The filtered processors
 */
function picu_default_to_gd( $editors ) {
	$picu_default = get_option( 'picu_default_image_processor' );
	if ( in_array( $picu_default, $editors ) ) {
		// Switch the order
		$editors = array_diff( $editors, [ $picu_default ] );
		array_unshift( $editors, $picu_default );
	}

	return $editors;
}

add_filter( 'wp_image_editors', 'picu_default_to_gd' );