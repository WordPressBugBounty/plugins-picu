<?php
/**
 * Emails
 *
 * This class handles all emails sent through picu
 *
 * @since 1.7.0
 */
defined( 'ABSPATH' ) || exit;

use Pelago\Emogrifier\CssInliner;
use Pelago\Emogrifier\HtmlProcessor\CssToAttributeConverter;
use Pelago\Emogrifier\HtmlProcessor\HtmlPruner;

/**
 * Picu_Emails Class
 * 
 * @since 1.7.0
 */

class Picu_Emails {
	/**
	 * Whether to send email in HTML
	 * 
	 * @since 1.7.0
	 */
	private $html = true;

	/**
	 * Holds the email content type
	 * 
	 * @since 1.7.0
	 */
	private $content_type;

	/**
	 * Collection post object
	 * 
	 * @since 2.0.0
	 */
	private $post;

	/**
	 * Collection post ID
	 * 
	 * @since 2.0.0
	 */
	private $post_id;

	/**
	 * Holds the from address
	 * 
	 * @since 1.7.0
	 */
	private $from_address;
	
	/**
	 * Holds the from name
	 * 
	 * @since 1.7.0
	 */
	private $from_name;

	/**
	 * Holds the to address
	 * 
	 * @since 2.0.0
	 */
	private $to_address;

	/**
	 * Holds the cc address
	 * 
	 * @since 1.7.0
	 */
	private $cc_address;

	/**
	 * Holds the bcc address
	 * 
	 * @since 1.7.0
	 */
	private $bcc_address;

	/**
	 * Holds the email headers
	 * 
	 * @since 1.7.0
	*/
	private $headers;

	/**
	 * Holds the email subject
	 * 
	 * @since 1.7.0
	 */
	private $subject;

	/**
	 * The email parts for the content of the mail
	 */
	private $mail_parts;

	/**
	 * Holds the URL to the picu logo
	 * 
	 * @since 1.7.0
	 */
	private $picu_logo_uri;

	/**
	 * Holds the hash for a recipients identification
	 * 
	 * @since 2.0.0
	 */
	private $ident;

	/**
	 * Holds URL to the photographer logo
	 * 
	 * @since 1.7.0
	 */
	private $photographer_logo_uri;

	/**
	 * Holds the attachments
	 * 
	 * @since 1.7.0
	 */
	private $attachments = [];

	/**
	 * Holds the mail context
	 * 
	 * @since 1.7.0
	 */
	private $mail_context;


	/**
	 * Get things going
	 *
	 * @param int $post_id The post ID
	 * @since 1.7.0
	 */
	public function __construct( int $post_id ) {

		$this->post_id = $post_id;
		$this->post = get_post( $post_id );

		// Set some defaults in here
		$this->html = $this->plain_or_html();
		$this->content_type = $this->get_content_type();
		$this->picu_logo_uri = $this->get_picu_logo_uri();
		$this->photographer_logo_uri = $this->get_photographer_logo_uri();
	}


	/**
	 * Set a property
	 *
	 * @param string $key The Property name
	 * @param mixed $value The Property value
	 * @since 1.7.0
	 */
	public function __set( $key, $value ) {
		$this->$key = $value;
	}


	/**
	 * Get a property
	 *
	 * @param string $key The Property name
	 * @since 1.7.0
	 */
	public function __get( $key ) {
		return $this->$key;
	}


	/**
	 * Set all our arguments
	 *
	 * @param array $args All the properties
	 * @since 1.7.0
	 */
	public function setArgs( $args ) {
		foreach ( $args as $key => $value ) {
			$this->$key = $value;
		}
	}


	/**
	 * Send html email or not
	 * 
	 * @return bool Whether to send HTML email
	 * @since 1.7.0
	 */
	public function plain_or_html() {
		if ( get_option( 'picu_send_html_mails' ) ) {
			return true;
		}

		return false;
	}


	/**
	 * Get the email from name
	 *
	 * @since 1.7.0
	 */
	public function get_from_name() {

		// Set default
		if ( ! $this->from_name ) {

			// Use default name
			$from_name = get_bloginfo( 'name' );

			// Get name from options
			if ( ! empty( get_option( 'picu_from_name' ) ) ) {
				$from_name = get_option( 'picu_from_name' );
			}

			$this->from_name = $from_name;
		}

		// Apply new filter
		$from_name = apply_filters( 'picu_email_from_name', $this->from_name, $this->mail_context, $this->post_id );
		$this->from_name = sanitize_text_field( $from_name );

		return $this->from_name;
	}


