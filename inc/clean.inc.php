<?php

	$basicsanity = true;
	$basicsanity_strict = true;
	$removeiponly = true;
	$add_udp_url = true;
	$rem_comment = true;
	
	// Trackers to remove
	include_once dirname( __FILE__ ) . '/blacklist.inc.php';
	
	// Trackers known to support UDP
	include_once dirname( __FILE__ ) . '/udptrackers.inc.php';
	
	/* Decoding loop - Start */
	reset( $trackers );
	if( is_array( $trackers ) && count( $trackers ) > 0 )
	{
		foreach( $trackers as $id => $tracker )
		{
			// Updated tracker url's  -  tracker{1-5}.istole.it:60500 to tracker.istole.it:80
			if( stristr( $tracker, '.istole.it:60500/' ) )
			{
				$trackers[$id] = 'udp://tracker.istole.it:80/announce';
				continue;
			}
			
			// Updated tracker url's  -  tracker.sladinki007.net to tracker.istole.it:80
			if( stristr( $tracker, 'sladinki007.net' ) )
			{
				$trackers[$id] = 'udp://tracker.istole.it:80/announce';
				continue;
			}
			
			// Updated tracker url's  -  eztv trackers to tracker.istole.it:80
			if( stristr( $tracker, 'eztv' ) )
			{
				$trackers[$id] = 'udp://tracker.istole.it:80/announce';
				continue;
			}
			
			// Updated tracker url's  -  denis trackers to tracker.ccc.de:80
			if( stristr( $tracker, 'denis.stalker.h3q.com' ) )
			{
				$trackers[$id] = 'udp://tracker.ccc.de:80/announce';
				continue;
			}
			
			// Miss spelled annonce => announce
			if( stristr( $tracker, 'annonce' ) )
			{
				$trackers[$id] = str_replace( 'annonce', 'announce', trim( urldecode( $tracker ) ) );
				continue;
			}
			
			// Detect typo /announce
			$l = $p = '';
			if( ( !stristr( $tracker, 'dht://' ) ) && ( stristr( $tracker, '/an' ) ) )
			{
				unset( $l, $p );
				$have_php = 0;
				$l = strlen( $tracker );
				$p = stripos( $tracker, '/an', 15 );
				if( stristr( substr( $tracker, $p ), '.php' ) )
					$have_php = 1;
				// Make sure we are at the end.
				if( ( $l - $p < 16 ) && ( $have_php ) )
				{
					$trackers[$id] = substr( $tracker, 0, $p ) . '/announce.php';
					continue;
				}
				elseif( $l - $p < 12 )
				{
					$trackers[$id] = substr( $tracker, 0, $p ) . '/announce';
					continue;
				}
			}
			
			$trackers[$id] = trim( urldecode( $tracker ) );
		}
	}
	/* Decoding loop - End */
	
	/* Sanity loop - Start */
	$tr_add = array();
	reset( $trackers );
	foreach( $trackers as $id => $tracker )
	{
		// Remove :80 from http URL's
		if( ( stristr( $tracker, 'http://' ) ) && ( strstr( $tracker, ':80/' ) ) )
		{
			unset( $trackers[$id] );
			$tr_add[] = str_replace( ':80/', '/', $tracker );
		}
		
		// Add :80 to udp:// url's with no port
		if( ( stristr( $tracker, 'udp://' ) ) && ( !strstr( substr( $tracker, 6 ), ':' ) ) )
		{
			unset( $trackers[$id] );
			$tr_add[] = substr( $tracker, 0, strpos( $tracker, '/', 6 ) ) . ':80' . substr( $tracker, strpos( $tracker, '/', 6 ) ) . '\n';
		}
	}
	$trackers = merge_trackers( $trackers, $tr_add );
	unset( $tr_add );
	/* Sanity loop - End */
	
	/* Removal loop - Start */
	reset( $trackers );
	foreach( $trackers as $id => $tracker )
	{
		reset( $rem_patterns );
		
		// Remove trackers specified in $rem_patterns.
		foreach( $rem_patterns as $rem_pattern )
		{
			if( stristr( $tracker, $rem_pattern ) )
			{
				unset( $trackers[$id] );
				continue;
			}
		}
		
		// Get host part
		unset( $s, $e );
		if( !stristr( $tracker, 'dht://' ) )
		{
			$s = stripos( $tracker, 'p://' ) + 4;
			$e = stripos( $tracker, ':', $s );
			if( $e === false )
				$e = strpos( $tracker, '/', $s );
			$trhost = substr( $tracker, $s, $e - $s );
		}
		
		// Basic sanity - remove failing tracker URL's
		if( $basicsanity )
		{
			// Remove too short urls, 'udp://aa.aa/announce' beeing the minimum for non dht
			if( ( !stristr( $tracker, 'dht://' ) ) && ( strlen( $tracker ) < 20 ) )
			{
				unset( $trackers[$id] );
				continue;
			}
			
			// A dht:// url is always 59 bytes 
			if( ( stristr( $tracker, 'dht://' ) ) && ( strlen( $tracker ) != 59 ) )
			{
				unset( $trackers[$id] );
				continue;
			}
			
			// Require a /announce or /tracker in the url
			if( $basicsanity_strict )
			{
				if( ( !stristr( $tracker, 'dht://' ) ) && ( !( stristr( $tracker, '/announce' ) ) || ( stristr( substr( $tracker, 8 ), '/tracker' ) ) ) )
				{
					/*
					 *  TODO: Add check in whitelist to find matching tracker
					 */
					unset( $trackers[$id] );
					continue;
				}
			}
			
			// Check that we have atleast 3 slashes, not including a / as last character
			if( ( !stristr( $tracker, 'dht://' ) ) && ( substr_count( substr( $tracker, 0, strlen( $tracker ) - 1 ), '/' ) < 3 ) )
			{
				unset( $trackers[$id] );
				continue;
			}
			
			// url having www. but no /announce
			if( ( !stristr( $tracker, 'dht://' ) ) && ( ( stristr( $tracker, 'www.' ) ) && ( !stristr( $tracker, '/announce' ) ) ) )
			{
				/*
				 *  TODO: Add check in whitelist to find matching tracker
				 */
				unset( $trackers[$id] );
				continue;
			}
			
			// Remove trackers not starting with udp:// http:// dht://
			if( !( ( stripos( ' ' . $tracker, 'udp://' ) == 1 ) || ( stripos( ' ' . $tracker, 'dht://' ) == 1 ) || ( stripos( ' ' . $tracker, 'http://' ) == 1 ) ) )
			{
				unset( $trackers[$id] );
				continue;
			}
			
			// Check for double protocol prefixes.
			if( stripos( $tracker, '://', 5 ) !== FALSE )
			{
				unset( $trackers[$id] );
				continue;
			}
			
			// Check for illegal characters.
			if( !is_valid_url( $tracker ) )
			{
				unset( $trackers[$id] );
				continue;
			}
			
			// Remove url's with two .. (dots) in a row..
			if( strstr( $trhost, '..' ) )
			{
				unset( $trackers[$id] );
				continue;
			}
			
			// Remove url's where any of the last 3 charrs is a number
			if( ( is_numeric( substr( $trhost, strlen( $trhost ) - 1, 1 ) ) ) || ( is_numeric( substr( $trhost, strlen( $trhost ) - 2, 1 ) ) ) || ( is_numeric( substr( $trhost, strlen( $trhost ) - 3, 1 ) ) ) )
			{
				unset( $trackers[$id] );
				continue;
			}
			
			// Remove url's that have no . (dot) in the host part
			if( !strstr( $trhost, '.' ) )
			{
				unset( $trackers[$id] );
				continue;
			}
		}
		
		// Removal of IP-only trackers
		if( $removeiponly )
		{
			if( ip2long( $trhost ) !== FALSE )
			{
				unset( $trackers[$id] );
			}
		}
	}
	/* Removal loop - End */
	
	/* UDP tracker Loop - Start */
	$tr_add = array();
	reset( $trackers );
	foreach( $trackers as $id => $tracker )
	{
		// Detection of several large trackers known to support UDP, and add the udp:// tracker url.
		if( $add_udp_url )
		{
			reset( $udp_patterns );
			foreach( $udp_patterns as $udp_pattern )
			{
				unset( $s, $e );
				if( stristr( $tracker, $udp_pattern ) )
				{
					unset( $trackers[$id] );
					$s = stripos( $tracker, 'p://' );
					$e = stripos( $tracker, ':', $s + 4 );
					if( $e === false )
					{
						$e = strpos( $tracker, '/', $s + 4 );
						$port = 80;
						$p = $e;
					}
					else
					{
						$p = strpos( $tracker, '/', $e );
						$port = substr( $tracker, $e + 1, $p - $e - 1 );
					}
					
					// Add - http://
					if( $port == 80 )
						$tr_add[] = 'htt' . substr( $tracker, $s, $e - $s ) . substr( $tracker, $p );
					else
						$tr_add[] = 'htt' . substr( $tracker, $s, $e - $s ) . ":$port" . substr( $tracker, $p );
						
					// Add - udp://
					$tr_add[] = 'ud' . substr( $tracker, $s, $e - $s ) . ":$port" . substr( $tracker, $p );
					
					/* I HAVE A BUG HERE */
				}
			}
		}
	}
	$trackers = merge_trackers( $trackers, $tr_add );
	/* UDP tracker Loop - END */
	
	// set our default trackers
	if( count( $SETTINGS['trackers'] ) > 0 )
	{
		foreach( $SETTINGS['trackers'] as $tracker )
		{
			array_push( $trackers, $tracker );
		}
	}
	$trackers = array_reverse( $trackers );
	
	/* Functions - Start */
	function merge_trackers( $tr, $tr_add )
	{
		if( is_array( $tr_add ) )
		{
			foreach( $tr_add as $a )
			{
				$tr[] = $a;
			}
		}
		unset( $a, $tr_add );
		return array_unique( $tr );
	}
	
	function is_valid_char( $c )
	{
		// Valid chars 37-38, 43, 45-58, 61, 63, 65-90, 95, 97-122
		$char = ord( $c );
		if( $char == 37 )
			return true;
		if( $char == 38 )
			return true;
		if( $char == 43 )
			return true;
		if( ( $char >= 45 ) && ( $char <= 58 ) )
			return true;
		if( $char == 61 )
			return true;
		if( $char == 63 )
			return true;
		if( ( $char >= 65 ) && ( $char <= 90 ) )
			return true;
		if( $char == 95 )
			return true;
		if( ( $char >= 97 ) && ( $char <= 122 ) )
			return true;
		
		return false;
	}
	
	function is_valid_url( $url )
	{
		for( $i = 0; $i < strlen( $url ); $i++ )
		{
			if( !is_valid_char( $url[$i] ) )
				return false;
		}
		return true;
	}
	/* Functions - End */
