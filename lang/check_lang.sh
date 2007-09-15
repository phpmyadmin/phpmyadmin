#!/bin/sh
# $Id$
##
# Shell script to check that all language files are syncronized
# Catches duplicate/missing strings
#
# Robin Johnson <robbat2@users.sourceforge.net>
# August 9, 2002
##

MASTER="english-utf-8.inc.php"
TMPDIR="tmp-check"
FILEPAT="*.inc.php"
STRINGMATCH='^[[:space:]]*\$[[:alnum:]_]+[[:blank:]]+='
IGNOREMATCH='strEncto|strKanjiEncodConvert|strXkana|allow_recoding|doc_lang'

if [ "`which diffstat`" = "" ] ; then
    echo 'You need diffstat to use this!'
    exit 1
fi

rm -rf $TMPDIR
mkdir -p $TMPDIR

# Build the list of variables in each file
echo "Building data"
for f in $FILEPAT;
do
    awk "/$STRINGMATCH/ && ! /$IGNOREMATCH/ { print \$1 }" $f | sort > $TMPDIR/$f
done


# Build the diff files used for checking
# And if there are no differences, delete the empty files
echo "Comparing data"
for f in $FILEPAT;
do
    if [ ! $MASTER = $f ]; then
        if diff -u $TMPDIR/$MASTER $TMPDIR/$f >$TMPDIR/$f.diff ; then
            rm -f $TMPDIR/$f.diff $TMPDIR/$f
        fi
    fi
done

# Cleanup
rm -f $TMPDIR/$MASTER

# Build the nice difference table
echo "Differences"
diffstat -f 0 $TMPDIR/*.diff >$TMPDIR/diffstat 2>/dev/null
echo "Dupe	Miss	Filename"
head -n -1 $TMPDIR/diffstat | \
while read filename sep change add plus sub minus edits exclaim; 
do 
    echo "$add	$sub	$filename"; 
done

echo
echo "Dupe = Duplicate Variables"
echo "Miss = Missing Variables"
echo "For exact problem listings, look in the $TMPDIR/ directory"
echo "Please remember to remove '$TMPDIR/' once you are done"
