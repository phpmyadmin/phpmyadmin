#!/bin/bash

set -e

FILE="$1"

if [ ! -f "${FILE}" ]; then
    echo 'Please provide a file as a first argument.'
    exit 1
fi

FILE_LIST="$(tar --list --file="${FILE}")"

found=0;

echo 'Searching for files'

foundFile() {
    found=1
    printf "Found: %s\n" "${filePath}"
}

foundFileExt() {
    found=1
    printf "Found unexpected file: %s with extension %s\n" "${pathWithoutFirst}" "${extension}"
}

validateExtension() {
    if [ "${filePath: -1}" = "/" ]; then
        return;
    fi

    pathWithoutFirst="$(echo "$filePath" | cut -d / -f 2-)"

    filename=$(basename -- "$pathWithoutFirst")
    extension="${filename##*.}"

    case $pathWithoutFirst in
        doc/*)
            if [ \
                "${extension}" != "png" -a "${extension}" != "txt" \
                -a "${extension}" != "html" -a "${extension}" != "js" \
                -a "${extension}" != "css" -a "${extension}" != "gif" \
            ]; then
                foundFileExt
            fi
        ;;
        js/global.d.ts)
        ;;
        js/vendor/*)
            if [ \
                "${extension}" != "js" -a "${extension}" != "map" \
                -a "${extension}" != "css" -a "${filename}" != "LICENSE" \
                -a "${extension}" != "txt" \
            ]; then
                foundFileExt
            fi
        ;;
        js/dist/*)
            if [ \
                "${extension}" != "js" -a "${extension}" != "map" \
            ]; then
                foundFileExt
            fi
        ;;
        js/config/*)
            if [ "${extension}" != "js" ];then
                foundFileExt
            fi
        ;;
        js/dist/*)
            if [ "${extension}" != "js" ];then
                foundFileExt
            fi
        ;;
        js/src/*)
            if [ \
                "${extension}" != "js" -a "${extension}" != "mjs" \
            ]; then
                foundFileExt
            fi
        ;;
        sql/*)
            if [ "${extension}" != "sql" ]; then
                foundFileExt
            fi
        ;;
        examples/*)
            if [ "${extension}" != "php" ]; then
                foundFileExt
            fi
        ;;
        locale/*)
            if [ "${extension}" != "mo" ]; then
                foundFileExt
            fi
        ;;
        setup/*)
            if [ \
                "${extension}" != "php" -a "${extension}" != "twig" \
                -a "${extension}" != "css" \
                -a "${extension}" != "scss" -a "${extension}" != "gif" -a "${extension}" != "map" \
            ]; then
                foundFileExt
            fi
        ;;
        templates/*)
            if [ "${extension}" != "twig" ]; then
                foundFileExt
            fi
        ;;
        libraries/*)
            if [ \
                "${extension}" != "php" -a "${extension}" != "md" \
                -a "${filename}" != "README" \
                -a "${filename}" != "TEMPLATE" -a "${filename}" != "TEMPLATE_ABSTRACT" \
            ]; then
                foundFileExt
            fi
        ;;
        themes/*)
            if [ \
                "${extension}" != "css" -a "${extension}" != "png" \
                -a "${extension}" != "scss" -a "${extension}" != "map" \
                -a "${extension}" != "svg" -a "${extension}" != "ico" \
                -a "${extension}" != "gif" -a "${extension}" != "json" \
            ]; then
                foundFileExt
            fi
        ;;
        vendor/phpmyadmin/sql-parser/locale/*)
            if [ "${extension}" != "mo" ]; then
                foundFileExt
            fi
        ;;
        vendor/composer/ca-bundle/res/cacert.pem)
        ;;
        vendor/pragmarx/google2fa-qrcode/composer.lock)
        ;;
        vendor/williamdes/mariadb-mysql-kbs/dist/merged-ultraslim.json)
        ;;
        vendor/composer/installed.json)
        ;;
        vendor/tecnickcom/tcpdf/*)
            if [ \
                "${extension}" != "php" -a "${filename}" != "LICENSE.TXT" \
                -a "${filename}" != "README.md" -a "${filename}" != "CHANGELOG.TXT" \
                -a "${filename}" != "VERSION" -a "${filename}" != "composer.json" \
                -a "${extension}" != "z" \
            ]; then
                foundFileExt
            fi
        ;;
        vendor/*)
            if [ \
                "${extension}" != "php" -a "${filename}" != "LICENSE" \
                -a "${filename}" != "README" -a "${filename}" != "CHANGELOG" \
                -a "${filename}" != "composer.json" -a "${filename}" != "CHANGELOG.md" \
                -a "${filename}" != "README.md" -a "${filename}" != "BACKERS.md" \
                -a "${filename}" != "LICENSE.md" -a "${filename}" != "ARCHITECTURE.md" \
                -a "${filename}" != "LICENSE.txt" -a "${filename}" != "AUTHORS" \
                -a "${filename}" != "LICENCE.md" -a "${filename}" != "LICENCE" \
            ]; then
                foundFileExt
            fi
        ;;
        ChangeLog)
        ;;
        LICENSE)
        ;;
        RELEASE-DATE-[1-9].[0-9].[0-9])
        ;;
        RELEASE-DATE-[1-9].[0-9].[0-9]-dev)
        ;;
        RELEASE-DATE-[1-9].[0-9]+snapshot)
        ;;
        CONTRIBUTING.md)
        ;;
        README)
        ;;
        favicon.ico)
        ;;
        tsconfig.json)
        ;;
        webpack.config.cjs)
        ;;
        package.json)
        ;;
        composer.json)
        ;;
        composer.lock)
        ;;
        yarn.lock)
        ;;
        robots.txt)
        ;;
        index.php)
        ;;
        config.sample.inc.php)
        ;;
        show_config_errors.php)
        ;;
        *)
            foundFileExt
        ;;
    esac

}

for filePath in ${FILE_LIST}; do
    validateExtension
    case $filePath in
        */rector*.php)
        foundFile;;
        */.gitkeep)
        foundFile;;
        */.editorconfig)
        foundFile;;
        */easy-coding-standard.neon)
        foundFile;;
        */.travis.yml)
        foundFile;;
        */psalm.xml)
        foundFile;;
        */.coveralls.yml)
        foundFile;;
        */appveyor.yml)
        foundFile;;
        */phpunit.xml)
        foundFile;;
        */phive.xml)
        foundFile;;
        */Makefile)
        foundFile;;
        */phpbench.json)
        foundFile;;
        */phpbench.json.dist)
        foundFile;;
        */.php-cs-fixer.dist.php)
        foundFile;;
        */.php_cs)
        foundFile;;
        */.php_cs.dist)
        foundFile;;
        */.php_cs.cache)
        foundFile;;
        */psalm.xml)
        foundFile;;
        */phpstan.neon)
        foundFile;;
        */phpstan.neon)
        foundFile;;
        */phpcs.xml.dist)
        foundFile;;
        */phpunit.xml.dist)
        foundFile;;
        */.scrutinizer.yml)
        foundFile;;
        */codecov.yml)
        foundFile;;
        */.gitattributes)
        foundFile;;
        */.gitignore)
        foundFile;;
        */infection.json.dist)
        foundFile;;
        */infection.json)
        foundFile;;
        */infection.json.dist)
        foundFile;;
        */makefile)
        foundFile;;
        */Makefile)
        foundFile;;
        */.phpunit.result.cache)
        foundFile;;
        */phpstan.neon.dist)
        foundFile;;
        */phpstan-baseline.neon)
        foundFile;;
        */phpmd.xml.dist)
        foundFile;;
        */.travis.php.ini)
        foundFile;;
        */vendor/*/tests/*)
        foundFile;;
        */vendor/*/Tests/*)
        foundFile;;
        */vendor/*/test/*)
        foundFile;;
        */twig/twig/lib/Twig/Node/Expression/Test/*)
        ;;
        */twig/twig/lib/Twig/Test/*)
        ;;
        *twig/twig/src/Node/Expression/Test/*)
        ;;
        */vendor/*/Test/*)
        foundFile;;
        */.dependabot/*)
        foundFile;;
        */.github/*)
        foundFile;;
        */.circleci/*)
        foundFile;;
        */vendor/examples/*)
        foundFile;;
        */.git/*)
        foundFile;;
        *vendor/*.rst)
        foundFile;;
        *vendor/*.po)
        foundFile;;
        *vendor/*.pot)
        foundFile;;
        *vendor/*.m4)
        foundFile;;
        *vendor/*.c)
        foundFile;;
        *vendor/*.h)
        foundFile;;
        *vendor/*.sh)
        foundFile;;
        *vendor/*.w32)
        foundFile;;
        *.hhconfig)
        foundFile;;
        *.hhi)
        foundFile;;
        *.xsd)
        foundFile;;
        *.xml)
        foundFile;;
        *vendor/*CONTRIBUTING.md*)
        foundFile;;
        *CODE_OF_CONDUCT.md*)
        foundFile;;
        *PERFORMANCE.md*)
        foundFile;;
        *phar*)
        foundFile;;
        *) ;;
    esac
done

if [ ${found} -gt 0 ]; then
    echo 'Some new files to be excluded were found.'
    echo 'Please update create-release.sh'
    exit 1
else
    echo 'Everything looks okay'
    exit 0
fi
