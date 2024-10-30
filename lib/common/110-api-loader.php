<?php
namespace CollaborativeFilteringApi;

if ( !defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) )
	exit;

class CollaborativeFiltering_API_Loader extends \CollaborativeFilteringBase\CollaborativeFiltering_Base_Class
{

	private static $_instance = null;

	private $use_functions = array();

	private function __construct()
	{
		add_action( 'init', function () {
			if ( $this->defined( 'DOING_AJAX' ) ) {
				$this->setup();
			} else {
				if ( is_admin() ) {
					add_action( 'admin_footer', array( $this, 'setup' ) );
				} else {
					add_action( 'wp_footer', array( $this, 'setup' ) );
				}
			}
		}, 11 );
	}

	public static function get_instance()
	{
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new CollaborativeFiltering_API_Loader();
		}
		return self::$_instance;
	}

	public function register_use_function( $name )
	{
		if ( !is_array( $this->use_functions ) ) {
			$this->use_functions = array();
		}
		$name = str_replace( "-", "_", $name );
		if ( !in_array( $name, $this->use_functions ) ) {
			$this->use_functions[] = $name;
		}
	}

	public function setup()
	{
		foreach ( scandir( COLLABORATIVE_FILTERING_LIB_API_DIR ) as $file ) {
			if ( preg_match( "/^[^\\.].*\\.php$/", $file ) ) {
				require_once COLLABORATIVE_FILTERING_LIB_API_DIR . DIRECTORY_SEPARATOR . $file;
				if ( isset( $GLOBALS[CollaborativeFiltering_API_Base::get_name( $file )] ) ) {
					$obj = $GLOBALS[CollaborativeFiltering_API_Base::get_name( $file )];
					if ( method_exists( $obj, 'get_api_name' ) && is_callable( array( $obj, 'get_api_name' ) ) &&
						method_exists( $obj, 'get_capability' ) && is_callable( array( $obj, 'get_capability' ) ) &&
						method_exists( $obj, 'setup' ) && is_callable( array( $obj, 'setup' ) ) &&
						method_exists( $obj, 'is_basic_api' ) && is_callable( array( $obj, 'is_basic_api' ) )
					) {
						$obj->setup();
					}
				}
			}
		}

		foreach ( scandir( COLLABORATIVE_FILTERING_API_DIR ) as $file ) {
			if ( preg_match( "/^[^\\.].*\\.php$/", $file ) ) {
				require_once COLLABORATIVE_FILTERING_API_DIR . DIRECTORY_SEPARATOR . $file;
				if ( isset( $GLOBALS[CollaborativeFiltering_API_Base::get_name( $file )] ) ) {
					$obj = $GLOBALS[CollaborativeFiltering_API_Base::get_name( $file )];
					if ( method_exists( $obj, 'get_api_name' ) && is_callable( array( $obj, 'get_api_name' ) ) &&
						method_exists( $obj, 'get_capability' ) && is_callable( array( $obj, 'get_capability' ) ) &&
						method_exists( $obj, 'setup' ) && is_callable( array( $obj, 'setup' ) ) &&
						method_exists( $obj, 'is_basic_api' ) && is_callable( array( $obj, 'is_basic_api' ) )
					) {
						if ( $this->defined( 'DOING_AJAX' ) || !is_admin() || in_array( $obj->get_api_name(), $this->use_functions ) || $obj->is_basic_api() ) {
							$obj->setup();
						}
					}
				}
			}
		}

		if ( !$this->defined( 'DOING_AJAX' ) ) {
			global $cf_minify;
			if ( is_admin() ) {
				$cf_minify->register_script( $this->view( "ajaxurl-admin", false, array(), true ) );
			} else {
				global $cf_ajax;
				$cf_minify->register_script( $this->view( "ajaxurl", false, array( "ajaxurl" => $cf_ajax->get_ajax_url() ), true ) );
			}
			$cf_minify->register_script( $this->view( "ajax", false, array(), true ) );
		}
	}
}

$GLOBALS['cf_api'] = CollaborativeFiltering_API_Loader::get_instance();
