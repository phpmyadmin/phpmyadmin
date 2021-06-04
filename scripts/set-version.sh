#!/bin/bash
#
# vim: expandtab sw=4 ts=4 sts=4:
#
# This requires Bash for the search and replace substitution to escape dots

# Do not run as CGI
if [ -n "$GATEWAY_INTERFACE" ] ; then
    echo 'Can not invoke as CGI!'
    exit 1
fi

# Fail on undefined variables
set -u
# Fail on failure
set -e

printHelp () {
    echo
    echo "Please pass the new version number as a command line argument such as"
    echo "$0 -v 5.0.0-dev"
    echo
}

if [ $# -eq 0 ]
then
    printHelp
   exit 1
fi

if [ "$1" = '-v' ]
then
    newVersion=$2
else
    printHelp
    exit 2
fi

# If we're in the scripts directory, we need to do all our work up a directory level
if [ "$(basename "$(pwd)")" = 'scripts' ]
then
    dir='../'
else
    dir=''
fi

# There are half a dozen ways to do this, including individually recreating the line of
# each file (search for 'version =' in conf.py and replace it with 'version = 5.0.0-dev'
# for instance), and while that's more precise this should work with less to fix if a line changes.
# This method wasn't selected for any particular strength.

oldVersion="$(grep 'version =' ${dir}doc/conf.py | cut -d "'" -f2)"

echo "Changing from ${oldVersion} to ${newVersion}..."

# Escape the dot for sed
oldVersion=${oldVersion//./\.}
newVersion=${newVersion//./\.}

for f in README doc/conf.py package.json
do
    if ! grep --quiet "${oldVersion}" "${dir}${f}"
    then
      # There may be a better way to test for a failure of the substitution itself, such as using the 'q' directive with sed
      # See https://stackoverflow.com/questions/15965073/return-code-of-sed-for-no-match
      echo "FAILED! Substitution string ${oldVersion} not found in ${dir}${f}"
    fi
    sed -i "s/${oldVersion}/${newVersion}/g" "${dir}${f}"
done


echo
echo "Next, you need to manually edit ChangeLog to manually change the version number and set the release date, and verify the changes with 'git diff'."
echo "You will probably want to call: \"./scripts/console set-version ${newVersion}\" afterwards"
echo "Suggested commit message: Prepare for version ${newVersion}"
