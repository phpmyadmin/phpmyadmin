#!/bin/sh
# $Id$
##
# Shell script to produce counts of just how out of date the translations are!
##
# Accepts optional parameters of a list of files to produce counts from
# Defaults to using all files
##
# Written by Robin Johnson <robbat2 at users.sourceforge.net>
##
srcfilelist=${@}
destfilelist=""
translationmatch='//to translate'
suffixtoremove='.inc.php'
added=0
if [ -z "${srcfilelist}" ]; then
  srcfilelist="*.inc.php"
  added=1
fi;
for i in ${srcfilelist}; do 
  if [ ! -e ${i} ]; then
    $i=`eval ${i}*php3`
  fi
  if [ -e ${i} ]; then
    destfilelist="${destfilelist} ${i}"
  fi;
done;
destfilelist=`echo ${destfilelist} | xargs -n1 | egrep '.inc.php$'` 
if [ ! -z "${destfilelist}" ]; then
  grep -c -- "${translationmatch}" ${destfilelist} | sort -t':' -n +1  | sed -e "s/${suffixtoremove}//g" | xargs -n1 | egrep -v ':0$' 
fi;
