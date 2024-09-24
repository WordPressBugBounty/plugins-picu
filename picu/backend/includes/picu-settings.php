<?php
/**
 * picu Settings
 *
 * Adds our admin menu and the settings page
 *
 * @since 0.5.0
 * @since 2.0.0 Major overhaul
 */
defined( 'ABSPATH' ) OR exit;


/**
 * Register settings menu for picu.
 *
 * @since 0.5.0
 * @since 2.0.0 Add individual pages for settings
 */
function picu_plugin_menu() {

	add_menu_page(
		'picu',
		'picu',
		picu_capability(),
		'picu',
		'',
		'data:image/svg+xml;base64,PHN2ZyB2aWV3Qm94PSIwIDAgNDAgMzIiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgZmlsbC1ydWxlPSJldmVub2RkIiBjbGlwLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiM5OTkiPjxwYXRoIGQ9Ik0yNy45NzYgMy42MzJoOC4zMjR2Ny45OTJoMy43di0xMS42MjRoLTEyLjAyNHYzLjYzMnpNMzYuMjkyIDI4LjM2aC04LjM0MXYzLjY0aDEyLjA0OXYtMTEuNjQ4aC0zLjcwOHY4LjAwOHpNMy43NCAyMC4yNDloLTMuNzR2MTEuNzUxaDExLjk2OXYtMy42NzJoLTguMjI5di04LjA3OXpNMy43NDIgMy42NzRoOC4yMzJ2LTMuNjc0aC0xMS45NzR2MTEuNzU2aDMuNzQydi04LjA4MnpNMjcuMTE4IDYuNDE4bC0xMC42NDcgMTIuOTQ3LTUuMjA0LTUuMDMzLTMuMzYyIDMuMzQ0IDkuMDI3IDguNzY5IDEzLjkwNS0xNy4xMjQtMy43MTktMi45MDN6Ii8+PC9nPjwvc3ZnPg==',
		25
	);

	add_submenu_page(
		'picu',
		__( 'New Collection', 'picu' ),
		__( 'New Collection', 'picu' ),
		picu_capability(),
		'post-new.php?post_type=picu_collection'
	);

	// Use this for the menu entry only
	add_submenu_page(
		'picu',
		__( 'picu Settings', 'picu' ),
		__( 'Settings', 'picu' ),
		'manage_options',
		'picu-settings',
		'picu_load_settings_page'
	);

	// Add individual page for each settings group
	$settings = picu_get_settings();
	foreach( $settings as $settings_group => $setting ) {
		// Add page
		add_submenu_page(
			'picu',
			__( 'picu Settings', 'picu' ),
			$setting['title'],
			'manage_options',
			'picu-' . $settings_group,
			'picu_load_settings_page'
		);
		// Hide page
		add_action( 'admin_head', function() use ( $settings_group ) {
			remove_submenu_page( 'picu', 'picu-' . $settings_group );
		} );

		// Mark settings menu item as `current`
		global $submenu;
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'picu-' . $settings_group ) {
			if ( array_key_exists( 'picu', $submenu ) ) {
				// Find `picu-settings` in the submenu array
				$key = array_search( 'picu-settings', array_column( $submenu['picu'], 2 ) );
				// Add current class
				$submenu['picu'][$key][4] = ' current';
			}
		}
	}

	add_submenu_page(
		'picu',
		__( 'picu Pro', 'picu' ),
		__( 'picu Pro', 'picu' ),
		'manage_options',
		'picu-add-ons',
		'picu_load_add_ons_page'
	);
}

add_action( 'admin_menu', 'picu_plugin_menu' );


/**
 * Redirect to first settings group page.
 *
 * Redirect from "generic settings" to the first real settings page.
 *
 * @since 2.0.0
 */
function picu_settings_redirect() {
	if ( isset( $_GET['page'] ) AND $_GET['page'] === 'picu-settings' ) {
		$settings = picu_get_settings();
		wp_redirect( admin_url( 'admin.php?page=picu-' . array_key_first( $settings ) ) );
		exit();
	}
}

add_action( 'admin_init', 'picu_settings_redirect' );


/**
 * Gather all picu settings.
 *
 * @since 2.0.0
 *
 * @return array picu Settings
 */