	/**
	 * Get the email from address
	 *
	 * @since 1.7.0
	 */
	public function get_from_address() {

		// Set default
		if ( empty( $this->from_address ) || ! is_email( $this->from_address ) ) {

			// Use default address
			$blog_url = parse_url( get_bloginfo( 'url' ) );
			$blog_url = $blog_url['host'];
			$from_address = 'no-reply@' . $blog_url;

			// Get address from options
			if ( ! empty( get_option( 'picu_from_email' ) ) ) {
				$from_address = get_option( 'picu_from_email' );
			}

			$this->from_address = $from_address;
		}

		// Apply new filter
		$from_address = apply_filters( 'picu_email_from_address', $this->from_address, $this->mail_context, $this->post_id );
		$this->from_address = sanitize_email( $from_address );

		return $this->from_address;
	}


	/**
	 * Get cc address
	 * 
	 * @since 1.7.0
	 */
	public function get_cc_address() {

		if ( empty( $this->cc_address ) || ! is_email( $this->cc_address ) ) {
			$this->cc_address = '';
		}

		$this->cc_address = apply_filters( 'picu_email_cc', $this->cc_address, $this->mail_context, $this->post_id );

		return $this->cc_address;
	}


	/**
	 * Get bcc address
	 * 
	 * @since 1.7.0
	 */
	public function get_bcc_address() {

		if ( empty( $this->bcc_address ) || ! is_email( $this->bcc_address ) ) {
			$this->bcc_address = '';
		}

		$this->bcc_address = apply_filters( 'picu_email_bcc', $this->bcc_address, $this->mail_context, $this->post_id );

		return $this->bcc_address;
	}


	/**
	 * Get the email subject
	 * 
	 * @since 1.7.0
	 */
	public function get_subject() {

		// Set the default
		if ( ! $this->subject ) {
			$this->subject = wp_strip_all_tags( $this->post->post_title );
		}

		$this->subject = apply_filters( 'picu_email_subject', $this->subject, $this->mail_context, $this->post_id );

		return $this->subject;
	}


	/**
	 * Get the plain email content
	 * 
	 * @since 1.7.0
	 */
	public function get_plain_message() {

		$message = '';

		foreach( $this->mail_parts as $part ) {

			if ( isset( $part['type'] ) AND $part['type'] == 'text' ) {
				$message = $message . wp_strip_all_tags( $part['text'] ) . "\n\n";
			}
			elseif ( isset( $part['type'] ) AND $part['type'] == 'button' ) {
				$message = $message . $part['text'] . ': ' . $part['url'] .  "\n\n";
			}
			elseif ( isset( $part['type'] ) AND $part['type'] == 'password' ) {
				/* translators: Used in an email, the collection password follows */
				$message = $message . __( 'Password:', 'picu' ) . $part['password'] .  "\n\n";
			}
			// Generic, looking for text 
			elseif ( ! empty( $part['text'] ) ) {
				$message = $message . wp_strip_all_tags( $part['text'] ) . "\n\n";
			}
		}

		return $message;
	}


	/**
	 * Get the email content type
	 *
	 * @since 1.7.0
	 */
	public function get_content_type() {
		if ( ! $this->content_type ) {
			if ( $this->html ) {
				$this->content_type = apply_filters( 'picu_email_default_content_type', 'text/html', $this );
			}
			else {
				$this->content_type = 'text/plain';
			}
		}

		$this->content_type = apply_filters( 'picu_email_content_type', $this->content_type, $this->mail_context, $this->post_id );

		return $this->content_type;
	}


	/**
	 * Get the email headers
	 *
	 * @since 1.7.0
	 */
	public function get_headers() {

		if ( ! $this->headers ) {
			$this->headers  = "From: {$this->get_from_name()} <{$this->get_from_address()}>\r\n";
			$this->headers .= "Content-Type: {$this->content_type}; charset=utf-8\r\n";
		}

		$cc_address = $this->get_cc_address();
		if ( ! empty( $cc_address ) ) {
			$this->headers .= "Cc: {$cc_address}\r\n";
		}

		$bcc_address = $this->get_bcc_address();
		if ( ! empty( $bcc_address ) ) {
			$this->headers .= "Bcc: {$bcc_address}\r\n";
		}

		return apply_filters( 'picu_email_headers', $this->headers, $this->mail_context, $this->post_id, $this->ident );
	}


	/**
	 * Get the picu logo uri, if "picu_love" setting is set to "on"
	 *
	 * @return string|null
	 * @since 1.7.0
	 */
	public function get_picu_logo_uri() {

		if ( ! $this->picu_logo_uri ) {

			if ( get_option( 'picu_picu_love' ) != 'on' ) {
				return null;
			}

			if ( get_option( 'picu_theme' ) == 'light' ) {
				$this->picu_logo_uri = PICU_URL . 'backend/images/picu_logo_dark.png';
			} else {
				$this->picu_logo_uri = PICU_URL . 'backend/images/picu_logo_light.png';
			}
		}

		return $this->picu_logo_uri;
	}


