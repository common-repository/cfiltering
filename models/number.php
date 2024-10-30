<?php
namespace CollaborativeFilteringModel;

if ( !defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) )
	exit;

class CollaborativeFiltering_Number extends CollaborativeFiltering_Model_Base
{

	private static $_instance = null;

	private function __construct()
	{

	}

	public static function get_instance()
	{
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new CollaborativeFiltering_Number();
		}
		return self::$_instance;
	}

	protected function get_table()
	{
		return CollaborativeFiltering_Model_Base::get_slug( __FILE__ );
	}
}

$GLOBALS[CollaborativeFiltering_Model_Base::get_name( __FILE__ )] = CollaborativeFiltering_Number::get_instance();
