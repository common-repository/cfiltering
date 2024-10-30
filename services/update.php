<?php
namespace CollaborativeFilteringService;

if ( !defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) )
	exit;

class CollaborativeFiltering_Update extends CollaborativeFiltering_Service_Base
{

	private static $_instance = null;

	protected function __construct()
	{
		$this->register();
	}

	public static function get_instance()
	{
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new CollaborativeFiltering_Update();
		}
		return self::$_instance;
	}

	private function register()
	{
		add_action( 'cf_updated', function ( $version ) {
			if ( version_compare( $version, '1.0.8', '<' ) ) {
				$this->version_1_0_8();
			}
			if ( version_compare( $version, '1.3.4', '<' ) ) {
				$this->version_1_3_4();
			}
			if ( version_compare( $version, '1.3.6', '<' ) ) {
				$this->version_1_3_6();
			}
			if ( version_compare( $version, '1.4.0', '<' ) ) {
				$this->version_1_4_0();
			}
			if ( version_compare( $version, '1.4.2', '<' ) ) {
				$this->version_1_4_2();
			}
		} );
	}

	private function version_1_0_8()
	{
		global $cf_post, $cf_calculate;
		$cf_post->delete_all( 'jaccard' );
		$cf_calculate->run_now();
	}

	private function version_1_3_4()
	{
		global $cf_option;
		$name = $this->get_filter_prefix() . 'front_admin_ajax';
		if ( "" === $cf_option->get( $name ) ) {
			$cf_option->set( $name, 'true' );
		}
	}

	private function version_1_3_6()
	{
		global $cf_option;
		$name = $this->get_filter_prefix() . 'check_update';
		$cf_option->set( $name, 'false' );
	}

	private function version_1_4_0()
	{
		global $cf_option;
		$name = $this->get_filter_prefix() . 'url_scheme';
		$cf_option->set( $name, 'admin' );
	}

	private function version_1_4_2()
	{
		global $cf_option;
		$name = $this->get_filter_prefix() . 'mod_rewrite_ajax_access';
		$cf_option->set( $name, 'true' );
	}
}

$GLOBALS['cf_update'] = CollaborativeFiltering_Update::get_instance();
