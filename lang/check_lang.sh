#!/bin/bash
# $Id$
##
# Shell script to check that all language files are syncronized
# Catches duplicate/missing strings
#
# Robin Johnson <robbat2@users.sourceforge.net>
# August 9, 2002
##
MASTER="english-iso-8859-1.inc.php3"
TMPDIR="tmp-check"
FILEPAT="*.inc.php3"
STRINGSTRING='^[[:space:]]*\$[[:alnum:]_]*[[:blank:]]* ='

rm -rf $TMPDIR
mkdir -p $TMPDIR

#Build the list of variables in each file
#Note the special case to strip out allow_recoding
echo -e "Building data"
for f in $FILEPAT; 
do

    egrep "$STRINGSTRING" $f | \
    grep -v 'allow_recoding' | \
    cut -d= -f1 | cut -d'$' -f2 | \
    grep -Ev 'strEncto|strKanjiEncodConvert|strXkana' | \
    sort > $TMPDIR/$f
done;

#Build the diff files used for checking
#And if there are no differences, delete the empty files
echo -e "Comparing data"
for f in $FILEPAT; 
do
    diff -u $TMPDIR/$MASTER $TMPDIR/$f >$TMPDIR/$f.diff
    if [ ! $MASTER == $f ]; then
        if [ `wc -l $TMPDIR/$f.diff | cut -c-8|xargs` == "0" ] ;
        then
            rm -f $TMPDIR/$f.diff $TMPDIR/$f
        fi;
    fi;
done;

#build the nice difference table
echo -e "Differences"
diffstat -f 0 $TMPDIR/*.diff >$TMPDIR/diffstat 2>/dev/null
head -n $((`wc -l <$TMPDIR/diffstat` - 1)) $TMPDIR/diffstat > $TMPDIR/diffstat.res
echo -e "Dupe\tMiss\tFilename"
cat $TMPDIR/diffstat.res | \
while read filename sep change add plus sub minus edits exclaim; 
do 
    echo -e "$add\t$sub\t$filename"; 
done;

echo
echo "Dupe = Duplicate Variables"
echo "Miss = Missing Variables"
echo "For exact problem listings, look in the $TMPDIR/ directory"
echo "Please remember to remove '$TMPDIR/' once you are done"