function picu_get_settings() {
	// Prepare variable
	$settings = [];

	$days = picu_expiration_length();

	// Start with general settings
	$settings['general'] = [
		'title' => __( 'General', 'picu' ),
		'description' => __( 'General picu settings.', 'picu' ),
		'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#007791" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>',
		'priority' => 1,
		'settings' => [
			'random_slugs' => [
				'type' => 'checkbox',
				'label' => __( 'Use random URLs for picu collections', 'picu' ),
				'description' => __( 'Disable, to use the WordPress default, generating the slug from the title.', 'picu' ),
				'default' => 'on'
			],
			'expiration' => [
				'type' => 'checkbox',
				'label' => __( 'Expire collections by default', 'picu' ),
				'description' => sprintf( _n( 'New collections will be set to expire %d day after being sent.', 'New collections will be set to expire %d days after being sent.', $days, 'picu' ), $days ),
				'default' => 'off'
			],
			'pro-banner' => [
				'banner-id' => 'custom-thank-you-page',
			],
			'picu_love' => [
				'type' => 'checkbox',
				'label' => '❤️ ' . __( 'Show picu logo', 'picu' ),
				'description' => __( 'Spread some picu love, by displaying our logo in collections and picu related emails.', 'picu' ),
				'default' => 'off',
			]
		]
	];

	// Design/appearance settings
	$settings['design-appearance'] = [
		'title' => __( 'Design/Appearance', 'picu' ),
		'description' => __( 'Configure the look of your collections.', 'picu' ),
		'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#007791" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>',
		'priority' => 10,
		'settings' => [
			'theme' => [
				'type' => 'html',
				'output' => 'picu_settings_theme',
				'label' => __( 'Theme', 'picu' ),
				'default' => 'dark'
			],
			'pro-banner' => [
				'banner-id' => 'customize-collection',
			]
		]
	];

	// Email settings
	$settings['email'] = [
		'title' => __( 'Email', 'picu' ),
		'description' => __( 'picu email settings.', 'picu' ),
		'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#007791" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>',
		'priority' => 20,
		'settings' => [
			'send_html_mails' => [
				'type' => 'checkbox',
				'label' => __( 'Use styling in emails', 'picu' ),
				'description' => __( 'Use beautiful HTML templates when sending emails. Otherwise plain text emails will be sent.', 'picu' ),
				'default' => 'on'
			],
			'send_password' => [
				'type' => 'checkbox',
				'label' => __( 'Include collection password in email', 'picu' ),
				'description' => __( 'If you set a collection password, it will be included in the email to the client.', 'picu' ),
				'default' => 'on'
			],
			'send_reminder' => [
				'type' => 'checkbox',
				'label' => __( 'Send email reminder', 'picu' ),
				'description' => __( 'If a client started selecting images but did not finally approve the collection, picu will automatically send a reminder after 24 hours.', 'picu' ),
				'default' => 'off'
			],
			'pro-banner' => [
				'banner-id' => 'email-message-templates'
			],
			'from_email' => [
				'type' => 'text',
				'label' => __( 'From Email', 'picu' ),
				'description' => __( 'The email address that emails are sent from.', 'picu' ),
				'placeholder' => apply_filters( 'wp_mail_from', 'no-reply@' . parse_url( get_site_url(), PHP_URL_HOST ) ),
				'disabled' => picu_email_setting_check_wp_mail_smtp( 'from_email' ),
				'disabled_hint' => sprintf( __( 'Disabled by WP Mail SMTP\'s "Force From Email" setting. %sChange%s', 'picu' ), '<a href="' . admin_url( 'admin.php?page=wp-mail-smtp#wp-mail-smtp-setting-row-from_email' ) . '">', '</a>' ),
				'default' => '',
				'validation' => 'picu_validate_from_email'
			],
			'from_name' => [
				'type' => 'text',
				'label' => __( 'From Name', 'picu' ),
				'description' => __( 'The name that emails are sent from.', 'picu' ),
				'placeholder' => apply_filters( 'wp_mail_from_name', get_bloginfo() ),
				'disabled' => picu_email_setting_check_wp_mail_smtp( 'from_name' ),
				'disabled_hint' => sprintf( __( 'Disabled by WP Mail SMTP\'s "Force From Name" setting. %sChange%s', 'picu' ), '<a href="' . admin_url( 'admin.php?page=wp-mail-smtp#wp-mail-smtp-setting-row-from_name' ) . '">', '</a>' ),
				'default' => ''
			],
			'notification_email' => [
				'type' => 'text',
				'label' => __( 'Notification Email', 'picu' ),
				'description' => __( 'The email address <strong>all</strong> notification emails are sent to.', 'picu' ),
				'hint' => __( 'Defaults to the collection author\'s email address.', 'picu' ),
				'placeholder' => '',
				'default' => '',
				'validation' => 'picu_validate_notification_email'
			],
		]
	];

	$settings['security'] = [
		'title' => __( 'Security', 'picu' ),
		'description' => __( 'Password protect you collections and more.', 'picu' ),
		'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#007791" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>',
		'priority' => 30,
		'settings' => [
			'password_by_default' => [
				'type' => 'checkbox',
				'label' => __( 'Password protection by default', 'picu' ),
				'description' => __( 'A random password will automatically assigned to all new picu collections.', 'picu' ),
				'default' => 'off',
			],
			'pro-banner' => [
				'banner-id' => 'collection-protection',
			]
		]
	];

	// Tools/debug settings
	$settings['tools-debug'] = [
		'title' => __( 'Tools/Debug', 'picu' ),
		'description' => __( 'Debug info and tools.', 'picu' ),
		'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#007791" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line><line x1="1" y1="14" x2="7" y2="14"></line><line x1="9" y1="8" x2="15" y2="8"></line><line x1="17" y1="16" x2="23" y2="16"></line></svg>',
		'priority' => 50,
		'settings' => [
			'debug' => [
				'type' => 'html',
				'output' => 'picu_settings_debug',
				'label' => 'Debug',
				'description' => '',
				'default' => '',
			]
		]
	];

	// Add image processor setting
	$image_processors = [];
	if ( extension_loaded( 'imagick' ) ) {
		$image_processors['WP_Image_Editor_Imagick'] = [
			'label' => 'WP Image Editor Imagick',
			'description' => __( 'Default processor, sometimes memory issue might cause not all images to be processed correctly. Allows to create PDF preview images.', 'picu' ),
		];
	};
	if ( extension_loaded( 'gd' ) ) {
		$image_processors['WP_Image_Editor_GD'] = [
			'label' => 'WP Image Editor GD',
			'description' => __( 'Older processor, better at processing lots of images. Not able to create PDF preview images.', 'picu' ),
		];
	};

	$settings['tools-debug']['settings']['default_image_processor'] = [
		'type' => 'radio',
		'options' => $image_processors,
		'title' =>  __( 'Image processor', 'picu' ),
		'description' => __( 'Switch between different image processors to improve performance when uploading/importing images.<br /><strong>Please be aware, that this affects all media uploads on your site, not just picu images.</strong>', 'picu' ) . ' <a class="picu-help" href="https://go.picu.io/image-processors">' . __( 'Help', 'picu-pro' ) . '</a>',
		'default' => 'WP_Image_Editor_Imagick'
	];

	// Add telemetry settings
	$settings['telemetry'] = [
		'title' => __( 'Telemetry', 'picu' ),
		'description' => __( 'Help us improve picu by providing real world usage data.', 'picu' ) . ' 🧡',
		'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#007791" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>',
		'priority' => 40,
		'settings' => [
			'telemetry_settings' => [
				'type' => 'html',
				'output' => 'picu_settings_telemetry',
				'label' => __( 'Activate picu telemetry', 'picu' ),
				'description' => '',
				'default' => 'off',
				'validation' => 'picu_telemetry_settings_validate'
			]
		]
	];

	$settings = apply_filters( 'picu_settings', $settings );

	// Sort by priority
	uasort( $settings, function( $a, $b ) {
		if ( empty( $a['priority'] ) ) {
			$a['priority'] = 1000;
		}
		if ( empty( $b['priority'] ) ) {
			$b['priority'] = 1000;
		}
		return $a['priority'] <=> $b['priority'];
	});

	return $settings;
}


