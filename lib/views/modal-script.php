<?php
if ( !defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) )
	exit;
?>
<script>
	var cf_obj = cf_obj || {};
	(function ($) {

		cf_obj.show_modal = function (loading, click, mes) {
			$("#cf-modal").fadeIn();
			if (loading) {
				$("#cf-modal .cf-loading").fadeIn();
				$("#cf-modal .cf-loading-message").fadeIn();
				if (mes) {
					$("#cf-modal .cf-loading-message").html(mes);
				}
			}
			$("#cf-modal-message-warp").fadeOut();
			if (click) {
				$("#cf-modal, #cf-modal-message-warp").unbind('click').click(function () {
					click();
					return false;
				});
			}
		};
		cf_obj.show_loading = function () {
			$("#cf-modal .cf-loading").fadeIn();
		};
		cf_obj.show_modal_message = function (mes) {
			if (mes) {
				cf_obj.set_modal_message(mes);
			}
			$("#cf-modal-message-warp").show();
			var check_resize = function () {
				if ($("#cf-modal-message-warp").is(":visible")) {
					cf_obj.set_modal_message_size();
					setTimeout(check_resize, 1000);
				}
			};
			check_resize();
		};
		cf_obj.hide_modal = function () {
			$("#cf-modal").fadeOut();
			$("#cf-modal .cf-loading").fadeOut();
			$("#cf-modal .cf-loading-message").fadeOut();
			$("#cf-modal-message-warp").fadeOut();
		};
		cf_obj.hide_loading = function () {
			$("#cf-modal .cf-loading").fadeOut();
			$("#cf-modal .cf-loading-message").fadeOut();
		};
		cf_obj.hide_modal_message = function () {
			$("#cf-modal-message-warp").fadeOut();
		};
		cf_obj.set_modal_message = function (mes) {
			$("#cf-modal-message").html(mes);
			cf_obj.set_modal_message_size();
		};
		cf_obj.set_modal_message_size = function () {
			var height = $("#cf-modal-message-warp").get(0).offsetHeight / 2;
			$("#cf-modal-message-warp").css('margin-top', -height + 'px');
		};
		$(function () {
			<?php if (is_admin()):?>
			$('<div id="cf-modal"><div class="cf-loading"></div>' + '<div class="cf-loading-message"></div>' + '</div>' + '<div id="cf-modal-message-warp">' + '<div id="cf-modal-message"></div>' + '</div>').prependTo("#wpwrap").hide();
			<?php else:?>
			$('<div id="cf-modal"><div class="cf-loading"></div>' + '<div class="cf-loading-message"></div>' + '</div>' + '<div id="cf-modal-message-warp">' + '<div id="cf-modal-message"></div>' + '</div>').prependTo("#container").hide();
			<?php endif;?>
			$("#cf-modal-message").click(function () {
				return false;
			});
		});
	})(jQuery);
</script>
