<?php
/**
 * Email Header
 *
 * This template can be overridden by copying it to yourtheme/picu/emails/email-header.php. 
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
<!doctype html>
<html>
<head>

	<meta name="viewport" content="width=device-width">
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	
	<style><?php picu_get_template_part( 'emails/email', 'styles', true, $args ); ?></style>
	<title><?php echo $args['subject']; ?></title>

</head>

<body>

	<table class="body-wrap">
		<tr>
			<td class="container">
				<div class="content">