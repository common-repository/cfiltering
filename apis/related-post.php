<?php
namespace CollaborativeFilteringApi;

if ( !defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) )
	exit;

class CollaborativeFiltering_RelatedPost extends CollaborativeFiltering_API_Base
{

	private static $_instance = null;

	public static function get_instance()
	{
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new CollaborativeFiltering_RelatedPost();
		}
		return self::$_instance;
	}

	protected function need_nonce_check()
	{
		return true;
	}

	public function get_api_name()
	{
		return "related_post";
	}

	public function get_method()
	{
		return "get";
	}

	public function get_capability()
	{
		return null;
	}

	protected function only_admin()
	{
		return true;
	}

	protected function consider_page_cache()
	{
		return false;
	}

	public function get_response()
	{
		$start = microtime( true );
		$elapsed = function ( $start ) {
			return round( microtime( true ) - $start, 6 ) * 1000 . ' ms';
		};

		if ( !isset( $_REQUEST["p"] ) || $_REQUEST["p"] <= 0 ) {
			return array(
				"result" => false,
				"message" => "parameter [p] is not set",
				"elapsed" => $elapsed( $start )
			);
		}

		$post_id = $_REQUEST["p"];
		$post = get_post( $post_id );
		if ( is_null( $post ) ) {
			return array(
				"result" => false,
				"message" => "post is not exist",
				"elapsed" => $elapsed( $start )
			);
		}

		$data = cf_get_jaccard( $post_id, 0, 0 );
		if (count($data) > 0) {
			$calculated = $data[0]['total'];
		} else {
			$calculated = 0;
		}
		$threshold = $this->apply_filters( 'jaccard_min_number', COLLABORATIVE_FILTERING_JACCARD_MIN_NUMBER );
		$html = $this->view( 'show-related-post', false, array(
			"post" => $post,
			"data" => $data,
			"calculated" => $calculated,
			"threshold" => $threshold
		) );
		return array(
			"result" => true,
			"message" => $html,
			"elapsed" => $elapsed( $start )
		);
	}
}

$GLOBALS[CollaborativeFiltering_API_Base::get_name( __FILE__ )] = CollaborativeFiltering_RelatedPost::get_instance();
