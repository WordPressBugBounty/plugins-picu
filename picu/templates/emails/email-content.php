<?php
/**
 * Email Content
 *
 * This template can be overridden by copying it to yourtheme/picu/emails/email-content.php. 
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
?>
				<table>
					<tr>
						<td>
							<?php
								if ( $args['photographer_logo_uri'] ) {
									echo '<p style="margin-bottom: 20px;"><a href="' . esc_url( home_url( '/' ) ) . '"><img class="logo" src="' . $args['photographer_logo_uri'] . '" /></a></p>';
								}
							?>

							<?php
								foreach ( $args['mail_parts'] as $mail_part ) {
									picu_get_template_part( 'emails/' . $mail_part['type'], null, true, $mail_part );
								}
							?>
						</td>
					</tr>
				</table>