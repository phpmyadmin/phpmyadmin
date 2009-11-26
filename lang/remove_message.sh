#!/bin/bash
# $Id$
#
# Shell script that removes a message from all message files (Lem9)
# it checks for the message, followed by a space
#
# Example:  remove_message.sh 'strMessageToRemove' 
#

if [ $# -ne 1 ] ; then
    echo "usage: remove_message.sh 'strMessageToRemove'"
    exit 1
fi
    
for file in *.inc.php
do
    echo "lines before:" `wc -l $file`
    grep -v "$1 " ${file} > ${file}.new
    rm $file
    mv ${file}.new $file
    echo " lines after:" `wc -l $file`
done
echo " "
