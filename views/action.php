<?php
if ( !defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) )
	exit;
global $cf_minify;
$confirm = str_replace( "'", '"', __( 'Are you sure you want?', COLLABORATIVE_FILTERING_TEXT_DOMAIN ) );
?>
<h2><?php _e( "action", COLLABORATIVE_FILTERING_TEXT_DOMAIN ); ?></h2>
<form action="<?php echo $_SERVER["REQUEST_URI"]; ?>" method="post">
	<table class="widefat striped">
		<?php if ( count( $data ) <= 0 ): ?>
			<tr>
				<td colspan="2"><?php _e( "item not found.", COLLABORATIVE_FILTERING_TEXT_DOMAIN ); ?></td>
			</tr>
		<?php else: ?>
			<?php foreach ( $data as $k => $v ): ?>
				<tr>
					<td><label for="<?php echo $k; ?>"><?php echo $v["description"]; ?></label></td>
					<td><input type="button" id="<?php echo $k; ?>" data-action="<?php echo $k; ?>"
							   data-confirm="<?php echo $v['confirm'] ? '1' : '0'; ?>"
							   value="<?php _e( 'submit', COLLABORATIVE_FILTERING_TEXT_DOMAIN ); ?>"
							   class="button-primary"></td>
				</tr>
				<?php
				$cf_minify->register_script(
					<<< EOS
<script>
	(function($){
		$("#$k").click(function(){
			if ($(this).data("confirm")) {
				if (!window.confirm('$confirm')) {
					return false;
				}
			}
			$("#cf-action").val($(this).data("action"));
			$("#cf-submit").trigger("click");
			return false;
		});
	})(jQuery);
</script>
EOS
				);
				?>
			<?php endforeach; ?>
		<?php endif; ?>
	</table>
	<input type="submit" hidden="hidden" style="display:none" id="cf-submit">
	<input type="hidden" value="<?php echo $nonce; ?>" name="nonce">
	<input type="hidden" value="" name="cf-action" id="cf-action">
</form>
