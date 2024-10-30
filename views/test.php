<?php
if ( !defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) )
	exit;
global $cf_minify;
$cf_minify->register_css( <<< EOS
.cf-now-loading{
	display: inline-block;
	margin: 5px;
}
.cf-now-loading-img{
	height: 15px;
	margin-left: 3px;
}
.cf-admin-message input[type="button"],
.cf-admin-message .cf-button,
#cf-test input[type="button"]{
	min-width: 100px;
	border: solid 2px #727272;
	box-shadow: #aaa 3px 3px 2px 2px;
	cursor: pointer;
	padding: 5px 30px;
	margin: 10px 5px;
	height: auto;
}
.cf-ng{
	color: red;
}
.cf-ok{
	color: green;
}
EOS
);
$button_value = $retest ? __( 'Retest', COLLABORATIVE_FILTERING_TEXT_DOMAIN ) : __( 'Test', COLLABORATIVE_FILTERING_TEXT_DOMAIN );
?>
<strong><?php echo COLLABORATIVE_FILTERING_PLUGIN_NAME; ?></strong>:
<input type="button" value="<?php echo esc_attr( $button_value ); ?>" id="cf-test-button">

<div id="cf-test-wrap" hidden="hidden" style="display:none"></div>

