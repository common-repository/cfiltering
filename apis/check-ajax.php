<?php
namespace CollaborativeFilteringApi;

if ( !defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) )
	exit;

class CollaborativeFiltering_CheckAjax extends CollaborativeFiltering_API_Base
{

	private static $_instance = null;

	public static function get_instance()
	{
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new CollaborativeFiltering_CheckAjax();
		}
		return self::$_instance;
	}

	protected function need_nonce_check()
	{
		return true;
	}

	public function get_api_name()
	{
		return 'check_ajax';
	}

	public function get_method()
	{
		return 'post';
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

	protected function setup_function()
	{
		if ( $this->defined( 'DOING_AJAX' ) ) {
			add_action( 'wp_ajax_nopriv_cf_check_front_ajax', function () {
				$data = $this->check_referer();
				$data['front'] = true;
				wp_send_json_success( $data );
			} );
			add_action( 'wp_ajax_cf_check_back_ajax', function () {
				$data = $this->check_referer();
				$data['back'] = true;
				wp_send_json_success( $data );
			} );
		}
	}

	public function get_response()
	{
		$start = microtime( true );
		$elapsed = function ( $start ) {
			return round( microtime( true ) - $start, 6 ) * 1000 . ' ms';
		};

		$this->filter_request( 'front' );
		$this->filter_request( 'admin' );
		$this->filter_request( 'referer' );
		$this->filter_request( 'mod_rewrite' );

		if ( !$_REQUEST['front'] ) {
			$_REQUEST['admin'] = true;
		}

		$scheme = $this->get_url_scheme();
		if ( 'relative' === $scheme ) {
			$scheme = 'admin';
		}
		global $cf_ajax;
		$ajaxurl = $cf_ajax->get_ajax_url( $_REQUEST['admin'], $scheme, $_REQUEST['mod_rewrite'] );
		if ( !$_REQUEST['front'] ) {
			$cookies = array();
			foreach ( $_COOKIE as $name => $value ) {
				$cookies[] = new \WP_Http_Cookie( array( 'name' => $name, 'value' => $value ) );
			}
			$query = array(
				'action' => 'cf_check_back_ajax',
				'scheme' => $scheme,
				'admin' => $_REQUEST['admin'],
				'mod_rewrite' => $_REQUEST['mod_rewrite'],
				'nonce' => $this->create_nonce( 'check-ajax' )
			);
			$args = array(
				'body' => http_build_query( $query ),
				'cookies' => $cookies,
			);
		} else {
			$query = array(
				'action' => 'cf_check_front_ajax',
				'scheme' => $scheme,
				'admin' => $_REQUEST['admin'],
				'mod_rewrite' => $_REQUEST['mod_rewrite'],
				'nonce' => $this->create_nonce( 'check-ajax' )
			);
			$args = array(
				'body' => http_build_query( $query )
			);
		}
		$request = wp_remote_post(
			$ajaxurl,
			$args
		);

		$result = false;
		if ( is_wp_error( $request ) ) {
			$message = $request->get_error_message();
		} elseif ( 200 != wp_remote_retrieve_response_code( $request ) ) {
			$message = wp_remote_retrieve_response_message( $request );
		} elseif ( isset( $request['body'] ) ) {
			$data = json_decode( $request['body'] );
			if ( 0 === $data ) {
				$message = __( 'Unexpected error', COLLABORATIVE_FILTERING_TEXT_DOMAIN ) . ' (0)';
			} elseif ( false === $data || is_null( $data ) ) {
				$message = __( 'Unexpected error', COLLABORATIVE_FILTERING_TEXT_DOMAIN ) . ' (json decode)';
				$message .= $request['body'];
			} elseif ( $data->success ) {
				if ( $_REQUEST['front'] && $data->data->front ) {
					$result = true;
					$message = 'success';
				} elseif ( !$_REQUEST['front'] && $data->data->back ) {
					$result = true;
					$message = 'success';
				} else {
					$message = __( 'Unexpected error', COLLABORATIVE_FILTERING_TEXT_DOMAIN ) . ' (has no data)';
				}
				if ( $result && $_REQUEST['referer'] ) {
					if ( !$data->data->result ) {
						$result = false;
						$message = __( 'Referer check error', COLLABORATIVE_FILTERING_TEXT_DOMAIN );
						$message .= ' (referer:' . $data->data->referer . ', host:' . $data->data->host . ')';
					}
				}
			} else {
				$message = __( 'Unexpected error', COLLABORATIVE_FILTERING_TEXT_DOMAIN ) . ' (has no flag)';
			}
		} else {
			$message = __( 'Unexpected error', COLLABORATIVE_FILTERING_TEXT_DOMAIN ) . ' (has no body)';
		}

		$messages = array(
			'Tested at: ' . date( 'Y-m-d h:i:s' ),
			'version: ' . COLLABORATIVE_FILTERING_PLUGIN_VERSION,
			'query' . json_encode( $query ),
			$message
		);
		$this->log( implode( "\n", $messages ) );

		return array(
			'result' => $result,
			'message' => $message,
			'elapsed' => $elapsed( $start )
		);
	}

	private function filter_request( $name )
	{
		if ( !isset( $_REQUEST[$name] ) ) {
			$_REQUEST[$name] = false;
		} elseif ( 'true' === $_REQUEST[$name] ) {
			$_REQUEST[$name] = true;
		} elseif ( 'false' === $_REQUEST[$name] ) {
			$_REQUEST[$name] = false;
		}
		$_REQUEST[$name] = $_REQUEST[$name] ? true : false;
	}
}

$GLOBALS[CollaborativeFiltering_API_Base::get_name( __FILE__ )] = CollaborativeFiltering_CheckAjax::get_instance();
