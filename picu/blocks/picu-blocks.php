<?php
/**
 * picu Custom Blocks
 *
 * @since 3.4.0
 */

/**
 * Registers our custom blocks using metadata loaded from the `block.json` file.
 * Behind the scenes, it registers all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 *
 * @since 3.4.0
 */
function picu_blocks_init() {
	// Register Block "collections-list"
	register_block_type(
		PICU_PATH . '/blocks/build/collections-list'
	);
}

add_action( 'init', 'picu_blocks_init' );


/**
 * Pass custom picu data to make it available in the block editor.
 * 
 * @since 3.4.0
 */
function picu_pass_blocks_data() {
	wp_register_script( 'picu-blocks-data', '' );
	wp_enqueue_script( 'picu-blocks-data' );

	$picu_data = [
		'canManagePicu' => current_user_can( picu_capability() ),
		'isProActive' => picu_is_pro_active()
	];

	wp_add_inline_script(
		'picu-blocks-data',
		'var picuBlocksData = ' . wp_json_encode( $picu_data ) . ';',
		'before'
	);
}

add_action( 'enqueue_block_editor_assets', 'picu_pass_blocks_data' );


/**
 * Register custom REST endpoint for emails
 *
 * A custom REST endpoint to get all email addresses used
 * in any of the proofing collections.
 *
 * @since 3.4.0
 */
function picu_register_endpoint_emails() {
	register_rest_route('picu/v1', '/emails', array(
		'methods' => 'GET',
		'callback' => 'picu_get_all_collection_emails_for_api',
		'permission_callback' => function() {
			return current_user_can( picu_capability() );
		},
	));
}

add_action('rest_api_init', 'picu_register_endpoint_emails' );


/**
 * Retrieve all client email addresses used in any of our picu collections
 *
 * @since 3.4.0
 */
function picu_get_all_collection_emails_for_api() {

	// Get all registered, public post_statuses except delivery-draft
	$all_post_statuses = get_post_stati( ['public' => true] );
	$exclude_statuses = ['delivery-draft'];
	$post_statuses = array_values( array_diff( $all_post_statuses, $exclude_statuses ) );

	// Get all posts of the custom post type, that were send via email
	$collections = get_posts([
		'post_type'      => 'picu_collection',
		'posts_per_page' => 1000,
		'post_status'    => $post_statuses,
		'fields'         => 'ids',
		'meta_query'     => [
			'relation' => 'OR',
			[
				'key'     => '_picu_collection_hashes',
				'value'   => '"email"',
				'compare' => 'LIKE',
			],
			[
				'key'     => '_picu_delivery_email_address',
				'compare' => 'EXISTS',
			],
		],
	]);

	$emails = [];

	// Loop through each post and get the email from post_meta
	foreach ( $collections as $collection_id ) {
		// Get emails from collection hashes
		$hashes = get_post_meta( $collection_id, '_picu_collection_hashes', true );

		if ( ! empty( $hashes ) && is_array( $hashes ) ) {
			foreach ( $hashes as $hash ) {
				if ( ! empty( $hash['email'] ) ) {
					$emails[] = $hash['email'];
				}
			}
		}

		// Get emails from delivery addresses as well
		$delivery_email = get_post_meta( $collection_id, '_picu_delivery_email_address', true );
		if ( ! empty( $delivery_email ) ) {
			$delivery_emails = array_map( 'trim', explode( ',', $delivery_email ) );
			foreach ( $delivery_emails as $email ) {
				if ( ! empty( $email ) ) {
					$emails[] = $email;
				}
			}
		}
	}

	$emails = array_unique( array_filter( $emails ) );
	
	// Sort emails for consistent output
	sort( $emails );

	// Return the emails in the response
	return rest_ensure_response( $emails );
}


/**
 * Register custom REST endpoint picu/v1/collections
 *
 * A custom REST endpoint to get all collections
 *
 * @since 3.4.0
 */
