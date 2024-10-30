<?php
namespace CollaborativeFilteringApi;

if ( !defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) )
	exit;

abstract class CollaborativeFiltering_API_Base extends \CollaborativeFilteringBase\CollaborativeFiltering_Base_Class
{
	abstract public function get_api_name();

	abstract public function get_method();

	public function get_capability()
	{
		return false;
	}

	protected function setup_filter()
	{
		return true;
	}

	protected function ajax_filter()
	{
		return true;
	}

	protected function admin_filter()
	{
		return true;
	}

	protected function front_filter()
	{
		return true;
	}

	protected function only_loggedin()
	{
		return false;
	}

	protected function only_not_loggedin()
	{
		return false;
	}

	protected function only_front()
	{
		return false;
	}

	protected function only_admin()
	{
		return false;
	}

	public function is_basic_api()
	{
		return false;
	}

	protected function is_form_data()
	{
		return false;
	}

	public function action()
	{
		wp_send_json( $this->get_response() );
	}

	protected function get_response()
	{
		return array();
	}

	protected function external_access()
	{
		return false;
	}

	protected function allowed_origin()
	{
		return null;
	}

	protected function setup_function()
	{

	}

	public function setup()
	{
		if ( !$this->setup_filter() ) {
			return false;
		}

		if ( !$this->defined( 'DOING_AJAX' ) ) {
			global $cf_minify;
			if ( is_admin() ) {
				if ( $this->only_front() ) {
					return false;
				}
				if ( $this->only_not_loggedin() ) {
					return false;
				}
				if ( !$this->admin_filter() ) {
					return false;
				}
			} else {
				if ( $this->only_admin() ) {
					return false;
				}
				if ( !$this->front_filter() ) {
					return false;
				}
			}
			$cf_minify->register_script( $this->get_output_js() );
		} else {
			if ( !$this->ajax_filter() ) {
				return false;
			}
		}

		$this->register_action();
		$this->setup_function();
		return true;
	}

	protected function need_nonce_check()
	{
		return $this->definedv( 'COLLABORATIVE_FILTERING_NEED_NONCE_CHECK' ) == true;
	}

	private function is_post()
	{
		return strtolower( trim( $this->get_method() ) ) == "post";
	}

	protected function nonce_check()
	{
		if ( "nonce" === $this->get_api_name() ) {
			return false;
		}
		return $this->apply_filters( "nonce_check", $this->is_post() || $this->need_nonce_check(), $this->get_api_name(), $this->is_post(), $this->need_nonce_check() );
	}

	private function do_nonce_check()
	{
		return isset( $_REQUEST[$this->nonce_key()] ) && wp_verify_nonce( $_REQUEST[$this->nonce_key()], $this->get_api_name() );
	}

	private function nonce_key()
	{
		return $this->get_api_name() . "_nonce";
	}

	final public function get_api_full_name()
	{
		return self::get_prefix() . $this->get_api_name();
	}

	private function get_output_js()
	{
		if ( $this->nonce_check() ) {
			return $this->get_output_js_nonce();
		}

		$ret = <<< EOS
<script>
	var cf_obj = cf_obj || {};
	cf_obj.{$this->get_api_name()} = function( data, done, fail, always ){

EOS;

		if ( $this->is_form_data() ) {
			$ret .= <<< EOS
		var d = data;
		d.append('action', '{$this->get_api_full_name()}');
EOS;
		} else {
			$ret .= <<< EOS
		var d = data || {};
		d.action = '{$this->get_api_full_name()}';
EOS;
		}
		$ret .= <<< EOS

		{$this->get_xhr_script()}
	};
</script>
EOS;

		return $ret;
	}

	private function get_output_js_nonce()
	{
		if ( $this->consider_page_cache() ) {
			return $this->get_output_js_nonce2();
		}

		$nonce = wp_create_nonce( $this->get_api_name() );
		$ret = <<< EOS
<script>
	var cf_obj = cf_obj || {};
	cf_obj.{$this->nonce_key()} = '{$nonce}';
	cf_obj.{$this->get_api_name()} = function( data, done, fail, always ){
EOS;

		if ( $this->is_form_data() ) {
			$ret .= <<< EOS
		var d = data;
		d.append('action', '{$this->get_api_full_name()}');
		d.append('{$this->nonce_key()}', cf_obj.{$this->nonce_key()});
EOS;
		} else {
			$ret .= <<< EOS
		var d = data || {};
		d.action = '{$this->get_api_full_name()}';
		d.{$this->nonce_key()} = cf_obj.{$this->nonce_key()};
EOS;
		}
		$ret .= <<< EOS

		{$this->get_xhr_script()}
	};
</script>
EOS;

		return $ret;
	}

