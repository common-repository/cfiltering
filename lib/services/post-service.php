<?php
namespace CollaborativeFilteringService;

if ( !defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) )
	exit;

class CollaborativeFiltering_Post extends CollaborativeFiltering_Service_Base
{

	private static $_instance = null;

	public static function get_instance()
	{
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new CollaborativeFiltering_Post();
		}
		return self::$_instance;
	}

	private function post_meta_prefix()
	{
		return "cfiltering_";
	}

	public function get( $key, $post_id = null, $single = true, $default = "" )
	{
		if ( is_null( $post_id ) ) {
			global $post, $wp_query;
			if ( is_null( $post ) ) {
				if ( isset( $wp_query, $wp_query->query_vars['p'] ) ) {
					$post_id = $wp_query->query_vars['p'];
				} else {
					$post_id = 0;
				}
			} else {
				$post_id = $post->ID;
			}
		}
		if ( $post_id <= 0 )
			return $this->apply_filters( "get_postmeta", $default, $key, $post_id, $single, $default );
		return $this->apply_filters( "get_postmeta", get_post_meta( $post_id, $this->post_meta_prefix() . $key, $single ), $key, $post_id, $single, $default );
	}

	public function set( $post_id, $key, $value )
	{
		if ( $post_id <= 0 )
			return false;
		return update_post_meta( $post_id, $this->post_meta_prefix() . $key, $value );
	}

	public function set_all( $key, $value )
	{
		global $wpdb;
		$query = $wpdb->prepare( "UPDATE $wpdb->postmeta SET meta_value = %s WHERE meta_key LIKE %s", $value, $this->post_meta_prefix() . $key );
		$wpdb->query( $query );
	}

	public function delete_all( $key )
	{
		global $wpdb;
		$query = $wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE %s", $this->post_meta_prefix() . $key );
		$wpdb->query( $query );
	}

	public function find( $key, $value )
	{
		global $wpdb;
		$query = <<< SQL
			SELECT * FROM {$wpdb->postmeta}
			WHERE meta_key LIKE %s
			AND   meta_value LIKE %s
SQL;
		$results = $wpdb->get_results( $wpdb->prepare( $query, $this->post_meta_prefix() . $key, $value ) );
		$results = array_map( function ( $d ) {
			return $d->post_id;
		}, $results );
		return $this->apply_filters( "find_postmeta", $results, $key, $value );
	}

	public function uninstall()
	{
		global $wpdb;
		$query = $wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE %s%%", $this->post_meta_prefix() );
		$wpdb->query( $query );
	}
}

$GLOBALS['cf_post'] = CollaborativeFiltering_Post::get_instance();
