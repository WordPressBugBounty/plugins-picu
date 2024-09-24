<?php
/**
 * Compatibility Fixes for plugin: Real Media Library
 * 
 * @since 1.1.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
 * Deactivate Real Media Library for picu collections
 *
 * @since 1.3.5
 */
function picu_deactivate_real_media_library( $active ) {

	global $post;

	if ( isset( $post->post_type ) AND $post->post_type == 'picu_collection' ) {
		$active = false;
	}

	return $active;

}
add_filter( 'RML/Active', 'picu_deactivate_real_media_library' );