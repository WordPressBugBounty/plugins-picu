<?php
/**
 * Compatibility Fixes for plugin: Rankmath SEO
 * 
 * @since 1.1.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
 * Disable RankMath for picu collections.
 *
 * @since 2.3.0
 *
 * @param array $post_types An array of post_types where RankMath is available
 */
function picu_disable_rank_math( $post_types ) {
	// Use array_diff to remove picu collections from array of available post_types
	$post_types = array_values( array_diff( $post_types, ['picu_collection'] ) );
	return $post_types;
}
add_filter( 'rank_math/excluded_post_types', 'picu_disable_rank_math', 10, 1 );


/**
 * Remove RankMath "content AI" metabox from piucu editor screens (classic editor).
 *
 * @since 2.3.0
 */
function disable_rankmath_content_ai_metabox() {
	global $post;
	if ( get_post_type( $post ) === 'picu_collection' ) {
		remove_meta_box( 'rank_math_metabox_content_ai', $post->post_type, 'side' );
	}
}

add_action( 'admin_head', 'disable_rankmath_content_ai_metabox', 99 );