function picu_register_route_collections() {
	register_rest_route( 'picu/v1', '/collections', array(
		'methods' => 'GET',
		'callback' => 'picu_get_collections_api_response',
		'permission_callback' => function() {
			return current_user_can( 'edit_posts' );
		},
		'args' => [
			'email' => [
				'required' => false,
				'type' => 'string',
			],
			'current_user' => [
				'required' => false,
				'type' => 'boolean',
			],
			'ids' => [
				'required' => false,
				'type' => 'string',
			],
			'order' => [
				'required' => false,
				'type' => 'string',
			],
		]
	) );
}

add_action( 'rest_api_init', 'picu_register_route_collections' );


/**
 * Load all collections used in collection list block
 *
 * @since 3.4.0
 *
 * @return WP_Post[] Array of collection posts
 */
function picu_get_collections_for_list_block( $attributes ) {

	// Get email and current_user settings from the block's attributes
	$email = isset( $attributes['email'] ) ? sanitize_email( $attributes['email'] ) : '';
	$current_user = isset( $attributes['currentUser'] ) ? $attributes['currentUser'] : false;

	// Maps postStatus from block attributes to actual client's statuses in post_meta
	$client_status_map = [
		'any'    => [ 'sent', 'approved', 'failed' ],
		'open'   => [ 'sent' ],
		'closed' => [ 'approved', 'failed' ],
	];

	// If current_user is enabled and a user is logged in, override $email with their address
	if ( $current_user ) {
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			if ( $user && $user->user_email ) {
				$email = $user->user_email;
			}
		} else {
			// If currentUser is enabled but no user logged in, return empty collections
			return [];
		}
	}

	// Prepare arguments for the collection query
	$args = picu_get_collections_query_args( null, $attributes );

	// Load the collections
	$collections = new WP_Query( $args );
	$collections = $collections->posts;

	// Check if we have an email address (from email attribute or current_user)
	// and if not, return $collections without further filtering
	if ( empty( $email ) ) {
		return $collections;
	}

	// If an $email was set, we use the individual client's status instead of
	// the post_status of the collection.
	$filtered_collections = [];
	foreach( $collections as $collection ) {

		// Delivery collections should be included, if the postStatus of the block is set to 'any' or 'delivered'.
		// We compare $email to _picu_delivery_email_address, to only keep delivery collections sent to this address.
		if ( in_array( $attributes['postStatus'], ['delivered', 'any' ], true ) ) {
			$delivery_addresses = get_post_meta( $collection->ID, '_picu_delivery_email_address', true );
			if ( ! empty( $delivery_addresses ) ) {
				$addresses = array_map( 'trim', explode( ',', $delivery_addresses ) );
				if ( in_array( $email, $addresses, true ) ) {
					$filtered_collections[ $collection->ID ] = $collection;
					continue;
				}
			}
		}

		// For all collections other than delivered, we do the same but with the proofing addresses.
		// We compare $email to '_picu_collection_hashes' and only keep collections sent to this address.
		if ( $attributes['postStatus'] !== 'delivered' ) {
			// The filtering is slightly different than above, because proofing collections store emails differently
			$hashes = get_post_meta( $collection->ID, '_picu_collection_hashes', true );
			if ( ! empty( $hashes ) && is_array( $hashes ) ) {
				foreach ( $hashes as $hash => $client ) {
					if (
						isset( $client['email'] ) &&
						$client['email'] === $email &&
						isset( $client['status'] ) &&
						in_array( $client['status'], $client_status_map[ $attributes['postStatus'] ], true )
					) {
						$filtered_collections[ $collection->ID ] = $collection;
						break;
					}
				}
			}
		}
	}

	// Return final, filtered collections array
	return array_values( $filtered_collections );

}


/**
 * Prepare the query arguments for the collection list query
 *
 * @since 3.4.0
 *
 * @param array $args Query arguments
 * @param array $attributes Block attributes
 *
 * @return array $args Prepared query arguments
 */
