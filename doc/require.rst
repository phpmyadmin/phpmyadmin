.. _require:

Requirements
============

Web server
----------

Since, phpMyAdmin's interface is based entirely in your browser, you'll need a
web server (such as Apache, :term:`IIS`) to install phpMyAdmin's files into.

PHP
---

* You need PHP 5.3.0 or newer, with ``session`` support, the Standard PHP Library 
  (SPL) extension, JSON support, and the ``mbstring`` extension.

* To support uploading of ZIP files, you need the PHP ``zip`` extension.

* You need GD2 support in PHP to display inline thumbnails of JPEGs
  ("image/jpeg: inline") with their original aspect ratio.

* When using the cookie authentication (the default), the `mcrypt
  <http://www.php.net/mcrypt>`_ extension is strongly suggested for most
  users and is **required** for 64–bit machines. Not using mcrypt will
  cause phpMyAdmin to load pages significantly slower.

* To support upload progress bars, see :ref:`faq2_9`.

* To support XML and Open Document Spreadsheet importing, you need the 
  `libxml <http://www.php.net/libxml>`_ extension.

* Performance suggestion: install the ``ctype`` extension.

.. seealso:: :ref:`faq1_31`, :ref:`authentication_modes`

Database
--------

phpMyAdmin supports MySQL-compatible databases. 

* MySQL 5.5 or newer
* MariaDB 5.5 or newer
* Drizzle

.. seealso:: :ref:`faq1_17`

Web browser
-----------

To access phpMyAdmin you need a web browser with cookies and javascript
enabled.

