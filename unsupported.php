<?php

if ( !defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) ) {
	die();
}

function cf_old_php_message()
{
	$ret = sprintf( __( 'Your PHP version is %s.', COLLABORATIVE_FILTERING_TEXT_DOMAIN ), phpversion() ) . '<br>';
	$ret .= __( 'Please update your PHP.', COLLABORATIVE_FILTERING_TEXT_DOMAIN ) . '<br>';
	$ret .= sprintf( __( '<strong>%s</strong> requires PHP version %s or above.', COLLABORATIVE_FILTERING_TEXT_DOMAIN ), COLLABORATIVE_FILTERING_PLUGIN_NAME, COLLABORATIVE_FILTERING_REQUIRED_PHP_VERSION );
	return $ret;
}

function cf_old_php_admin_notices()
{
	?>
	<div class="notice error notice-error">
		<p><?php echo cf_old_php_message(); ?></p>
	</div>
	<?php
}

add_action( 'admin_notices', 'cf_old_php_admin_notices' );
