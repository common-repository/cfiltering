<?php
namespace CollaborativeFilteringService;

if ( !defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) )
	exit;

class CollaborativeFiltering_Ajax extends CollaborativeFiltering_Service_Base
{

	private static $_instance = null;

	protected function __construct()
	{
		add_action( 'init', function () {
			if ( $this->defined( 'COLLABORATIVE_FILTERING_AJAX_ACCESS' ) ) {
				add_action( 'wp_loaded', array( $this, 'setup' ) );
			} else {
				$this->check_url();
			}
		} );
	}

	public static function get_instance()
	{
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new CollaborativeFiltering_Ajax();
		}
		return self::$_instance;
	}

	private function check_url()
	{
		if ( $this->defined( 'DOING_AJAX' ) ) {
			return;
		}

		if ( !$this->check_ajax_url() ) {
			return;
		}

		define( 'DOING_AJAX', true );

		if ( empty( $_REQUEST['action'] ) )
			die( '0' );

		add_action( 'wp_loaded', array( $this, 'setup' ) );
	}

	private function check_ajax_url()
	{
		$exploded = explode( '?', $this->get_ajax_url( null, 'relative', true ) );
		return $this->apply_filters( 'check_ajax_url', $_SERVER["REQUEST_URI"] === $exploded[0] );
	}

	public function setup()
	{
		@header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
		@header( 'X-Robots-Tag: noindex' );

		send_nosniff_header();
		nocache_headers();

		if ( is_user_logged_in() ) {
			do_action( 'wp_ajax_' . $_REQUEST['action'] );
		} else {
			do_action( 'wp_ajax_nopriv_' . $_REQUEST['action'] );
		}

		// Default status
		die( '0' );
	}

	public function get_ajax_url( $admin_ajax = null, $scheme = null, $mod_rewrite = null )
	{
		if ( is_null( $admin_ajax ) ) {
			$admin_ajax = $this->front_admin_ajax();
		}
		if ( is_null( $scheme ) ) {
			$scheme = $this->get_url_scheme();
		}

		if ( $admin_ajax ) {
			return admin_url( 'admin-ajax.php', $scheme );
		}
		return $this->apply_filters( 'ajax_url', $this->get_plugin_url( $this->get_access_file( $mod_rewrite ), false, $scheme ) );
	}

	private function get_access_file( $mod_rewrite = null )
	{
		if ( is_null( $mod_rewrite ) ) {
			$mod_rewrite = $this->mod_rewrite_ajax_access();
		}
		if ( $mod_rewrite ) {
			return COLLABORATIVE_FILTERING_MOD_REWRITE_AJAX;
		} else {
			return COLLABORATIVE_FILTERING_AJAX_FILE;
		}
	}
}

$GLOBALS['cf_ajax'] = CollaborativeFiltering_Ajax::get_instance();
