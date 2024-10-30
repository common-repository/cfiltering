<?php
if ( !defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) )
	exit;
$loading_message = str_replace( '"', "'", __( 'loading', COLLABORATIVE_FILTERING_TEXT_DOMAIN ) ) . '...';
?>
<script>
	var cf_obj = cf_obj || {};
	cf_obj.test_executing = false;
	cf_obj.tests = null;
	cf_obj.now_loading = '<div class="cf-now-loading">Now Loading...<img src="<?php echo esc_attr( $loading_image );?>" class="cf-now-loading-img"></div>';
	cf_obj.test_number = 2;
	cf_obj.close_modal_func = null;

	(function ($) {

		function get_tests(func) {
			if (cf_obj.tests !== null) {
				if (func) func(cf_obj.tests);
				return;
			}
			cf_obj.get_tests({}, function (data) {
				if (!data.result) {
					set_error(data.message);
					return;
				}
				cf_obj.tests = data;
				if (func) func(data);
			}, function (error) {
				set_error(error);
			});
		}

		function do_test(elem, func) {
			if ($(elem).hasClass('cf-test-executing') || $(elem).hasClass('cf-test-executed')) {
				return;
			}
			$(elem).html(cf_obj.now_loading);
			$(elem).addClass('cf-test-executing');
			var method = $(elem).data('method');
			var params = $(elem).data('params');
			cf_obj[method](params, function (data) {
				set_test(elem, data);
				if (func) func(data);
			}, function (error) {
				set_error(error);
			}, function () {
				$(elem).addClass('cf-test-executed');
				$(elem).removeClass('cf-test-executing');
			});
		}

		function set_test(elem, data) {
			if (data.result) {
				$(elem).html('<span class="cf-ok">OK</span>');
				$(elem).data('result', 1);
			} else {
				$(elem).html('<span class="cf-ng">NG</span>');
				$(elem).data('result', 0);
			}
		}

		function reflect_results(r, func, finished) {
			cf_obj.reflect_results({r: r}, function (data) {
				if (func) func(data);
			}, function (error) {
				set_error(error);
			}, function () {
				if (finished) finished();
			});
		}

		function set_error(error) {
			console.log(error);
			cf_obj.test_executing = false;
			cf_obj.hide_modal();
		}

		function parse_data(data, f, n) {
			var html = '';
			if (n === undefined) {
				n = 3;
			}
			for (var key in data) {
				if (f === undefined) {
					var func = key;
				} else {
					var func = f;
				}
				html += '<div data-id="cf-test-' + key + '">';
				if ('title' in data[key]) {
					html += '<h' + n + '>' + data[key]['title'] + '</h' + n + '>';
				}
				if ('groups' in data[key]) {
					html += parse_data(data[key]['groups'], func, n + 1);
				}
				if ('items' in data[key]) {
					html += '<div class="cf-test-group" data-method="' + func + '" data-group="' + key + '">';
					for (var i in data[key]['items']) {
						html += '<div class="cf-test-item" data-method="' + func + '" data-group="' + key + '"';
						html += ' data-params=\'' + JSON.stringify(data[key]['items'][i]) + '\'>';
						html += '</div>';
					}
					html += '</div>';
				}
				html += '</div>';
			}
			return html;
		}

		function end_test(func) {
			cf_obj.end_test({}, function (data) {
				if (!data.result) {
					set_error(data.message);
					return;
				}
				if (func)func(data);
			}, function (error) {
				set_error(error);
			});
		}

		$(function () {
			$('#cf-test-button').click(function () {
				if (cf_obj.test_executing) {
					return;
				}
				cf_obj.test_executing = true;
				cf_obj.close_modal_func = null;
				cf_obj.show_modal(true, function () {
					cf_obj.test_executing = false;
					if (cf_obj.close_modal_func) {
						cf_obj.close_modal_func();
					}
					cf_obj.hide_modal();
				}, "<?php echo esc_js( $loading_message );?>");

				get_tests(function (data) {
					if (!cf_obj.test_executing) {
						return;
					}
					var html = '<div id="cf-test">';
					html += parse_data(data.result);
					html += '</div>';
					cf_obj.hide_loading();
					cf_obj.show_modal_message(html);

					cf_obj.timer = setInterval(function () {
						if (!cf_obj.test_executing) {
							clearInterval(cf_obj.timer);
							return;
						}
						var rest = $('.cf-test-item').not('.cf-test-executed, .cf-test-executing').length;
						if (rest <= 0) {
							clearInterval(cf_obj.timer);
							cf_obj.timer = setInterval(function () {
								if (!cf_obj.test_executing) {
									clearInterval(cf_obj.timer);
									return;
								}
								var rest = $('.cf-test-executing').length;
								if (rest <= 0) {
									clearInterval(cf_obj.timer);

									var r = {};
									$('.cf-test-group').each(function () {
										var method = $(this).data('method');
										var group = $(this).data('group');
										if (r[method] === undefined) {
											r[method] = {};
										}
										r[method][group] = {};
										$(this).find('.cf-test-item').each(function (index) {
											r[method][group][index] = $(this).data('result');
										});
									});
									$('.cf-test-group').html(cf_obj.now_loading);

									reflect_results(r, function (data) {
										if (!cf_obj.test_executing) {
											return;
										}
										var error = false;
										for (var key in data.result.results) {
											var html = '';
											if (data.result.results[key].result) {
												html += '<span class="cf-ok">' + data.result.results[key].message + '</span>';
											} else {
												html += '<span class="cf-ng">' + data.result.results[key].message + '</span>';
												error = true;
											}
											$('.cf-test-group[data-group="' + key + '"]').html(html);
										}

										var elem = $('#cf-test-button').closest('.cf-admin-message');
										$(elem).fadeOut();
										var modal = $('#cf-test');
										if (data.result.fatal) {
											$(modal).append('<input type="button" value="プラグインページへ" id="cf-test-plugin-page">');
											$('#cf-test-plugin-page').click(function () {
												location.href = data.result.urls.plugin;
												return false;
											});
										}
										$(modal).append('<input type="button" value="閉じる" id="cf-test-close-button">');
										cf_obj.close_modal_func = function () {
											if (location.href == data.result.urls.setting) {
												location.reload();
											} else {
												cf_obj.hide_modal();
											}
										};
										$('#cf-test-close-button').click(function () {
											cf_obj.close_modal_func();
											return false;
										});
									}, function () {
										cf_obj.test_executing = false;
									});
								}
							}, 500);
							return;
						}
						var executing = $('.cf-test-executing').length;
						for (var i = cf_obj.test_number - executing; --i >= 0 && --rest >= 0;) {
							do_test($('.cf-test-item').not('.cf-test-executed, .cf-test-executing').eq(0), function (data) {
							});
						}
					}, 500);
				});
			})
		});
	})(jQuery);
</script>
