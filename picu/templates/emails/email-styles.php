<?php
/**
 * Email Styles
 *
 * This template can be overridden by copying it to yourtheme/picu/emails/email-styles.php. 
 *
 * Please note: On occasion picu will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://picu.io/docs/template-structure/
 * @version 1.7.0
 */

defined( 'ABSPATH' ) || exit;


/**
 * Dark Theme (default)
 */
$primary_color = '#7ad03a';
$primary_color = apply_filters( 'picu_primary_color', $primary_color );

$styles = apply_filters( 'picu_mail_styles', [
	'max_width' => '600px',
	'background' => '#17171d',
	'background_content' => '#1D1D24',
	'primary_color' => $primary_color,
	'border_line' => '#333',
	'text_color' => '#eee',
	'font_size' => '16px',
	'font_size_button' => '16px',
	'button_text' => '#fff',
	'button_text_hover' => '#fff',
	'button_background' => $primary_color,
	'button_background_hover' => $primary_color,
	'button_text--secondary' => '#fff',
	'button_text_hover--secondary' => '#fff',
	'button_background--secondary' => '#17171d',
	'button_background_hover--secondary' => '#17171d',

	'link_footer' => '#999999'
] );


/**
 * Light Theme
 */
if ( get_option( 'picu_theme') == 'light' ) {
	$primary_color = '#2f92a7';
	$primary_color = apply_filters( 'picu_primary_color', $primary_color );
	$styles['background'] = '#f6f6f6';
	$styles['background_content'] = 'white';
	$styles['primary_color'] = $primary_color;
	$styles['border_line'] = '#ededed';
	$styles['text_color'] = '#666666';
	$styles['button_text'] = '#fff';
	$styles['button_text_hover'] = '#fff';
	$styles['button_background'] = $primary_color;
	$styles['button_background_hover'] = $primary_color;
	$styles['button_text--secondary'] = '#666666';
	$styles['button_text_hover--secondary'] = '#666666';
	$styles['button_background--secondary'] = '#f6f6f6';
	$styles['button_background_hover--secondary'] = '#f6f6f6';
	$styles['link_footer'] = '#999999';
}


/**
 * Make all styles filterable
 */
$styles = apply_filters( 'picu_email_styles', $styles );

// !important; is a gmail hack to prevent styles being stripped if it doesn't like something.
// body{padding: 0;} ensures proper scale/positioning of the email in the iOS native email app.
?>


/* -------------------------------------
	GLOBAL
------------------------------------- */
* {
	font-family: "Helvetica Neue", "Helvetica", Helvetica, Arial, sans-serif;
	font-size: 100%;
	line-height: 1.6em;
	margin: 0;
	padding: 0;
}

img {
	max-width: 100%;
	height: auto;
}

html {
	height: 100%;
}

body {
	background: <?php echo esc_attr( $styles['background_content'] ); ?>;
	border-bottom: 10px solid <?php echo esc_attr( $styles['primary_color'] ); ?>;
	padding: 0;
	font-family: "Helvetica Neue", "Helvetica", Helvetica, Arial, sans-serif;
	-webkit-font-smoothing: antialiased;
	height: 100%;
	-webkit-text-size-adjust: none;
	width: 100% !important;
	box-sizing: border-box;
}

hr {
	margin: 30px 0 25px;
	border: none;
	border-bottom: 1px solid <?php echo esc_attr( $styles['border_line'] ); ?>;
}


/* -------------------------------------
	ELEMENTS
------------------------------------- */
a {
	color: <?php echo esc_attr( $styles['text_color'] ); ?>;
}

a:hover {
	color: <?php echo esc_attr( $styles['button_text_hover'] ); ?>;
}

.logo {
	height: 55px;
	width: auto;
}

.button {
	margin: 0 auto;
	margin-top: 30px;
	margin-bottom: 10px;
	width: auto !important;
}

.button td {
	border-radius: 3px;
	font-family: "Helvetica Neue", Helvetica, Arial, "Lucida Grande", sans-serif; 
	font-size: <?php echo esc_attr( $styles['font_size_button'] ); ?>; 
	text-align: center;
	vertical-align: top;
}

.button td:hover {
	background-color: <?php echo esc_attr( $styles['button_background_hover'] ); ?>;
}

.button td a {
	background-color: <?php echo esc_attr( $styles['button_background'] ); ?>;
	border: solid 10px <?php echo esc_attr( $styles['button_background'] ); ?>; // border-hack is used to add padding in Outlook
	border-radius: 3px;
	padding: 0 20px;
	display: inline-block;
	color: <?php echo esc_attr( $styles['button_text'] ); ?>;
	cursor: pointer;
	font-weight: bold;
	line-height: 2;
	text-decoration: none;
}

