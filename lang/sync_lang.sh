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
        CONVERTOR=recode
        CONVERTOR_PARAMS=" -f %s..%s"
        shift
        ;;
    *)
        echo Using recode as default, force with --iconv/--recode
        CONVERTOR=recode
        CONVERTOR_PARAMS=" -f %s..%s"
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
BASE_TRANSLATIONS=`cat <<EOT
afrikaans-iso-8859-1
albanian-iso-8859-1
arabic-windows-1256
azerbaijani-iso-8859-9
basque-iso-8859-1
bosnian-windows-1250
brazilian_portuguese-iso-8859-1
bulgarian-windows-1251
catalan-iso-8859-1
chinese_big5-utf-8
chinese_gb
croatian-iso-8859-2
czech-iso-8859-2
danish-iso-8859-1
dutch-iso-8859-1
english-iso-8859-1
estonian-iso-8859-1
finnish-iso-8859-1
french-iso-8859-1
galician-iso-8859-1
german-iso-8859-1
greek-iso-8859-7
hebrew-iso-8859-8-i
hungarian-iso-8859-2
indonesian-iso-8859-1
italian-iso-8859-1
japanese-euc
korean-ks_c_5601-1987
latvian-windows-1257
lithuanian-windows-1257
malay-iso-8859-1
norwegian-iso-8859-1
persian-windows-1256
polish-iso-8859-2
portuguese-iso-8859-1
romanian-iso-8859-1
russian-windows-1251
serbian_cyrillic-windows-1251
serbian_latin-windows-1250
slovenian-iso-8859-2
slovak-iso-8859-2
spanish-iso-8859-1
swedish-iso-8859-1
thai-tis-620
turkish-iso-8859-9
ukrainian-windows-1251
EOT`

##
# which translations should not be translated to utf-8
##
# List here any translation that should not be converted to utf-8. The name is
# same as above.
#
IGNORE_UTF=`cat <<EOT
hebrew-iso-8859-8-i
korean-ks_c_5601-1987
EOT`

##
# which translations should not be automatically generated
##
# List here any translation should not be automatically generated from base
# translation for that language (usually for those which are not correctly
# supported by convertor).
#
IGNORE_TRANSLATIONS=`cat <<EOT
japanese-sjis
russian-dos-866
EOT`

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
    echo "$base [charset $src_charset]"

    is_utf=no

    # at first update existing translations
    for file in $create_files ; do
        # charset of destination file

        # grepping from file causes problems when it is empty...
        charset=$(grep '\$charset' $file | sed "s%^[^'\"]*['\"]\\([^'\"]*\\)['\"][^'\"]*$%\\1%")
        if [ -z "$charset" ] ; then
            charset=$(echo $file | sed -e 's/^[^-]*-//' -e 's/\.inc\.php\?$//')
        fi

        # check whether we need to update translation
        if [ ! "$base.inc.php" -nt "$file" -a "$FORCE" -eq 0 -a -s "$file" ] ; then
            if [ $charset = 'utf-8' ] ; then
                is_utf=yes
            fi
            echo " $file is not needed to update"
            continue
        fi

        echo -n " to $charset..."
        if [ $charset = 'utf-8' ] ; then
            # if we convert to utf-8, we should add allow_recoding
            is_utf=yes
            $CONVERTOR $(printf "$CONVERTOR_PARAMS" $src_charset $charset) < $base.inc.php| sed -e "s/$src_charset/$charset/" -e '/\$charset/a\
$allow_recoding = TRUE;' > $TEMPFILE
            if [ -s $TEMPFILE ] ; then
                cat $TEMPFILE > $file
                echo done
            else
                FAILED="$FAILED $file"
                echo FAILED
            fi
        elif [ $src_charset = 'utf-8' ] ; then
            is_utf=yes
            # if we convert from utf-8, we should remove allow_recoding
            $CONVERTOR $(printf "$CONVERTOR_PARAMS" $src_charset $charset) < $base.inc.php| grep -v allow_recoding | sed "s/$src_charset/$charset/" > $TEMPFILE
            if [ -s $TEMPFILE ] ; then
                cat $TEMPFILE > $file
                echo done
            else
                FAILED="$FAILED $file"
                echo FAILED
            fi
        else
            # just convert
            $CONVERTOR $(printf "$CONVERTOR_PARAMS" $src_charset $charset) < $base.inc.php| sed "s/$src_charset/$charset/" > $TEMPFILE
            if [ -s $TEMPFILE ] ; then
                cat $TEMPFILE > $file
                echo done
            else
                FAILED="$FAILED $file"
                echo FAILED
            fi
        fi
    done

    # now check whether we found utf-8 translation
    if [ $is_utf = no ] ; then
        if ( echo $IGNORE_UTF | grep -q $base ) ; then
            # utf-8 should not be created
            true
        else
            # we should create utf-8 translation
            echo -n " creating utf-8 translation ... "
            charset=utf-8
            file=$lang-$charset.inc.php
            $CONVERTOR $(printf "$CONVERTOR_PARAMS" $src_charset $charset) < $base.inc.php| sed -e "s/$src_charset/$charset/" -e '/\$charset/a\
$allow_recoding = TRUE;' > $TEMPFILE
            if [ -s $TEMPFILE ] ; then
                cat $TEMPFILE > $file
                echo done
            else
                FAILED="$FAILED $file"
                echo FAILED
            fi
        fi
    fi
    echo "$lang processing finished."
    echo "-------------------------------------------------------------------"
done

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
