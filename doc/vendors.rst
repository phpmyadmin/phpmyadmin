Distributing and packaging phpMyAdmin
=====================================

This document is intended to give advices to people who want to
redistribute phpMyAdmin inside other software package such as Linux
distribution or some all in one package including web server and MySQL
server.

Generally you can customize some basic aspects (paths to some files and
behavior) in :file:`libraries/vendor_config.php`.

For example if you want setup script to generate config file in var, change
``SETUP_CONFIG_FILE`` to :file:`/var/lib/phpmyadmin/config.inc.php` and you
will also probably want to skip directory writable check, so set
``SETUP_DIR_WRITABLE`` to false.

External libraries
------------------

phpMyAdmin includes several external libraries, you might want to
replace them with system ones if they are available, but please note
that you should test whether version you provide is compatible with the
one we ship.

Currently known list of external libraries:

js/jquery
    jQuery js framework and various jQuery based libraries.

libraries/php-gettext
    php-gettext library
libraries/tcpdf
    tcpdf library, stripped down of not needed files
libraries/phpseclib
    portions of phpseclib library