function picu_get_collections_query_args( $args, $attributes ) {

	// Get all registered, public post_statuses except delivery-draft
	$all_post_statuses = get_post_stati( ['public' => true] );
	$exclude_statuses = ['delivery-draft'];
	$post_statuses = array_values( array_diff( $all_post_statuses, $exclude_statuses ) );

	// Setting default arguments for our query
	$default_args = [
		'post_type' => 'picu_collection',
		'orderby' => 'date',
		'order' => 'DESC',
		'post_status' => $post_statuses,
		'posts_per_page' => 1000
	];

	// Maps postStatus from block attributes to actual post_status values
	$post_status_map = [
		'any'       => [ 'sent', 'publish', 'delivered', 'approved', 'expired', 'failed' ],
		'open'      => [ 'sent', 'publish' ],
		'closed'    => [ 'approved', 'expired', 'failed' ],
		'delivered' => [ 'delivered' ],
	];

	$email = '';

	// Set email from block attributes
	if ( ! empty( $attributes['email'] ) ) {
		$email = $attributes['email'];
	}

	// Override $email variable with current users address, if currentUser is set
	if ( ! empty( $attributes['currentUser'] ) ) {
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			if ( $user && $user->user_email ) {
				$email = $user->user_email;
			}
		}
	}

	if ( $attributes['postStatus'] === 'any' ) {

		$args['post_status'] = $post_status_map['any'];

		// Set meta_query to query all collections by $email
		if ( ! empty( $email ) ) {
			$args['meta_query'] = [
				'relation' => 'OR',
				[
					'key'     => '_picu_collection_hashes',
					'value'   => $email,
					'compare' => 'LIKE',
				],
				[
					'key'     => '_picu_delivery_email_address',
					'value'   => $email,
					'compare' => 'LIKE',
				],
			];
		}

	} elseif ( $attributes['postStatus'] === 'open' ) {

		$args['post_status'] = $post_status_map['open'];
		
	} elseif ( $attributes['postStatus'] === 'closed' ) {

		$args['post_status'] = $post_status_map['closed'];

		// Set meta_query to query closed collections by $email
		if ( ! empty( $email ) ) {
			// If querying by email, we want all collections, minus delivery
			$args['post_status'] = array_diff($post_status_map['any'], ['delivered']);

			$args['meta_query'] = [
				[
					'key'     => '_picu_collection_hashes',
					'value'   => $email,
					'compare' => 'LIKE',
				]
			];
		}
		
	} elseif ( $attributes['postStatus'] === 'delivered' ) {

		$args['post_status'] = $post_status_map['delivered'];

		// Set meta_query to query delivery collections by $email
		if ( ! empty( $email ) ) {
			$args['meta_query'] = [
				[
					'key'     => '_picu_delivery_email_address',
					'value'   => $email,
					'compare' => 'LIKE',
				]
			];
		}
	} 

	$args = wp_parse_args( $args, $default_args );

	// Filter by IDs, if attribute was set
	if ( ! empty( $attributes['ids'] ) ) {
		$args['post__in'] = wp_parse_id_list( $attributes['ids'] );
	}

	// Change the order of collections, if attribute was set
	if ( ! empty( $attributes['order'] ) ) {
		$order_value = sanitize_text_field( $attributes['order'] );
		switch ( $order_value ) {
			case 'date_desc':
				$args['orderby'] = 'date';
				$args['order'] = 'DESC';
				break;
			case 'date_asc':
				$args['orderby'] = 'date';
				$args['order'] = 'ASC';
				break;
			case 'title_asc':
				$args['orderby'] = 'title';
				$args['order'] = 'ASC';
				break;
			case 'title_desc':
				$args['orderby'] = 'title';
				$args['order'] = 'DESC';
				break;
		}
	}

	// Make query arguments filterable
	$args = apply_filters( 'picu_list_collections_args', $args );

	// Return query arguments
	return $args;

}


/**
 * Prepare the HTML markup of the collections list, used in the frontend
 *
 * @since 3.4.0
 *
 * @param WP_Post[] $collections Array of collection posts
 * 
 * @return string HTML markup of collection list
 */
