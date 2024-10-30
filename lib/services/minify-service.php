<?php
namespace CollaborativeFilteringService;

if ( !defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) )
	exit;

class CollaborativeFiltering_Minify extends CollaborativeFiltering_Service_Base
{

	private static $_instance = null;

	private $script            = array();
	private $has_output_script = false;
	private $css               = array();
	private $end_footer        = false;

	protected function __construct()
	{
		$this->initialize();
	}

	public static function get_instance()
	{
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new CollaborativeFiltering_Minify();
		}
		return self::$_instance;
	}

	private function initialize()
	{
		if ( is_admin() ) {
			add_action( 'admin_print_footer_scripts', function () {
				$this->output_js();
			} );
			add_action( 'admin_head', function () {
				$this->output_css();
			} );
			add_action( 'admin_footer', function () {
				$this->output_css();
				$this->end_footer = true;
			} );
		} else {
			add_action( 'wp_print_footer_scripts', function () {
				$this->output_js();
			} );
			add_action( 'wp_print_styles', function () {
				$this->output_css();
			} );
			add_action( 'wp_print_footer_scripts', function () {
				$this->output_css();
				$this->end_footer = true;
			}, 9 );
		}
	}

	public function register_script( $script, $priority = 10 )
	{
		$this->set_script( preg_replace( '/<\s*\/?script\s*>/', '', $script ), $priority );
	}

	public function register_js_file( $file, $priority = 10 )
	{
		$this->set_script( @file_get_contents( $file ), $priority );
	}

	private function set_script( $script, $priority )
	{
		$script = trim($script);
		if ("" === $script) {
			return;
		}
		$this->script[$priority][] = $script;
		if ( $this->has_output_script ) {
			$this->output_js();
		}
	}

	private function output_js()
	{
		if ( empty( $this->script ) ) {
			return;
		}
		ksort( $this->script );
		$script = implode( "\n", array_map( function ( $s ) {
			return implode( "\n", $s );
		}, $this->script ) );

		if ( $this->apply_filters( "minify_js", COLLABORATIVE_FILTERING_MINIFY_JS ) ) {
			if ( !class_exists( '\JSMin' ) ) {
				require_once COLLABORATIVE_FILTERING_LIB_LIBRARY_DIR . DIRECTORY_SEPARATOR . 'jsmin-php' . DIRECTORY_SEPARATOR . 'jsmin.php';
			}
			echo '<script>' . \JSMin::minify( $script ) . '</script>';
		} else {
			echo '<script>' . $script . '</script>';
		}
		$this->script = array();
		$this->has_output_script = true;
	}

	public function register_css( $css, $priority = 10 )
	{
		$this->set_css( preg_replace( '/<\s*\/?style\s*>/', '', $css ), $priority );
	}

	public function register_css_file( $file, $priority = 10 )
	{
		$this->set_css( @file_get_contents( $file ), $priority );
	}

	private function set_css( $css, $priority )
	{
		$css = trim( $css );
		if ( "" === $css ) {
			return;
		}
		$this->css[$priority][] = $css;
		if ( $this->end_footer ) {
			$this->output_css();
		}
	}

	private function output_css()
	{
		if ( empty( $this->css ) ) {
			return;
		}
		ksort( $this->css );
		$css = implode( "\n", array_map( function ( $s ) {
			return implode( "\n", $s );
		}, $this->css ) );

		if ( $this->apply_filters( 'minify_css', COLLABORATIVE_FILTERING_MINIFY_CSS ) ) {
			if ( !class_exists( '\CSSmin' ) ) {
				require_once COLLABORATIVE_FILTERING_LIB_LIBRARY_DIR . DIRECTORY_SEPARATOR . 'YUI-CSS-compressor' . DIRECTORY_SEPARATOR . 'cssmin.php';
			}
			$compressor = new \CSSmin();
			echo '<style>' . $compressor->run( $css ) . '</style>';
		} else {
			echo '<style>' . $css . '</style>';
		}
		$this->css = array();
	}
}

$GLOBALS['cf_minify'] = CollaborativeFiltering_Minify::get_instance();
