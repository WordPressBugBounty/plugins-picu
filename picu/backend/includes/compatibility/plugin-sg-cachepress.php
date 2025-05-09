<?php
/**
 * Compatibility Fixes for plugin: SiteGround Speed Optimizer.
 *
 * @since 2.5.3
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
 * Prevent SiteGround Speed Optimizer from combining important JS files.
 *
 * @since 2.5.3
 *
 * @param array $exclude_list List of excluded scripts.
 * @return array The list now containing picu scripts.
 */
function picu_sgo_exclude_combine_js( $exclude_list ) {
	$exclude_list[] = 'picu-jquery-js';
	$exclude_list[] = 'picu-jquery-visible-js';
	$exclude_list[] = 'picu-underscore-js';
	$exclude_list[] = 'picu-backbone-js';
	$exclude_list[] = 'picu-dateformat-js';

	return $exclude_list;
}

add_filter( 'sgo_javascript_combine_exclude_ids', 'picu_sgo_exclude_combine_js' );