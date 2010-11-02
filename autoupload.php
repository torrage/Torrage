<?php
	include_once dirname( __FILE__ ) . '/inc/main.inc.php';
	
	// to allow for big files
	set_time_limit( 0 );
	ini_set( 'upload_max_filesize', 6 * 1048576 );
	error_reporting( 0 );
	
	upload_error_handler( handle_upload( $_FILES['torrent']['tmp_name'] ) );
	
	function upload_error_handler( $error )
	{
		global $SETTINGS;
		
		// only do error check test if the value we get back is purely a number
		if( is_numeric( $error ) && strlen( $error ) == 2 )
		{
			switch( $error )
			{
				case TORRAGE_FILE_NOT_FOUND:
					header( 'X-Torrage-Error-Msg: File empty.' );
					die( "Error $error: File not found.\n" );
				case TORRAGE_FILE_EMPTY:
					header( 'X-Torrage-Error-Msg: File empty.' );
					die( "Error $error: File empty.\n" );
				case TORRAGE_FILE_INVALID:
					header( 'X-Torrage-Error-Msg: Broken torrent file, please recreate it and try again.' );
					die( "Error $error: Broken torrent file, please recreate it and try again.\n" );
				case TORRAGE_FILE_UNKNOWN:
					header( 'X-Torrage-Error-Msg: Error ' . $error );
					die( "Error $error" );
				case TORRAGE_FILE_ERROR:
					header( 'X-Torrage-Error-Msg: Something didn\'t work, please try again later!' );
					die( "Error $error: Something didn't work, please try again later!\n" );
				default:
					header( 'X-Torrage-Error-Msg: UNKNOWN ERROR' );
					die( "Error $error: Unknown error occurred\n" );
			}
		}
		elseif( empty( $error ) )
		{
			header( 'X-Torrage-Error-Msg: Empty hash.' );
			die( "Error: No info hash returned.\n" );
		}
		
		header( "X-Torrage-Infohash: $error" );
		echo "$error\n";
		exit( 0 );
	}
