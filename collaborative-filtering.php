<?php
/*
  Plugin Name: CFiltering
  Plugin URI: https://wordpress.org/plugins/cfiltering/
  Description: Recommendation plugin using collaborative filtering
  Author: 123teru321
  Version: 1.5.0
  Author URI: http://technote.space/
  Text Domain: CollaborativeFiltering
  Domain Path: /languages
*/

if ( !defined( 'ABSPATH' ) )
	exit;

if ( defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) )
	return;

if ( defined( 'WP_INSTALLING' ) && WP_INSTALLING )
	return;

//plugin
define( 'COLLABORATIVE_FILTERING_PLUGIN', 'COLLABORATIVE_FILTERING_PLUGIN' );

//plugin name
define( 'COLLABORATIVE_FILTERING_PLUGIN_NAME', 'CFiltering' );

//plugin version
define( 'COLLABORATIVE_FILTERING_PLUGIN_VERSION', '1.5.0' );

//required php version
define( 'COLLABORATIVE_FILTERING_REQUIRED_PHP_VERSION', '5.4' );

//plugin file name
define( 'COLLABORATIVE_FILTERING_PLUGIN_FILE_NAME', __FILE__ );

//plugin directory
define( 'COLLABORATIVE_FILTERING_PLUGIN_DIR', dirname( COLLABORATIVE_FILTERING_PLUGIN_FILE_NAME ) );

//plugin directory name
define( 'COLLABORATIVE_FILTERING_PLUGIN_DIR_NAME', basename( COLLABORATIVE_FILTERING_PLUGIN_DIR ) );

//plugin base name
define( 'COLLABORATIVE_FILTERING_PLUGIN_BASE_NAME', plugin_basename( COLLABORATIVE_FILTERING_PLUGIN_FILE_NAME ) );

//text domain
define( 'COLLABORATIVE_FILTERING_TEXT_DOMAIN', 'CollaborativeFiltering' );
load_plugin_textdomain( COLLABORATIVE_FILTERING_TEXT_DOMAIN, false, COLLABORATIVE_FILTERING_PLUGIN_DIR_NAME . DIRECTORY_SEPARATOR . 'languages' );

if ( version_compare( phpversion(), COLLABORATIVE_FILTERING_REQUIRED_PHP_VERSION, '<' ) ) {
	// php version isn't high enough
	require_once 'unsupported.php';
	return;
}

//load
require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . "load.php";

//functions.php
require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . "functions.php";


