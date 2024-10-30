<?php
if ( !defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) )
	exit;
global $cf_minify;
$cf_minify->register_css( <<< EOS
.cf-submit{
	float:right;
	margin-top:10px!important;
}
#cf-setting-table td {
	vertical-align: middle;
}
.cf-group-label-1 {
	background: #eaeaea !important;
}
.cf-group-label-0 {
	background: #e0e0e0 !important;
}
.cf-detail-link {
	vertical-align: middle;
	padding: 6px 20px;
	margin: 0 15px;
	background: #ccc;
	border: solid 2px #aaa;
	float: right;
}
EOS
);
?>
<h2><?php _e( "setting", COLLABORATIVE_FILTERING_TEXT_DOMAIN ); ?></h2>
<form action="<?php echo $_SERVER["REQUEST_URI"]; ?>" method="post">
	<table id="cf-setting-table" class="widefat striped">
		<tr>
			<th><?php _e( "group", COLLABORATIVE_FILTERING_TEXT_DOMAIN ); ?></th>
			<th><?php _e( "parameter", COLLABORATIVE_FILTERING_TEXT_DOMAIN ); ?></th>
			<th><?php _e( "saved value", COLLABORATIVE_FILTERING_TEXT_DOMAIN ); ?></th>
			<th><?php _e( "used value", COLLABORATIVE_FILTERING_TEXT_DOMAIN ); ?></th>
		</tr>
		<?php if ( count( $items ) <= 0 ): ?>
			<tr>
				<td colspan="4"><?php _e( "item not found.", COLLABORATIVE_FILTERING_TEXT_DOMAIN ); ?></td>
			</tr>
		<?php else: ?>
			<?php $n = 0; ?>
			<?php foreach ( $items as $v ): ?>
				<tr>
				<td rowspan="<?php echo count( $v['settings'] ); ?>"
					class="cf-group-label-<?php echo( $n++ % 2 ); ?>"><?php echo esc_html( $v['label'] ); ?></td>
				<?php if ( count( $v['settings'] ) <= 0 ): ?>
					<td colspan="3"><?php _e( "item not found.", COLLABORATIVE_FILTERING_TEXT_DOMAIN ); ?></td>
				<?php else: ?>
					<?php foreach ( $v['settings'] as $k => $setting ): ?>
						<?php if ( $k > 0 ): ?>
							<tr>
						<?php endif; ?>
						<td><label for="<?php echo $setting['key']; ?>"><?php echo $setting["label"]; ?></label></td>
						<td><input type="text" id="<?php echo $setting['key']; ?>"
								   name="<?php echo $setting['name']; ?>"
								   value="<?php echo esc_attr( $setting["db"] ); ?>"
								   placeholder="<?php echo esc_attr( $setting["placeholder"] ); ?>"></td>
						<td><?php echo esc_html( $setting["used"] ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
	</table>
	<input type="submit" value="<?php _e( "setting", COLLABORATIVE_FILTERING_TEXT_DOMAIN ); ?>"
		   class="button-primary cf-submit">
	<input type="hidden" value="<?php echo $nonce; ?>" name="nonce">
</form>
