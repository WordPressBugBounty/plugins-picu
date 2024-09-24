<?php
/**
 * Compatibility Fixes for plugin: Media Library Assistant
 * 
 * @since 1.1.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
 * Remove picu images from Media Library Assistant shortcode generated galleries
 * 
 * @since 1.4.13
 */
function picu_remove_images_from_media_library_galleries( $filtered, $attachments ) {

	$filtered = array();

	$filtered = array_filter( $attachments, function( $attachment ) {
		if ( isset( $attachment->post_parent ) AND get_post_type( $attachment->post_parent ) == 'picu_collection' ) {
			return false;
		}
		return true;
	});
	
	return $filtered;
}
add_filter( 'mla_gallery_the_attachments', 'picu_remove_images_from_media_library_galleries', 10, 2 );