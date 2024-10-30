<?php
namespace CollaborativeFilteringService;

if ( !defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) )
	exit;

class CollaborativeFiltering_Test extends CollaborativeFiltering_Service_Base
{

	private static $_instance = null;

	private $test_params = null;

	protected function __construct()
	{
		$this->initialize();
	}

	public static function get_instance()
	{
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new CollaborativeFiltering_Test();
		}
		return self::$_instance;
	}

	private function initialize()
	{
		//		add_action( 'cf_changed_option', function ( $key ) {
		//			if ( $this->get_filter_prefix() . 'front_admin_ajax' === $key ||
		//				$this->get_filter_prefix() . 'check_referer' === $key
		//			) {
		//				add_action( 'admin_footer', function () {
		//					$this->undone();
		//				} );
		//			}
		//		} );

		add_action( 'admin_head', function () {
			$fatal = false;
			if ( !$this->apply_filters( 'test', COLLABORATIVE_FILTERING_TEST ) ) {
				global $cf_option;
				$fatal = $cf_option->get( 'fatal_error' );
				if ( $fatal ) {
					$this->error( sprintf( __( "<strong>%s</strong> doesn't work on your server.", COLLABORATIVE_FILTERING_TEXT_DOMAIN ), COLLABORATIVE_FILTERING_PLUGIN_NAME ) );
				} else {
					return;
				}
			}
			global $cf_api;
			foreach ( $this->get_use_methods() as $method ) {
				$cf_api->register_use_function( $method );
			}
			$cf_api->register_use_function( 'get-tests' );
			$cf_api->register_use_function( 'reflect-results' );

			$loading_file = $this->apply_filters( "loading_image", COLLABORATIVE_FILTERING_LIB_IMG_DIR . DIRECTORY_SEPARATOR . 'loading.gif' );
			$loading_file = $this->dir2path( $loading_file );

			$back_file = $this->apply_filters( "back_image", COLLABORATIVE_FILTERING_LIB_IMG_DIR . DIRECTORY_SEPARATOR . 'back.png' );
			$back_file = $this->dir2path( $back_file );

			global $cf_minify;
			$cf_minify->register_script( $this->view( 'modal-script', false, array(), true ) );
			$cf_minify->register_script( $this->view( 'test-script', false, array( 'loading_image' => $loading_file ) ) );
			$cf_minify->register_css( $this->view( 'modal-style', false, array( 'loading_file' => $loading_file, "back_file" => $back_file ), true ) );

			if ( $fatal ) {
				$this->error( $this->view( 'test', false, array( 'retest' => $fatal ) ) );
			} else {
				$this->message( $this->view( 'test', false, array( 'retest' => $fatal ) ) );
			}
		} );

		add_action( 'init', function () {
			if ( isset( $_POST['action'], $_POST['scheme'], $_POST['admin'], $_POST['mod_rewrite'], $_POST['nonce'] ) && $this->verify_nonce( $_POST['nonce'], 'check-ajax' ) ) {
				$this->test_params = array(
					'scheme' => $_POST['scheme'],
					'admin' => $_POST['admin'] === '1',
					'mod_rewrite' => $_POST['mod_rewrite'] === '1',
				);
			}
		}, 1 );
	}

	public function undone()
	{
		global $cf_option;
		$cf_option->set( $this->get_filter_prefix() . 'test', 'true' );
	}

	public function done()
	{
		global $cf_option;
		$cf_option->set( $this->get_filter_prefix() . 'test', 'false' );
	}

	public function get_test_settings()
	{
		return $this->apply_filters( 'get_test_settings', array(
			'check_ajax' => array(
				'title' => __( 'Ajax test', COLLABORATIVE_FILTERING_TEXT_DOMAIN ),
				//								'groups' => array(
				//									'front' => array(
				//										'title' => __( 'Frontend test', COLLABORATIVE_FILTERING_TEXT_DOMAIN ),
				'items' => array(
					array(
						'front' => true,
						'admin' => false,
						'referer' => true,
						'mod_rewrite' => false
					),
					array(
						'front' => true,
						'admin' => false,
						'referer' => true,
						'mod_rewrite' => true
					),
					array(
						'front' => true,
						'admin' => true,
						'referer' => true,
						'mod_rewrite' => false
					),
					array(
						'front' => true,
						'admin' => true,
						'referer' => true,
						'mod_rewrite' => true
					),
					array(
						'front' => true,
						'admin' => false,
						'referer' => false,
						'mod_rewrite' => false
					),
					array(
						'front' => true,
						'admin' => false,
						'referer' => false,
						'mod_rewrite' => true
					),
					array(
						'front' => true,
						'admin' => true,
						'referer' => false,
						'mod_rewrite' => false
					),
					array(
						'front' => true,
						'admin' => true,
						'referer' => false,
						'mod_rewrite' => true
					),
				)
			),
			//								'back' => array(
			//									'title' => __( 'Backend test', COLLABORATIVE_FILTERING_TEXT_DOMAIN ),
			//									'items' => array(
			//										array(
			//											'front' => false,
			//											'admin' => true,
			//											'referer' => true
			//										),
			//										array(
			//											'front' => false,
			//											'admin' => true,
			//											'referer' => false
			//										),
			//									)
			//								)
			//							)
			//						),
		) );
	}

	public function get_use_methods()
	{
		return array_keys( $this->get_test_settings() );
	}

	public function reflect_result( $test_results )
	{
		if ( !is_array( $test_results ) ) {
			return null;
		}

		if ( isset( $test_results['check_ajax'] ) ) {
			$check_ajax = $this->reflect_ajax_result( $test_results['check_ajax'] );
			$fatal = $check_ajax[0];
			$check_ajax = $check_ajax[1];
		} else {
			$fatal = true;
			$check_ajax = array();
		}

		$results = array_merge( $check_ajax );
		global $cf_option;
		$cf_option->set( 'fatal_error', $fatal );
		$cf_option->set( 'test_results', serialize( $results ) );

		$this->done();

		return array(
			'fatal' => $fatal,
			'results' => $results,
			'urls' => array(
				'plugin' => admin_url( 'plugins.php' ),
				'setting' => admin_url( 'admin.php?page=cf-setting' ),
			)
		);
	}

	private function reflect_ajax_result( $result )
	{
		global $cf_option;

		$front_admin_ajax = $this->apply_filters( 'front_admin_ajax', COLLABORATIVE_FILTERING_FRONT_ADMIN_AJAX );
		$check_referer = $this->apply_filters( 'check_referer', COLLABORATIVE_FILTERING_CHECK_REFERER );
		$mod_rewrite_ajax_access = $this->apply_filters( 'mod_rewrite_ajax_access', COLLABORATIVE_FILTERING_MOD_REWRITE_AJAX_ACCESS );

		$front_result_message = __( "There's no problem", COLLABORATIVE_FILTERING_TEXT_DOMAIN );
		$fatal_error = false;

		$s1 = __( 'whether to use admin-ajax.php on front page', COLLABORATIVE_FILTERING_TEXT_DOMAIN );
		$s2 = __( 'whether to check referer when ajax access without nonce check', COLLABORATIVE_FILTERING_TEXT_DOMAIN );
		$s3 = __( 'whether to use mod rewrite access when ajax access', COLLABORATIVE_FILTERING_TEXT_DOMAIN );
		$front_admin_ajax_result = $front_admin_ajax;
		$check_referer_result = $check_referer;
		$mod_rewrite_ajax_access_result = $mod_rewrite_ajax_access;

		if ( $result['check_ajax'][0] ) {
			$front_admin_ajax_result = false;
			$check_referer_result = true;
			$mod_rewrite_ajax_access_result = false;
		} elseif ( $result['check_ajax'][1] ) {
			$front_admin_ajax_result = false;
			$check_referer_result = true;
			$mod_rewrite_ajax_access_result = true;
		} elseif ( $result['check_ajax'][2] ) {
			$front_admin_ajax_result = true;
			$check_referer_result = true;
			$mod_rewrite_ajax_access_result = false;
		} elseif ( $result['check_ajax'][3] ) {
			$front_admin_ajax_result = true;
			$check_referer_result = true;
			$mod_rewrite_ajax_access_result = true;
		} elseif ( $result['check_ajax'][4] ) {
			$front_admin_ajax_result = false;
			$check_referer_result = false;
			$mod_rewrite_ajax_access_result = false;
		} elseif ( $result['check_ajax'][5] ) {
			$front_admin_ajax_result = false;
			$check_referer_result = false;
			$mod_rewrite_ajax_access_result = true;
		} elseif ( $result['check_ajax'][6] ) {
			$front_admin_ajax_result = true;
			$check_referer_result = false;
			$mod_rewrite_ajax_access_result = false;
		} elseif ( $result['check_ajax'][7] ) {
			$front_admin_ajax_result = true;
			$check_referer_result = false;
			$mod_rewrite_ajax_access_result = true;
		} else {
			$fatal_error = true;
			$front_result_message = sprintf( __( "<strong>%s</strong> doesn't work on your server.", COLLABORATIVE_FILTERING_TEXT_DOMAIN ), COLLABORATIVE_FILTERING_PLUGIN_NAME );
		}

		$changed_message = false;
		if ( $front_admin_ajax !== $front_admin_ajax_result ) {
			$changed_message = sprintf( __( 'Changed [%s] to [%s]', COLLABORATIVE_FILTERING_TEXT_DOMAIN ), $s1, var_export( $front_admin_ajax_result, true ) );
			$cf_option->set( $this->get_filter_prefix() . 'front_admin_ajax', var_export( $front_admin_ajax_result, true ) );
		}
		if ( $check_referer !== $check_referer_result ) {
			if ( false !== $changed_message ) {
				$changed_message .= '<br>';
			} else {
				$changed_message = '';
			}
			$changed_message .= sprintf( __( 'Changed [%s] to [%s]', COLLABORATIVE_FILTERING_TEXT_DOMAIN ), $s2, var_export( $check_referer_result, true ) );
			$cf_option->set( $this->get_filter_prefix() . 'check_referer', var_export( $check_referer_result, true ) );
		}
		if ( $mod_rewrite_ajax_access !== $mod_rewrite_ajax_access_result ) {
			if ( false !== $changed_message ) {
				$changed_message .= '<br>';
			} else {
				$changed_message = '';
			}
			$changed_message .= sprintf( __( 'Changed [%s] to [%s]', COLLABORATIVE_FILTERING_TEXT_DOMAIN ), $s3, var_export( $mod_rewrite_ajax_access_result, true ) );
			$cf_option->set( $this->get_filter_prefix() . 'mod_rewrite_ajax_access', var_export( $mod_rewrite_ajax_access_result, true ) );
		}
		if ( false !== $changed_message ) {
			$front_result_message = $changed_message;
		}

		return array(
			$fatal_error,
			array(
				'check_ajax' => array(
					'result' => false === $fatal_error,
					'message' => $front_result_message,
				),
			)
		);
	}

	public function is_test()
	{
		return null !== $this->test_params;
	}

	public function get_test_params()
	{
		return $this->test_params;
	}

	public function get_test_param( $name, $default = null )
	{
		if ( !$this->is_test() ) {
			return $default;
		}
		if ( array_key_exists( $name, $this->test_params ) ) {
			return $this->test_params[$name];
		}
		return $default;
	}
}

$GLOBALS['cf_test'] = CollaborativeFiltering_Test::get_instance();
