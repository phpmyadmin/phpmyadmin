#!/bin/sh
# $Id$
##
# Shell script that synchronises all translations in phpMyAdmin
##
# Any parameters (except --iconv/--recode) will be passed to grep to filter
# processed translation, for example: './sync_lang.sh czech' will process only
# czech translation, './sync_lang.sh -e czech -e english' will process czech
# and english translations.
##
# Written by Michal Cihar <nijel at users.sourceforge.net>
##
# Changes:
# 2005-12-08
#   * less verbose output to allow quick overview
# 2005-11-29
#   * hack for multibyte chars, so that \'; at the end will not fool PHP
# 2004-09-22
#   * default to iconv, as it doesn't break things as recode does
# 2004-09-03
#   * hack for hebrew
# 2003-11-18
#   * switch php3 -> php
# 2003-04-14
#   * convert only files that are needed to convert (checks mtime), --force to
#     avoid this checking
#   * get charset from filename when reading from file failed
#   * report failed translations at the end
# 2002-09-18
#   * now accepts parameters --iconv/--recode for specifying which convertor
#     to use
# 2002-08-13
#   * support for synchronisation only for selected language(s)
# 2002-07-18
#   * can exclude some languages from conversion
# 2002-07-17
#   * support for multiple convertors (recode added)
##

##
# convertor setup
##
# CONVERTOR_PARAMS is used for printf and it also receives two params: source
# and target charset
#

case "$1" in
    --iconv)
        echo Using iconv on user request
        CONVERTOR=iconv
        # the space on following is REQUIRED
        CONVERTOR_PARAMS=" -f %s -t %s"
        shift
        ;;
    --recode)
        echo Using recode on user request
        echo '(please use iconv for arabic)'
        CONVERTOR=recode
        CONVERTOR_PARAMS=" -f %s..%s"
        shift
        ;;
    *)
        echo Using iconv as default, force with --iconv/--recode
        CONVERTOR=iconv
        # the space on following is REQUIRED
        CONVERTOR_PARAMS=" -f %s -t %s"
        ;;
esac

if [ "$1" = "--force" ] ; then
    FORCE=1
    shift
else
    FORCE=0
fi


##
# names of translations to process
##
# Here should be listed all translations for which conversion should be done.
# The name is filename without inc.php.
#
BASE_TRANSLATIONS="afrikaans-iso-8859-1
albanian-iso-8859-1
arabic-windows-1256
azerbaijani-iso-8859-9
basque-iso-8859-1
belarusian_cyrillic-windows-1251
belarusian_latin-utf-8
bosnian-windows-1250
brazilian_portuguese-iso-8859-1
bulgarian-utf-8
catalan-iso-8859-1
chinese_traditional-utf-8
chinese_simplified-gb2312
croatian-utf-8
czech-utf-8
danish-iso-8859-1
dutch-iso-8859-1
english-iso-8859-1
estonian-iso-8859-1
finnish-iso-8859-1
french-iso-8859-1
galician-iso-8859-1
german-utf-8
greek-iso-8859-7
hebrew-iso-8859-8-i
hungarian-iso-8859-2
indonesian-iso-8859-1
italian-utf-8
japanese-utf-8
korean-utf-8
latvian-windows-1257
lithuanian-windows-1257
malay-iso-8859-1
macedonian_cyrillic-windows-1251
norwegian-iso-8859-1
persian-windows-1256
polish-iso-8859-2
portuguese-iso-8859-1
romanian-utf-8
russian-windows-1251
serbian_cyrillic-utf-8
serbian_latin-utf-8
slovenian-iso-8859-2
slovak-utf-8
spanish-utf-8
swedish-iso-8859-1
tatarish-iso-8859-9
thai-utf-8
turkish-utf-8
ukrainian-windows-1251"

##
# which translations should not be translated to utf-8
##
# List here any translation that should not be converted to utf-8. The name is
# same as above.
#
IGNORE_UTF=""

##
# which translations should not be automatically generated
##
# List here any translation should not be automatically generated from base
# translation for that language (usually for those which are not correctly
# supported by convertor).
#
IGNORE_TRANSLATIONS="
russian-cp-866"

##
# end of configuration, you hopefully won't need to edit anything bellow
##

TEMPFILE=`mktemp /tmp/pma-sync-lang.XXXXXX`

cleanup() {
    rm -f $TEMPFILE
}

trap cleanup INT ABRT TERM

FAILED=""

