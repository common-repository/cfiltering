<?php
namespace CollaborativeFilteringService;

if ( !defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) )
	exit;

class CollaborativeFiltering_System extends CollaborativeFiltering_Service_Base
{

	private static $_instance = null;

	protected function __construct()
	{
		$this->initialize();
	}

	public static function get_instance()
	{
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new CollaborativeFiltering_System();
		}
		return self::$_instance;
	}

	private function initialize()
	{
		//		load_plugin_textdomain( COLLABORATIVE_FILTERING_TEXT_DOMAIN, false, COLLABORATIVE_FILTERING_PLUGIN_DIR_NAME . DIRECTORY_SEPARATOR . 'languages' );

		if ( $this->apply_filters( "check_update", COLLABORATIVE_FILTERING_CHECK_UPDATE ) ) {
			if ( !class_exists( '\PucFactory' ) ) {
				require_once COLLABORATIVE_FILTERING_LIB_LIBRARY_DIR . DIRECTORY_SEPARATOR . 'plugin-update-checker' . DIRECTORY_SEPARATOR . 'plugin-update-checker.php';
			}
			\PucFactory::buildUpdateChecker(
				COLLABORATIVE_FILTERING_UPDATE_INFO_FILE_URL,
				COLLABORATIVE_FILTERING_PLUGIN_FILE_NAME,
				COLLABORATIVE_FILTERING_PLUGIN_DIR_NAME
			);
		}

		add_action( 'init', function () {
			$this->check_updated();
		} );

		add_filter( 'cf_http_host', function () {
			return isset( $_SERVER['HTTP_X_FORWARDED_HOST'] ) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : $_SERVER['HTTP_HOST'];
		} );
	}

	private function check_updated()
	{
		global $cf_option;
		$version = $cf_option->get( 'version', -1 );
		if ( version_compare( $version, COLLABORATIVE_FILTERING_PLUGIN_VERSION, '<' ) ) {
			$cf_option->set( 'version', COLLABORATIVE_FILTERING_PLUGIN_VERSION );
			$this->do_action( 'updated', $version );
		}
	}
}

$GLOBALS['cf_system'] = CollaborativeFiltering_System::get_instance();