/**
 * Register settings.
 *
 * @since 0.7.0
 * @since 2.0.0 Major overhaul, now registering individually for each setting
 */
function picu_register_settings() {
	$settings = picu_get_settings();
	foreach( $settings as $option_group => $group_settings ) {
		foreach( $group_settings['settings'] as $name => $setting ) {
			// Skip for pro ads
			if ( $name == 'pro-banner' ) {
				continue;
			}
			$name = 'picu_' . $name;

			if ( ! empty( $setting['validation'] ) ) {
				$validation = $setting['validation'];
			}
			else {
				$validation = 'picu_settings_validate';
			}

			register_setting( 'picu_' . $option_group, $name, [ 'sanitize_callback' => "$validation", 'show_in_rest' => false, 'default' => $setting['default'] ] );
		}
	}
}

add_action( 'init', 'picu_register_settings' );


/**
 * Render checkbox settings field.
 *
 * @since 2.0.0
 *
 * @param string $name The field name
 * @param array $setting The settings array
 * @param string $value The value currently stored in the db for this field
 * @return string HTML output for checkbox settings field
 */
function picu_checkbox_field( $name, $setting, $value ) {
	ob_start();
	echo '<fieldset class="picu_settings__settings-item';
	if ( isset( $setting['new'] ) && $setting['new'] === true ) {
		echo ' picu_settings__settings-item__new" data-new="' . __( 'new', 'picu' );
	}
	echo '">';
	if ( ! empty( $setting['title'] ) ) {
		echo '<h2>' . $setting['title'] . '</h2>';
	}
	echo '<p class="picu-settings__item"><input type="checkbox" id="picu_' . $name .'" name="picu_' . $name .'"' . checked( $value, 'on', false ) . '/> <label for="picu_' . $name . '" class="after">' . $setting['label'] . '<br /><span class="description">' . $setting['description'] . '</span></label></p>';
	echo '</fieldset>';

	return ob_get_clean();
}


/**
 * Render number settings field.
 * 
 * @since 2.1.0
 * @param string $name The field name
 * @param array $setting The settings array
 * @param string $value The value currently stored in the db for this field
 * @return string HTML output for number settings field
 */
function picu_number_field( $name, $setting, $value ) {
	ob_start();
	echo '<fieldset class="picu_settings__settings-item';
	if ( isset( $setting['new'] ) && $setting['new'] === true ) {
		echo ' picu_settings__settings-item__new" data-new="' . __( 'new', 'picu' );
	}
	echo '">';
	if ( ! empty( $setting['title'] ) ) {
		echo '<h2>' . $setting['title'] . '</h2>';
	}
	$disabled = false;
	if ( ! empty( $setting['disabled'] ) && $setting['disabled'] === true ) {
		$disabled = true;
	}
	?>
		<p class="picu-settings__item">
			<label for="picu_<?php echo $name; ?>"><?php echo $setting['label']; ?><br /><span class="description"><?php echo $setting['description']; ?><br /></span></label>
			<span class="picu-settings__input-wrap">
				<input type="number" name="picu_<?php echo $name; ?>" id="picu_<?php echo $name; ?>" value="<?php echo $value; ?>" placeholder="<?php if ( ! empty( $setting['placeholder'] ) ) { echo $setting['placeholder']; } ?>" <?php disabled( $disabled ); ?> <?php if ( ! empty( $setting['min'] ) ) { echo ' min="' . $setting['min'] . '"'; } ?> <?php if ( ! empty( $setting['max'] ) ) { echo ' max="' . $setting['max'] . '"'; } ?> <?php if ( ! empty( $setting['step'] ) ) { echo ' step="' . $setting['step'] . '"'; } ?> />
				<?php if ( ! empty( $setting['hint'] ) ) { ?>
				<span class="picu-settings__input__hint"><?php echo $setting['hint']; ?></span>
				<?php } ?>
				<?php
				if ( $disabled AND ! empty( $setting['disabled_hint'] ) ) { echo '<span class="picu-settings__input__hint"><span class="picu-settings__input__hint--alert">'. $setting['disabled_hint'] . '</span></span>'; } ?>
			</span>
		</p>
	<?php
	echo '</fieldset>';

	return ob_get_clean();
}


