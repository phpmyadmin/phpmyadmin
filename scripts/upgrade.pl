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
# 2005-10-29  swix@users.sourceforge.net:
# - some fixes & improvements
#

use strict;
my $source_url = "http://phpmyadmin.net/home_page/version.php";


#
# usage
#

if (!$ARGV[0] || (($ARGV[0] eq "--force") && !$ARGV[1])) {
	print "\n";
	print "usage: $0 [--force] <target_directory>\n\n";
	print "  The location specified by <target_directory> will be backed up and replaced\n";
	print "  by the latest stable version of phpMyAdmin.\n";
	print "  Your config.inc.php file will be preserved.\n\n";
	exit(0);
}

my $forced;
my $targetdirectory;

if ($ARGV[0] eq "--force") {
	$forced = 1;
	$targetdirectory = $ARGV[1];
} else {
	$forced = 0;
	$targetdirectory = $ARGV[0];
}

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
my @today;
my $installedversion;

if (open(LATEST, "wget -o /dev/null -O - $source_url|")) {

	$version = <LATEST>; chomp($version);
	$releasedate = <LATEST>; chomp($releasedate);
	$filename = "phpMyAdmin-" . $version . "-all-languages.tar.gz";
	$directory = "phpMyAdmin-" . $version . "-all-languages";

	my $i = 0;

	while (my $line = <LATEST>) {
		chomp($line);
		if ($line =~ /http/) {
			$urls[$i++] = $line;
		}
	}

	close(LATEST);

} else {

	print "error: open of $source_url failed.\n";
	exit(0);

}


if (-d $directory) {
	print "error: target directory ($directory) already exists, exiting\n";
	exit(0);
}

#
# check the installed version
#

if (open(DEFINES, $targetdirectory .'/libraries/Config.class.php')) {
	my $versionStatus = 0;
	$installedversion = "unknownversion";

	while (my $line = <DEFINES>) {

		next unless $line =~ /'PMA_VERSION',\ '(.*)?'\);$/;
		$installedversion = $1;

		# take care of "pl", "rc" and "dev": dev < rc < pl

		my $converted_installedversion = $installedversion;
		$converted_installedversion =~ s/dev/aaa/g;
		$converted_installedversion =~ s/rc/bbb/g;
		$converted_installedversion =~ s/pl/ccc/g;

		my $converted_version = $version;
		$converted_version =~ s/dev/aaa/g;
		$converted_version =~ s/rc/bbb/g;
		$converted_version =~ s/pl/ccc/g;

		if ($converted_installedversion gt $converted_version && !$forced) {
			print "Local version ($installedversion) newer than latest stable release ($version), not updating.  (use \"--force\")\n";
			exit(0);

		} elsif ($installedversion eq $version && !$forced) {
			print "Local version ($version) already up to date, not updating  (you can use \"--force\")\n";
			exit(0);

		} else {
			$versionStatus = 1;
		}
	}
	if (!$versionStatus && !$forced) {
		print "Old version could not be identified, not updating  (use \"--force\" if you are sure) \n";
		exit(0);
	}
}

#
# ask for confirmation
#

print "\n";
print "phpMyAdmin upgrade summary:\n";
print "---------------------------\n";
print "     phpMyAdmin Path: $targetdirectory\n";
print "   Installed version: $installedversion\n";
print "    Upgraded version: $version\n\n";
print "Proceed with upgrade?  [Y/n] ";
my $kbdinput = <STDIN>; chomp($kbdinput);
if (lc(substr($kbdinput,0,1)) ne "y" && length($kbdinput) >= 1) {
	print "Aborting.\n";
	exit(0);
} else {
	print "Proceeding...\n\n";
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


if (!-f $filename) {
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

@today = localtime(time); $today[4]++; $today[5]+=1900;
my $timestamp = sprintf("%04d%02d%02d%02d%02d", $today[5], $today[4], $today[3], $today[2], $today[1]);

my $backupdir = $targetdirectory . "-" . $timestamp . "-" . $installedversion;
print "- backup directory: $backupdir\n";

system("cp $directory/config.inc.php $directory/config.inc-dist.php");
print "- original distribution config.inc.php renamed to config.inc-dist.php\n";

system("cp $targetdirectory/config.inc.php $directory/config.inc.php");
print "- previous config.inc.php copied to the new setup\n";

system("mv $targetdirectory $backupdir");
system("mv $directory $targetdirectory");
system("rm $filename");

print "\ndone!  phpMyAdmin $version installed in $targetdirectory\n";
print "backup of your old installation in $backupdir\n";
print "Enjoy! :-)\n\n";
