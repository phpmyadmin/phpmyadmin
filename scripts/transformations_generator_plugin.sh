#!/bin/bash
#
# Shell script that creates a new transformation plug-in (both main and
# abstract class) using a template.
#
# The 'description' parameter will add a new entry in the language file.
# Watch out for special escaping.
#
# $1: MIMEType
# $2: MIMESubtype
# $3: Transformation Name
# $4: (optional) Description

# Do not run as CGI
if [ -n "$GATEWAY_INTERFACE" ] ; then
    echo 'Can not invoke as CGI!'
    exit 1
fi

if [ $# -ne 3 ] && [ $# -ne 4 ]; then
  echo -e "Usage: ./generator_plugin.sh MIMEType MIMESubtype TransformationName [Description]\n"
  exit 65
fi

# make sure that the MIME Type, MIME Subtype and Transformation names
# are in the correct format

# make all names lowercase
MT="$(echo "$1" | tr '[:upper:]' '[:lower:]')"
MS="$(echo "$2" | tr '[:upper:]' '[:lower:]')"
TN="$(echo "$3" | tr '[:upper:]' '[:lower:]')"
# make first letter uppercase
MT="${MT^}"
MS="${MS^}"
TN="${TN^}"

# make the first letter after each underscore uppercase
# define the name of the main class file and of its template
CLASS_NAME=$(echo "$MT"_"$MS"_"$TN" | sed -e 's/_./\U&\E/g')
BASE_DIR="./src/Plugins/Transformations"
ClassFile="$BASE_DIR"/"$CLASS_NAME".php
Template="$BASE_DIR"/TEMPLATE
# define the name of the abstract class file and its template
AbstractClassFile="$BASE_DIR"/Abs/"$TN"TransformationsPlugin.php
AbstractTemplate="$BASE_DIR"/TEMPLATE_ABSTRACT
# replace template names with argument names
sed "s/\[MIMEType]/$MT/; s/\[MIMESubtype\]/$MS/; s/\[TransformationName\]/$TN/;" < $Template > "$ClassFile"
echo "Created $ClassFile"

GenerateAbstractClass=1
if [ -n "$4" ]; then
    if [ "$4" == "--generate_only_main_class" ]; then
        if [ -e "$AbstractClassFile" ]; then
            GenerateAbstractClass=0
        fi
    fi
fi

if [ $GenerateAbstractClass -eq 1 ]; then
    # replace template names with argument names
    sed "s/\[TransformationName\]/$TN/; s/Description of the transformation./$4/;" < $AbstractTemplate > "$AbstractClassFile"
    echo "Created $AbstractClassFile"
fi
