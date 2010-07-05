<?php
	
	include_once dirname( __FILE__ ) . '/../inc/settings.inc.php';
	
	ini_set( 'soap.wsdl_cache_enabled', '0' );
	
	$torrent = base64_encode( file_get_contents( 'test.torrent' ) );
	
	$client = new SoapClient( getProto() . $SETTINGS['torrstoredns'] . '/api/torrage.wsdl' );
	$infoHash = $client->cacheTorrent( $torrent );
	
	echo $infoHash . "\n";
	// URL will be http(s)://torrage.com/torrent/$infoHash.torrent

