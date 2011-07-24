<?php

	// some torrent files are large (>4MB) allow for some processing space
	ini_set( 'memory_limit', '256M' );

	class Torrent
	{
		// public class members
		// public $torrent;
		public $info;

		// Public error message, $error is set if load() returns false
		public $error;

		// Load torrent file data
		// $data - raw torrent file contents
		public function load( &$data )
		{
			if( empty( $data ) )
			{
				$this->error = 'Error loading torrent file.';
				return false;
			}

			try
			{
				$this->torrent = new BEncode();
				$this->torrent->decode( $data );
			}
			catch( Exception $e )
			{
				$this->error = $e->getMessage();
				return false;
			}

			$this->info = $this->torrent->get( 'info' );
			if( empty( $this->info ) )
			{
				$this->error = 'Could not find info dictionary.';
				return false;
			}

			return true;
		}

		// Get comment
		// return - string
		public function getComment()
		{
			return $this->torrent->get( 'comment' ) ? $this->torrent->get( 'comment' ) : null;
		}

		// Get creatuion date
		// return - php date
		public function getCreationDate()
		{
			return $this->torrent->get( 'creation date' ) ? $this->torrent->get( 'creation date' ) : null;
		}

		// Get created by
		// return - string
		public function getCreatedBy()
		{
			return $this->torrent->get( 'created by' ) ? $this->torrent->get( 'created by' ) : null;
		}

		// Get name
		// return - filename (single file torrent)
		//          directory (multi-file torrent)
		// see also - getFiles()
		public function getName()
		{
			return $this->info->get( 'name' );
		}

		// Get piece length
		// return - int
		public function getPieceLength()
		{
			return $this->info->get( 'piece length' );
		}

		// Get pieces
		// return - raw binary of peice hashes
		public function getPieces()
		{
			return $this->info->get( 'pieces' );
		}

		// Get public flag
		// return - -1 public, implicit
		//           0 public, explicit
		//           1 private
		public function getPrivate()
		{
			if( $this->info->get( 'private' ) )
			{
				return $this->info->get( 'private' );
			}
			return -1;
		}

		// Get a list of files
		// return - array of Torrent_File
		public function getFiles()
		{
			// Load files
			$filelist = array();
			$length = $this->info->get( 'length' );

			if( $length )
			{
				$file = new Torrent_File();
				$file->name = $this->info->get( 'name' );
				$file->length = $this->info->get( 'length' );
				array_push( $filelist, $file );
			}
			else
			{
				if( $this->info->get( 'files' ) )
				{
					$files = $this->info->get( 'files' );
					while( list( $key, $value ) = each( $files ) )
					{
						$file = new Torrent_File();

						$path = $value->get( 'path' );
						while( list( $key, $value2 ) = each( $path ) )
						{
							$file->name .= '/' . $value2;
						}
						$file->name = ltrim( $file->name, '/' );
						$file->length = $value->get( 'length' );

						array_push( $filelist, $file );
					}
				}
			}

			return $filelist;
		}

		// Get a list of trackers
		// return - array of strings
		public function getTrackers()
		{
			// Load tracker list
			$trackerlist = array();
			$trackers = $this->torrent->get( 'announce-list' );

			if( !empty( $trackers ) )
			{
				while( list( $key, $value ) = each( $trackers ) )
				{
					if( is_array( $value ) )
					{
						while( list( $key, $value2 ) = each( $value ) )
						{
							if( is_array( $value2 ) )
							{
								while( list( $key, $value3 ) = each( $value2 ) )
								{
									array_push( $trackerlist, array( $value3 ) );
								}
							}
							else
							{
								array_push( $trackerlist, array( $value2 ) );
							}
						}
					}
					else
					{
						array_push( $trackerlist, array( $value ) );
					}
				}
			}
			else
			{
				if( $this->torrent->get( 'announce' ) )
				{
					array_push( $trackerlist, array( $this->torrent->get( 'announce' ) ) );
				}
			}

			return $trackerlist;
		}

		// Helper function to make adding a tracker easier
		// $tracker_url - string
		public function addTracker( $tracker_url )
		{
			$trackers = $this->getTrackers();
			array_push( $trackers, array( $tracker_url ) );
			$this->setTrackers( $trackers );
		}

		// Replace the current trackers with the supplied list
		// $trackerlist - array of strings
		public function setTrackers( $trackerlist )
		{
			if( count( $trackerlist ) > 0 )
			{
				$this->torrent->remove( 'announce-list' );
				$this->torrent->set( 'announce', $trackerlist[0][0] );
			}

			if( count( $trackerlist ) > 1 )
			{
				$this->torrent->set( 'announce-list', $trackerlist );
			}
		}

		// Update the list of files
		// $filelist - array of Torrent_File
		public function setFiles( $filelist )
		{
			// Load files
			$length = $this->info->get( 'length' );

			if( $length )
			{
				$filelist[0] = str_replace( '\\', '/', $filelist[0] );
				$this->info->set( 'name', $filelist[0] );
			}
			else
			{
				$files = $this->info->get( 'files' );
				if( !empty( $files ) )
				{
					for( $i = 0; $i < count( $files ); ++$i )
					{
						$file_parts = split( '/', $filelist[$i] );
						$files[$i]->set( 'path', $file_parts );
					}
				}
			}
		}

		// Set the comment field
		// $value - string
		public function setComment( $value )
		{
			$type = 'comment';

			if( $value == '' )
			{
				$this->torrent->remove( $type );
			}
			else
			{
				$this->torrent->set( $type, $value );
			}
		}

		// Set the created by field
		// $value - string
		public function setCreatedBy( $value )
		{
			$type = 'created by';

			if( $value == '' )
			{
				$this->torrent->remove( $type );
			}
			else
			{
				$this->torrent->set( $type, $value );
			}
		}

		// Set the creation date
		// $value - php date
		public function setCreationDate( $value )
		{
			$type = 'creation date';

			if( $value == '' )
			{
				$this->torrent->remove( $type );
			}
			else
			{
				$this->torrent->set( $type, $value );
			}
		}

		// Change the public flag
		// $value - -1 public, implicit
		//           0 public, explicit
		//           1 private
		public function setPrivate( $value )
		{
			if( $value == -1 )
			{
				$this->info->remove( 'private' );
			}
			else
			{
				$this->info->set( 'private', $value );
			}
		}

		// Bencode the torrent
		public function bencode()
		{
			return $this->torrent->encode( null );
		}

		// Return the torrent's hash
		public function getHash()
		{
			return strtoupper( sha1( $this->torrent->encode( $this->info ) ) );
		}
	}

	// Simple class to encapsulate filename and length
	class Torrent_File
	{
		public $name;
		public $length;
	}

	/**
	 * 
	 *
	 **/	
	class BEncode
	{
		public $__data;

		function get( $key )
		{
		    return $this->___get( $this->__data, $key );
		}

		public function remove( $key )
		{
			$this->___remove( $this->__data, $key );
		}

		public function set( $key, $value )
		{
			if( $this->___set( $this->__data, $key, $value ) === false )
			{
				$this->__data[$key] = $value;
			}
		}

		function ___get( $array, $matchkey )
		{
			if( is_array( $array ) && count( $array ) > 0 )
			{
				foreach( $array as $key => $val )
				{
					if( $key === $matchkey )
					{
						return $val;
					}
					else if( is_array( $val ) )
					{
						$return = $this->___get( $val, $matchkey );
						if( $return != null )
						{
							return $return;
						}
					}
				}
			}

			return null;
		}

		function ___remove( &$array, $matchkey )
		{
			if( is_array( $array ) && count( $array ) > 0 )
			{
				foreach( $array as $key => &$value )
				{
					if( $key === $matchkey )
					{ 
						unset( $array[$key] );
					}
					else 
					{
						if( is_array( $value ) )
						{
							$this->___remove( $value, $matchkey );
						}
					}
				}
			}
		}

		function ___set( &$array, $matchkey, $newvalue )
		{
			if( is_array( $array ) && count( $array ) > 0 )
			{
				foreach( $array as $key => &$value )
				{
					if( $key === $matchkey )
					{ 
						$array[$key] = $newvalue;
						return true;
					}
					else 
					{
						if( is_array( $value ) )
						{
							return $this->___set( $value, $matchkey, $newvalue );
						}
					}
				}
			}
			return false;
		}

		public function sort()
		{
			ksort( $this->__data );
		}

		public function count()
		{
			return count( $this->__data );
		}

		public function decode( $data, $usegmp = true, $strict = false )
		{
			$stack = array();
			$offset = 0;
			$len = strlen( $data );

			// type: 0: nothing, 1: string, 2: integer, 3: list, 4: dict
			$parenttype = 0;
			$parent = null;
			$key = null;
			$value = null;

			while( $offset < $len )
			{
				$c = $data[$offset];
				$value = null;
				if( $c >= '0' && $c <= '9' )
				{
					$colon = strpos( $data, ':', $offset + 1 );

					if( FALSE === $colon )
					{
						throw new Exception( "Couldn't find ':' in encoded string at position $offset" );
					}

					$slen = substr( $data, $offset, $colon - $offset );

					if( !ctype_digit( $slen ) || ( $slen != '0' && $slen[0] == '0' ) )
					{
						throw new Exception( "Couldn't parse string length '$slen' at position $offset" );
					}

					$colon++;

					if( $colon + $slen > $len )
					{
						throw new Exception( "Unexpected end of bencoded data in string at position $offset" );
					}

					$offset = $colon + $slen;
					$value = substr( $data, $colon, $slen );
				}
				elseif( $c === 'i' )
				{
					if( $parenttype === 4 && null === $key )
					{
						throw new Exception( "Expected string as key in dict, not integer at position $offset" );
					}

					$end = strpos( $data, 'e', $offset + 1 );

					if( FALSE === $end )
					{
						throw new Exception( "Couldn't find end of integer at position $offset" );
					}

					$offset++;
					$value = substr( $data, $offset, $end - $offset );

					if( $value === "" || "-0" === $value )
					{
						throw new Exception( "Invalid integer '$value' at position $offset" );
					}

					$cv = $value;
					if( $cv[0] === '-' )
						$cv = substr( $cv, 1 );

					if( !ctype_digit( $cv ) || ( $cv !== '0' && $cv[0] === '0' ) )
					{
						throw new Exception( "Invalid integer '$value' at position $offset" );
					}

					if( $usegmp )
					{
						if( is_int( 0 + $value ) )
						{
							$value = 0 + $value;
						}
						else
						{
							$value = gmp_init( $value );
						}
					}
					else
					{
						if( is_int( 0 + $value ) )
							$value = 0 + $value;
					}
					$offset = $end + 1;
				}
				elseif( $c === 'l' )
				{
					if( $parenttype === 4 && null === $key )
					{
						throw new Exception( "Expected string as key in dict, not list at position $offset" );
					}

					$offset++;

					if( 0 != $parenttype )
						array_push( $stack, array( $parent, $parenttype, $key ) );

					$parent = array();
					$parenttype = 3;
					$key = null;
					continue;
				}
				elseif( $c === 'd' )
				{
					if( $parenttype === 4 && null === $key )
					{
						throw new Exception( "Expected string as key in dict, not dict at position $offset" );
					}

					$offset++;

					if( 0 !== $parenttype )
						array_push( $stack, array( $parent, $parenttype, $key ) );

					$parent = array();
					$parenttype = 4;
					$key = null;
					continue;
				}
				elseif( $c === 'e' )
				{
					$offset++;
					if( $parenttype === 3 )
					{
						$value = $parent;
						$t = array_pop( $stack );

						if( $t )
						{
							list( $parent, $parenttype, $key ) = $t;
						}
						else
						{
							$parenttype = 0;
						}
					}
					elseif( $parenttype === 4 )
					{
						if( null !== $key )
						{
							throw new Exception( "Expected value for dict, got end of dict at position $offset" );
						}

						$value = $parent;
						$t = array_pop( $stack );

						if( $t )
						{
							list( $parent, $parenttype, $key ) = $t;
						}
						else
						{
							$parenttype = 0;
						}
					}
					else
					{
						throw new Exception( "Unexpected 'e' - no container at position $offset" );
					}
				}
				else
				{
					throw new Exception( "Unexpected character '$c' at position $offset" );
				}

				if( $parenttype === 4 )
				{
					if( null === $key )
					{
						$key = $value;
					}
					else
					{
						$parent[$key] = $value;
						$key = null;
					}
				}
				elseif( $parenttype === 3 )
				{
					array_push( $parent, $value );
				}
				else
				{
					if( $strict && $offset < $len )
					{
						throw new Exception( "Trash at end of bencoded data at position $offset" );
					}

					$this->__data = $value;
					return true;
				}
			}

			throw new Exception( "Unexpected end of bencoded data" );
		}

		function encode( $var = null, $autonumbers = false )
		{
			if( $var === null )
			{
				$var = &$this->__data;
			}

			if( is_string( $var ) )
			{
				if( $autonumbers && $var !== "" && $var !== "-" && $var !== "-0" )
				{
					$v = $var;
					if( $v[0] === '-' )
						$v = substr( $v, 1 );

					if( ctype_digit( $v ) && ( $v[0] !== '0' || $v === '0' ) )
					{
						return "i" . $var . "e";
					}
				}
				return strlen( $var ) . ":" . $var;
			}
			elseif( is_int( $var ) )
			{
				return "i" . $var . "e";
			}
			elseif( is_resource( $var ) && get_resource_type( $var ) == "GMP integer" )
			{
				return "i" . gmp_strval( $var ) . "e";
			}
			elseif( is_array( $var ) )
			{
				ksort( $var );
				if( is_string( key( $var ) ) )
				{
					$text = 'd';
					foreach( $var as $key => $val )
					{
						$text .= strlen( $key ) . ':' . $key . $this->encode( $val, $autonumbers );
					}
					return $text . 'e';
				}
				else
				{
					$text = 'l';
					foreach( $var as $val )
					{
						$text .= $this->encode( $val, $autonumbers );
					}
					return $text . 'e';
				}
			}
			elseif( $var instanceof BEncodeStatic )
			{
				return $var->code;
			}
			else
			{
				// try as string
				return strlen( $var ) . ":" . $var;
			}
		}
	}

	// If you need to send an empty dict use @new BEncodeStatic("de")@
	// an empty array will be sent as empty list ("le")
	class BEncodeStatic
	{
		var $code;

		function __construct( $code )
		{
			$this->code = $code;
		}
	}