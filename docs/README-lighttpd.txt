# Special config for using the torrage system with lighttpd
# You will of course also need to add your php handler.

#
#
#
#
#
# Lighttpd 1.5
#
#
#
#
#

server.modules = (
  "mod_rewrite",
  "mod_setenv",
)

server.error-handler-404    = "/404.php"

url.rewrite-once = (
  "/torrent/([0-9A-F]{2,2})([0-9A-F]{2,2})([0-9A-F]{36,36}).*" => "/t/$1/$2/$3.torrent",
)

$HTTP["url"] =~ "\.torrent" {
  setenv.add-response-header = ( "Content-Encoding" => "gzip" )
  mimetype.assign = ( ".torrent" => "application/x-bittorrent" )
}
$HTTP["url"] =~ "\.ghtml" {
  setenv.add-response-header = ( "Content-Encoding" => "gzip" )
}

#
#
#
#
#
# Lighttpd 2.0
#
#
#
#
#

module_load (
	"mod_status",
	"mod_fastcgi",
	"mod_dirlist",
	"mod_rewrite",
	"mod_redirect",
	"mod_expire",
	"mod_deflate",
	"mod_cache_disk_etag",
);

do_deflate {
	if req.is_handled {
		if response.header["Content-Type"] =~ "^(.*/javascript|text/.*)(;|$)" {
			deflate;
		}
	}
}

php {
	fastcgi "unix:/var/run/lighttpd/php-fastcgi.socket";
	do_deflate;
}

pregzipped {
	header.add "Content-Encoding" => "gzip";
	header.add "Vary" => "Accept-Encoding";
}

if req.host =~ "^(.+\.)?torrage.com" {
	rewrite "/torrent/([0-9A-F]{2})([0-9A-F]{2})([0-9A-F]{36}).*" => "/t/$1/$2/$3.torrent";
	docroot "/var/data/torrage.com/www";
	index ( "index.php" );
	if req.path == "/sync/" { dirlist; do_deflate; }
	if !physical.exists { rewrite "/404.php"; docroot "/var/data/torrage.com/www"; php; set_status 404; }
	else if req.path =$ ".torrent" { pregzipped; }
	else if req.path =$ ".php" { php; }
	if req.path =$ ".ghtml" { pregzipped; }
}