	/**
	 * Get the photographers logo
	 *
	 * @return string|null
	 * @since 1.7.0
	 */
	public function get_photographer_logo_uri() {

		if ( ! $this->photographer_logo_uri ) {
			$this->photographer_logo_uri = apply_filters( 'picu_logo', '', $this->mail_context, $this->post_id );
		}

		return $this->photographer_logo_uri;
	}


	/**
	 * Apply inline styles to dynamic content.
	 *
	 * We only inline CSS for html emails, and to do so we use Emogrifier library (if supported).
	 *
	 * @param string|null $content Content that will receive inline styles
	 * @return string
	 * @version 1.7.0
	 */
	public function style_inline( $content ) {
		if ( in_array( $this->content_type, array( 'text/html', 'multipart/alternative' ), true ) ) {

			$css_inliner_class = CssInliner::class;

			if ( $this->supports_emogrifier() && class_exists( $css_inliner_class ) ) {
				try {
					$css_inliner = CssInliner::fromHtml( $content )->addExcludedSelector( 'head, meta, title' )->inlineCss();

					$dom_document = $css_inliner->getDomDocument();

					HtmlPruner::fromDomDocument( $dom_document )->removeElementsWithDisplayNone();
					$content = CssToAttributeConverter::fromDomDocument( $dom_document )
						->convertCssToVisualAttributes()
						->render();
				} catch ( Exception $e ) {
					// TODO: add some error logging?
				}
			}
		}

		return $content;
	}

	/**
	 * Return if emogrifier library is supported.
	 *
	 * @version 4.0.0
	 * @since 1.7.0
	 * @return bool
	 */
	protected function supports_emogrifier() {
		return class_exists( 'DOMDocument' );
	}


	/**
	 * Build the final email
	 * 
	 * @return string
	 * @since 1.7.0
	 */
	public function build() {

		$args = get_object_vars( $this );

		// Filter mail parts
		if ( ! empty( $args['mail_parts'] ) AND is_array( $args['mail_parts'] ) ) {
			$args['mail_parts'] = apply_filters( 'picu_mail_parts', $args['mail_parts'], $this->mail_context, $this->post_id );
		}

		// Prepare plain email
		if ( $this->html === false ) {
			$mail_message = $this->get_plain_message();
			return wp_strip_all_tags( $mail_message );
		}

		// Make $args available inside all of the templates as $picu_template_args
		set_query_var( 'picu_template_args', $args );

		ob_start();

		// Require base templates
		picu_get_template_part( 'emails/email', 'header', true, $args );
		picu_get_template_part( 'emails/email', 'content', true, $args );
		picu_get_template_part( 'emails/email', 'footer', true, $args );

		$body = $this->style_inline( ob_get_clean() );

		return $body;
	}


	/**
	 * Send the email
	 *
	 * @since 1.7.0
	 */
	public function send() {
		
		if ( ! did_action( 'init' ) && ! did_action( 'admin_init' ) ) {
			/* translators: Error message */
			_doing_it_wrong( __FUNCTION__, __( 'You cannot send email with Picu_Emails until init/admin_init has been reached', 'picu' ), null );
			return false;
		}

		$subject = $this->get_subject();

		$mail_content = $this->build();
		
		$this->attachments = apply_filters( 'picu_email_attachments', $this->attachments, $this->mail_context, $this->post_id );

		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

		$sent = wp_mail( $this->to_address, $subject, $mail_content, $this->get_headers(), $this->attachments );

		add_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

		// TODO: Get last error, log why sending the mail failed

		if ( ! $sent ) {
			
			$log_message = sprintf(
				/* translators: Error message, when sending an email failed, written into the debug log; %s = timestamp, the to address and the email subject */
				__( "Email from picu failed to send.\n Send time: %s \n To: %s \n Subject: %s \n\n", 'picu' ),
				date_i18n( 'F j Y H:i:s', time() ),
				$this->to_address,
				$subject
			);

			error_log( $log_message );
		}
		else {
			// Execute after email has been sent successfully
			do_action( 'picu_after_email_sent', $this->mail_context, $this->post_id );
		}

		return $sent;
	}


	/**
	 * Converts text to formatted HTML. This is primarily for turning line breaks into <p> and <br/> tags.
	 *
	 * @since 1.7.0
	 */
	public function text_to_html( $message ) {

		if ( 'text/html' == $this->content_type || true === $this->html ) {
			$message = wpautop( $message );
			$message = make_clickable( $message );
			$message = str_replace( '&#038;', '&amp;', $message );
		}

		return $message;
	}
}
