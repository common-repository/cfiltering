<?php
namespace CollaborativeFilteringApi;

if ( !defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) )
	exit;

class CollaborativeFiltering_GetResults extends CollaborativeFiltering_API_Base
{

	private static $_instance = null;

	public static function get_instance()
	{
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new CollaborativeFiltering_GetResults();
		}
		return self::$_instance;
	}

	public function get_api_name()
	{
		return "reflect_results";
	}

	public function get_method()
	{
		return "post";
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

		if ( !isset( $_REQUEST["r"] ) || $_REQUEST["r"] <= 0 ) {
			return array(
				"result" => false,
				"message" => "parameter [r] is not set",
				"elapsed" => $elapsed( $start )
			);
		}

		global $cf_test;
		$result = $cf_test->reflect_result( $_REQUEST['r'] );

		return array(
			"result" => $result,
			"elapsed" => $elapsed( $start )
		);
	}
}

$GLOBALS[CollaborativeFiltering_API_Base::get_name( __FILE__ )] = CollaborativeFiltering_GetResults::get_instance();
