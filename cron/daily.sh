#!/bin/bash
# cron setup
# @daily /var/data/torrage.com/www/cron/daily.sh

cd /var/data/torrage.com/www/sync/

yesterday="$(date -d "yesterday" +%Y%m%d)"

sort $yesterday.txt | uniq | sort > $yesterday.sort;
rm $yesterday.txt;
mv $yesterday.sort $yesterday.txt;