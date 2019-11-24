#!/bin/bash

date

# TZ
TZ='Australia/Adelaide'; export TZ
date

_NOW_=$(date +%Y%m%d%H%M%S)

cd /home/allaroundadmin/

mysqldump 'i1100450_jos1' > mysql_backup/i1100450_jos1.sql
# option -c to keep original file
gzip -c mysql_backup/i1100450_jos1.sql > mysql_backup/i1100450_jos1.sql.gz

git add .
git status
git commit -m "$_NOW_"
git push -u origin master

cd -

