phpMyAdmin SSL certificates
===========================

This directory contains copy of root certificates used to sign phpmyadmin.net
and reports.phpmyadmin.net websites. It is used to allow operation on systems
where the certificates are missing or wrongly configured (happens on Windows
with wrongly compiled CURL).

Currently included SSL certificates:

* ISRG Root X1
* DST Root CA X3

See https://letsencrypt.org/certificates/ for more info on them.

In case of update, the filenames can be generated using c_rehash tool.
