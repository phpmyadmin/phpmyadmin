#!/bin/bash
# $Id$
#
# Shell script that adds a message to all message files (Lem9)
#
# Example:  add_message.sh '$strNewMessage' 'new message contents'
#

if [ $# -ne 2 ] ; then
    echo "usage: add_message.sh '\$strNewMessage' 'new message contents'"
    exit 1
fi

for file in *.inc.php
do
    echo $file " "
    grep -v '?>' ${file} > ${file}.new
    case $file in
        english*)
            echo "$1 = '"$2"';" >> ${file}.new
            ;;
        *)
            echo "$1 = '"$2"';  //to translate" >> ${file}.new
            ;;
    esac
    echo "?>" >> ${file}.new
    rm $file
    mv ${file}.new $file
done
./sort_lang.sh english*
echo " "
echo "Message added to all message files (including english)"
