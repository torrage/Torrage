<?php
	include_once dirname( __FILE__ ) . '/../inc/settings.inc.php';
	
	function cacheTorrent( $torrent )
	{
		$torrent = base64_decode( $torrent );
		$fp = tempnam( '/tmp', 'soapApi' );
		file_put_contents( $fp, $torrent );
		$toReturn = handle_upload( $fp );
		unlink( $fp );
		return $toReturn;
	}
	
	ini_set( 'soap.wsdl_cache_enabled', '0' ); // disabling WSDL cache
	$server = new SoapServer( 'torrage.wsdl' );
	$server->addFunction( 'cacheTorrent' );
	$server->handle();
