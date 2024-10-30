<?php
namespace CollaborativeFilteringApi;

if ( !defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) )
	exit;

class CollaborativeFiltering_AccessLog extends CollaborativeFiltering_API_Base
{

	private static $_instance = null;

	public static function get_instance()
	{
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new CollaborativeFiltering_AccessLog();
		}
		return self::$_instance;
	}

	protected function need_nonce_check()
	{
		return true;
	}

	public function get_api_name()
	{
		return "access";
	}

	public function get_method()
	{
		return "post";
	}

	protected function only_front()
	{
		return true;
	}

	protected function setup_filter()
	{
		if ( $this->utilize_wpp() ) {
			if ( $this->defined( 'DOING_AJAX' ) ) {
				add_action( 'wpp_post_update_views', function ( $post_id ) {
					$_REQUEST['p'] = $post_id;
					$response = $this->get_response();
					if ( !$this->apply_filters( 'suppress_message', COLLABORATIVE_FILTERING_SUPPRESS_MESSAGE ) ) {
						if ( !$response['error'] ) {
							$elapsed = $response["elapsed"] / 1000;
							echo COLLABORATIVE_FILTERING_PLUGIN_NAME . ": OK. Execution time: {$elapsed} seconds\n";
						} else {
							echo COLLABORATIVE_FILTERING_PLUGIN_NAME . ": Oops, invalid request ({$response['message']})\n";
						}
					}
				} );
			}
			return false;
		}
		return true;
	}

	protected function front_filter()
	{
		global $post;
		return $this->apply_filters( 'access_api_filter', in_array( $post->post_type, $this->valid_post_types() ) );
	}

	protected function setup_function()
	{
		global $cf_minify, $post;
		if ( !isset( $post ) || empty( $post->ID ) ) {
			return;
		}
		$cf_minify->register_script(
			<<< EOS
			<script>
				cf_obj.access({p:$post->ID});
			</script>
EOS
		, 100);
	}

	public function get_response()
	{
		$response = $this->execute();
		if ( $this->apply_filters( 'suppress_message', COLLABORATIVE_FILTERING_SUPPRESS_MESSAGE ) ) {
			return array();
		}
		return $response;
	}

	private function execute()
	{
		$start = microtime( true );
		$elapsed = function ( $start ) {
			return round( microtime( true ) - $start, 6 ) * 1000 . ' ms';
		};
		global $cf_deviceinfo;
		if ( $cf_deviceinfo->is_bot() ) {
			return array(
				"result" => false,
				"error" => false,
				'validity' => false,
				"message" => "it is bot",
				"elapsed" => $elapsed( $start )
			);
		}

		if ( $this->apply_filters( "exclude_loggedin_user", COLLABORATIVE_FILTERING_EXCLUDE_LOGGEDIN_USER ) ) {
			global $cf_user;
			if ( $cf_user->loggedin ) {
				return array(
					"result" => false,
					"error" => false,
					'validity' => false,
					"message" => "excluded logged in user",
					"elapsed" => $elapsed( $start )
				);
			}
		}

		if ( !isset( $_REQUEST["p"] ) || $_REQUEST["p"] <= 0 ) {
			return array(
				"result" => false,
				"error" => true,
				'validity' => false,
				"message" => "parameter [p] is not set",
				"elapsed" => $elapsed( $start )
			);
		}

		$post_id = $_REQUEST["p"];
		$post = get_post( $post_id );
		if ( is_null( $post ) ) {
			return array(
				"result" => false,
				"error" => true,
				'validity' => false,
				"message" => "post is not exist",
				"elapsed" => $elapsed( $start )
			);
		}

		if ( isset( $_COOKIE["cf_access"] ) && !empty( $_COOKIE["cf_access"] ) && $elements = $this->parse_cookie( $_COOKIE["cf_access"] ) ) {
			$uuid = $elements["user_id"];
			$validity = $elements["validity"];
			if ( $this->apply_filters( "update_cookie_expire", COLLABORATIVE_FILTERING_UPDATE_COOKIE_EXPIRE ) ) {
				setcookie( "cf_access", $_COOKIE["cf_access"], time() + $this->apply_filters( "user_expire", COLLABORATIVE_FILTERING_USER_EXPIRE ), "/" );
			}
			if ( !$validity ) {
				return array(
					"result" => true,
					"error" => false,
					'validity' => false,
					"message" => "rejected",
					"elapsed" => $elapsed( $start )
				);
			}
			$new = false;
		} else {
			$validity = true;
			$sampling = $this->apply_filters( "sampling_rate", COLLABORATIVE_FILTERING_SAMPLING_RATE );
			if ( $sampling > 0 ) {
				if ( $sampling < 1 ) {
					if ( $sampling <= mt_rand() / mt_getrandmax() ) {
						$validity = false;
					}
				}
			}
			$uuid = $this->apply_filters( "create_uuid", $this->uuid() );
			setcookie( "cf_access", $this->generate_cookie( $uuid, $validity ), time() + $this->apply_filters( "user_expire", COLLABORATIVE_FILTERING_USER_EXPIRE ), "/" );
			if ( !$validity ) {
				return array(
					"result" => true,
					"error" => false,
					'validity' => false,
					"message" => 'rejected',
					"elapsed" => $elapsed( $start )
				);
			}
			$new = true;
		}

		global $cf_model_access;
		if ( $new || !$cf_model_access->fetch(
				array(
					array(
						"AND",
						array(
							array( "user_id", "LIKE", "?", $uuid ),
							array( "post_id", "=", "?", $post_id ),
						)
					)
				)
			)
		) {
			$cf_model_access->insert(
				array(
					"user_id" => $uuid,
					"post_id" => $post_id,
					"is_processed" => 0
				)
			);
		}

		return array(
			"result" => true,
			"error" => false,
			'validity' => true,
			"message" => "accepted",
			"elapsed" => $elapsed( $start )
		);
	}

	private function generate_cookie( $user_id, $validity )
	{
		if ( $this->apply_filters( "check_data", COLLABORATIVE_FILTERING_CHECK_DATA ) ) {
			$data = $user_id . '|' . ( $validity ? 'true' : 'false' );
			$algo = function_exists( 'hash' ) ? 'sha256' : 'sha1';
			$hash = hash_hmac( $algo, $data, $this->get_server_key() );
			return $data . '|' . $hash;
		} else {
			$data = $user_id . '|' . ( $validity ? 'true' : 'false' );
			return $data;
		}
	}

	private function parse_cookie( $cookie )
	{
		$elements = explode( '|', $cookie );
		if ( $this->apply_filters( "check_data", COLLABORATIVE_FILTERING_CHECK_DATA ) ) {
			if ( count( $elements ) !== 3 ) {
				return false;
			}
			list( $user_id, $validity, $hash ) = $elements;
			$data = $user_id . '|' . $validity;
			$algo = function_exists( 'hash' ) ? 'sha256' : 'sha1';
			$hash_check = hash_hmac( $algo, $data, $this->get_server_key() );
			if ( $hash !== $hash_check ) {
				return false;
			}
		} else {
			if ( count( $elements ) !== 2 ) {
				return false;
			}
			list( $user_id, $validity ) = $elements;
		}
		$validity = 'true' === $validity;
		return compact( 'user_id', 'validity' );
	}
}

$GLOBALS[CollaborativeFiltering_API_Base::get_name( __FILE__ )] = CollaborativeFiltering_AccessLog::get_instance();
