#!/bin/sh

# $Id$ 

# original php3->phtml converter by Pavel Piankov <pashah@spb.sitek.net>
# modified by Tobias Ratschiller to allow any file extension
# part of the phpMyAdmin distribution <http://phpwizard.net/phpMyAdmin>

if [$1 eq ""]
then
  echo "Missing first parameter (extension to be changed)"
  echo "Usage: extchg <extension to change from> <extension to change to>"
  exit
fi

if [$2 eq ""]
then
  echo "Missing second parameter (extension to change to)"
  echo "Usage: extchg <extension to change from> <extension to change to>"
  exit
fi


if test ! -s *.$1
then
  echo 'Nothing to convert! Try to copy the script to the directory where you have files to convert.' 
  exit
fi


if  test ! -d bak
then
	mkdir bak
else
	echo 'Directory bak is already there - will try to use it to backup your files...'
fi

for i in *.$1
	 do 
	 sed -e 's/'$1'/'$2'/g' $i > `ls $i|sed -e 's/'$1'/'$2'/g'`
	 mv $i bak/$i
	done;

