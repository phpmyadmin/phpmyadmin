#!/bin/bash
#
# Shell script that adds a message file to all message files 
# adding "//to translate" on each line 
#
# Example:  add_message_file.sh  xxx
#
for file in *.inc.php3
do
        echo $file " "
        grep -v '?>' ${file} > ${file}.new
	sed 's/;/;\/\/to translate/' <$1 >> ${file}.new
        echo "?>" >> ${file}.new
        rm $file
        mv ${file}.new $file
done
echo " "
echo "This script also added the new messages to english.inc.php3"
