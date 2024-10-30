<?php
namespace CollaborativeFiltering;

if ( defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) || defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.1 403 Forbidden' );
	echo 'Forbidden';
	die;
}

if ( !defined( 'COLLABORATIVE_FILTERING_AJAX_ACCESS' ) ) {
	define( 'COLLABORATIVE_FILTERING_AJAX_ACCESS', true );
}

if ( !defined( 'WP_USE_THEMES' ) && !defined( 'COLLABORATIVE_FILTERING_AJAX_INCLUDE' ) ) {

	if ( empty( $_REQUEST['action'] ) )
		die( '0' );

	define( 'COLLABORATIVE_FILTERING_AJAX_INCLUDE', true );
	define( 'WP_USE_THEMES', false );
	define( 'DOING_AJAX', true );

	require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'wp-load.php' );

}

header( 'HTTP/1.1 500 Internal Server Error' );
echo 'Internal Server Error';
die( );
