#!/bin/bash

_NOW_=$(date +%Y%m%d%H%M%S)

cd /home/allaroundadmin/

mysqldump 'i1100450_jos1' > mysql_backup/i1100450_jos1.sql

git add .
git status
git commit -m "$_NOW_"
git push -u origin master

cd -

