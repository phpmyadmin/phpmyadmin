#!/bin/sh
# $Id$
##
# Shell script to make each language file neat and tidy
#
# Robin Johnson <robbat2@users.sourceforge.net>
# August 9, 2002
##

LC_COLLATE=C

specialsort()
{
    in=$1
    out=$2

    STRINGORDER="A B C D E F G H I J K L M N O P Q R S T U V W X Y Z"

    for i in $STRINGORDER;
    do
        egrep '^\$str'$i $in | sort >> $out
        echo >> $out
    done
}

sortlang()
{
    f=$1
    targetdir=tmp-$f
    mkdir -p $targetdir

    TRANSLATIONSTRING='//.*translate.*$'
    STRINGSTRING='^\$str[[:alnum:]_]+'
    WHITESPACE='^[[:blank:]]*$'
    CVSID='/\* \$Id$ \*/'

    echo -n "Extracting:"
    echo -n " head"
    egrep -i -v $TRANSLATIONSTRING $f | \
    egrep -v "$STRINGSTRING|$CVSID|\?>|<\?php" >> $targetdir/head

    echo -n " cvs"
    egrep "$CVSID" $f >>$targetdir/cvs

    echo -n " strings"
    egrep -i -v "$WHITESPACE|$TRANSLATIONSTRING" $f | \
    egrep $STRINGSTRING > $targetdir/tmp-tosort

    echo -n " pending_translations"
    egrep -i "$STRINGSTRING.*$TRANSLATIONSTRING" $f > $targetdir/tmp-translate
    echo

    echo -n "Building:"
    echo -n " strings"
    specialsort $targetdir/tmp-tosort $targetdir/sort

    echo -n " pending_translations"
    if [ -s $targetdir/tmp-translate ] ; then
        echo '// To translate:' > $targetdir/translate
        specialsort $targetdir/tmp-translate $targetdir/translate
    else
        echo -n > $targetdir/translate
    fi
    echo

    echo "Assembling final"
    echo "<?php" > $f
    cat $targetdir/cvs $targetdir/head $targetdir/sort $targetdir/translate \
    | uniq >> $f
    echo "?>" >> $f

    rm -rf $targetdir
}

echo "-------------------------------------------------------------------"
for i in "$@";
do
    if [ ! -f $i ] ; then
        echo "$i is not a file, skipping"
    else
        echo "Sorting $i"
        sortlang $i
    fi
    echo "-------------------------------------------------------------------"
done;
