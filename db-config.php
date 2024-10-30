<?php
if ( !defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) )
	exit;

global $cf_db;

//access
$cf_db->add_table(
	"access", "access",
	array(
		"user_id" => array( "user_id", "VARCHAR(32)", "NOT NULL" ),
		"post_id" => array( "post_id", "BIGINT(20)", "NOT NULL" ),
		"is_processed" => array( "is_processed", "TINYINT(1)", "NOT NULL" )
	),
	array( "user_id", "post_id" )
);

//number
$cf_db->add_table(
	"number", "number",
	array(
		"post_id1" => array( "post_id1", "BIGINT(20)", "NOT NULL" ),
		"post_id2" => array( "post_id2", "BIGINT(20)", "NOT NULL" ),
		"number" => array( "number", "INT(11)", "NOT NULL" )
	),
	array( "post_id1", "post_id2" )
);
