<?php
namespace CollaborativeFilteringController;

if ( !defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) )
	exit;

class CollaborativeFiltering_Action extends CollaborativeFiltering_Controller_Base
{

	private static $_instance = null;

	public static function get_instance()
	{
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new CollaborativeFiltering_Action();
		}
		return self::$_instance;
	}

	public function get_page_title()
	{
		return __( 'action', COLLABORATIVE_FILTERING_TEXT_DOMAIN );
	}

	public function get_menu_name()
	{
		return __( 'action', COLLABORATIVE_FILTERING_TEXT_DOMAIN );
	}

	private function get_actions()
	{
		return $this->apply_filters( 'action_list', array(
			'delete-cron-schedule' => array(
				'description' => "Delete cron schedule that registered by this plugin.",
				'action' => 'delete_cron_schedule',
				'confirm' => false
			),
			'run-calculate-now' => array(
				'description' => "Run calculate process now.",
				'action' => 'run_calculate_now',
				'confirm' => false
			),
			'delete-log' => array(
				'description' => "Delete log.",
				'action' => 'delete_log',
				'confirm' => true
			),
			'reset-settings' => array(
				'description' => "Reset settings.",
				'action' => 'reset_settings',
				'confirm' => true
			),
			'init-calculate-sampling-rate-data' => array(
				'description' => "Initialize data to calculate sampling rate.",
				'action' => 'init_calc_sampling_rate_data',
				'confirm' => true
			),
			'init-user-cookie' => array(
				'description' => "Invalidate all user cookie if nonce check is valid.",
				'action' => 'init_user_cookie',
				'confirm' => true
			),
		) );
	}

	public function setup()
	{
		$actions = $this->get_actions();
		if ( strtolower( $_SERVER['REQUEST_METHOD'] ) == 'post' && isset( $_REQUEST['cf-action'] ) && array_key_exists( $_REQUEST['cf-action'], $actions ) && isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'cf-action' ) ) {
			$action = $actions[$_REQUEST['cf-action']];
			if ( is_callable( array( $this, $action['action'] ) ) ) {
				$this->{$action['action']}();
			}
			$this->do_action( 'did_action', $_REQUEST['cf-action'] );
		}
	}

	public function load()
	{
		$data = array();
		foreach ( $this->get_actions() as $k => $v ) {
			$data[$k] = $v;
			$data[$k]['description'] = implode( '<br>', array_map( function ( $d ) {
				return __( $d, COLLABORATIVE_FILTERING_TEXT_DOMAIN );
			}, explode( "\n", $v['description'] ) ) );
		}
		$this->view( 'action', true, array( 'data' => $data, 'nonce' => wp_create_nonce( 'cf-action' ) ) );
	}

	private function delete_cron_schedule()
	{
		global $cf_calculate;
		$cf_calculate->clear_event();
		$this->message( __( 'Done.', COLLABORATIVE_FILTERING_TEXT_DOMAIN ) );
	}

	private function run_calculate_now()
	{
		global $cf_calculate;
		$cf_calculate->run_now();
		$this->message( __( 'Done.', COLLABORATIVE_FILTERING_TEXT_DOMAIN ) );
	}

	private function delete_log()
	{
		if ( file_exists( COLLABORATIVE_FILTERING_LOG_FILE ) ) {
			unlink( COLLABORATIVE_FILTERING_LOG_FILE );
		}
		$this->message( __( 'Done.', COLLABORATIVE_FILTERING_TEXT_DOMAIN ) );
	}

	private function reset_settings()
	{
		global $cf_option;
		$cf_option->clear_option();
		$this->message( __( 'Done.', COLLABORATIVE_FILTERING_TEXT_DOMAIN ) );
	}

	private function init_calc_sampling_rate_data()
	{
		global $cf_option;
		$cf_option->delete( "last_calculated" );
		$cf_option->delete( "total_calculated" );
		$this->message( __( 'Done.', COLLABORATIVE_FILTERING_TEXT_DOMAIN ) );
	}

	private function init_user_cookie()
	{
		$this->init_server_key();
		$this->message( __( 'Done.', COLLABORATIVE_FILTERING_TEXT_DOMAIN ) );
	}
}

$GLOBALS[CollaborativeFiltering_Controller_Base::get_name( __FILE__ )] = CollaborativeFiltering_Action::get_instance();

