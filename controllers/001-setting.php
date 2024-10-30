<?php
namespace CollaborativeFilteringController;

if ( !defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) )
	exit;

class CollaborativeFiltering_Setting extends CollaborativeFiltering_Controller_Base
{

	private static $_instance = null;

	public static function get_instance()
	{
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new CollaborativeFiltering_Setting();
		}
		return self::$_instance;
	}

	public function get_page_title()
	{
		return __( "dashboard", COLLABORATIVE_FILTERING_TEXT_DOMAIN );
	}

	public function get_menu_name()
	{
		return __( "dashboard", COLLABORATIVE_FILTERING_TEXT_DOMAIN );
	}

	public function setup()
	{
		if ( strtolower( $_SERVER["REQUEST_METHOD"] ) == "post" && isset( $_REQUEST["nonce"] ) && wp_verify_nonce( $_REQUEST["nonce"], "cf-setting" ) ) {
			global $cf_option;
			$settings = $this->get_settings();
			foreach ( $settings as $setting ) {
				foreach ( $setting['settings'] as $v ) {
					$cf_option->set_post( $v["name"], false );
				}
			}
			$cf_option->save();
			$this->message( __( "Options saved.", COLLABORATIVE_FILTERING_TEXT_DOMAIN ) );
		}
	}

	public function load()
	{
		$settings = $this->get_settings();

		$this->view( "setting", true, array( "items" => $settings, "nonce" => wp_create_nonce( "cf-setting" ) ) );
	}
}

$GLOBALS[CollaborativeFiltering_Controller_Base::get_name( __FILE__ )] = CollaborativeFiltering_Setting::get_instance();