function picu_prepare_collections_list_html( $collections, $attributes = [] ) {

	// Display a warning when currentUser is set but user is not logged in
	$use_current_user = ! empty( $attributes['currentUser'] );
	
	if ( $use_current_user ) {
		if ( ! is_user_logged_in() ) {
			/* translators: Opening and closing link tags for Login URL */
			return '<p>' . sprintf( __( 'You must be %slogged in%s to see collections.', 'picu' ), '<a href="' . esc_url( wp_login_url( get_permalink() ) ) . '">', '</a>' ) . '</p>';
		}
	}

	if ( empty( $collections ) ) {
		return '<p>' . esc_html__( 'No collections found.', 'picu' ) . '</p>';
	}

	// Get email for ident parameter
		$email = '';
	if ( $use_current_user ) {
		$email = sanitize_email( wp_get_current_user()->user_email );
	} elseif ( ! empty( $attributes['email'] ) ) {
		$email = sanitize_email( $attributes['email'] );
	}

	// Build HTML list
	$collections_list = '<ul class="picu-collections-list">';
	foreach( $collections as $collection ) {
		$permalink = get_permalink( $collection->ID );
		$status = $collection->post_status;

		// Add ident parameter if email is set (but not for delivered collections)
		if ( ! empty( $email ) && $status !== 'delivered' ) {
			$permalink = add_query_arg( 'ident', picu_get_ident_from_email( $collection->ID, $email ), $permalink );
		}

		$collections_list .= '<li class="picu-status-' . esc_attr( $status ) . '"><a href="' . esc_url( $permalink ) . '">' . esc_html( get_the_title( $collection->ID ) ) . '</a></li>';
	}
	$collections_list .= '</ul>';

	return $collections_list;
}


/**
 * Prepare the data array of the collections list, used in the API response
 *
 * @since 3.4.0
 *
 * @param WP_Post[] $collections Array of collection posts
 *
 * @return array Array of collection data
 */
function picu_prepare_collections_list_data( $collections ) {
	$collections_data = [];

	if ( empty( $collections ) ) {
		return $collections_data;
	}

	foreach( $collections as $collection ) {
		$collections_data[] = [
			'id' => $collection->ID,
			'title' => $collection->post_title,
			'slug' => $collection->post_name
		];
	}

	return $collections_data;
}


/**
 * Get collections API response
 *
 * @since 3.4.0
 *
 * @param WP_REST_Request $request REST API Request object
 *
 * @return WP_REST_Response
 */
function picu_get_collections_api_response( $request ) {
	// Get query parameters from request
	$param_map = [
		'post_status' => 'postStatus',
		'email' => 'email',
		'current_user '=> 'currentUser',
		'ids' => 'ids',
		'order' => 'order',
	];

	$attributes = [];
	foreach ( $param_map as $param => $attr ) {
		if ( $request->has_param( $param ) ) {
			$attributes[$attr] = $request->get_param( $param );
		}
	}

	$collections = picu_get_collections_for_list_block( $attributes );
	$collections_data = picu_prepare_collections_list_data( $collections );

	return rest_ensure_response( [
		'success' => true,
		'count' => count( $collections_data ),
		'collections' => $collections_data
	] );
}


/**
 * Get collections list HTML markup
 *
 * @since 3.4.0
 *
 * @param array $attributes Block attributes
 *
 * @return string $collections_list HTML markup
 */
function picu_get_collections_list( $attributes = [] ) {
	$collections = picu_get_collections_for_list_block( $attributes );
	$collections_list = picu_prepare_collections_list_html( $collections, $attributes );
	return $collections_list;
}


/**
 * Outputs the HTML markup for a collection list.
 * Wrapper for picu_get_collections_list
 *
 * @since 3.4.0
 *
 * @param array $attributes Block attributes
 */
function picu_the_collections_list( $attributes = [] ) {
	echo picu_get_collections_list( $attributes );
}