/**
 * Render text settings field.
 * 
 * @since 2.0.0
 * @param string $name The field name
 * @param array $setting The settings array
 * @param string $value The value currently stored in the db for this field
 * @return string HTML output for text settings field
 */
function picu_text_field( $name, $setting, $value ) {
	ob_start();
	echo '<fieldset class="picu_settings__settings-item';
	if ( isset( $setting['new'] ) && $setting['new'] === true ) {
		echo ' picu_settings__settings-item__new" data-new="' . __( 'new', 'picu' );
	}
	echo '">';
	if ( ! empty( $setting['title'] ) ) {
		echo '<h2>' . $setting['title'] . '</h2>';
	}
	$disabled = false;
	if ( ! empty( $setting['disabled'] ) && $setting['disabled'] === true ) {
		$disabled = true;
	}
	?>
		<p class="picu-settings__item">
			<label for="picu_<?php echo $name; ?>"><?php echo $setting['label']; ?><br /><span class="description"><?php echo $setting['description']; ?><br /></span></label>
			<span class="picu-settings__input-wrap">
				<input type="text" name="picu_<?php echo $name; ?>" id="picu_<?php echo $name; ?>" value="<?php echo $value; ?>" placeholder="<?php if ( ! empty( $setting['placeholder'] ) ) { echo $setting['placeholder']; } ?>" <?php disabled( $disabled ); ?> />
				<?php if ( ! empty( $setting['hint'] ) ) { ?>
				<span class="picu-settings__input__hint"><?php echo $setting['hint']; ?></span>
				<?php } ?>
				<?php
				if ( $disabled AND ! empty( $setting['disabled_hint'] ) ) { echo '<span class="picu-settings__input__hint"><span class="picu-settings__input__hint--alert">'. $setting['disabled_hint'] . '</span></span>'; } ?>
			</span>
		</p>
	<?php
	echo '</fieldset>';

	return ob_get_clean();
}


/**
 * Render radio button settings field.
 *
 * @since 2.0.0
 *
 * @param string $name The field name
 * @param array $setting The settings array
 * @param string $value The value currently stored in the db for this field
 * @return string HTML output for checkbox settings field
 */
function picu_radio_field( $name, $setting, $value ) {
	ob_start();
	echo '<fieldset class="picu_settings__settings-item picu_settings__settings-item__radio';
	if ( isset( $setting['new'] ) && $setting['new'] === true ) {
		echo ' picu_settings__settings-item__new" data-new="' . __( 'new', 'picu' );
	}
	echo '">';
	if ( ! empty( $setting['title'] ) ) {
		echo '<h2>' . $setting['title'] . '</h2>';
	}
	if ( ! empty( $setting['description'] ) ) {
		echo '<p class="">' . $setting['description'] . '</p>';
	}

	foreach( $setting['options'] as $option => $content ) {
		echo '<p class="picu_settings__settings-item__radio-wrapper">';
		echo '<input type="radio" id="' . $option . '" name="picu_' . $name .'" value="' . $option . '" ' . checked( $value, $option, false ) . ' /> <label class="after" for="' . $option . '">' . $content['label'] . '<br /><span class="description">' . $content['description'] . '</span></label>';
		echo '</p>';
	}

	echo '</fieldset>';

	return ob_get_clean();
}


/**
 * Render button settings field.
 *
 * @since 2.0.0
 *
 * @param string $name The field name
 * @param array $setting The settings array
 * @param string $value The value currently stored in the db for this field
 * @return string HTML output for checkbox settings field
 */
function picu_button_field( $name, $setting, $value ) {
	ob_start();
	echo '<fieldset class="picu_settings__settings-item picu_settings__settings-item__button';
	if ( isset( $setting['new'] ) && $setting['new'] === true ) {
		echo ' picu_settings__settings-item__new" data-new="' . __( 'new', 'picu' );
	}
	echo '">';
	if ( ! empty( $setting['title'] ) ) {
		echo '<h2>' . $setting['title'] . '</h2>';
	}
	echo '<p class="picu-settings__item">
	<span class="description">' . $setting['description'] . '</span><br /><br />
	<input class="button" type="submit" id="picu_' . $name .'" name="picu_' . $name .'" value="' . $setting['label'] . '" /></p>';
	echo '</fieldset>';

	return ob_get_clean();
}


/**
 * Add theme setting.
 *
 * @since 2.0.0
 *
 * @param array $options picu settings
 * @return string HTML output for the theme setting
 */
