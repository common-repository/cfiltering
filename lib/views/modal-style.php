<?php
if ( !defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) )
	exit;

?>
<style>
	.cf-loading {
		background: url(<?php echo $loading_file;?>);
		background-size: contain;
		background-repeat: no-repeat;
		text-align: center;
		margin: 0 auto;
		height: 30px;
		width: 30px;
		display: inline-block;
		vertical-align: middle;
	}

	#cf-modal {
		background: url(<?php echo $back_file;?>);
		background-size: cover;
		position: fixed;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		z-index: 10000;
	}

	#cf-modal .cf-loading {
		display: none;
		position: fixed;
		top: 50%;
		left: 50%;
		margin-top: -15px;
		margin-left: -15px;
	}

	#cf-modal .cf-loading-message {
		display: none;
		position: fixed;
		top: 50%;
		color: white;
		margin-top: 25px;
		width: 100%;
		text-align: center;
		max-height: 90%;
	}

	#cf-modal-message-warp {
		position: fixed;
		display: inline-block;
		color: black;
		width: 100%;
		max-height: 90%;
		z-index: 10001;
		overflow-y: scroll;
		text-align: center;
		top: 50%;
	}

	#cf-modal-message-warp #cf-modal-message {
		background: white;
		display: inline-block;
		color: black;
		padding: 20px;
	}
</style>
