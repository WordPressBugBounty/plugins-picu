<?php
/**
 * Adding debug info to Site Health Screen
 *
 * @since 1.7.8
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function picu_add_debug_info( $debug_info ) {

	// $licenses = get_option( 'picu_addon_licenses' );
	// $license = picu_get_license_info( $licenses['picu_pro'], 'picu Pro' );

	$picu_collection_cpt = get_post_type_object( 'picu_collection' );
	$picu_collection_slug = $picu_collection_cpt->rewrite['slug'];

	$php_extensions = get_loaded_extensions();
	sort( $php_extensions );

	$debug_info['picu'] = array(
		'label'    => __( 'picu', 'picu' ),
		'fields'   => array(
			// 'license' => array(
			// 	'label'    => 'License',
			// 	'value'   => $license->license,
			// 	'private' => true,
			// ),
			'picu_upload_dir' => [
				'label' => 'picu upload directory',
				'value' => PICU_UPLOAD_DIR
			],
			'picu_base_slug' => [
				'label' => 'picu base slug',
				'value' => $picu_collection_slug
			],
			'temp_dir' => [
				'label' => 'Temp directory',
				'value' => ( function() {
					$temp_dir   = sys_get_temp_dir();
					$doc_root   = realpath( $_SERVER['DOCUMENT_ROOT'] ?? ABSPATH );
					$temp_real  = realpath( $temp_dir ) ?: $temp_dir;
					$writable   = is_writable( $temp_dir );
					$in_webroot = $doc_root && str_starts_with( $temp_real, $doc_root );
					$flags      = [];
					$flags[]    = $writable ? 'writable' : 'not writable';
					if ( $in_webroot ) $flags[] = 'potentially web accessible';
					return $temp_dir . ' (' . implode( ', ', $flags ) . ')';
				} )()
			],
			'safe_mode' => [
				'label' => 'Safe mode',
				'value' => ini_get( 'safe_mode' ) ? 'On' : 'Off'
			],
			'server_time' => [
				'label' => 'Server time',
				'value' => esc_html( date( 'H:i' ) ),
			],
			'blog_time' => [
				'label' => 'Blog time',
				'value' => wp_date( 'H:i', time() )
			],
			'memory_in_use' => [
				'label' => 'Memory in use',
				'value' => size_format( @memory_get_usage( TRUE ), 2 )
			],
			'php_extensions' => [
				'label' => 'Loaded PHP extensions',
				'value' => esc_html( implode( ', ', $php_extensions ) )
			]
		),
	);

	return $debug_info;
}
add_filter( 'debug_information', 'picu_add_debug_info' );