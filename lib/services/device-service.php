<?php
namespace CollaborativeFilteringService;

if ( !defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) )
	exit;

class CollaborativeFiltering_DeviceInfo extends CollaborativeFiltering_Service_Base
{

	private static $_instance = null;

	public static function get_instance()
	{
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new CollaborativeFiltering_DeviceInfo();
		}
		return self::$_instance;
	}

	public function is_bot()
	{
		$check = $this->apply_filters( "pre_check_bot", null );
		if ( is_bool( $check ) ) {
			return $check;
		}

		$bot_list = $this->apply_filters( "bot_list", array(
			"facebookexternalhit",
			"Googlebot",
			"Baiduspider",
			"bingbot",
			"Yeti",
			"NaverBot",
			"Yahoo! Slurp",
			"Y!J-BRI",
			"Y!J-BRJ\\/YATS crawler",
			"Tumblr",
			//		"livedoor",
			//		"Hatena",
			"Twitterbot",
			"Page Speed",
			"Google Web Preview",
		) );

		$ua = $_SERVER["HTTP_USER_AGENT"];
		foreach ( $bot_list as $value ) {
			if ( preg_match( '/' . $value . '/i', $ua ) ) {
				return true;
			}
		}
		return false;
	}
}

$GLOBALS['cf_deviceinfo'] = CollaborativeFiltering_DeviceInfo::get_instance();