function picu_settings_theme() {
	ob_start();
?>
	<fieldset class="picu_settings__settings-item">
		<h2><?php _e( 'Theme', 'picu' ); ?></h2>
		<p><?php _e( 'Choose how your collections will be displayed:', 'picu' ); ?></p>
		<p>
			<span class="nowrap">
				<input type="radio" class="picu-radio-image" name="picu_theme" id="picu_dark_theme" value="dark" <?php checked( get_option( 'picu_theme' ), 'dark' ); ?> />
				<label for="picu_dark_theme" class="after"><img class="theme-thumbnail" src="<?php echo PICU_URL; ?>/backend/images/dark-theme.png" alt="<?php _e( 'Dark', 'picu' ); ?>" /></label>
			</span>
			<span class="nowrap">
				<input type="radio" class="picu-radio-image" name="picu_theme" id="picu_light_theme" value="light" <?php checked( get_option( 'picu_theme' ), 'light' ); ?> />
				<label for="picu_light_theme" class="after"><img class="theme-thumbnail" src="<?php echo PICU_URL; ?>/backend/images/light-theme.png" alt="<?php _e( 'Light', 'picu' ); ?>" /></label>
			</span>
		</p>
	</fieldset>
<?php
	return ob_get_clean();
}


/**
 * Add debug "setting".
 *
 * @since 2.0.0
 *
 * @return Debug page HTML output
 */
function picu_settings_debug() {
	ob_start();
?>
	<fieldset class="picu_settings__settings-item">
		<h2><?php _e( 'Debug Info', 'picu' ); ?></h2>
		<p>👉 <?php
		/* translators: %s: Opening and closing link tags */
		echo sprintf( __( 'Debug info can be found in %sTools > Site Health%s.', 'picu' ), '<a href="' . get_admin_url( null, 'site-health.php?tab=debug' ) .'">', '</a>' ); ?></p>

		<p>🛟 <?php
		/* translators: %s: Opening and closing link tags */
		echo sprintf( __( 'When submitting a %ssupport request%s, please use the button below to include your site info.', 'picu' ), '<a href="mailto:support@picu.io">', '</a>' ); ?></p>

		<?php
			require_once( ABSPATH . 'wp-admin/includes/class-wp-debug-data.php' );
			$debug_info = new WP_Debug_Data();
		?>
		<div class="picu-debug-copy-button-wrapper">
			<button type="button" class="button picu-debug-copy-button" data-clipboard-text="<?php echo esc_attr( $debug_info->format( $debug_info->debug_data(), 'debug' ) ); ?>">
				<?php /* translators: Button text */ _e( 'Copy site info to clipboard', 'picu' ); ?>
			</button>
			<span class="success hidden" aria-hidden="true"><?php /* translators: Shown,when copying to clipboard was successful */ _e( 'Copied!', 'picu' ); ?></span>
		</div>
	</fieldset>
<?php
	return ob_get_clean();
}


/**
 * Add telemetry setting.
 *
 * @since 2.0.0
 *
 * @return The telemetry page HTML output
 */
function picu_settings_telemetry() {
	ob_start();
	echo '<fieldset class="picu_settings__settings-item">';
	$default_telemetry_options = array(
		'consent' => false,
	);
	$telemetry_options = wp_parse_args( get_option( 'picu_telemetry_settings' ), $default_telemetry_options );
	?>
		<p><input type="checkbox" name="picu_telemetry_settings[consent]" id="picu_telemetry_consent"<?php checked( $telemetry_options['consent'], true ); ?> /> <label for="picu_telemetry_consent" class="after"><?php _e( 'Activate picu telemetry', 'picu' ); ?></label></p>
		<div class="picu-statistics-info-box">
		<?php
			if ( isset( $telemetry_options['consent'] ) AND $telemetry_options['consent'] == true ) { 
		?>
			<p><?php _e( 'You have activated picu telemetry and are currently allowing us to gather anonymized picu usage data.', 'picu' ); ?><br /><a href="https://picu.io/docs/telemetry/"><?php _e( 'Learn more about how we use this data', 'picu' ); ?></a></p>
		<?php
			}
			else {
		?>
			<p><?php _e( '<strong>By activating picu telemetry, you allow us to gather anonymized picu usage data.</strong><br />This data will help us to learn how picu is used and it will directly inform future development.', 'picu' ); ?></p>
			<ul>
				<li>💯 <?php _e( 'The transmitted data is 100% anonymous.', 'picu' ); ?></li>
				<li>🛑 <?php _e( 'No images or client communication are ever transmitted.', 'picu' ); ?></li>
				<li>👀 <?php _e( 'You can actually download and look at the raw data that we are compiling, once activated.', 'picu' ); ?></li>
				<li>✅ <?php _e( 'You can disable picu telemetry at any time by unchecking the box and clicking "Save Settings".', 'picu' ); ?></li>
			</ul>
			<p><a href="https://picu.io/docs/telemetry/"><?php _e( 'Learn more about how we use this data', 'picu' ); ?></a></p>
		<?php } ?>
		</div>
	<?php
		if ( isset( $telemetry_options['consent'] ) AND $telemetry_options['consent'] == true AND ! empty( picu_prepare_telemetry_data_package() ) ) { ?>
		<hr />
		<p><?php _e( 'Current telemetry data cache:', 'picu' ); ?></p>
		<?php
			echo '<p><textarea class="picu-telemetry-cache">';
			echo picu_prepare_telemetry_data_package();
			echo '</textarea></p>';
			$next_run = date( 'Y-m-d H:i:s', wp_next_scheduled( 'picu_run_telemetry_transmit' ) );
			/* translators: %s = date in the blog's date/time format */
			echo sprintf ( __( 'Next transmission scheduled for %s.', 'picu' ), get_date_from_gmt( $next_run, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) );
		?>
	<?php }
	echo '</fieldset>';

	return ob_get_clean();
}


