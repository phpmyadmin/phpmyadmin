#!/bin/sh
# $Id$
##
# Shell script that synchronises all translations in phpMyAdmin
##
# Any parameters will be passed to grep to filter processed translation, for
# example: './sync_lang.sh czech' will process only czech translation,
# './sync_lang.sh -e czech -e english' will process czech and english
# translations.
##
# Written by Michal Cihar <nijel at users.sourceforge.net>
##
# Changes:
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
#for iconv
#CONVERTOR=iconv
#CONVERTOR_PARAMS="-f %s -t %s"
#for recode:
CONVERTOR=recode
CONVERTOR_PARAMS="%s..%s"


##
# names of translations to process
##
# Here should be listed all translations for which conversion should be done.
# The name is filename without inc.php3.
#
BASE_TRANSLATIONS=`cat <<EOT
afrikaans-iso-8859-1
albanian-iso-8859-1
arabic-windows-1256
brazilian_portuguese-iso-8859-1
bulgarian-koi8-r
catalan-iso-8859-1
chinese_big5
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
polish-iso-8859-2
portuguese-iso-8859-1
romanian-iso-8859-1
russian-windows-1251
serbian-windows-1250
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
EOT`

##
# end of configuration, you hopefully won't need to edit anything bellow
##

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
    create_files=$(ls --color=none -1 $lang*.inc.php3|grep -v $base.inc.php3)

    for ignore in $IGNORE_TRANSLATIONS ; do
        create_files=$(echo "$create_files" | grep -v $ignore)
    done

    # charset of source file
    src_charset=$(grep '\$charset' $base.inc.php3 | sed "s%^[^'\"]*['\"]\\([^'\"]*\\)['\"][^'\"]*$%\\1%")
    echo "$base [charset $src_charset]"

    is_utf=no

    # at first update existing translations
    for file in $create_files ; do
        # charset of destination file
        charset=$(grep '\$charset' $file | sed "s%^[^'\"]*['\"]\\([^'\"]*\\)['\"][^'\"]*$%\\1%")
        echo -n " to $charset..."
        if [ $charset = 'utf-8' ] ; then
            # if we convert to utf-8, we should add allow_recoding
            is_utf=yes
            $CONVERTOR $(printf -- "$CONVERTOR_PARAMS" $src_charset $charset) < $base.inc.php3| sed -e "s/$src_charset/$charset/" -e '/\$charset/a\
$allow_recoding = TRUE;' > $file
            echo done
        elif [ $src_charset = 'utf-8' ] ; then
            # if we convert fomo utf-8, we should remove allow_recoding
            $CONVERTOR $(printf -- "$CONVERTOR_PARAMS" $src_charset $charset) < $base.inc.php3| grep -v allow_recoding > $file
            echo done
        else
            # just convert
            $CONVERTOR $(printf -- "$CONVERTOR_PARAMS" $src_charset $charset) < $base.inc.php3| sed "s/$src_charset/$charset/" > $file 
            echo done
        fi
    done
  
    # now check whether we found utf-8 translation
    if [ $is_utf = no ] ; then
        if ( echo $IGNORE_UTF | grep -q $base ) ; then
            # utf-8 should not be created
            true
        else
            # we should create utf-8 translation
            echo " creating utf-8 translation"
            charset=utf-8
            iconv -f $src_charset -t $charset $base.inc.php3| sed -e "s/$src_charset/$charset/" -e '/\$charset/a\
$allow_recoding = TRUE;' > $lang-$charset.inc.php3
        fi
    fi
    echo "$lang processing finished."
    echo "-------------------------------------------------------------------"
done

