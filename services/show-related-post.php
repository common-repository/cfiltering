<?php
namespace CollaborativeFilteringService;

if ( !defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) )
	exit;

class CollaborativeFiltering_ShowRelatedPost extends CollaborativeFiltering_Service_Base
{

	private static $_instance = null;

	protected function __construct()
	{
		$this->initialize();
	}

	public static function get_instance()
	{
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new CollaborativeFiltering_ShowRelatedPost();
		}
		return self::$_instance;
	}

	private function initialize()
	{
		if ( !$this->apply_filters( 'show_result', COLLABORATIVE_FILTERING_SHOW_RESULT ) ) {
			return;
		}

		add_action( 'admin_head-edit.php', function () {
			$post_type = isset( $_GET['post_type'] ) ? $_GET['post_type'] : 'post';
			$post_types = $this->valid_post_types();
			if ( !empty( $post_types ) && !in_array( $post_type, $post_types ) ) {
				return;
			}

			global $cf_api;
			$cf_api->register_use_function( 'related-post' );

			$loading_file = $this->apply_filters( "loading_image", COLLABORATIVE_FILTERING_LIB_IMG_DIR . DIRECTORY_SEPARATOR . 'loading.gif' );
			$loading_file = $this->dir2path( $loading_file );

			$back_file = $this->apply_filters( "back_image", COLLABORATIVE_FILTERING_LIB_IMG_DIR . DIRECTORY_SEPARATOR . 'back.png' );
			$back_file = $this->dir2path( $back_file );

			global $cf_minify;
			$cf_minify->register_script( $this->view( 'modal-script', false, array(), true ) );
			$cf_minify->register_script( $this->view( 'show-related-post-script', false ) );
			$cf_minify->register_css( $this->view( 'modal-style', false, array( 'loading_file' => $loading_file, "back_file" => $back_file ), true ) );

			add_filter( "manage_{$post_type}_posts_columns", function ( $columns ) {
				$columns['cf_show_related_post'] = __( 'recommendation', COLLABORATIVE_FILTERING_TEXT_DOMAIN );
				return $columns;
			}, $this->apply_filters( 'manage_posts_columns_priority', 10 ) );
			add_action( "manage_{$post_type}_posts_custom_column", function ( $column_name, $post_id ) {
				if ( 'cf_show_related_post' !== $column_name ) {
					return;
				}
				?>
				<input type="button"
					   value="<?php echo esc_attr( __( 'show', COLLABORATIVE_FILTERING_TEXT_DOMAIN ) ); ?>"
					   data-id="<?php echo $post_id; ?>" class="cf_show_related_post_button button-primary">
				<?php
			}, $this->apply_filters( 'manage_posts_custom_column_priority', 10 ), 2 );
		} );
	}
}

$GLOBALS['cf_show_related_post'] = CollaborativeFiltering_ShowRelatedPost::get_instance();
