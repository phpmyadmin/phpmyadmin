#!/bin/sh
# $Id$
##
# Shell script to make each language file neat and tidy
#
# Robin Johnson <robbat2@users.sourceforge.net>
# August 9, 2002
##

function sortlang()
{
    f=$1
    targetdir=tmp-$f
    mkdir -p $targetdir

    TRANSLATIONSTRING='//.*translate.*$'
    STRINGSTRING='^[[:space:]]*\$str[[:alnum:]_]*'
    WHITESPACE='^[[:blank:]]*$'
    STRINGORDER="A B C D E F G H I J K L M N O P Q R S T U V W X Y Z"

    echo -n "Extracting:"
    echo -n " head"
    egrep -i -v $TRANSLATIONSTRING $f | \
    egrep -v $STRINGSTRING | \
    sed 's/?>//g;s/<?php//g'| \
    uniq >>$targetdir/head

    echo -n " strings"
    egrep -i -v $TRANSLATIONSTRING $f | \
    egrep $STRINGSTRING | \
    egrep -v $WHITESPACE >$targetdir/tmp-tosort

    echo -n " pending_translations"
    egrep -i $TRANSLATIONSTRING $f | \
    uniq >$targetdir/tmp-translate

    echo -e -n "\nBuilding:"
    echo -n " strings"
    for i in $STRINGORDER;
    do
        echo
        egrep '^\$str'$i'[[:alpha:]]*' $targetdir/tmp-tosort | sort 
    done | \
    uniq >>$targetdir/sort

    echo -n " pending_translations"
    egrep -v $STRINGSTRING $targetdir/tmp-translate | uniq > $targetdir/translate
    echo >> $targetdir/translate
    for i in $STRINGORDER;
    do
        echo
        egrep '^\$str'$i'[[:alpha:]]*' $targetdir/tmp-translate | sort
    done | \
    uniq >>$targetdir/translate

    echo -e "\nAssembling final"
    f=$f$2
    echo "<?php" >$f
    cat $targetdir/head $targetdir/sort $targetdir/translate >>$f
    echo "?>" >>$f

    rm -rf $targetdir
}

echo "-------------------------------------------------------------------"
for i in $1; 
do
    echo "Sorting $i"
    sortlang $i
    echo "-------------------------------------------------------------------"
done;
