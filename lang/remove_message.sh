#!/bin/bash
# $Id$
#
# Shell script that removes a message from all message files (Lem9)
# it checks for the message, followed by a space
#
# Example:  remove_message.sh 'strMessageToRemove' 
#
for file in *.inc.php3
do
    echo "lines before:" `wc -l $file`
    grep -v "$1 " ${file} > ${file}.new
    rm $file
    mv ${file}.new $file
    echo " lines after:" `wc -l $file`
done
echo " "
