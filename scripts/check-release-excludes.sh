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

for filePath in ${FILE_LIST}; do
    case $filePath in
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
        */.php_cs.dist)
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
        */.gitattributes)
        foundFile;;
        */.gitignore)
        foundFile;;
        */.php_cs.cache)
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
        *vendor/*CONTRIBUTING.md*)
        foundFile;;
        *CODE_OF_CONDUCT.md*)
        foundFile;;
        *PERFORMANCE.md*)
        foundFile;;
        *) ;;
    esac
done

if [ ${found} -gt 0 ]; then
    echo 'Some new files to be excluded where found.'
    echo 'Please update create-release.sh'
    exit 1
else
    echo 'Everything looks okay'
    exit 0
fi
