#!/bin/sh

mkdir -p po


sed "
    /to translate/D;
    /\$allow_recoding/D;
    s/\$byteUnits = array('\(.*\)', '\(.*\)', '\(.*\)', '\(.*\)', '\(.*\)', '\(.*\)', '\(.*\)');/\$str_B = '\1';\n\$str_KiB = '\2';\n\$str_MiB = '\3';\n\$str_GiB = '\4';\n\$str_TiB = '\5';\n\$str_PiB = '\6';\n\$str_EiB = '\7';/;
    s/\$day_of_week = array('\(.*\)', '\(.*\)', '\(.*\)', '\(.*\)', '\(.*\)', '\(.*\)', '\(.*\)');/\$str_Sun = '\1';\n\$str_Mon = '\2';\n\$str_Tue = '\3';\n\$str_Wed = '\4';\n\$str_Thu = '\5';\n\$str_Fri = '\6';\n\$str_Sat = '\7';\n/;
    s/\$month = array('\(.*\)', '\(.*\)', '\(.*\)', '\(.*\)', '\(.*\)', '\(.*\)', '\(.*\)', '\(.*\)', '\(.*\)', '\(.*\)', '\(.*\)', '\(.*\)');/\$str_Jan = '\1';\n\$str_Feb = '\2';\n\$str_Mar = '\3';\n\$str_Apr = '\4';\n\$str_May = '\5';\n\$str_Jun = '\6';\n\$str_Jul = '\7';\n\$str_Aug = '\8';\n\$str_Sep = '\9';\n\$str_Oct = '\10';\n\$str_Nov = '\11';\n\$str_Dec = '\12';\n/;
    " < lang/english-utf-8.inc.php > po/english.php

for lang in lang/*.inc.php ; do
    loc=`basename $lang | sed 's/-utf-8.inc.php//'`
    # Unfold arrays, delete not translated strings
    sed "
    /to translate/D;
    /\$allow_recoding/D;
    s/\$byteUnits = array('\(.*\)', '\(.*\)', '\(.*\)', '\(.*\)', '\(.*\)', '\(.*\)', '\(.*\)');/\$str_B = '\1';\n\$str_KiB = '\2';\n\$str_MiB = '\3';\n\$str_GiB = '\4';\n\$str_TiB = '\5';\n\$str_PiB = '\6';\n\$str_EiB = '\7';/;
    s/\$day_of_week = array('\(.*\)', '\(.*\)', '\(.*\)', '\(.*\)', '\(.*\)', '\(.*\)', '\(.*\)');/\$str_Sun = '\1';\n\$str_Mon = '\2';\n\$str_Tue = '\3';\n\$str_Wed = '\4';\n\$str_Thu = '\5';\n\$str_Fri = '\6';\n\$str_Sat = '\7';\n/;
    s/\$month = array('\(.*\)', '\(.*\)', '\(.*\)', '\(.*\)', '\(.*\)', '\(.*\)', '\(.*\)', '\(.*\)', '\(.*\)', '\(.*\)', '\(.*\)', '\(.*\)');/\$str_Jan = '\1';\n\$str_Feb = '\2';\n\$str_Mar = '\3';\n\$str_Apr = '\4';\n\$str_May = '\5';\n\$str_Jun = '\6';\n\$str_Jul = '\7';\n\$str_Aug = '\8';\n\$str_Sep = '\9';\n\$str_Oct = '\10';\n\$str_Nov = '\11';\n\$str_Dec = '\12';\n/;
    " < $lang > po/$loc.php

    case $loc in
        afrikaans) langcode='af';;
        arabic) langcode='ar';;
        azerbaijani) langcode='az';;
        bangla) langcode='bn';;
        belarusian_cyrillic) langcode='be';;
        belarusian_latin) langcode='be@latin';;
        bulgarian) langcode='bg';;
        bosnian) langcode='bs';;
        catalan) langcode='ca';;
        czech) langcode='cs';;
        danish) langcode='da';;
        german) langcode='de';;
        greek) langcode='el';;
        english) langcode='en';;
        english-gb) langcode='en_GB';;
        spanish) langcode='es';;
        estonian) langcode='et';;
        basque) langcode='eu';;
        persian) langcode='fa';;
        finnish) langcode='fi';;
        french) langcode='fr';;
        galician) langcode='gl';;
        hebrew) langcode='he';;
        hindi) langcode='hi';;
        croatian) langcode='hr';;
        hungarian) langcode='hu';;
        indonesian) langcode='id';;
        italian) langcode='it';;
        japanese) langcode='jp';;
        korean) langcode='ko';;
        georgian) langcode='ka';;
        lithuanian) langcode='lt';;
        latvian) langcode='lv';;
        macedonian_cyrillic) langcode='mk';;
        mongolian) langcode='mn';;
        malay) langcode='ms';;
        dutch) langcode='nl';;
        norwegian) langcode='no';;
        polish) langcode='pl';;
        brazilian_portuguese) langcode='pt_BR';;
        portuguese) langcode='pt';;
        romanian) langcode='ro';;
        russian) langcode='ru';;
        sinhala) langcode='si';;
        slovak) langcode='sk';;
        slovenian) langcode='sl';;
        albanian) langcode='sq';;
        serbian_latin) langcode='sr@latin';;
        serbian_cyrillic) langcode='sr';;
        swedish) langcode='sv';;
        thai) langcode='th';;
        turkish) langcode='tr';;
        tatarish) langcode='tt';;
        ukrainian) langcode='uk';;
        chinese_traditional) langcode='zh_TW';;
        chinese_simplified) langcode='zh_CN';;
        uzbek_cyrillic) langcode='uz';;
        uzbek_latin) langcode='uz@latin';;
        *) echo "Wrong loc: $loc"; exit 1;;
    esac

    echo "$loc -> $langcode"
    if [ $langcode = en ] ; then
        php2po -i po/english.php -o po/phpmyadmin.pot -P
        sed -i '
            s/PACKAGE VERSION/phpMyAdmin 3.4/;
            s/Report-Msgid-Bugs-To: .*\\n/Report-Msgid-Bugs-To: phpmyadmin-devel@lists.sourceforge.net\\n/;
            ' po/phpmyadmin.pot
    else
        php2po -t po/english.php -i po/$loc.php  -o po/$langcode.po
        sed -i "
            s/PACKAGE VERSION/phpMyAdmin 3.4/;
            /, fuzzy/D;
            s/LANGUAGE <LL@li.org>/$loc <$langcode@li.org>/;
            s/YEAR-MO-DA HO:MI+ZONE/`date +'%Y-%m-%d %H:%M%z'`/;
            s/FULL NAME <EMAIL@ADDRESS>/Automatically generated/;
            s/Report-Msgid-Bugs-To: .*\\\\n/Report-Msgid-Bugs-To: phpmyadmin-devel@lists.sourceforge.net\\\\n/;
            " po/$langcode.po
    fi
done
