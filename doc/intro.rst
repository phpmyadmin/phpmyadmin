.. _intro:

Introduction
============

phpMyAdmin is a free software tool written in PHP that is intended to handle the administration of MySQL over the web.
It helps in using most of the operations on MySQL and MariaDB using a User Interface.
Using phpMyAdmin we can perform operations such as creating a database, run queries, add user accounts and so on making it simple and easy to use even for beginners.

Supported features
------------------

Currently phpMyAdmin can:

* create, browse and drop, rename and edit databases, tables, columns and indexes
* Run all SQL queries and obtain multiple results.
* maintaining server, databases and tables, with proposals on server configuration
* execute, edit and bookmark any :term:`SQL`-statement, even batch-queries
* Use the Console to run and execute Query commands
* load text files into tables
* create [#f1]_ and read dumps of tables
* export [#f1]_ data to various formats: :term:`CSV`, :term:`XML`, :term:`PDF`,
  :term:`ISO`/:term:`IEC` 26300 - :term:`OpenDocument` Text and Spreadsheet, Microsoft
  Word 2000, and LATEX formats
* import data and :term:`MySQL` structures from :term:`OpenDocument` spreadsheets, as
  well as :term:`XML`, :term:`CSV`, and :term:`SQL` files
* administer and manage multiple servers
* add or edit MySQL user accounts and assign privileges
* check referential integrity in MyISAM tables
* using Query-by-example (QBE), create complex queries automatically
  connecting required tables
* create :term:`PDF` graphics of your database layout
* search globally in a database or a subset of it
* transform stored data into any format using a set of predefined
  functions, like displaying BLOB-data as image or download-link
* track changes on databases, tables and views
* support InnoDB tables and foreign keys
* support mysqli, the improved MySQL extension see :ref:`faq1_17`
* create, edit, call, export and drop stored procedures and functions
* create, edit, export and drop events and triggers
* communicate in `over 72 different languages
  <https://www.phpmyadmin.net/translations/>`_

Shortcut keys
-------------

Currently phpMyAdmin supports following shortcuts:

* k - Toggle console
* h - Go to home page
* s - Open settings
* d + s - Go to database structure (Provided you are in database related page)
* d + f - Search database (Provided you are in database related page)
* t + s - Go to table structure (Provided you are in table related page)
* t + f - Search table (Provided you are in table related page)
* backspace - Takes you to older page.

A word about users
------------------

A lot of people  have difficulty in understanding the concept of user
management with regards to phpMyAdmin.
When a user logs in to phpMyAdmin, his or her username and password are passed directly to MySQL.
phpMyAdmin does not perform any account management on its own (other than allowing one to edit the MySQL user account information); all users must be valid MySQL users.

.. rubric:: Footnotes

.. [#f1]

    phpMyAdmin can compress (:term:`Zip`, :term:`GZip` or :term:`RFC 1952`
    formats) dumps and :term:`CSV` exports if you use PHP with
    :term:`Zlib` support (``--with-zlib``).
    Proper support may also need changes in :file:`php.ini`.
