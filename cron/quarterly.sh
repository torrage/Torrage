#!/bin/bash
# cron setup
# m		h	dom		mon					dow		command
# 0		0	1		JAN,APR,JUL,OCT		*		/var/data/torrage.com/www/cron/quarterly.sh

cd /var/data/torrage.com/www/

lastquarter="$(date -d "5 days ago" +%Y%m)"

find t/ -name "*.torrent" | cut -c 3- | sed -e "s/\///g" -e "s/\.torrent//g" > sync/all${lastquarter}.txt

sort sync/all${lastquarter}.txt | uniq | sort > sync/all${lastquarter}.sort;
rm sync/all${lastquarter}.txt;
mv sync/all${lastquarter}.sort sync/all${lastquarter}.txt;
bzip2 sync/all${lastquarter}.txt;