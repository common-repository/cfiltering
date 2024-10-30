<?php

if ( !defined( 'ABSPATH' ) && !defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit();

require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'collaborative-filtering.php' );

global $cf_db, $cf_option, $cf_post, $cf_user;
if ( !is_multisite() ) {
	$cf_db->uninstall();
	$cf_option->uninstall();
	$cf_post->uninstall();
	$cf_user->uninstall();

	wp_clear_scheduled_hook( 'cf_calculate_event' );
	wp_clear_scheduled_hook( 'cf_calculate_hook' );
	wp_clear_scheduled_hook( 'cf_clear_event' );
	wp_clear_scheduled_hook( 'cf_clear_hook' );
} else {
	global $wpdb;
	$current_blog_id = get_current_blog_id();
	$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
	foreach ( $blog_ids as $blog_id ) {
		switch_to_blog( $blog_id );

		$cf_db->uninstall();
		$cf_option->uninstall();
		$cf_post->uninstall();
		$cf_user->uninstall();

		wp_clear_scheduled_hook( 'cf_calculate_event' );
		wp_clear_scheduled_hook( 'cf_calculate_hook' );
		wp_clear_scheduled_hook( 'cf_clear_event' );
		wp_clear_scheduled_hook( 'cf_clear_hook' );
	}
	switch_to_blog( $current_blog_id );
}

