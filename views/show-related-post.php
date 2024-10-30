<?php
if ( !defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) )
	exit;
?>
<h2><?php echo esc_html( $post->post_title ); ?></h2>
<table class="widefat striped">
	<tr>
		<th><?php _e( "rank", COLLABORATIVE_FILTERING_TEXT_DOMAIN ); ?></th>
		<th><?php _e( "post id", COLLABORATIVE_FILTERING_TEXT_DOMAIN ); ?></th>
		<th><?php _e( "post name", COLLABORATIVE_FILTERING_TEXT_DOMAIN ); ?></th>
		<th><?php _e( "score", COLLABORATIVE_FILTERING_TEXT_DOMAIN ); ?></th>
	</tr>
	<?php if ( count( $data ) <= 0 ): ?>
		<tr>
			<td colspan="4"><?php _e( "item not found.", COLLABORATIVE_FILTERING_TEXT_DOMAIN ); ?></td>
		</tr>
	<?php else: ?>
		<?php $n = 1; ?>
		<?php foreach ( $data as $d ): ?>
			<tr>
				<td><?php echo $n++; ?></td>
				<td><?php echo $d['post_id']; ?></td>
				<td><a href="<?php echo esc_url( get_permalink( $d['post_id'] ) ); ?>"
					   onclick="window.open('<?php echo esc_url( get_permalink( $d['post_id'] ) ); ?>', 'cf-window'); return false;"><?php echo esc_html( $d['post']->post_title ); ?></a>
				</td>
				<td><?php echo round( $d['jaccard'], 4 ); ?></td>
			</tr>
		<?php endforeach; ?>
	<?php endif; ?>
</table>
<div class="cf-calculated-status">
	<?php if ( $calculated >= $threshold ): ?>
		(<?php _e( 'data num', COLLABORATIVE_FILTERING_TEXT_DOMAIN ); ?>: <span
			class="cf-green"><?php echo $calculated; ?> >= <?php echo $threshold; ?></span>)
	<?php else: ?>
		(<?php _e( 'data num', COLLABORATIVE_FILTERING_TEXT_DOMAIN ); ?>: <span
			class="cf-red"><?php echo $calculated; ?> < <?php echo $threshold; ?></span>)
	<?php endif; ?>
</div>
<input type="button" value="<?php _e( 'Close', COLLABORATIVE_FILTERING_TEXT_DOMAIN ); ?>" class="button-primary"
	   onclick="cf_obj.hide_modal(); return false;">

<style>
	#cf-modal-message .button-primary {
		float: right;
		margin-top: 10px;
	}

	.cf-calculated-status {
		text-align: right;
		margin-top: 10px;
	}

	.cf-green {
		color: green;
	}

	.cf-red {
		color: orangered;
	}
</style>
