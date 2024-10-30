<?php
namespace CollaborativeFilteringService;

if ( !defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) )
	exit;

class CollaborativeFiltering_Clear extends CollaborativeFiltering_Service_Base
{

	private static $_instance = null;

	protected function __construct()
	{
		if ( !wp_next_scheduled( 'cf_clear_event' ) ) {
			wp_schedule_single_event( time() + $this->apply_filters( 'clear_interval', COLLABORATIVE_FILTERING_CLEAR_INTERVAL ), 'cf_clear_event' );
		}
		add_action( 'cf_clear_event', function () {
			$this->check_progress();
		} );

		if ( $this->apply_filters( 'clear_log', COLLABORATIVE_FILTERING_CLEAR_LOG ) ) {
			add_action( 'cf_start_clear_process', function () {
				$this->log( 'start clear' );
			} );
			add_action( 'cf_end_clear_process', function ( $start ) {
				$elapsed = ( microtime( true ) - $start ) * 1000;
				$this->log( 'end clear [elapsed time: ' . $elapsed . ' ms]' );
			} );
		}
	}

	public static function get_instance()
	{
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new CollaborativeFiltering_Clear();
		}
		return self::$_instance;
	}

	public function clear_event()
	{
		wp_clear_scheduled_hook( 'cf_clear_event' );
	}

	private function check_progress()
	{
		$time = $this->get_time();
		$now = time();
		if ( $now - $time <= $this->apply_filters( 'clear_interval', COLLABORATIVE_FILTERING_CLEAR_INTERVAL ) ) {
			return;
		}
		$this->set_time( time() );

		$this->execute();
	}

	private function execute()
	{
		$start = microtime( true );
		$this->do_action( 'start_clear_process', $start );

		delete_transient( 'doing_cron' );
		set_time_limit( 0 );

		$this->set_time( time() );

		$this->clear();

		$this->do_action( 'end_clear_process', $start );
	}

	private function clear()
	{
		$expire = time() - $this->apply_filters( 'data_expire', COLLABORATIVE_FILTERING_DATA_EXPIRE );
		$expire = date( 'Y-m-d H:i:s', $expire );

		global $cf_model_access;
		$cf_model_access->clear(
			array(
				array(
					'AND',
					array(
						array( 'updated_at', '<', '?', $expire )
					)
				)
			)
		);
	}

	private function get_time()
	{
		global $cf_option;
		return $cf_option->get( 'clear_time', 0 );
	}

	private function set_time( $time )
	{
		global $cf_option;
		return $cf_option->set( 'calculate_time', $time );
	}
}

$GLOBALS['cf_clear'] = CollaborativeFiltering_Clear::get_instance();