/**
 * Check if from email/name are defined by WP Mail SMTP.
 *
 * @since 2.0.0
 *
 * @param string $setting The setting to check
 * @return bool Whether the setting is defined or not
 */
function picu_email_setting_check_wp_mail_smtp( $setting ) {
	$wp_mail_smtp_options = get_option( 'wp_mail_smtp' );
	if ( function_exists( 'wp_mail_smtp' ) AND ! empty( $wp_mail_smtp_options ) ) {
		if ( $setting == 'from_email' && $wp_mail_smtp_options['mail']['from_email_force'] == true ) {
			return true;
		}
		if ( $setting == 'from_name' && $wp_mail_smtp_options['mail']['from_name_force'] == true ) {
			return true;
		}
	}

	return false;
}


/**
 * Load Picu settings page.
 *
 * Echo settings page header, side navigation fieldsets and submit button.
 *
 * @since 0.7.0
 * @since 2.0.0 Ability to render different settings pages
 */
function picu_load_settings_page() {
	// Determin which setting to show
	$current_screen = get_current_screen();
	$current_screen = $current_screen->base;
	$settings = picu_get_settings();
	$temp = str_replace( 'picu_page_picu-', '', $current_screen );
	// Fallback to general
	if ( $temp == 'settings' ) {
		$temp = 'general';
		$this_setting = $settings['general'];
	}
	else {
		$this_setting = $settings[str_replace( 'picu_page_picu-', '', $current_screen )];
	}
?>
	<input type="checkbox" id="picu-settings-nav-toggle" />
	<div class="picu-settings__head-wrapper">
		<span class="picu-settings__head-line"><img class="picu-settings__logo" src="<?php echo PICU_URL; ?>/backend/images/picu_logo_dark_w_o_text.png" /><label for="picu-settings-nav-toggle"><?php _e( 'Settings', 'picu' ); ?></label></span>
		<nav class="picu-settings__head-nav">
			<a href="https://picu.io/docs/"><?php /* translators: Link text */ _e( 'Documentation', 'picu' ); ?></a>
			<a href="https://picu.io/support/"><?php /* translators: Link text */ _e( 'Support', 'picu' ); ?></a>
		</nav>
	</div>
	<div class="wrap">
		<h2 style="display: none;"><!-- h2 headline is necessary for WordPress to properly position admin notices --></h2>
		<?php
		$settings_notifications = get_settings_errors();

		foreach( $settings_notifications as $notification ) {
			echo '<div id="setting-error-' . $notification['code'] . '" class="notice notice-' . $notification['type'] . ' settings-' . $notification['code'] . ' is-dismissible picu-settings-notice"> 
				<p>' . $notification['message'] . '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">' . __( 'Dismiss this notice.', 'picu' ) . '</span></button></div>';
		}
		?>
		<div class="picu-settings__wrap">
			<nav class="picu_settings__nav">
				<ul>
					<?php

					foreach( $settings as $key => $setting ) {
						echo '<li class="picu_settings__nav-item';
						if ( $key === $temp ) {
							echo ' picu_settings__nav-item__current-item';
						}
						echo '"><a href="' . admin_url( 'admin.php?page=picu-' . $key ) . '">' . $setting['icon'] . ' ' . $setting['title'] . '</a></li>';
					}
					?>
				</ul>
			</nav>
			<form class="picu-settings__form" action="options.php" method="post">
				<header class="picu-settings__form-header">
					<h1 class="picu-settings__form-headline"><?php echo $this_setting['title']; ?></h1>
					<p class="picu-settings__form-description"><?php echo $this_setting['description']; ?></p>
				</header>
				<?php
					// Render settings fields
					settings_fields( 'picu_' . $temp );

					foreach( $this_setting['settings'] as $key => $setting ) {
						if ( $key == 'pro-banner' && ! empty( $setting['banner-id'] ) ) {
							echo picu_settings_pro_box( $setting['banner-id'] );
						}
						elseif ( $setting['type'] == 'checkbox' ) {
							echo picu_checkbox_field( $key, $setting, get_option( 'picu_' . $key ) );
						}
						elseif ( $setting['type'] == 'text' ) {
							echo picu_text_field( $key, $setting, get_option( 'picu_' . $key ) );
						}
						elseif ( $setting['type'] == 'number' ) {
							echo picu_number_field( $key, $setting, get_option( 'picu_' . $key ) );
						}
						elseif ( $setting['type'] == 'radio' ) {
							echo picu_radio_field( $key, $setting, get_option( 'picu_' . $key ) );
						}
						elseif ( $setting['type'] == 'button' ) {
							echo picu_button_field( $key, $setting, get_option( 'picu_' . $key ) );
						}
						elseif ( $setting['type'] == 'html' ) {
							echo $setting['output'](); // Call settings render function here
						}
					}
				?>
					<p class="picu-settings__save-wrap"><input class="button button-primary picu-settings__save-button" type="submit" name="save-picu-settings" value="<?php _e( 'Save Settings', 'picu'); ?>" /></p>
				
			</form>
		</div><!-- settings-wrap -->
	</div>
<?php
}


