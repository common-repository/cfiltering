<?php
namespace CollaborativeFilteringApi;

if ( !defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) )
	exit;

class CollaborativeFiltering_GetTests extends CollaborativeFiltering_API_Base
{

	private static $_instance = null;

	public static function get_instance()
	{
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new CollaborativeFiltering_GetTests();
		}
		return self::$_instance;
	}

	protected function need_nonce_check()
	{
		return true;
	}

	public function get_api_name()
	{
		return "get_tests";
	}

	public function get_method()
	{
		return "get";
	}

	protected function only_admin()
	{
		return true;
	}

	protected function consider_page_cache()
	{
		return false;
	}

	public function get_response()
	{
		$start = microtime( true );
		$elapsed = function ( $start ) {
			return round( microtime( true ) - $start, 6 ) * 1000 . ' ms';
		};

		global $cf_test;
		return array(
			"result" => $cf_test->get_test_settings(),
			"elapsed" => $elapsed( $start )
		);
	}
}

$GLOBALS[CollaborativeFiltering_API_Base::get_name( __FILE__ )] = CollaborativeFiltering_GetTests::get_instance();
