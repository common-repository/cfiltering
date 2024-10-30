<?php
if ( !defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) )
	exit;
?>
<h2><?php _e( "log", COLLABORATIVE_FILTERING_TEXT_DOMAIN ); ?></h2>
<table class="widefat striped">
	<tr>
		<th><?php _e( "date", COLLABORATIVE_FILTERING_TEXT_DOMAIN ); ?></th>
		<th><?php _e( "message", COLLABORATIVE_FILTERING_TEXT_DOMAIN ); ?></th>
	</tr>
	<?php if ( count( $date ) !== count( $message ) ): ?>
		<tr>
			<td colspan="2"><?php _e( "the log file was broken and could not be opened.", COLLABORATIVE_FILTERING_TEXT_DOMAIN ); ?></td>
		</tr>
	<?php elseif ( count( $date ) <= 0 ): ?>
		<tr>
			<td colspan="2"><?php _e( "item not found.", COLLABORATIVE_FILTERING_TEXT_DOMAIN ); ?></td>
		</tr>
	<?php else: ?>
		<?php for ( $i = count( $date ); --$i >= 0 && --$number >= 0; ) : ?>
			<tr>
				<td><?php echo $date[$i]; ?></td>
				<td><?php echo nl2br( $message[$i] ); ?></td>
			</tr>
		<?php endfor; ?>
	<?php endif; ?>
</table>


