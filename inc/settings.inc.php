<?php

	// Assign default timezone to stockholm, this is mainly
	// so sync script with multiple sites will obtain the same
	// date structure making syncing much easier.
	date_default_timezone_set( 'Europe/Stockholm' );

	/**
	 * Torrage configuration
	 * Only change sections that you know you have to modify
	 */
	$SETTINGS = array(
		// where .torrent's are stored
		'savepath' => dirname( __FILE__ ) . '/../t/',
	
		// list of trackers that will always exist in torrent
		'trackers' => array(
			'udp://tracker.openbittorrent.com:80/announce',
			'udp://tracker.publicbt.com:80/announce',
			'udp://tracker.istole.it:80/announce',
			'udp://tracker.ccc.de:80/announce',
		),
		
		// used for link generation
		'torrstoredns' => 'torrage.com',
		
		// sync configuration
		'sync' => array(
			'enabled' => true, // enable/disable sync files
			'day' => true, // sync daily
			'month' => true, // sync monthly
			'path' => dirname( __FILE__ ) . '/../sync', // sync storage path
			'tmppath' => dirname( __FILE__ ) . '/../tmp', // tmp path for sync script
		
			// list of mirror sites
			'mirrors' => array(
				array( 'domain' => 'torrage.com', 'active' => true ),
				array( 'domain' => 'zoink.it', 'active' => true ),
				array( 'domain' => 'torcache.com', 'active' => true ),
				array( 'domain' => 'torrage.ws', 'active' => true ),
			),
		),
	);
	
	// error defines
	define( 'TORRAGE_FILE_NOT_FOUND', 10 );
	define( 'TORRAGE_FILE_EMPTY',     11 );
	define( 'TORRAGE_FILE_INVALID',   12 );
	define( 'TORRAGE_FILE_UNKNOWN',   13 );
	define( 'TORRAGE_FILE_ERROR',     14 );
	
	define( 'TORRAGE_DEBUG', false ); // enable if you want backtrace logs
	
	define( 'TORRENT_IS_GZIP', 1 );
	define( 'TORRENT_IS_BZ2',  2 );
	
	// create folders if they do not exist
	if( !is_dir( $SETTINGS['savepath'] ) ) @mkdir( $SETTINGS['savepath'], 0755, true );
	if( !is_dir( $SETTINGS['sync']['path'] ) ) @mkdir( $SETTINGS['sync']['path'], 0755, true );
	
	// get alot of uploads.
	function add_tosyncfiles( $info_hash )
	{
		global $SETTINGS;
		
		// only append hashes if sync folder exists
		if( is_dir( $SETTINGS['sync']['path'] ) )
		{
			date_default_timezone_set( 'CET' );
			$day = date( 'Ymd' );
			$month = date( 'Ym' );
			
			// Open file and append infohash from this upload
			if( $SETTINGS['sync']['day'] )
				file_put_contents( $SETTINGS['sync']['path'] . "/$day.txt", "$info_hash\n", FILE_APPEND );
			if( $SETTINGS['sync']['month'] )
				file_put_contents( $SETTINGS['sync']['path'] . "/$month.txt", "$info_hash\n", FILE_APPEND );
		}
	}
	
	function check_if_compressed( $filename )
	{
		$s = file_get_contents( $filename );
		if( bin2hex( substr( $s, 0, 2 ) ) == '1f8b' ) { return TORRENT_IS_GZIP; }
		if( substr( $s, 0, 3 ) == 'BZh' ) { return TORRENT_IS_BZ2; }
		return false;
	}
		
	function __flattern_array( $trackers )
	{
		$__trackers = array();
		if( is_array( $trackers ) && count( $trackers ) > 0 )
		{
			foreach( $trackers as $tracker )
			{
				if( is_array( $tracker ) )
				{
					$temp = __flattern_array( $tracker );
					$__trackers = array_merge( $__trackers, $temp );
				}
				elseif( !empty( $tracker ) )
				{
					array_push( $__trackers, $tracker );
				}
			}
		}
		
		return $__trackers;
	}
	
	function handle_upload( $f )
	{
		global $SETTINGS;
		
		include_once dirname( __FILE__ ) . '/TEapi.inc.php';
		$torr = new Torrent();
		
		// test for possible gzip/bzip in torrent file
		$status = check_if_compressed( $f );
		if( $status > 0 )
		{
			switch( $status )
			{
				case TORRENT_IS_GZIP:
					// file is gzip, uncompress and resave
					$gz = gzopen( $f, 'rb' );
					$gzip = '';
					while( !feof( $gz ) )
						$gzip .= gzread( $gz, 4096 );
					gzclose( $gz );
					file_put_contents( $f, $gzip );
					unset( $gzip );
					break;
				case TORRENT_IS_BZ2:
					// file is bz2, uncompess and resave
					$bz = bzopen( $f, 'rb' );
					$bzip = '';
					while( !feof( $bz ) )
						$bzip .= bzread( $bz, 4096 );
					bzclose( $bz );
					file_put_contents( $f, $bzip );
					unset( $bzip );
					break;
			}
		}
		
		if( !$torr->load( file_get_contents( $f ) ) )
		{
			@unlink( $f ); // remove the temp file
			return TORRAGE_FILE_INVALID;
		}
		@unlink( $f ); // remove the temp file
		
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
				$existtrackers = __flattern_array( $existtorr->getTrackers() );
			}
		}
		
		$tr_from = __flattern_array( $torr->getTrackers() );
		
		$tr = array();
		if( !empty( $existtrackers ) && count( $existtrackers ) > 0 )
		{
			include_once dirname( __FILE__ ) . '/whitelist.inc.php';
		}
		else
		{
			$tr = $tr_from;
		}
		
		$tr = array_merge( $tr, $existtrackers );
		$trackers = array_unique( $tr );
		
		// Do tracker cleaning
		include_once dirname( __FILE__ ) . '/clean.inc.php';
		
		// store trackers into new array format for bencoding
		$__trackers = array();
		if( !empty( $trackers ) && is_array( $trackers ) )
		{
			foreach( $trackers as $a )
			{
				$__trackers[] = array( $a );
			}
		}
		
		unset( $existtrackers, $tr, $tr_from, $trackers );
		
		$torr->torrent->remove( 'comment.utf-8' );
		$torr->setComment( 'Torrent downloaded from torrent cache at ' . getProto() . $SETTINGS['torrstoredns'] );
		
		$torr->setTrackers( $__trackers );
		$tdata = $torr->bencode();
		
		if( empty( $tdata ) )
		{
			return TORRAGE_FILE_ERROR;
		}
		
		$savefile = $SETTINGS['savepath'] . $hashtorr . '.torrent';
		
		@mkdir( dirname( $savefile ), 0777, true );
		file_put_contents( $savefile, gzencode( $tdata, 9 ) );
		
		if( $SETTINGS['sync']['enabled'] )
			add_tosyncfiles( $torr->getHash() );
			
		// sync to any possible mirrors
		
		/**
		 * @todo:
		 * PUSH mechanism caused too much of a slowdown
		 * when lots of incoming hashes were occuring.
		 * Changing to a pull system. Left this here
		 * incase can come up with some kind of nicer
		 * system to push, maybe fork the process as not
		 * to slow down this.
		 **/
			
		/*
		if( count( $SETTINGS['sync']['mirrors'] ) > 0 )
		{
			$files = array(
				array(
					'name' => 'torrent',
					'type' => 'application/x-bittorrent',
					'file' => $savefile
				)
			);
			
			$options = array(
				'timeout' => 10,
				'connecttimeout' => 5,
				'dns_cache_timeout' => 60,
			);
			
			// iterate through mirrors
			foreach( $SETTINGS['sync']['mirrors'] as $mirror )
			{
				// upload to mirrors,
				// but if mirror is actually itself, or it comes from one of our mirrors,
				// ignore it
				if( $mirror['active'] === true && !( $_SERVER['SERVER_ADDR'] == $mirror['ip'] ) && !( $_SERVER['REMOTE_ADDR'] == $mirror['ip'] ) )
				{
					// upload to mirror
					http_post_fields( 'http://'.$mirror['domain'].'/autoupload.php', array(), $files, $options );
				}
			}
		}
		*/
		
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
		return ( @$_SERVER['SERVER_PORT'] == 443 ) ? 'https://' : 'http://';
	}
	
	function check_if_mirror_is_self( $mirror )
	{
		global $SETTINGS;
		
		$is_self = false;
		// check if config name is domain (required for cron scripts)
		// otherwise check if the host information is itself.
		if( $SETTINGS['torrstoredns'] == $mirror['domain'] || ( ( strtolower( @$_SERVER['HTTP_HOST'] ) == $mirror['domain'] . ':80' ) || ( strtolower( @$_SERVER['HTTP_HOST'] ) == $mirror['domain'] . ':443' ) || ( strtolower( @$_SERVER['HTTP_HOST'] ) == $mirror['domain'] ) ) )
		{
			$is_self = true;
		}
		
		return $is_self;
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