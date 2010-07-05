<?php

	$SETTINGS = array(
		'savepath' => '/var/data/torrage.com/www/t/', // where .torrent's are stored
	
		'trackers' => array( // list of trackers that will always exist in torrent
			'http://tracker.openbittorrent.com',
			'udp://tracker.openbittorrent.com',
			'http://tracker.publicbt.com/announce',
			'udp://tracker.publicbt.com:80/announce',
			'http://denis.stalker.h3q.com:6969/announce',
			'udp://denis.stalker.h3q.com:6969/announce',
		),
		
		'torrstoredns' => 'torrage.com', // used for link generation
		
		'synclists_enable' => true, // enable/disable sync files
		'synclists_day' => true, // sync daily
		'synclists_month' => true, // sync monthly
		'synclists_path' => dirname( __FILE__ ) . '/../sync', // sync storage path
	);
	
	// error defines
	define( 'TORRAGE_FILE_NOT_FOUND', 10 );
	define( 'TORRAGE_FILE_EMPTY',     11 );
	define( 'TORRAGE_FILE_INVALID',   12 );
	define( 'TORRAGE_FILE_UNKNOWN',   13 );
	define( 'TORRAGE_FILE_ERROR',     14 );
	
	define( 'TORRAGE_DEBUG', false ); // enable if you want backtrace logs
	
	// create folders if they do not exist
	if( !is_dir( $SETTINGS['savepath'] ) ) @mkdir( $SETTINGS['savepath'], 0755, true );
	if( !is_dir( $SETTINGS['synclists_path'] ) ) @mkdir( $SETTINGS['synclists_path'], 0755, true );
	
	// get alot of uploads.
	function add_tosyncfiles( $info_hash )
	{
		global $SETTINGS;
		
		// only append hashes if sync folder exists
		if( is_dir( $SETTINGS['synclists_path'] ) )
		{
			date_default_timezone_set( 'CET' );
			$day = date( 'Ymd' );
			$month = date( 'Ym' );
			
			// Open file and append infohash from this upload
			if( $SETTINGS['synclists_day'] )
				file_put_contents( $SETTINGS['synclists_path'] . "/$day.txt", "$info_hash\n", FILE_APPEND );
			if( $SETTINGS['synclists_month'] )
				file_put_contents( $SETTINGS['synclists_path'] . "/$month.txt", "$info_hash\n", FILE_APPEND );
		}
	}
	
	function handle_upload( $f )
	{
		global $SETTINGS;
		
		include_once dirname( __FILE__ ) . '/TEapi.inc.php';
		$torr = new Torrent();
		
		if( !$torr->load( file_get_contents( $f ) ) )
		{
			return TORRAGE_FILE_INVALID;
		}
		
		$hashtorr = create_hashtorr( $torr->getHash() );
		
		// Read tracker list from existsing torrents
		if( file_exists( $SETTINGS['savepath'] . $hashtorr . '.torrent' ) )
		{
			$existtorr = new Torrent();
			
			if( !$existtorr->load( gzdecode( file_get_contents( $SETTINGS['savepath'] . $hashtorr . '.torrent' ) ) ) )
			{
				$existtrackers = array();
			}
			else
			{
				$existtrackers = $existtorr->getTrackers();
			}
		}
		
		$tr_from = $torr->getTrackers();
		if( count( $existtrackers ) > 0 )
		{
			include_once dirname( __FILE__ ) . '/whitelist.inc.php';
		}
		else
		{
			$tr = $tr_from;
		}
		unset( $tr_from );
		
		if( is_array( $existtrackers ) )
		{
			foreach( $existtrackers as $a )
			{
				$tr[] = $a;
			}
		}
		unset( $a, $existtrackers );
		$trackers = array_unique( $tr );
		
		// Do tracker cleaning
		include_once dirname( __FILE__ ) . '/clean.inc.php';
		
		$torr->torrent->remove( 'comment.utf-8' );
		$torr->setComment( 'Torrent downloaded from torrent cache at http://torrage.com' );
		
		$torr->setTrackers( $trackers );
		$tdata = $torr->bencode();
		
		if( empty( $tdata ) )
		{
			return TORRAGE_FILE_ERROR;
		}
		
		$savefile = $SETTINGS['savepath'] . $hashtorr . '.torrent';
		
		@mkdir( dirname( $savefile ), 0777, 1 );
		file_put_contents( $savefile, gzencode( $tdata, 9 ) );
		
		if( $SETTINGS['synclists_enable'] )
			add_tosyncfiles( $torr->getHash() );
		
		return $torr->getHash();
	}
	
	function create_hashtorr( $ht )
	{
		$hashtorr = '';
		$o = 0;
		
		for( $i = 0; $i < 4; $i++ )
		{
			$hashtorr .= $ht[$o];
			$o++;
			if( $i % 2 == 1 )
				$hashtorr .= '/';
		}
		
		return $hashtorr .= substr( $ht, 4 );
	}
	
	function gzdecode( $data )
	{
		$g = tempnam( '/tmp', 'php-gz' );
		@file_put_contents( $g, $data );
		ob_start();
		readgzfile( $g );
		$d = ob_get_clean();
		unlink( $g );
		return $d;
	}
	
	function print_head()
	{
		global $SETTINGS;
		?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<title>Torrage - Torrent Storage Cache</title>
		<link rel="stylesheet" href="/style.css" type="text/css" media="screen" />
	</head>
	<body>
		<div id="canvas"><a href="/"><img src="/images/logo.jpg" border="0" alt="Torrage - Torrent Storage Cache" vspace="10" width="" height="" /></a>
		<?php
	}
	
	function print_foot()
	{
		?>
		</div>
	</body>
</html>
		<?php
	}
	
	function getProto()
	{
		return ( $_SERVER['SERVER_PORT'] == 443 ) ? 'https://' : 'http://';
	}
	
	if( TORRAGE_DEBUG === true )
	{
		set_error_handler( 'torrage_custom_error_handler' );
	}

	function torrage_custom_error_handler( $number, $string, $file, $line, $context )
	{
		// Honour what we set for error_reporting
		if( ( $number & error_reporting() ) == 0 ) return;
    	
		ob_start();
		debug_print_backtrace();
		$bt = ob_get_contents();
		ob_end_clean();
		
		echo <<< HTML
		<pre>
		Backtrace:
		{$bt}
		</pre>
HTML;
	}