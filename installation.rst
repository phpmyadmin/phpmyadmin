Installation
============

Requirements
------------

* **PHP**
    * You need PHP 5.2.0 or newer, with `session` support (see :ref:`faq_1_31`),
      the Standard PHP Library (SPL) extension and JSON support.
    * To support uploading of ZIP files, you need the PHP `zip` extension.
    * For proper support of multibyte strings (eg. UTF-8, which is
      currently the default), you should install the `mbstring` and `ctype`
      extensions.
    * You need GD2 support in PHP to display inline
      thumbnails of JPEGs ("image/jpeg: inline") with their
      original aspect ratio.
    * When using the "cookie" authentication method</a>, the `mcrypt` extension
       is strongly suggested for most users and is **required** for
       64-bit machines. Not using mcrypt will cause phpMyAdmin to
       load pages significantly slower.
    * to support upload progress bars, see :ref:`faq_2_9`
    * To support XML and Open Document Spreadsheet importing, you need PHP 5.2.17 or newer and the
      `libxml` extension.
* **MySQL** 5.0 or newer (see :ref:`faq_1_17`).
* **Web browser** with cookies enabled.
