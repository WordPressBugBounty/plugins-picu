<?php
/**
 * Compatibility Fixes for plugin: The SEO Framework
 * 
 * @since 1.1.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
 * Remove picu collections from sitemaps created with the SEO Framework plugin
 *
 * @since 1.3.0
 */
function picu_remove_collections_from_seo_framework_sitemap() {
	return array( 'picu_collection' );
}
add_filter( 'the_seo_framework_sitemap_exclude_cpt', 'picu_remove_collections_from_seo_framework_sitemap' );


/**
 * Remove the SEO Framework meta box from picu collections
 *
 * @since 1.3.0
 */
function picu_remove_seo_framework_meta_box() {
	$current_screen = get_current_screen();

	if ( $current_screen->id == 'picu_collection' ) {
		add_filter( 'the_seo_framework_seobox_output', '__return_false' );
	}
}
add_action( 'current_screen', 'picu_remove_seo_framework_meta_box' );