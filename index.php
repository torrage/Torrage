<?php
	include_once dirname( __FILE__ ) . '/inc/main.inc.php';
	
	print_head();
?>
	<h2>Torrage is a free service for caching torrent files online.</h2>
	
	You can not search or list torrent files that are stored here, you can only access them if you already know the info_hash value of the torrent you want to download.
	<br />The way to access torrents is simple, you just use the url <?=getProto();?><?=$SETTINGS['torrstoredns'];?>/torrent/<i>INFO_HASH_IN_HEX</i>.torrent (note HEX values A-F must be in uppercase)</p>
	<p><b>Example URL:</b><br /> <a href="/torrent/640FE84C613C17F663551D218689A64E8AEBEABE.torrent"><?=getProto();?><?=$SETTINGS['torrstoredns']; ?>/torrent/640FE84C613C17F663551D218689A64E8AEBEABE.torrent</a> (Slackware 12.2 DVD ISO)</p>
	<p>The torrent files are saved to disk in gzip format, that means you have to use a browser that understands the gzip transfer encoding.</p>
	
	<div id="uploadbox">
		<h2>Send .torrent to cache:</h2>
		<form enctype="multipart/form-data" method="post" action="upload.php">
			<input type="file" name="torrent"></input>&nbsp;&nbsp;<input type="submit" value="Cache!"></input>
		</form>
		<p>Just choose the torrent file you want to add to the cache and click "Cache!".
	</div>
	
	<h2>Removal</h2>
	<p>Torrents that have not been downloaded for a period of 6 months will automatically be removed from the system.</p>
	<h2>Legal</h2>
	<ul>
	<li>We <b>DO NOT</b> have or track any information about what type of content the torrents point to.</li>
	<li>We <b>DO NOT</b> have any type of search or listing system.</li>
	<li>We <b>DO NOT</b> run our own trackers. </li>
	<li>The original filename of the torrent is <b>NOT</b> saved.</li>
	<li>We <b>DO NOT</b> log any IP adresses of uploaders / downloaders.</li>
	<li>You can <b>ONLY</b> download torrents if you <b>ALREADY KNOW</b> the INFO_HASH of the torrent file.</li>
	<li>Torrent files are cached on disk in gzip format making it extremely time consuming to search for any data contained within the torrent files.</li>
	</ul>
	
	<h2>Donations</h2>
	<p>
		We do not have any income or advertising on the site, instead we get the money it takes to run the service from the general public and other sites
		that use our services for caching torrent files. If you like our service and want to contribute please contact donations@torrage.com
	</p>
	
	<h2>Setup your own torrent storage cache!</h2>
	<p>
		The source code of torrage can be found <a href="/source/torrage-0.4.tar.bz2">here (v0.4)</a>. Completly revised code!<br />
		You can also grab the source code via github here: <a href="http://github.com/torrage/Torrage" target="_blank">http://github.com/torrage/Torrage</a>
	</p>
	
	<h2>I want automation / an API!</h2>
	<p>Here's a list of our <a href="/automation.php">APIs</a>.</p>
<?php
	print_foot();