echo "-------------------------------------------------------------------"
# go through all file we should process
for base in $BASE_TRANSLATIONS ; do
    if [ "$#" -gt 0 ] ; then
        if ( echo $base | grep -q "$@" ) ; then
            true
        else
            continue
        fi
    fi
    # grep language from basename
    lang=$(echo $base|sed 's%-.*%%')
    # which files will we create from current?
    create_files=$(ls --color=none -1 $lang*.inc.php|grep -v $base.inc.php)

    for ignore in $IGNORE_TRANSLATIONS ; do
        create_files=$(echo "$create_files" | grep -v $ignore)
    done

    # charset of source file
    src_charset=$(grep '\$charset' $base.inc.php | sed "s%^[^'\"]*['\"]\\([^'\"]*\\)['\"][^'\"]*$%\\1%")
    replace_charset=$src_charset
    # special case for hebrew
    if [ $src_charset = 'iso-8859-8-i' ] ; then
        src_charset=iso-8859-8
    fi
    echo -n "$base [charset $src_charset]"

    # do we already have utf-8 translation?
    if [ $src_charset = 'utf-8' ] ; then
        is_utf=yes
    else
        is_utf=no
    fi

    # at first update existing translations
    for file in $create_files ; do
        # charset of destination file

        # grepping from file causes problems when it is empty...
        charset=$(grep '\$charset' $file | sed "s%^[^'\"]*['\"]\\([^'\"]*\\)['\"][^'\"]*$%\\1%")
        if [ -z "$charset" ] ; then
            charset=$(echo $file | sed -e 's/^[^-]*-//' -e 's/\.inc\.php\?$//')
        fi

        if [ $charset = 'utf-8' ] ; then
            is_utf=yes
        fi

        # check whether we need to update translation
        if [ ! "$base.inc.php" -nt "$file" -a "$FORCE" -eq 0 -a -s "$file" ] ; then
            echo -n " ($file:ok)"
            continue
        fi

        echo -n " ($file:to $charset:"
        if [ $charset = 'utf-8' ] ; then
            # if we convert to utf-8, we should add allow_recoding
            is_utf=yes
            $CONVERTOR $(printf "$CONVERTOR_PARAMS" $src_charset $charset) < $base.inc.php| sed -e "s/$replace_charset/$charset/" -e '/\$charset/a\
$allow_recoding = TRUE;' > $TEMPFILE
        elif [ $src_charset = 'utf-8' ] ; then
            is_utf=yes
            # if we convert from utf-8, we should remove allow_recoding
            $CONVERTOR $(printf "$CONVERTOR_PARAMS" $src_charset $charset) < $base.inc.php| grep -v allow_recoding | sed "s/$replace_charset/$charset/" > $TEMPFILE
        else
            # just convert
            $CONVERTOR $(printf "$CONVERTOR_PARAMS" $src_charset $charset) < $base.inc.php| sed "s/$replace_charset/$charset/" > $TEMPFILE
        fi
        if [ -s $TEMPFILE ] ; then
            sed "s/\\\\';[[:space:]]\+$/\\\\\\\\';/" $TEMPFILE > $file
            echo -n 'done)'
        else
            FAILED="$FAILED $file"
            echo -n 'FAILED)'
        fi
    done

    # now check whether we found utf-8 translation
    if [ $is_utf = no ] ; then
        if ( echo $IGNORE_UTF | grep -q $base ) ; then
            # utf-8 should not be created
            true
        else
            # we should create utf-8 translation
            charset=utf-8
            file=$lang-$charset.inc.php
            echo -n " [$file:$charset:"
            $CONVERTOR $(printf "$CONVERTOR_PARAMS" $src_charset $charset) < $base.inc.php| sed -e "s/$replace_charset/$charset/" -e '/\$charset/a\
$allow_recoding = TRUE;' > $TEMPFILE
            if [ -s $TEMPFILE ] ; then
                cat $TEMPFILE > $file
                echo -n 'done)'
            else
                FAILED="$FAILED $file"
                echo -n 'FAILED)'
            fi
        fi
    fi
    echo
done

echo "-------------------------------------------------------------------"

if [ -z "$FAILED" ] ; then
    echo "Everything seems to went okay"
else
    echo "!!!SOME CONVERSION FAILED!!!"
    echo "Following file were NOT updated:"
    echo
    echo "$FAILED"
    echo
    echo "!!!SOME CONVERSION FAILED!!!"
fi

cleanup
