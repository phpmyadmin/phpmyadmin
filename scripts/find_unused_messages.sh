#!/bin/sh

# Simple script to find unused message strings by Michal Čihař

phpfiles=`find . -type f -a -name '*.php' -a -not -path '*/lang/*'`

grep -o 'str[A-Z][a-zA-Z_]*' lang/english-iso-8859-1.inc.php \
    | grep -Ev '^str(Transformation_|ShowStatus)' \
    | while read x
        do
            echo "Checking for $x" >&2
            if [ `grep -r "\\<$x\\>" $phpfiles | wc -l` -eq 0 ]
            then
                echo $x
            fi
        done
