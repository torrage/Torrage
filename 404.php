<?php
	include_once dirname( __FILE__ ) . '/inc/settings.inc.php';
	
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
			<li><b>OK:</b> <?=getProto();?><?=$SETTINGS['torrstoredns'];?>/torrent/<b>640FE84C613C17F663551D218689A64E8AEBEABE</b>.torrent</li>
			<br />
			<li><b>ERROR:</b> <?=getProto();?><?=$SETTINGS['torrstoredns'];?>/torrent/<b><?= strtolower('640FE84C613C17F663551D218689A64E8AEBEABE'); ?></b>.torrent</li>
		</ul>
	</p>
	<br />
	<br />
<?php
	print_foot();

