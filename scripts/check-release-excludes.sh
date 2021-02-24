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
        foundFile "${foundFile}";;
        */easy-coding-standard.neon)
        foundFile "${foundFile}";;
        */.travis.yml)
        foundFile "${foundFile}";;
        */psalm.xml)
        foundFile "${foundFile}";;
        */.coveralls.yml)
        foundFile "${foundFile}";;
        */appveyor.yml)
        foundFile "${foundFile}";;
        */phpunit.xml)
        foundFile "${foundFile}";;
        */phive.xml)
        foundFile "${foundFile}";;
        */Makefile)
        foundFile "${foundFile}";;
        */phpbench.json)
        foundFile "${foundFile}";;
        */.php_cs.dist)
        foundFile "${foundFile}";;
        */psalm.xml)
        foundFile "${foundFile}";;
        */phpstan.neon)
        foundFile "${foundFile}";;
        */phpstan.neon)
        foundFile "${foundFile}";;
        */phpcs.xml.dist)
        foundFile "${foundFile}";;
        */phpunit.xml.dist)
        foundFile "${foundFile}";;
        */.scrutinizer.yml)
        foundFile "${foundFile}";;
        */.gitattributes)
        foundFile "${foundFile}";;
        */.gitignore)
        foundFile "${foundFile}";;
        */.php_cs.cache)
        foundFile "${foundFile}";;
        */makefile)
        foundFile "${foundFile}";;
        */.phpunit.result.cache)
        foundFile "${foundFile}";;
        */phpstan.neon.dist)
        foundFile "${foundFile}";;
        */phpstan-baseline.neon)
        foundFile "${foundFile}";;
        */phpmd.xml.dist)
        foundFile "${foundFile}";;
        */.travis.php.ini)
        foundFile "${foundFile}";;
        */vendor/*/tests/*)
        foundFile "${foundFile}";;
        */vendor/*/Tests/*)
        foundFile "${foundFile}";;
        */vendor/*/test/*)
        foundFile "${foundFile}";;
        */.dependabot/*)
        foundFile "${foundFile}";;
        */.github/*)
        foundFile "${foundFile}";;
        */.circleci/*)
        foundFile "${foundFile}";;
        */vendor/examples/*)
        foundFile "${foundFile}";;
        */.git/*)
        foundFile "${foundFile}";;
        *vendor/*.rst)
        foundFile "${foundFile}";;
        *vendor/*.po)
        foundFile "${foundFile}";;
        *vendor/*.pot)
        foundFile "${foundFile}";;
        *vendor/*.m4)
        foundFile "${foundFile}";;
        *vendor/*.c)
        foundFile "${foundFile}";;
        *vendor/*.h)
        foundFile "${foundFile}";;
        *vendor/*.sh)
        foundFile "${foundFile}";;
        *vendor/*.w32)
        foundFile "${foundFile}";;
        *.hhconfig)
        foundFile "${foundFile}";;
        *.hhi)
        foundFile "${foundFile}";;
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
