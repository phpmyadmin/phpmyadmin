#!/usr/bin/perl
#
# $Id$
#
# upgrade.pl - automatic phpmyadmin upgrader 
# 
#
# 2005-05-08, swix@users.sourceforge.net:
# - created script
#
# TODO: check if current version is not already uptodate
#       (let's assume the sysadmin still has a brain... :-)
#


use strict;
my $source_url = "http://www.phpmyadmin.net/latest.txt";


#
# usage
#

if (!$ARGV[0]) { 
	print "\n";
	print "usage: upgrade.pl target_directory\n\n";
	print "  target_directory will be backuped and replaced\n";
	print "  by the current version of phpMyAdmin, by keeping your\n";
	print "  config.inc.php file.\n\n";
	exit(0);
}

my $targetdirectory = $ARGV[0];
if ($targetdirectory =~ /^(.*)\/$/) {
	# remove trailing slash, if any
	$targetdirectory = $1;
}

if (!-d $targetdirectory) {
	print "error: target directory ($targetdirectory) does not exists\n";
	exit(0);
}

if (!-f "$targetdirectory/config.inc.php") {
	print "error: target directory doesn't seem to contain phpMyAdmin\n";
	exit(0);
}


# 
# get current release information
#

my $version;
my $filename;
my $directory;
my $releasedate;
my @urls;

if (open(LATEST, "wget -o /dev/null -O - $source_url|")) {

	$version = <LATEST>; chomp($version);
	$releasedate = <LATEST>; chomp($releasedate);
	$filename = "phpMyAdmin-" . $version . ".tar.gz";
	$directory = "phpMyAdmin-" . $version;

	my $i = 0;

	while(my $line = <LATEST>) {
		chomp($line);
		if ($line =~ /http/) {
			$urls[$i++] = $line;
		}
	}

	close(LATEST);

} else {

	print "error: open failed.\n";
	exit(0);

}


if (-d $directory) {
	print "error: target directory ($directory) already exists, exiting\n";
	exit(0);
}


#
# get file
#

if (!-f $filename) {

	print "getting phpMyAdmin $version\n";
	foreach my $url (@urls) {

		print "trying $url...\n";
		system("wget -o /dev/null $url");
		if (-f $filename) {
			print "-> ok\n";
			last;
		}
	}
} else {
	print "already got $filename, not downloading\n";
}


if (!-filename) {
	print "error: $filename download failed\n";
	exit(0);
}



#
# setup
# 

print "installing...\n";

system("tar xzf $filename");
if (!$directory) {
	print "error: $directory still not exists after untar...\n";
	exit(0);
}

my $backupdir = $targetdirectory . "-" . time;
print "- backup directory: $backupdir\n";

system("cp $directory/config.inc.php $directory/config.inc-dist.php");
print "- original config.inc.php renamed to config.inc-dist.php\n";

system("cp $targetdirectory/config.inc.php $directory/config.inc.php");
system("mv $targetdirectory $backupdir");
system("mv $directory $targetdirectory");


print "\ndone!  phpMyAdmin $version installed in $directory\n";
print "backup of your old installation in $backupdir\n\n";
print "Enjoy! :-)\n\n";
