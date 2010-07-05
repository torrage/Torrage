#
# This is our varnishd config file, save it as /etc/varnish.vlc
#
# Change the IP to your IP and start varnish with:
# ulimit -n 131072 ; varnishd -a yourip:80 -f /etc/varnish.vlc -s file,/tmp/varnish,10G
#


#
# change the IP below to your backend "storage"-servers IP
#
backend default {
.host = "192.168.12.100";
.port = "80";
}

sub vcl_recv {
    set req.grace = 1d;
    if (req.url ~ "\.(png|gif|jpg|swf|css|js|torrent)$") {
        return (lookup);
    }
    if (req.url ~ "^/torrents") {
        return (lookup);
    }
    if (req.request != "GET" && req.request != "HEAD") {
        /* We only deal with GET and HEAD by default */
        return (pass);
    }
    if (req.http.Authorization || req.http.Cookie) {
        /* Not cacheable by default */
        return (lookup);
    }
    return (lookup);
}

sub vcl_fetch {
    set req.grace = 1d;
    if (req.url ~ "\.(png|gif|jpg|swf|css|js|torrent)$") {
        unset obj.http.set-cookie;
        if (obj.status != 404) {
            set obj.ttl = 1h;
        }
    }
    if (req.url ~ "^/torrents") {
        unset obj.http.set-cookie;
        if (obj.status != 404) {
            set obj.ttl = 1h;
        }
    }
    return (deliver);
}
