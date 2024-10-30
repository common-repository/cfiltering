<?php
namespace CollaborativeFilteringController;

if ( !defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) )
	exit;

class CollaborativeFiltering_Log extends CollaborativeFiltering_Controller_Base
{

	private static $_instance = null;

	public static function get_instance()
	{
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new CollaborativeFiltering_Log();
		}
		return self::$_instance;
	}

	public function get_page_title()
	{
		return __( "log", COLLABORATIVE_FILTERING_TEXT_DOMAIN );
	}

	public function get_menu_name()
	{
		return __( "log", COLLABORATIVE_FILTERING_TEXT_DOMAIN );
	}

	public function load()
	{
		$logfile = COLLABORATIVE_FILTERING_LOG_FILE;
		$message = array();
		if ( !file_exists( $logfile ) ) {
			$date = array();
		} else {
			$log = @file_get_contents( $logfile );
			$data = preg_split( '#\[(\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2})\]#', $log, -1, PREG_SPLIT_DELIM_CAPTURE );
			if ( count( $data ) > 0 ) {
				array_shift( $data );
				$date = array_map( 'current', array_chunk( $data, 2 ) );
				$message = array_map( 'current', array_chunk( array_slice( $data, 1 ), 2 ) );
			} else {
				$date = array();
			}
		}
		$this->view( "log", true, array(
			"date" => $date,
			"message" => $message,
			"number" => $this->apply_filters( "display_log_number", COLLABORATIVE_FILTERING_DISPLAY_LOG_NUMBER )
		) );
	}
}

$GLOBALS[CollaborativeFiltering_Controller_Base::get_name( __FILE__ )] = CollaborativeFiltering_Log::get_instance();

