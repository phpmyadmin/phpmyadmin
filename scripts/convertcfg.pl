#!/usr/bin/perl
#
#
# Configuration converter
# Converts from old-style (Pre-2.3) configuration files to new format found in PMA-2.3
#
# Takes input from STDIN, sends output to STDOUT
#

while(<>) 
{	s/\$cfg(\w+)/\$cfg\[\'$1\'\]/g; 
	print; 
	}


