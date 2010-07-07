#!/bin/bash
# cron setup
# @monthly /var/data/torrage.com/www/cron/monthly.sh

cd /var/data/torrage.com/www/sync/

lastmonth="$(date -d "5 days ago" +%Y%m)"

sort ${lastmonth}.txt ${lastmonth}??.txt | uniq | sort > ${lastmonth}.sort;
rm ${lastmonth}.txt;
mv ${lastmonth}.sort $lastmonth.txt;

rm ${lastmonth}??.txt;