.button td a:hover {
	color: <?php echo esc_attr( $styles['button_text_hover'] ); ?>;
	background-color: <?php echo esc_attr( $styles['button_background_hover'] ); ?>;
	border-color: <?php echo esc_attr( $styles['button_background_hover'] ); ?>;
}

.button--secondary td {
	border-color: <?php echo esc_attr( $styles['button_background--secondary'] ); ?>;
}

.button--secondary td:hover {
	background-color: <?php echo esc_attr( $styles['button_background_hover--secondary'] ); ?>;
}

.button--secondary td a {
	background-color: <?php echo esc_attr( $styles['button_background--secondary'] ); ?>;
	border-color: <?php echo esc_attr( $styles['button_background--secondary'] ); ?>;
	color: <?php echo esc_attr( $styles['button_text--secondary'] ); ?>;
}

.button--secondary td:hover a {
	color: <?php echo esc_attr( $styles['button_text_hover--secondary'] ); ?>;
	background-color: <?php echo esc_attr( $styles['button_background_hover--secondary'] ); ?>;
	border-color: <?php echo esc_attr( $styles['button_background_hover--secondary'] ); ?>;
}

.password {
	border-radius: 3px;
	font-family: "Helvetica Neue", Helvetica, Arial, "Lucida Grande", sans-serif; 
	font-size: <?php echo esc_attr( $styles['font_size_button'] ); ?>; 
	text-align: center;
	vertical-align: top;
	padding-top: 20px;
	color: <?php echo esc_attr( $styles['text_color'] ); ?>;
}

.last {
	margin-bottom: 0;
}

.first {
	margin-top: 0;
}

.padding {
	padding: 10px 0;
}

.additional-info {
	padding: 1em 1.5em;
	border: 1px solid <?php echo esc_attr( $styles['border_line'] ); ?>;
}


/* -------------------------------------
	BODY
------------------------------------- */
table.body-wrap {
	background: <?php echo esc_attr( $styles['background'] ); ?>;
	width: 100%;
	padding: 15px 15px 50px;
}

table.body-wrap	.container {
	background: <?php echo esc_attr( $styles['background_content'] ); ?>;
}

table.body-wrap .container p {
	color: <?php echo esc_attr( $styles['text_color'] ); ?>;
	font-size: <?php echo esc_attr( $styles['font_size'] ); ?>;
}

table.body-wrap .container a {
	word-wrap: anywhere;
	hyphens: auto;
	-webkit-hyphens: auto;
}

/* -------------------------------------
	FOOTER
------------------------------------- */
table.footer-wrap {
	width: 100%;
	padding: 30px 20px;
	background: <?php echo esc_attr( $styles['background_content'] ); ?>;
}

table.footer-wrap .container p {
	color: <?php echo esc_attr( $styles['text_color'] ); ?>;
	font-size: <?php echo esc_attr( $styles['font_size'] ); ?>;
}

table.footer-wrap a {
	color: <?php echo esc_attr( $styles['link_footer'] ); ?>;
}


/* -------------------------------------
	TYPOGRAPHY
------------------------------------- */
h1, 
h2, 
h3 {
	color: <?php echo esc_attr( $styles['text_color'] ); ?>;
	font-family: "Helvetica Neue", Helvetica, Arial, "Lucida Grande", sans-serif;
	font-weight: 200;
	line-height: 1.2em;
	margin: 40px 0 10px;
}

h1 {
	font-size: 36px;
}

h2 {
	font-size: 28px;
}

h3 {
	font-size: 22px;
}

p, 
ul, 
ol {
	font-size: 14px;
	font-weight: normal;
	margin-bottom: 10px;
}

ul li, 
ol li {
	margin-left: 5px;
	list-style-position: inside;
}


/* ---------------------------------------------------
	RESPONSIVENESS
------------------------------------------------------ */
/* Set a max-width, and make it display as block so it will automatically stretch to that width, but will also shrink down on a phone or something */
.container {
	clear: both !important;
	display: block !important;
	margin: 0 auto !important;
	max-width: <?php echo esc_attr( $styles['max_width'] ); ?> !important;
}

/* Set the padding on the td rather than the div for Outlook compatibility */
table.body-wrap .container {
	padding: 20px;
}

/* This should also be a block element, so that it will fill 100% of the .container */
.content {
	display: block;
	margin: 0 auto;
	max-width: <?php echo esc_attr( $styles['max_width'] ); ?>;
}

/* Let's make sure tables in the content area are 100% wide */
.content table {
	width: 100%;
}

@media screen and ( max-width: 600px ) {
	table.body-wrap {
		padding: 15px;
	}
}