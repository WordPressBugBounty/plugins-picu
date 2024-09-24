<?php
/**
 * Email Password
 *
 * This template can be overridden by copying it to yourtheme/picu/emails/email-password.php. 
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
		<td class="password">
			<?php 
				$password_string = __( 'Password:', 'picu' ) . ' ' . $args['password'];
				echo $password_string;
			?>
		</td>
	</tr>
</table>