<?php
	include_once dirname( __FILE__ ) . '/inc/main.inc.php';
	
	print_head();
?>
<h1>API</h1>
<p>People that are interested in using our service for automated caching of their newly created .torrent files or caching massive amounts of older files, can do so by using one our APIs.<br />
This page contains some documentation on the APIs but also some example code in different languages. If you have some code for any additional language we do not cover, please give us some working example code over e-mail.</p>
<p><i>All files will be cached at <?=getProto();?><?=$SETTINGS['torrstoredns'];?>/torrent/&lt;infoHash&gt;.torrent</i></p>
<p><b>Note:</b> HEX values A-F must be in uppercase in torrent URL's</p>

<h2>SOAP API</h2>
<p>The SOAP API is probably the most easy to use in a modern scripting/programming language. The WSDL offers one simply function; <b>cacheTorrent()</b>. The function returns the info hash of the torrent on success, or a three digit error code if there was an error.<br />
The SOAP WSDL is located at <a href="/api/torrage.wsdl"><?=getProto();?><?=$SETTINGS['torrstoredns'];?>/api/torrage.wsdl</a>.</p>
<h3>PHP</h3>
<p>Below is example code in PHP to cache "my.torrent". <i>You would need to compile PHP --with-soap</i>.</p>
<pre>
&lt;?php
        $client = new SoapClient( '<?=getProto();?><?=$SETTINGS['torrstoredns'];?>/api/torrage.wsdl' );
        $infoHash = $client->cacheTorrent( base64_encode( file_get_contents( 'my.torrent' ) ) );
?&gt;
</pre>
<h3>PERL</h3>
<p>Perl code to cache "my.torrent". <i>Requires SOAP::Lite (libsoap-lite-perl in debian)</i></p>
<pre>#!/usr/bin/perl

use MIME::Base64 ();
use SOAP::Lite ();

open( FILE, 'my.torrent' ) or die "$!";
while( read( FILE, $buf, 60*57 ) ) { $tor .= MIME::Base64::encode( $buf ); }
close( FILE );

$infoHash = SOAP::Lite-&gt;service( '<?=getProto();?><?=$SETTINGS['torrstoredns'];?>/api/torrage.wsdl' )-&gt;cacheTorrent( $tor );

print $infoHash;
</pre>
<h2>HTTP POST</h2>
<p>If you don't have support for SOAP there is a normal HTTP POST interface. Here we show some example code for that as well.</p>

<h3>PHP</h3>
<p>Below is example code to cache "my.torrent". This feature requires the <i>pecl_http</i> extension.</p>
<pre>
&lt;?php
	$files = array(
	    array(
	        'name' => 'torrent',			// Don't change
	        'type' => 'application/x-bittorrent',
	        'file' => 'my.torrent'			// Full path for file to upload
	    )
	);

	$http_resp = http_post_fields( '<?=getProto();?><?=$SETTINGS['torrstoredns'];?>/autoupload.php', array(), $files );
	$tmp = explode( "\r\n", $http_resp );
	$infoHash = substr( $tmp[count( $tmp ) - 1], 0, 40 );
	unset( $tmp, $http_resp, $files );
?&gt;
</pre>
<h2>libTorrage</h2>
<p>We've also made a small client in C that you can build if you want to script on command line or use the code in your own C project.<br />
The file is available to download here: <a href="/source/libtorrage-0.1.1.tar.gz">libtorrage-0.1.1.tar.gz</a>.</p>

<h3>Sample usage</h3>
<pre>
./torragecache <?=getProto();?><?=$SETTINGS['torrstoredns'];?>/autoupload.php my.torrent
</pre>
<?php
	print_foot();
