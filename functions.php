<?php
if ( !defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) )
	exit;

function cf_get_post_id( $post_id = null, $threshold = null, $min_number = null )
{
	global $cf_calculate;
	return $cf_calculate->get_post_ids( $post_id, $threshold, $min_number );
}

function cf_get_posts( $post_id = null, $threshold = null, $min_number = null )
{
	global $cf_calculate;
	return $cf_calculate->get_posts( $post_id, $threshold, $min_number );
}

function cf_get_jaccard( $post_id = null, $threshold = null, $min_number = null )
{
	global $cf_calculate;
	return $cf_calculate->get_jaccard( $post_id, $threshold, $min_number );
}
