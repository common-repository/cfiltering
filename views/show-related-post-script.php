<?php
if ( !defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) )
	exit;
$loading_message = str_replace('"', "'", __('loading', COLLABORATIVE_FILTERING_TEXT_DOMAIN)) . '...';
?>
<script>
	var cf_obj = cf_obj || {};
	(function ($) {
		$(function () {
			cf_obj.loading_related_post = false;
			$(".cf_show_related_post_button").click(function () {
				if (cf_obj.loading_related_post) {
					return false;
				}
				cf_obj.loading_related_post = true;

				var obj = cf_obj.related_post({p: $(this).data('id')}, function (data) {
					if (data.result) {
						cf_obj.hide_loading();
						cf_obj.show_modal_message(data.message);
					} else {
						console.log(data.message);
						cf_obj.hide_modal();
					}
				}, function (error) {
					console.log(error);
					cf_obj.hide_modal();
				}, function () {
					cf_obj.loading_related_post = false;
				});

				cf_obj.show_modal(true, function () {
					obj.abort();
					cf_obj.hide_modal();
				}, "<?php echo esc_js($loading_message);?>");
				return false;
			});
		});
	})(jQuery);
</script>