/**
 * Default picu settings validation.
 *
 * @since 0.7.0
 * @since 2.0.0 Just make sure the value is sanitized
 *
 * @param string $value The settings field value
 * @return string The sanatized value
 */
function picu_settings_validate( $value ) {
	return sanitize_text_field( $value );
}


/**
 * Validate from email setting.
 *
 * @since 2.0.0
 *
 * @param string $email The user entered email address
 * @return string The validated email address
 */
function picu_validate_from_email( $email ) {
	// Only run validation once:
	if ( did_action( 'validate_from_email' ) ) {
		return $email;
	}
	do_action( 'validate_from_email' );

	if ( ! empty( $email ) AND ! is_email( $email ) ) {
		add_settings_error(
			'picu',
			'from-email',
			'<strong>' . __( 'From Email', 'picu' ) . ':</strong> ' . __( 'Please enter a valid email address.', 'picu' ),
			'error'
		);
		return '';
	}

	return sanitize_email( $email );
}


/**
 * Validate notification email setting.
 *
 * @since 2.0.0
 *
 * @param string $email The user entered email address
 * @return string The validated email address
 */
function picu_validate_notification_email( $email ) {
	// Only run validation once:
	if ( did_action( 'validate_notification_email' ) ) {
		return $email;
	}
	do_action( 'validate_notification_email' );

	if ( ! empty( $email ) AND ! is_email( $email ) ) {
		add_settings_error(
			'picu',
			'notification-email',
			'<strong>' . __( 'Notification Email', 'picu' ) . ':</strong> ' . __( 'Please enter a valid email address.', 'picu' ),
			'error'
		);
		return '';
	}

	return sanitize_email( $email );
}


/**
 * Validate picu telemetry settings.
 *
 * @since 1.10.0
 *
 * @param array $args The settings form data
 */
function picu_telemetry_settings_validate( $args ) {
	// Only run validation once:
	// Circumvent a core issue, see: https://core.trac.wordpress.org/ticket/21989
	if ( did_action( 'picu_telemetry_validated_settings' ) ) {
		return $args;
	}

	// Load telemetry settings
	$picu_telemetry_settings = get_option( 'picu_telemetry_settings' );

	// Validate telemetry consent checkbox
	if ( isset( $args['consent'] ) AND $args['consent'] == 'on' ) {
		$args['consent'] = true;

		// Check if telemetry is already active. If so: do nothing!
		if ( ! empty( $picu_telemetry_settings['consent'] ) AND $picu_telemetry_settings['consent'] == true ) {
			// Update consent date
			$args['datetime'] = $picu_telemetry_settings['datetime'];
		}
		// Newly activated telemetry
		else {
			// Save telemetry option as active
			$args['consent'] = true;
			$args['datetime'] = time();

			// We do not need to clear the telemetry cache. It clears itself once it has been sent the next time
			$process_collections = get_posts( [ 'fields' => 'ids', 'post_type' => 'picu_collection', 'post_status' => [ 'approved', 'expired', 'delivered' ], 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'ASC' ] );
			foreach( $process_collections as $collection_id ) {
				picu_compile_collection_telemetry_data( $collection_id );
			}
		}
	}
	else {
		$args['consent'] = false;
		$args['datetime'] = time();
	}

	// Mark that his has already run
	do_action( 'picu_telemetry_validated_settings' );

	return $args;
}


/**
 * Upgrade settings to new schema.
 *
 * @since 2.0.0
 */
