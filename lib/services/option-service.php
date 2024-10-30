<?php
namespace CollaborativeFilteringService;

if ( !defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) )
	exit;

class CollaborativeFiltering_Option extends CollaborativeFiltering_Service_Base
{

	private static $_instance = null;

	protected function __construct()
	{
		$this->load();
	}

	public static function get_instance()
	{
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new CollaborativeFiltering_Option();
		}
		return self::$_instance;
	}

	private $data = null;

	private function load()
	{
		$this->data = wp_parse_args(
			get_option( "cf_options", array() ), $this->get_default_option()
		);
		$this->unescape_options();
		return true;
	}

	private function get_default_option()
	{
		return array(
			//	"name" => COLLABORATIVE_FILTERING_PLUGIN_NAME,
			//	"version" => COLLABORATIVE_FILTERING_PLUGIN_VERSION,
		);
	}

	private function unescape_options()
	{
		foreach ( $this->data as $key => $value ) {
			if ( is_string( $value ) ) {
				$this->data[$key] = stripslashes( htmlspecialchars_decode( $this->data[$key] ) );
			}
		}
	}

	public function get( $key, $default = '' )
	{
		if ( array_key_exists( $key, $this->data ) ) {
			return $this->apply_filters( "get_option", $this->data[$key], $key, $default );
		}
		return $this->apply_filters( "get_option", $default, $key, $default );
	}

	public function set( $key, $value, $save = true )
	{
		$prev = isset( $this->data[$key] ) ? $this->data[$key] : null;
		$this->data[$key] = $value;
		if ( $prev !== $value ) {
			$this->do_action( 'changed_option', $key, $value, $prev );
		}
		if ( $save )
			return $this->save();
		return true;
	}

	public function delete( $key, $save = true )
	{
		if ( array_key_exists( $key, $this->data ) ) {
			unset( $this->data[$key] );
			if ( $save ) {
				return $this->save();
			}
		}
		return true;
	}

	public function set_post( $key, $save = true )
	{
		if ( !isset( $_POST[$key] ) ) {
			return false;
		}
		return $this->set( $key, $_POST[$key], $save );
	}

	public function save()
	{
		$data = $this->data;
		foreach ( $data as $key => $value ) {
			if ( is_string( $value ) ) {
				$data[$key] = htmlspecialchars( $value );
			}
		}
		return update_option( "cf_options", $data );
	}

	public function clear_option()
	{
		delete_option( "cf_options" );
		$this->load();
	}

	public function uninstall()
	{
		delete_option( "cf_options" );
	}
}

$GLOBALS['cf_option'] = CollaborativeFiltering_Option::get_instance();
