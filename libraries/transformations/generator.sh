#!/bin/bash
# $Id$
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

cat $1 | sed "s/\[ENTER_FILENAME_HERE\]/$functionupper/" | sed "s/\[enter_filename_here\]/$functionlower/" >> $2.inc.php

if [ "$3" ]
then
    echo ""
    echo "To do later:"
    echo "cd ../../lang"
    echo "./add_message.sh '\$strTransformation_${functionlower}' '$3'"
    echo ""
fi

echo "Created $2.inc.php"
echo ""
