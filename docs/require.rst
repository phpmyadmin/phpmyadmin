.. _require:

Requirements
============

Web server
----------

Since phpMyAdmin's interface is based entirely in your browser, you'll need a
web server (such as Apache, nginx, :term:`IIS`) to install phpMyAdmin's files into.

PHP
---

* You need PHP 8.2.0 or newer.

* You need the following PHP extensions
  (``php -m`` or ``phpinfo()`` will indicate the enabled ones):

  * normally bundled into PHP and enabled by default:

    - `json <https://www.php.net/json>`_ provides JSON functions
    - `session <https://www.php.net/session>`_ provides session storage
    - `pcre <https://www.php.net/pcre>`_ provides regular expression functions
    - `hash <https://www.php.net/hash>`_ provides basic hashing functions
    - `spl <https://www.php.net/spl>`_ the Standard PHP Library (SPL) extension
    - `sodium <https://www.php.net/sodium>`_ a modern cryptography library

  * manual installation required:

    - `mysqli <https://www.php.net/mysqli>`_ To connect to MySQL/MariaDB databases
    - `ctype <https://www.php.net/ctype>`_ To check the type of strings

* The `mbstring <https://www.php.net/mbstring>`_ extension (see :term:`mbstring`) is strongly recommended
  for performance reasons.

* To support uploading of ZIP files, you need the PHP `zip <https://www.php.net/zip>`_ extension.

* You need `GD2 <https://www.php.net/gd>`_ support in PHP to display inline thumbnails of JPEGs
  (``"image/jpeg: inline"``) with their original aspect ratio.

* When using the cookie authentication (the default), the `openssl
  <https://www.php.net/openssl>`_ extension is strongly suggested.

* To support upload progress bars, see :ref:`faq2_9`.

* To support XML and Open Document Spreadsheet importing, you need the
  `libxml <https://www.php.net/libxml>`_ extension.

* To support reCAPTCHA on the login page, you need the
  `openssl <https://www.php.net/openssl>`_ extension.

* To support displaying phpMyAdmin's latest version, you need to enable
  ``allow_url_open`` in your :file:`php.ini` or to have the
  `curl <https://www.php.net/curl>`_ extension.

.. seealso:: :ref:`faq1_31`, :ref:`authentication_modes`

Database
--------

phpMyAdmin supports MySQL-compatible databases.

* MySQL 5.5 or newer
* MariaDB 5.5 or newer

.. seealso:: :ref:`faq1_17`

Web browser
-----------

To access phpMyAdmin you need a web browser with cookies and JavaScript
enabled.

You need a browser which is supported by Bootstrap 4.5, see
<https://getbootstrap.com/docs/4.5/getting-started/browsers-devices/>.

.. versionchanged:: 5.2.0

    You need a browser which is supported by Bootstrap 5.0, see
    <https://getbootstrap.com/docs/5.0/getting-started/browsers-devices/>.
