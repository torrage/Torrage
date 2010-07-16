<?php

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
			$this->torrent = BEncode::decode( $data );
			
			if( $this->torrent->get_type() == 'error' )
			{
				$this->error = $this->torrent->get_plain();
				return false;
			}
			else
			{
				if( $this->torrent->get_type() != 'dictionary' )
				{
					$this->error = 'The file was not a valid torrent file.';
					return false;
				}
			}
			
			$this->info = $this->torrent->get_value( 'info' );
			if( !$this->info )
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
			return $this->torrent->get_value( 'comment' ) ? $this->torrent->get_value( 'comment' )->get_plain() : null;
		}
	
		// Get creatuion date
		// return - php date
		public function getCreationDate()
		{
			return $this->torrent->get_value( 'creation date' ) ? $this->torrent->get_value( 'creation date' )->get_plain() : null;
		}
	
		// Get created by
		// return - string
		public function getCreatedBy()
		{
			return $this->torrent->get_value( 'created by' ) ? $this->torrent->get_value( 'created by' )->get_plain() : null;
		}
	
		// Get name
		// return - filename (single file torrent)
		//          directory (multi-file torrent)
		// see also - getFiles()
		public function getName()
		{
			return $this->info->get_value( 'name' )->get_plain();
		}
	
		// Get piece length
		// return - int
		public function getPieceLength()
		{
			return $this->info->get_value( 'piece length' )->get_plain();
		}
	
		// Get pieces
		// return - raw binary of peice hashes
		public function getPieces()
		{
			return $this->info->get_value( 'pieces' )->get_plain();
		}
	
		// Get public flag
		// return - -1 public, implicit
		//           0 public, explicit
		//           1 private
		public function getPrivate()
		{
			if( $this->info->get_value( 'private' ) )
			{
				return $this->info->get_value( 'private' )->get_plain();
			}
			return -1;
		}
	
		// Get a list of files
		// return - array of Torrent_File
		public function getFiles()
		{
			// Load files
			$filelist = array();
			$length = $this->info->get_value( 'length' );
			
			if( $length )
			{
				$file = new Torrent_File();
				$file->name = $this->info->get_value( 'name' )->get_plain();
				$file->length = $this->info->get_value( 'length' )->get_plain();
				array_push( $filelist, $file );
			}
			else
			{
				if( $this->info->get_value( 'files' ) )
				{
					$files = $this->info->get_value( 'files' )->get_plain();
					while( list( $key, $value ) = each( $files ) )
					{
						$file = new Torrent_File();
						
						$path = $value->get_value( 'path' )->get_plain();
						while( list( $key, $value2 ) = each( $path ) )
						{
							$file->name .= '/' . $value2->get_plain();
						}
						$file->name = ltrim( $file->name, '/' );
						$file->length = $value->get_value( 'length' )->get_plain();
						
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
			
			if( $this->torrent->get_value( 'announce-list' ) )
			{
				$trackers = $this->torrent->get_value( 'announce-list' )->get_plain();
				while( list( $key, $value ) = each( $trackers ) )
				{
					if( is_array( $value->get_plain() ) )
					{
						while( list( $key, $value2 ) = each( $value ) )
						{
							while( list( $key, $value3 ) = each( $value2 ) )
							{
								array_push( $trackerlist, $value3->get_plain() );
							}
						}
					}
					else
					{
						array_push( $trackerlist, $value->get_plain() );
					}
				}
			}
			else
			{
				if( $this->torrent->get_value( 'announce' ) )
				{
					array_push( $trackerlist, $this->torrent->get_value( 'announce' )->get_plain() );
				}
			}
			
			return $trackerlist;
		}
	
		// Helper function to make adding a tracker easier
		// $tracker_url - string
		public function addTracker( $tracker_url )
		{
			$trackers = $this->getTrackers();
			$trackers[] = $tracker_url;
			$this->setTrackers( $trackers );
		}
	
		// Replace the current trackers with the supplied list
		// $trackerlist - array of strings
		public function setTrackers( $trackerlist )
		{
			if( count( $trackerlist ) >= 1 )
			{
				$this->torrent->remove( 'announce-list' );
				$string = new BEncode_String( $trackerlist[0] );
				$this->torrent->set( 'announce', $string );
			}
			
			if( count( $trackerlist ) > 1 )
			{
				$list = new BEncode_List();
				
				while( list( $key, $value ) = each( $trackerlist ) )
				{
					$list2 = new BEncode_List();
					$string = new BEncode_String( $value );
					$list2->add( $string );
					$list->add( $list2 );
				}
				
				$this->torrent->set( 'announce-list', $list );
			}
		}
	
		// Update the list of files
		// $filelist - array of Torrent_File
		public function setFiles( $filelist )
		{
			// Load files
			$length = $this->info->get_value( 'length' );
			
			if( $length )
			{
				$filelist[0] = str_replace( '\\', '/', $filelist[0] );
				$string = new BEncode_String( $filelist[0] );
				$this->info->set( 'name', $string );
			}
			else
			{
				if( $this->info->get_value( 'files' ) )
				{
					$files = $this->info->get_value( 'files' )->get_plain();
					for( $i = 0; $i < count( $files ); ++$i )
					{
						$file_parts = split( '/', $filelist[$i] );
						$path = new BEncode_List();
						foreach( $file_parts as $part )
						{
							$string = new BEncode_String( $part );
							$path->add( $string );
						}
						$files[$i]->set( 'path', $path );
					}
				}
			}
		}
	
		// Set the comment field
		// $value - string
		public function setComment( $value )
		{
			$type = 'comment';
			$key = $this->torrent->get_value( $type );
			if( $value == '' )
			{
				$this->torrent->remove( $type );
			}
			elseif( $key )
			{
				$key->set( $value );
			}
			else
			{
				$string = new BEncode_String( $value );
				$this->torrent->set( $type, $string );
			}
		}
	
		// Set the created by field
		// $value - string
		public function setCreatedBy( $value )
		{
			$type = 'created by';
			$key = $this->torrent->get_value( $type );
			if( $value == '' )
			{
				$this->torrent->remove( $type );
			}
			elseif( $key )
			{
				$key->set( $value );
			}
			else
			{
				$string = new BEncode_String( $value );
				$this->torrent->set( $type, $string );
			}
		}
	
		// Set the creation date
		// $value - php date
		public function setCreationDate( $value )
		{
			$type = 'creation date';
			$key = $this->torrent->get_value( $type );
			if( $value == '' )
			{
				$this->torrent->remove( $type );
			}
			elseif( $key )
			{
				$key->set( $value );
			}
			else
			{
				$int = new BEncode_Int( $value );
				$this->torrent->set( $type, $int );
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
				$int = new BEncode_Int( $value );
				$this->info->set( 'private', $int );
			}
		}
	
		// Bencode the torrent
		public function bencode()
		{
			return $this->torrent->encode();
		}
	
		// Return the torrent's hash
		public function getHash()
		{
			return strtoupper( sha1( $this->info->encode() ) );
		}
	}
	
	// Simple class to encapsulate filename and length
	class Torrent_File
	{
		public $name;
		public $length;
	}
	
	class BEncode
	{
		public static function &decode( &$raw, &$offset = 0 )
		{
			if( $offset >= strlen( $raw ) )
			{
				return new BEncode_Error( 'Decoder exceeded max length.' );
			}
			
			$char = $raw[$offset];
			switch( $char )
			{
				case 'i':
					$int = new BEncode_Int();
					$int->decode( $raw, $offset );
					return $int;
				case 'd':
					$dict = new BEncode_Dictionary();
					
					if( $check = $dict->decode( $raw, $offset ) )
					{
						return $check;
					}
					return $dict;
				case 'l':
					$list = new BEncode_List();
					$list->decode( $raw, $offset );
					return $list;
				case 'e':
					$end = new BEncode_End();
					return $end;
				case '0':
				case is_numeric( $char ):
					$str = new BEncode_String();
					$str->decode( $raw, $offset );
					return $str;
				default:
					return new BEncode_Error( "Decoder encountered unknown char '$char' at offset $offset." );
			}
		}
	}
	
	class BEncode_End
	{
		public function get_type()
		{
			return 'end';
		}
	}
	
	class BEncode_Error
	{
		public $error;
	
		public function BEncode_Error( $error )
		{
			$this->error = $error;
		}
	
		public function get_plain()
		{
			return $this->error;
		}
	
		public function get_type()
		{
			return 'error';
		}
	}
	
	class BEncode_Int
	{
		public $value;
	
		public function BEncode_Int( $value = null )
		{
			$this->value = $value;
		}
	
		public function decode( &$raw, &$offset )
		{
			$end = strpos( $raw, 'e', $offset );
			$this->value = substr( $raw, ++$offset, $end - $offset );
			$offset += ( $end - $offset );
		}
	
		public function get_plain()
		{
			return $this->value;
		}
	
		public function get_type()
		{
			return 'int';
		}
	
		public function encode()
		{
			return "i{$this->value}e";
		}
	
		public function set( $value )
		{
			$this->value = $value;
		}
	}
	
	class BEncode_Dictionary
	{
		public $value = array();
	
		public function decode( &$raw, &$offset )
		{
			$dictionary = array();
			
			while( true )
			{
				$name = BEncode::decode( $raw, ++$offset );
				
				if( $name->get_type() == 'end' )
				{
					break;
				}
				else
				{
					if( $name->get_type() == 'error' )
					{
						return $name;
					}
					else
					{ 
						if( $name->get_type() != 'string' )
						{
							return new BEncode_Error( 'Key name in dictionary was not a string.' );
						}
					}
				}
				
				$value = BEncode::decode( $raw, ++$offset );
				
				if( $value->get_type() == 'error' )
				{
					return $value;
				}
				
				$dictionary[$name->get_plain()] = $value;
			}
			
			$this->value = $dictionary;
		}
	
		public function get_value( $key )
		{
			if( isset( $this->value[$key] ) )
			{
				return $this->value[$key];
			}
			else
			{
				return null;
			}
		}
	
		public function encode()
		{
			$this->sort();
			
			$encoded = 'd';
			while( list( $key, $value ) = each( $this->value ) )
			{
				$bstr = new BEncode_String();
				$bstr->set( $key );
				$encoded .= $bstr->encode();
				$encoded .= $value->encode();
			}
			$encoded .= 'e';
			return $encoded;
		}
	
		public function get_type()
		{
			return 'dictionary';
		}
	
		public function remove( $key )
		{
			unset( $this->value[$key] );
		}
	
		public function set( $key, $value )
		{
			$this->value[$key] = $value;
		}
	
		public function sort()
		{
			ksort( $this->value );
		}
	
		public function count()
		{
			return count( $this->value );
		}
	}
	
	class BEncode_List
	{
		public $value = array();
	
		public function add( $bval )
		{
			array_push( $this->value, $bval );
		}
	
		public function decode( &$raw, &$offset )
		{
			$list = array();
			
			while( true )
			{
				$value = BEncode::decode( $raw, ++$offset );
				
				if( $value->get_type() == 'end' )
				{
					break;
				}
				else
				{
					if( $value->get_type() == 'error' )
					{
						return $value;
					}
				}
				array_push( $list, $value );
			}
			
			$this->value = $list;
		}
	
		public function encode()
		{
			$encoded = 'l';
			
			for( $i = 0; $i < count( $this->value ); ++$i )
			{
				$encoded .= $this->value[$i]->encode();
			}
			$encoded .= 'e';
			return $encoded;
		}
	
		public function get_plain()
		{
			return $this->value;
		}
	
		public function get_type()
		{
			return 'list';
		}
	}
	
	class BEncode_String
	{
		public $value;
	
		public function BEncode_String( $value = null )
		{
			$this->value = $value;
		}
	
		public function decode( &$raw, &$offset )
		{
			$end = strpos( $raw, ':', $offset );
			$len = substr( $raw, $offset, $end - $offset );
			$offset += ( $len + ( $end - $offset ) );
			$end++;
			$this->value = substr( $raw, $end, $len );
		}
	
		public function get_plain()
		{
			return $this->value;
		}
	
		public function get_type()
		{
			return 'string';
		}
	
		public function encode()
		{
			$len = strlen( $this->value );
			return "$len:{$this->value}";
		}
	
		public function set( $value )
		{
			$this->value = $value;
		}
	}
