Distributing and packaging phpMyAdmin
=====================================

This document is intended to give pieces of advice to people who want to
redistribute phpMyAdmin inside other software packages such as Linux
distribution or some all in one package including web server and MySQL
server.

Generally, you can customize some basic aspects (paths to some files and
behavior) in :file:`libraries/vendor_config.php`.

For example, if you want setup script to generate a config file in var, change
``SETUP_CONFIG_FILE`` to :file:`/var/lib/phpmyadmin/config.inc.php` and you
will also probably want to skip directory writable check, so set
``SETUP_DIR_WRITABLE`` to false.

External libraries
------------------

phpMyAdmin includes several external libraries, you might want to
replace them with system ones if they are available, but please note
that you should test whether the version you provide is compatible with the
one we ship.

Currently known list of external libraries:

js/vendor
    jQuery js framework libraries and various js libraries.

vendor/
    The download kit includes various Composer packages as
    dependencies.
