#
# Change paths and ip's to match your setup, save the file as /etc/stunnel.conf
# start stunnel with the following command:
#
# ulimit -n 131072 ; stunnel /etc/stunnel.conf
#

debug=0

[http]
CAfile=/var/data/torrage.com/ssl/cafile.ca
cert=/var/data/torrage.com/ssl/torrage_com.pem
connect=212.63.212.212:80
accept=212.63.212.212:443
