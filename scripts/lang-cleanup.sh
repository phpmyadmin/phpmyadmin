#!/bin/sh
#
# vim: expandtab sw=4 ts=4 sts=4:
#
# Script for removing language selection from phpMyAdmin

# Do not run as CGI
if [ -n "$GATEWAY_INTERFACE" ] ; then
    echo 'Can not invoke as CGI!'
    exit 1
fi

if [ $# -lt 1 ] ; then
    echo "Usage: lang-cleanup.sh type"
    echo "Type can be one of:"
    echo "  all-languages - nothing will be done"
    echo "  source - nothing will be done"
    echo "  english - no translations will be kept"
    echo "  langcode - keeps language"
    echo
    echo "Languages can be specified multiple times"
    exit 1
fi

# Expression for find
match=""
for type in "$@" ; do
    case $type in
        all-languages|source)
            exit 0
            ;;
        english)
            rm -rf po
            rm -rf locale
            exit 0
            ;;
        *)
            match="$match -and -not -name $type.po -and -not -path locale/$type/LC_MESSAGES/phpmyadmin.mo"
            ;;
    esac
done

# Delete unvanted languages
find po locale -type f $match -print0 | xargs -0r rm

# Delete empty directories
rmdir --ignore-fail-on-non-empty locale/*/*
rmdir --ignore-fail-on-non-empty locale/*