	private function get_output_js_nonce2()
	{
		$ret = <<< EOS
<script>
	var cf_obj = cf_obj || {};
	cf_obj.{$this->get_api_name()} = function( data, done, fail, always ){
		if (cf_obj.{$this->nonce_key()}) {

EOS;

		if ( $this->is_form_data() ) {
			$ret .= <<< EOS
			var d = data;
			d.append('action', '{$this->get_api_full_name()}');
			d.append('{$this->nonce_key()}', cf_obj.{$this->nonce_key()});
EOS;
		} else {
			$ret .= <<< EOS
			var d = data || {};
			d.action = '{$this->get_api_full_name()}';
			d.{$this->nonce_key()} = cf_obj.{$this->nonce_key()};
EOS;
		}
		$ret .= <<< EOS

			{$this->get_xhr_script()}
		} else {
			var obj = {};

			var ajax = cf_obj.nonce({name:'{$this->get_api_name()}'}, function(res){
				if (res.nonce) {
					cf_obj.{$this->nonce_key()} = res.nonce;
					ajax = cf_obj.{$this->get_api_name()}(data, done, fail, always);
				} else {
					if( fail ) fail( res );
					if( always ) always( );
				}
			}, function(error){
				if( fail ) fail( error );
				if( always ) always( );
			});
			obj.abort = function() {
				ajax.abort();
			};
			return obj;
		}
	};
</script>
EOS;

		return $ret;
	}

	private function get_xhr_script()
	{
		return <<< EOS

		return cf_obj.ajax(d, "{$this->get_method()}", done, fail, always);
EOS;
	}

	final public function output_js()
	{
		echo $this->get_output_js();
	}

	final public function ajax_action()
	{
		if ( $this->nonce_check() ) {
			if ( !$this->do_nonce_check() ) {
				status_header( '403' );
				echo 'Forbidden';
				die;
			}
		} else {
			if ( $this->apply_filters( 'check_referer', COLLABORATIVE_FILTERING_CHECK_REFERER ) ) {
				$check = $this->check_referer();
				if ( !$check['result'] ) {
					status_header( '403' );
					echo 'Forbidden';
					die;
				}
			}
		}

		$capability = $this->get_capability();
		if ( is_null( $capability ) || is_string( $capability ) ) {
			global $cf_user;
			if ( !$cf_user->loggedin ) {
				status_header( '403' );
				echo 'Forbidden';
				die;
			}
			if ( !$cf_user->user_can( $capability ) ) {
				status_header( '403' );
				echo 'Forbidden';
				die;
			}
		}

		if ( $this->external_access() ) {
			$origins = $this->allowed_origin();
			if ( is_array( $origins ) ) {
				if ( isset( $_SERVER['HTTP_ORIGIN'] ) && in_array( $_SERVER['HTTP_ORIGIN'], $origins ) ) {
					header( "Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN'] );
					header( "Access-Control-Allow-Credentials: true" );
					header( "Access-Allow-Control-Headers: X-Requested-With, Authorization" );
				} else {
					status_header( '403' );
					echo 'Forbidden';
					die;
				}
			} else {
				header( "Access-Control-Allow-Origin: *" );
			}
		}
		$this->action();
	}

	final public function register_action()
	{
		if ( !$this->only_loggedin() ) {
			add_action( 'wp_ajax_' . $this->get_api_full_name(), array( $this, 'ajax_action' ) );
		}
		if ( !$this->only_not_loggedin() ) {
			add_action( 'wp_ajax_nopriv_' . $this->get_api_full_name(), array( $this, 'ajax_action' ) );
		}
	}

	public static function get_prefix()
	{
		return "cf_api_";
	}

	public static function get_slug( $file )
	{
		return str_replace( "-", "_", preg_replace( "/^(.+)\\.php$/", "$1", basename( $file ) ) );
	}

	public static function get_name( $file )
	{
		return self::get_prefix() . self::get_slug( $file );
	}

}