function picu_settings_upgrade() {
	// Handle regular picu settings
	$picu_settings = get_option( 'picu_settings' );
	if ( ! empty( $picu_settings ) && is_array( $picu_settings ) ) {
		// Save new settings structure
		foreach( $picu_settings as $setting_name => $value ) {
			add_option( 'picu_' . $setting_name, $value, '', false );
		}
	}

	// Handle Brand & Customize settings
	$picu_settings_brand_customize = get_option( 'picu_settings_brand_customize' );
	if ( ! empty( $picu_settings_brand_customize ) && is_array( $picu_settings_brand_customize ) ) {
		// Save new settings structure
		foreach( $picu_settings_brand_customize as $setting_name => $value ) {
			if ( $setting_name == 'show_blog_title' ) {
				add_option( 'picu_site_title', $value, '', false );
			}
			elseif ( $setting_name == 'font_method' ) {
				// Update value
				if ( $value == 'picu-bc-external-font' ) {
					$value = 'picu-external-font';
				}
				$font = get_option( 'picu_font' );
				// Use new parameter name ("method" instead of "font_method")
				$font['method'] = $value;
				update_option( 'picu_font', $font, false );
			}
			elseif ( $setting_name == 'font' ) {
				$font = get_option( 'picu_font' );
				$font['font'] = $value;
				update_option( 'picu_font', $font, false );
			}
			elseif ( $setting_name == 'external_font_name' ) {
				$font = get_option( 'picu_font' );
				$font['external_font_name'] = $value;
				update_option( 'picu_font', $font, false );
			}
			elseif ( $setting_name == 'external_font_code' ) {
				$font = get_option( 'picu_font' );
				$font['external_font_vendor'] = $value['vendor'];
				// Google Fonts
				if ( ! empty ( $value['family_parameter'] ) ) {
					$font['external_font_family_parameter'] = $value['family_parameter'];
				}
				// Typekit
				if ( ! empty ( $value['kit_id'] ) ) {
					$font['external_font_kit_id'] = $value['kit_id'];
				}
				update_option( 'picu_font', $font, false );
			}

			elseif ( $setting_name == 'image_size' ) {
				add_option( 'picu_image_size', substr( $value, 10 ), '', false );
			}
			elseif ( $setting_name == 'redirect_timer' ) {
				$after_approval = get_option( 'picu_after_approval' );
				$after_approval['redirect_timer'] = $value;
				update_option( 'picu_after_approval', $after_approval, false );
			}
			elseif ( $setting_name == 'after_approval_message' ) {
				$after_approval = get_option( 'picu_after_approval' );
				$after_approval['after_approval_message'] = $value;
				update_option( 'picu_after_approval', $after_approval, false );
			}
			elseif ( $setting_name == 'redirect' ) {
				$after_approval = get_option( 'picu_after_approval' );
				$after_approval['target_url'] = $value;
				update_option( 'picu_after_approval', $after_approval, false );
			}
			else {
				add_option( 'picu_' . $setting_name, $value, '', false );
			}
		}
	}

	// Handle Import settings
	$picu_settings_import = get_option( 'picu_settings_import' );
	if ( ! empty( $picu_settings_import ) && is_array( $picu_settings_import ) ) {
		// Save new settings structure
		foreach( $picu_settings_import as $setting_name => $value ) {
			// Use "keep_files" instead of "keep", etc.
			add_option( 'picu_import_' . $setting_name, $value . '_files', '', false );
		}
	}

	// Handle Theft Protection settings
	$picu_settings_theft_protection = get_option( 'picu_settings_theft_protection' );
	if ( ! empty( $picu_settings_theft_protection ) && is_array( $picu_settings_theft_protection ) ) {
		// Save new settings structure
		foreach( $picu_settings_theft_protection as $setting_name => $value ) {
			if ( $setting_name == 'disable_right_click' ) {
				add_option( 'picu_disable_right_click', $value, '', false );
			}
			elseif ( $setting_name == 'prevent_hotlinking' ) {
				// do nothing
			}
			else {
				$watermark = get_option( 'picu_watermark', [] );
				$watermark[$setting_name] = $value;
				update_option( 'picu_watermark', $watermark, false );
			}
		}
	}

	update_option( 'picu_settings_version', '2.0.0', false );
}


/**
 * Render Pro banners in settings.
 *
 * @since 2.4.0
 * 
 * @param string $banner_id The banner ID
 */
function picu_settings_pro_box( $banner_id ) {
	$ad = [];

	switch( $banner_id ) {
		case 'custom-thank-you-page':
			$ad = [
				[
					'id' => 'redirect',
					'headline' => __( 'Improve client loyalty with a custom „Thank You“ page', 'picu' ),
					'icon' => '🔀',
					'link-text' => __( 'Learn how to redirect to a custom page after approval', 'picu' ),
					'url' => 'https://go.picu.io/custom-thank-you-page',
					'image' => PICU_URL . '/backend/images/pro/redirect.png',
				]
			];
			break;
		case 'customize-collection':
			$ad = [
				[
					'id' => 'color',
					'headline' => __( 'Want to add your logo and customize the theme color?', 'picu' ),
					'icon' => '💡',
					'link-text' => __( 'Learn how to add personal branding to your galleries', 'picu' ),
					'url' => 'https://go.picu.io/customize-collection',
					'image' => PICU_URL . '/backend/images/pro/color-picker.png',
				]
			];
			break;
		case 'collection-protection':
			$ad = [
				[
					'id' => 'watermark',
					'headline' => __( 'Additional protection for your work', 'picu' ),
					'icon' => '🔒',
					'link-text' => __( 'Learn how to add a watermark to your images during upload', 'picu' ),
					'url' => 'https://go.picu.io/collection-protection',
					'image' => PICU_URL . '/backend/images/pro/watermark.png',
				]
			];
			break;
		case 'email-message-templates':
			$ad = [
				[
					'id' => 'message-templates',
					'headline' => __( 'Save time with re-usable message templates', 'picu' ),
					'icon' => '🗄️',
					'link-text' => __( 'Learn how to use prepared messages for different types of clients', 'picu' ),
					'url' => 'https://go.picu.io/email-message-templates',
					'image' => PICU_URL . '/backend/images/pro/email-templates.png',
				]
			];
			break;
	}

	$ad = $ad[rand(0,count($ad)-1)];

	ob_start();
	?>
	<div class="picu-pro-box picu-pro-box--<?php echo $ad['id']; ?>">
		<div class="picu-pro-box__inner">
			<div class="picu-pro-box__icon">Pro</div>
			<h2 class="picu-pro-box__headline"><?php echo $ad['headline']; ?></h2>
			<p class="picu-pro-box__claim"><?php echo $ad['icon']; ?> <a class="picu-pro-box__link" href="<?php echo $ad['url']; ?>" target="_blank"><?php echo $ad['link-text']; ?></a></p>
		</div>
		<div class="picu-pro-box__image_wrap">
			<img class="picu-pro-box__image" src="<?php echo $ad['image']; ?>" />
		</div>
	</div>
	<?php
	return ob_get_clean();
}