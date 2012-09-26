Introduction
============

phpMyAdmin can manage a whole MySQL server (needs a super-user) as well as a
single database. To accomplish the latter you'll need a properly set up MySQL
user who can read/write only the desired database. It's up to you to look up
the appropriate part in the MySQL manual.

Supported features
------------------

* browse and drop databases, tables, views, columns and indexes
* display multiple results sets through stored procedures or queries
* create, copy, drop, rename and alter databases, tables, columns and
  indexes
* maintenance server, databases and tables, with proposals on server
  configuration
* execute, edit and bookmark any :abbr:`SQL (structured query language)`-statement, even
  batch-queries
* load text files into tables
* create and read dumps of tables
* export data to various formats:
    * :abbr:`CSV (Comma Separated Values)`
    * :abbr:`XML (Extensible Markup Language)`
    * :abbr:`PDF (Portable Document Format)`
    * :abbr:`ISO (International Standards Organisation)`/:abbr:`IEC (International Electrotechnical Commission)` 26300 - OpenDocument Text and Spreadsheet
    * :abbr:`Word (Microsoft Word 2000)`
* import data and MySQL structures from OpenDocument spreadsheets, as well as :abbr:`XML (Extensible Markup Language)`, :abbr:`CSV (Comma Separated Values)`, and :abbr:`SQL (Server Query Language)` files
* administer multiple servers
* manage MySQL users and privileges
* check referential integrity in MyISAM tables
* using Query-by-example (QBE), create complex queries automatically
  connecting required tables
* create :abbr:`PDF (Portable Document Format)` graphics of
  your Database layout
* search globally in a database or a subset of it
* transform stored data into any format using a set of predefined
  functions, like displaying BLOB-data as image or download-link
    
* track changes on databases, tables and views
* support InnoDB tables and foreign keys, see :ref:`faq_3_6`
* support mysqli, the improved MySQL extension, see :ref:`faq_1_17`
* create, edit, call, export and drop stored procedures and functions
* create, edit, export and drop events and triggers
* communicate in 62 different languages
* synchronize two databases residing on the same as well as remote servers, see :ref:`faq_9_1`

