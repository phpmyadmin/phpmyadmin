#!/bin/sh
#
# inno2pma
#
# insert InnoDB foreign keys into phpmyadmin relation table
# by Ernie Hershey 3/15/2003
#
# THIS IS AN EXPERIMENTAL SCRIPT - PLEASE DO NOT USE THIS
# IF YOU DON'T UNDERSTAND WHAT IT IS DOING!
#
# This was posted on the Open Discussion forum by
# ehershey. The idea is interesting, however we should
# implement it in PHP.
#
# In future versions of phpMyAdmin the use of a proprietary
# storage of relational tables will be discarded in favor of
# the InnoDB relations (or in addition to). Support of InnoDB
# relations is on our ToDo-List.
#
# requires :
# mysql client tools
# standard unix shell tools
# special PHPMyAdmin relation table setup
#
#
 
# config
 
database="triohost2"
#table="domains"
relationdb="phpmyadmin"
relationtable="PMA_relation"
 
# end config
 
mysqldump --no-data $database $table | egrep "^CREATE|^ FOREIGN" | while read line
do
 line=`echo "$line"|sed 's/^ *//g`
 first_token=`echo "$line" | cut -f1 -d" "`
 case $first_token in
    CREATE)
      table_name=`echo "$line"|sed 's/CREATE TABLE \(.*\) (/\1/'`
      echo "DELETE FROM $relationtable WHERE master_db='$database' AND master_table='$table_name'" | mysql $relationdb
      ;;
    FOREIGN)
      localcolumn=`echo "$line" | cut -f2 -d\\\``
      foreigntablefull=`echo "$line" | cut -f4 -d\\\``
      foreigndb=`echo $foreigntablefull | cut -f1 -d.`
      foreigntable=`echo $foreigntablefull | cut -f2 -d.`
      foreigncolumn=`echo "$line" | cut -f6 -d\\\``

      echo processing foreign key on column $database.$table_name.$localcolumn to $foreigndb.$foreigntable.$foreigncolumn >&2
      echo "INSERT INTO $relationtable VALUES ('$database','$table_name','$localcolumn','$foreigndb','$foreigntable','$foreigncolumn');" | mysql $relationdb
      ;;
 esac
done