#!/bin/bash
# $Id$
#
# Shell script that adds a message file to all message files
# adding "//to translate" on each line
#
# Example:  add_message_file.sh  xxx
#
if [ $# -ne 1 ] ; then
    echo "usage: add_message_file.sh filename"
    exit 1
fi

for file in *.inc.php
do
    echo $file " "
    grep -v '?>' ${file} > ${file}.new
    case $file in
        english*)
            sed -n 's/\(.*\);/\1;/p' $1 >> ${file}.new
            ;;
        *)
            sed -n 's/\(.*\);/\1;  \/\/to translate/p' $1 >> ${file}.new
            ;;
    esac
    echo "?>" >> ${file}.new
    rm $file
    mv ${file}.new $file
done
./sort_lang.sh english*
echo " "
echo "Messages added to add message files (including english)"
