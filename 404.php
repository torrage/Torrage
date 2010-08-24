<?php
	include_once dirname( __FILE__ ) . '/inc/settings.inc.php';
	
	$url = $_SERVER['REQUEST_URI'];
	if( preg_match( '/\/torrent\/([0-9A-F]{2})([0-9A-F]{2})([0-9A-F]{36}).+/i', $url, $match ) !== false )
	{
		$hash = strtoupper( $match[1] . $match[2] . $match[3] );
		$url_hash = strtoupper( $match[1] . '/' . $match[2] . '/' . $match[3] );
		if( file_exists( $SETTINGS['savepath'] . $url_hash . '.torrent' ) )
		{
			header( 'Location: ' . getProto() . $SETTINGS['torrstoredns'] . '/torrent/' . $hash . '.torrent', true, 302 );
			die();
		}
		$original_hash = $match[1] . $match[2] . $match[3];
		$correct_hash = strtoupper( $original_hash );
	}
	
	print_head();
?>
	<h2>Torrage is a free service for caching torrent files online.</h2>
	<p>404 - File not found.</p>
	<p>The torrent you are looking for can not be found on the system, if you want to, you can <a href="/">add it to the cache</a>.</p>
	<p>
		<b>Note:</b> HEX values A-F must be in uppercase in torrent URL's
		<br />
		<br /><i>Example:</i>
		<ul>
			<li><b>OK:</b> <?=getProto();?><?=$SETTINGS['torrstoredns'];?>/torrent/<b><?=$correct_hash;?></b>.torrent</li>
			<br />
			<li><b>ERROR:</b> <?=getProto();?><?=$SETTINGS['torrstoredns'];?>/torrent/<b><?=$original_hash;?></b>.torrent</li>
		</ul>
	</p>
	<br />
	<br />
<?php
	print_foot();

