#!/bin/bash
#
# Shell script that creates only the main class for a new transformation
# plug-in, using a template
#
# $1: MIMEType
# $2: MIMESubtype
# $3: Transformation Name

# Do not run as CGI
if [ -n "$GATEWAY_INTERFACE" ] ; then
    echo 'Can not invoke as CGI!'
    exit 1
fi

if [ $# != 3 ]
then
  echo -e "Usage: ./generator_main_class.sh MIMEType MIMESubtype TransformationName\n"
  exit 65
fi

./generator_plugin.sh "$1" "$2" "$3" "--generate_only_main_class"
