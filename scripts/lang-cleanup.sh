#!/bin/sh
#
# $Id$
# vim: expandtab sw=4 ts=4 sts=4:
#
# Script for removing language selection from phpMyAdmin

if [ $# -lt 1 ] ; then
    echo "Usage: lang-cleanup.sh type ..."
    echo "Type can be one of:"
    echo "  all-languages - nothing will be done"
    echo "  all-languages-utf-8-only - non utf-8 languages will be deleted"
    echo "  language - keeps utf-8 version of language"
    echo "  language-charset - keeps this exact language"
    echo
    echo "Types can be entered multiple times, all matched languages will be kept"
    exit 1
fi

# Construct expressions for find
match=""
for type in "$@" ; do
    case $type in
        all-languages)
            match="$match -and -false"
            ;;
        all-languages-utf-8-only)
            match="$match -and -not -name *-utf-8.inc.php"
            ;;
        *)
            if [ -f lang/$type-utf-8.inc.php ] ; then
                match="$match -and -not -name $type-utf-8.inc.php"
            elif [ -f lang/$type.inc.php ] ; then
                match="$match -and -not -name $type.inc.php"
            else
                echo "ERROR: $type seems to be wrong!"
                exit 2
            fi
            ;;
    esac
done

# Delete unvanted languages
find lang -name \*.inc.php $match -print0 | xargs -0r rm

# Cleanup libraries/select_lang.lib.php

# Find languages we have
langmatch="$(awk -F, \
    'BEGIN { pr = 1 } ; 
    /^\);/ { pr = 1 } ; 
    {if(!pr) print $2;}; 
    /^\$available_languages/ { pr = 0 };' \
    libraries/select_lang.lib.php \
    | tr -d \' \
    | while read lng ; do if [ -f lang/$lng.inc.php ] ; then echo $lng ; fi ; done \
    | tr '\n' '|' \
    | sed 's/|$//' \
    )"

# Prepare working copy
tmp=`mktemp libraries/select_lang.lib.php.XXXX`
cat libraries/select_lang.lib.php > $tmp

# Remove languages we don't have
awk -F, \
    'BEGIN { pr = 1 } ; 
    /^\);/ { pr = 1 } ; 
    {if(pr) print $0;}; 
    /'$langmatch'/ {if (!pr) print $0;};
    /^\$available_languages/ { pr = 0 };' \
    $tmp > libraries/select_lang.lib.php

# Final cleanup
rm -f $tmp

