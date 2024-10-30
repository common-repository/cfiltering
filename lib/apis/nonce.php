<?php
namespace CollaborativeFilteringApi;

if ( !defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) )
	exit;

class CollaborativeFiltering_Nonce extends CollaborativeFiltering_API_Base
{

	private static $_instance = null;

	public static function get_instance()
	{
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new CollaborativeFiltering_Nonce();
		}
		return self::$_instance;
	}

	protected function need_nonce_check()
	{
		return false;
	}

	public function get_api_name()
	{
		return "nonce";
	}

	public function get_method()
	{
		return "post";
	}

	public function is_basic_api()
	{
		return true;
	}

	public function get_response()
	{
		if ( !isset( $_REQUEST["name"] ) || empty( $_REQUEST["name"] ) ) {
			return array(
				"nonce" => ""
			);
		}
		$nonce = wp_create_nonce( $_REQUEST["name"] );
		return array(
			"nonce" => $nonce
		);
	}
}

$GLOBALS[CollaborativeFiltering_API_Base::get_name( __FILE__ )] = CollaborativeFiltering_Nonce::get_instance();
