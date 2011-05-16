#!/bin/bash
#
# Shell script that adds a new function file using a template. Should not be called directly
# but instead by template_Generator.sh and template_generator_mimetype.sh
#
#
# $1: Template
# $2: Filename
# $3: (optional) Description

if [ $# == 0 ]
then
  echo "Please call template_generator.sh or template_generator_mimetype.sh instead"
  echo ""
  exit 65
fi
functionupper="`echo $2 | tr [:lower:] [:upper:]`"
functionlower="`echo $2 | tr [:upper:] [:lower:]`"

sed "s/\[ENTER_FILENAME_HERE\]/$functionupper/; s/\[enter_filename_here\]/$functionlower/; s/Description of the transformation./$3/;" < $1 > $2.inc.php

echo "Created $2.inc.php"
echo ""
