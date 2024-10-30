<?php
namespace CollaborativeFiltering;

if ( !defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) )
	exit;

define( 'COLLABORATIVE_FILTERING_ROOT_DIR', COLLABORATIVE_FILTERING_PLUGIN_DIR );
define( 'COLLABORATIVE_FILTERING_LIB_ROOT_DIR', COLLABORATIVE_FILTERING_ROOT_DIR . DIRECTORY_SEPARATOR . "lib" );
define( 'COLLABORATIVE_FILTERING_COMMON_DIR', COLLABORATIVE_FILTERING_ROOT_DIR . DIRECTORY_SEPARATOR . "common" );
define( 'COLLABORATIVE_FILTERING_LIB_COMMON_DIR', COLLABORATIVE_FILTERING_LIB_ROOT_DIR . DIRECTORY_SEPARATOR . "common" );

//settings.php
@require_once( COLLABORATIVE_FILTERING_ROOT_DIR . DIRECTORY_SEPARATOR . "settings.php" );

function cf_scandir( $dir )
{
	if ( is_dir( $dir ) ) {
		foreach ( scandir( $dir ) as $file ) {
			if ( preg_match( "/^[^\\.].*\\.php$/", $file ) ) {
				require_once $dir . DIRECTORY_SEPARATOR . $file;
			}
		}
	}
}

//common
cf_scandir( COLLABORATIVE_FILTERING_LIB_COMMON_DIR );

//models
cf_scandir( COLLABORATIVE_FILTERING_LIB_MODELS_DIR );

@require_once( COLLABORATIVE_FILTERING_ROOT_DIR . DIRECTORY_SEPARATOR . "db-config.php" );

foreach ( array( COLLABORATIVE_FILTERING_LIB_SERVICES_DIR, COLLABORATIVE_FILTERING_COMMON_DIR, COLLABORATIVE_FILTERING_MODELS_DIR, COLLABORATIVE_FILTERING_SERVICES_DIR ) as $dir ) {
	cf_scandir( $dir );
}

