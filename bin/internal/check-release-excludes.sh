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
        public/docs/*)
            if [ "${extension}" != "png" ] && [ "${extension}" != "txt" ] &&
                [ "${extension}" != "html" ] && [ "${extension}" != "js" ] &&
                [ "${extension}" != "css" ] && [ "${extension}" != "gif" ]; then
                foundFileExt
            fi
        ;;
        resources/js/global.d.ts)
        ;;
        public/js/vendor/*)
            if [ "${extension}" != "js" ] && [ "${extension}" != "map" ] &&
                [ "${extension}" != "css" ] && [ "${filename}" != "LICENSE" ] &&
                [ "${extension}" != "txt" ]; then
                foundFileExt
            fi
        ;;
        public/js/*)
            if [ "${extension}" != "js" ] && [ "${extension}" != "map" ]; then
                foundFileExt
            fi
        ;;
        resources/js/*)
            if [ "${extension}" != "ts" ]; then
                foundFileExt
            fi
        ;;
        resources/sql/*)
            if [ "${extension}" != "sql" ]; then
                foundFileExt
            fi
        ;;
        examples/*)
            if [ "${extension}" != "php" ]; then
                foundFileExt
            fi
        ;;
        resources/locale/*)
            if [ "${extension}" != "mo" ]; then
                foundFileExt
            fi
        ;;
        public/setup/*)
            if [ "${extension}" != "php" ] && [ "${extension}" != "twig" ] &&
                [ "${extension}" != "css" ] && [ "${extension}" != "scss" ] &&
                [ "${extension}" != "gif" ] && [ "${extension}" != "map" ]; then
                foundFileExt
            fi
        ;;
        resources/templates/*)
            if [ "${extension}" != "twig" ]; then
                foundFileExt
            fi
        ;;
        app/*)
            if [ "${extension}" != "php" ]; then
                foundFileExt
            fi
        ;;
        src/*)
            if [ "${extension}" != "php" ] && [ "${extension}" != "md" ] &&
                [ "${filename}" != "README" ] && [ "${filename}" != "TEMPLATE" ] &&
                [ "${filename}" != "TEMPLATE_ABSTRACT" ]; then
                foundFileExt
            fi
        ;;
        public/themes/*)
            if [ "${extension}" != "css" ] && [ "${extension}" != "png" ] &&
                [ "${extension}" != "scss" ] && [ "${extension}" != "map" ] &&
                [ "${extension}" != "svg" ] && [ "${extension}" != "ico" ] &&
                [ "${extension}" != "gif" ] && [ "${extension}" != "json" ]; then
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
        vendor/williamdes/mariadb-mysql-kbs/cliff.toml)
        ;;
        vendor/williamdes/mariadb-mysql-kbs/dist/merged-ultraslim.json)
        ;;
        vendor/composer/installed.json)
        ;;
        vendor/tecnickcom/tcpdf/*)
            if [ "${extension}" != "php" ] && [ "${filename}" != "LICENSE.TXT" ] &&
                [ "${filename}" != "README.md" ] && [ "${filename}" != "CHANGELOG.TXT" ] &&
                [ "${filename}" != "VERSION" ] && [ "${filename}" != "composer.json" ] &&
                [ "${extension}" != "z" ]; then
                foundFileExt
            fi
        ;;
        vendor/*)
            if [ "${extension}" != "php" ] && [ "${filename}" != "LICENSE" ] &&
                [ "${filename}" != "README" ] && [ "${filename}" != "CHANGELOG" ] &&
                [ "${filename}" != "composer.json" ] && [ "${filename}" != "CHANGELOG.md" ] &&
                [ "${filename}" != "README.md" ] && [ "${filename}" != "BACKERS.md" ] &&
                [ "${filename}" != "LICENSE.md" ] && [ "${filename}" != "ARCHITECTURE.md" ] &&
                [ "${filename}" != "LICENSE.txt" ] && [ "${filename}" != "AUTHORS" ] &&
                [ "${filename}" != "LICENCE.md" ] && [ "${filename}" != "LICENCE" ] &&
                [ "${filename}" != "COPYRIGHT.md" ]; then
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
        public/favicon.ico)
        ;;
        tsconfig.json)
        ;;
        webpack.config.cjs)
        ;;
        babel.config.json)
        ;;
        package.json)
        ;;
        composer.json)
        ;;
        composer.lock)
        ;;
        yarn.lock)
        ;;
        public/robots.txt)
        ;;
        index.php)
        ;;
        public/index.php)
        ;;
        config.sample.inc.php)
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
        */phpstan.neon)
        foundFile;;
        */phpcs.xml.dist)
        foundFile;;
        */phpunit.xml.dist)
        foundFile;;
        */.scrutinizer.yml)
        foundFile;;
        */.phpstorm.meta.php)
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
        */infection.json5.dist)
        foundFile;;
        */infection.json5)
        foundFile;;
        */makefile)
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
