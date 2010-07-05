<?php
	header( 'Expires: Mon, 26 Jul 1997 05:00:00 GMT' );
	header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
	header( 'Cache-Control: no-store, no-cache, must-revalidate' );
	header( 'Cache-Control: post-check=0, pre-check=0', false );
	header( 'Pragma: no-cache' );
	
	include_once dirname( __FILE__ ) . '/settings.inc.php';
	
	if( !( ( strtolower( $_SERVER['HTTP_HOST'] ) == $SETTINGS['torrstoredns'] . ':80' ) || ( strtolower( $_SERVER['HTTP_HOST'] ) == $SETTINGS['torrstoredns'] . ':443' ) || ( strtolower( $_SERVER['HTTP_HOST'] ) == $SETTINGS['torrstoredns'] ) ) )
	{
		header( 'Location: ' . getProto() . $SETTINGS['torrstoredns'] . $_SERVER['REQUEST_URI'], true, 301 );
		exit();
	}
