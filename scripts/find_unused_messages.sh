#!/bin/sh

export LANG=C
set -e

# Simple script to find unused message strings by Michal Čihař

tmp1=`mktemp`
tmp2=`mktemp`
grep -o '^\$\<str[A-Z][a-zA-Z0-9_]*\>' lang/english-utf-8.inc.php \
    | tr -d '$' \
    | grep -Ev '^str(Transformation_|ShowStatus)' | sort -u > $tmp1
grep -ho '\<str[A-Z][a-zA-Z0-9_]*\>' `find . -type f -a -name '*.php' -a -not -path '*/lang/*'` \
    | grep -Ev '^str(Transformation_|ShowStatus|Lang|Locale|SetupForm_|SetupFormset_)' | sort -u > $tmp2

echo Please note that you need to check results of this script, it doesn\'t
echo understand PHP, it only tries to find what looks like message name.

echo
echo Used messages not present in english language file:
echo '(this contains generated messages and composed message names, so these'
echo 'are not necessary a errors!)'
echo

# filter out known false positives
diff $tmp1 $tmp2 | awk '/^>/ {print $2}' | grep -Ev '(strEncto|strXkana|strDBLink|strPrivDesc|strPrivDescProcess|strTableListOptions|strMissingParameter|strAttribute|strDoSelectAll)'

echo
echo Not used messages present in english language file:
echo

diff $tmp1 $tmp2 | awk '/^</ {print $2}' | grep -Ev '(strSetup.*_(desc|name)|strSetupForm_|strSetupFormset_)'


rm -f $tmp1 $tmp2
