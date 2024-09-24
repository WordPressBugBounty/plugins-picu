<?php
/**
 * Compatibility Fixes for plugin: Nextgen Gallery
 * 
 * @since 1.1.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
 * Disable code injection by NextGen Gallery
 *
 * @since 1.0.4
 */
function picu_disable_ngg_resource_manager() {
	if ( isset( $_SERVER['REQUEST_URI'] ) AND preg_match( "/post_type=picu_collection/", $_SERVER['REQUEST_URI'] ) ){
		return false;
	}
}

add_filter( 'run_ngg_resource_manager', 'picu_disable_ngg_resource_manager' );