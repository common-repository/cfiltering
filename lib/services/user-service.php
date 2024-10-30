<?php
namespace CollaborativeFilteringService;

if ( !defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) )
	exit;

class CollaborativeFiltering_User extends CollaborativeFiltering_Service_Base
{

	private static $_instance = null;

	protected function __construct()
	{
		add_action( 'init', array( $this, "set_user_info" ), 1 );
	}

	public static function get_instance()
	{
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new CollaborativeFiltering_User();
		}
		return self::$_instance;
	}

	public function set_user_info()
	{
		global $user_ID;
		$current_user = wp_get_current_user();

		if ( $user_ID ) {
			$this->user_info = get_userdata( $user_ID );
			$this->user_level = $this->user_info->user_level - 0;
			$this->super_admin = is_super_admin( $user_ID );
		} else {
			$this->user_info = $current_user;
			$this->user_level = 0;
			$this->super_admin = false;
		}
		$this->user_id = $this->user_info->ID;
		$this->user_name = $this->user_info->user_login;
		$this->display_name = $this->user_info->display_name;
		$this->user_email = $this->user_info->user_email;
		$this->loggedin = is_user_logged_in();
		if ( empty( $this->user_name ) ) {
			$this->user_name = $_SERVER['REMOTE_ADDR'];
		}
	}

	private function user_meta_prefix()
	{
		return "cfiltering_";
	}

	public function get( $key, $user_id = NULL, $single = true, $default = "" )
	{
		if ( is_null( $user_id ) )
			$user_id = $this->user_id;
		if ( $user_id <= 0 )
			return $this->apply_filters( "get_usermeta", $default, $key, $user_id, $single, $default );
		return $this->apply_filters( "get_usermeta", get_user_meta( $user_id, $this->user_meta_prefix() . $key, $single ), $key, $user_id, $single, $default );
	}

	public function set( $key, $value, $user_id = NULL )
	{
		if ( is_null( $user_id ) )
			$user_id = $this->user_id;
		if ( $user_id <= 0 )
			return false;
		return update_user_meta( $user_id, $this->user_meta_prefix() . $key, $value );
	}

	public function find( $key, $value )
	{
		global $wpdb;
		$query = <<< SQL
			SELECT * FROM {$wpdb->usermeta}
			WHERE meta_key LIKE %s
			AND   meta_value LIKE %s
SQL;
		$results = $wpdb->get_results( $wpdb->prepare( $query, $this->user_meta_prefix() . $key, $value ) );
		$results = array_map( function ( $d ) {
			return $d->user_id;
		}, $results );
		return $this->apply_filters( "find_usermeta", $results, $key, $value );
	}

	public function user_can( $capability = null )
	{
		if ( is_null( $capability ) ) {
			$capability = COLLABORATIVE_FILTERING_ADMIN_CAPABILITY;
		}
		return current_user_can( $this->apply_filters( "user_can", $capability ) );
	}

	public function uninstall()
	{
		global $wpdb;
		$query = $wpdb->prepare( "DELETE FROM $wpdb->usermeta WHERE meta_key LIKE %s%%", $this->user_meta_prefix() );
		$wpdb->query( $query );
	}
}

$GLOBALS['cf_user'] = CollaborativeFiltering_User::get_instance();
