.. _config:

Configuration
=============

.. warning::

    :abbr:`Mac (Apple Macintosh)` should note that PHP does not seem to like
    :abbr:`Mac (Apple Macintosh)` end of lines character ("``\r``"). So ensure
    you choose the option that allows to use the \*nix end of line character
    ("``\n``") in your text editor before saving a script you have modified.

.. note::

    Almost all configurable data is placed in ``config.inc.php``. If this file
    does not exist, please refer to the section to create one. This file only
    needs to contain the parameters you want to change from their corresponding
    default value in ``libraries/config.default.php``.

    The parameters which relate to design (like colors) are placed in
    ``themes/themename/layout.inc.php``. You might also want to create
    *config.footer.inc.php* and *config.header.inc.php* files to add your
    site specific code to be included on start and end of each page.

.. _cfg_PmaAbsoluteUri:

$cfg['PmaAbsoluteUri'] string
-----------------------------

Sets here the complete :abbr:`URL (Uniform Resource Locator)` (with
full path) to your phpMyAdmin installation's directory. E.g.
``http://www.your\_web.net/path\_to\_your\_phpMyAdmin\_directory/``.
Note also that the :abbr:`URL (Uniform Resource Locator)` on some web
servers are case–sensitive. Don’t forget the trailing slash at the
end. Starting with version 2.3.0, it is advisable to try leaving this
blank. In most cases phpMyAdmin automatically detects the proper
setting. Users of port forwarding will need to set PmaAbsoluteUri
(`more info <https://sourceforge.net/tracker/index.php?func=detail&aid
=1340187&group_id=23067&atid=377409>`_). A good test is to browse a
table, edit a row and save it. There should be an error message if
phpMyAdmin is having trouble auto–detecting the correct value. If you
get an error that this must be set or if the autodetect code fails to
detect your path, please post a bug report on our bug tracker so we
can improve the code.

.. _cfg_PmaNoRelation_DisableWarning:

$cfg['PmaNoRelation\_DisableWarning'] boolean
---------------------------------------------

Starting with version 2.3.0 phpMyAdmin offers a lot of features to
work with master / foreign – tables (see ).  If you tried to set this
up and it does not work for you, have a look on the "Structure" page
of one database where you would like to use it. You will find a link
that will analyze why those features have been disabled. If you do not
want to use those features set this variable to ``TRUE`` to stop this
message from appearing.

.. _cfg_SuhosinDisableWarning:

$cfg['SuhosinDisableWarning'] boolean
-------------------------------------

A warning is displayed on the main page if Suhosin is detected. You
can set this parameter to ``TRUE`` to stop this message from
appearing.

.. _cfg_McryptDisableWarning:

$cfg['McryptDisableWarning'] boolean
------------------------------------

Disable the default warning that is displayed if mcrypt is missing for
cookie authentication. You can set this parameter to ``TRUE`` to stop
this message from appearing.

.. _cfg_ServerLibraryDifference_DisableWarning:

$cfg['ServerLibraryDifference\_DisableWarning'] boolean
-------------------------------------------------------

A warning is displayed on the main page if there is a difference
between the MySQL library and server version. You can set this
parameter to ``TRUE`` to stop this message from appearing.

.. _cfg_TranslationWarningThreshold:

$cfg['TranslationWarningThreshold'] integer
-------------------------------------------

Show warning about incomplete translations on certain threshold.

.. _cfg_blowfish_secret:

$cfg['blowfish\_secret'] string
-------------------------------

The "cookie" auth\_type uses blowfish algorithm to encrypt the
password. If you are using the "cookie" auth\_type, enter here a
random passphrase of your choice. It will be used internally by the
blowfish algorithm: you won’t be prompted for this passphrase. There
is no maximum length for this secret. Since version 3.1.0 phpMyAdmin
can generate this on the fly, but it makes a bit weaker security as
this generated secret is stored in session and furthermore it makes
impossible to recall user name from cookie.

.. _cfg_Servers:

$cfg['Servers'] array
---------------------

Since version 1.4.2, phpMyAdmin supports the administration of
multiple MySQL servers. Therefore, a -array has been added which
contains the login information for the different servers. The first
contains the hostname of the first server, the second  the hostname of
the second server, etc. In ``./libraries/config.default.php``, there
is only one section for server definition, however you can put as many
as you need in ``./config.inc.php``, copy that block or needed parts
(you don't have to define all settings, just those you need to
change).

.. _cfg_Servers_host:

$cfg['Servers'][$i]['host'] string
----------------------------------

The hostname or :abbr:`IP (Internet Protocol)` address of your $i-th
MySQL-server. E.g. localhost.

.. _cfg_Servers_port:

$cfg['Servers'][$i]['port'] string
----------------------------------

The port-number of your $i-th MySQL-server. Default is 3306 (leave
blank). If you use "localhost" as the hostname, MySQL ignores this
port number and connects with the socket, so if you want to connect to
a port different from the default port, use "127.0.0.1" or the real
hostname in .

.. _cfg_Servers_socket:

$cfg['Servers'][$i]['socket'] string
------------------------------------

The path to the socket to use. Leave blank for default. To determine
the correct socket, check your MySQL configuration or, using the
``mysql`` command–line client, issue the ``status`` command. Among the
resulting information displayed will be the socket used.

.. _cfg_Servers_ssl:

$cfg['Servers'][$i]['ssl'] boolean
----------------------------------

Whether to enable SSL for connection to MySQL server.

.. _cfg_Servers_connect_type:

$cfg['Servers'][$i]['connect\_type'] string
-------------------------------------------

What type connection to use with the MySQL server. Your options are
``'socket'`` and ``'tcp'``. It defaults to 'tcp' as that is nearly
guaranteed to be available on all MySQL servers, while sockets are not
supported on some platforms. To use the socket mode, your MySQL server
must be on the same machine as the Web server.

.. _cfg_Servers_extension:

$cfg['Servers'][$i]['extension'] string
---------------------------------------

What php MySQL extension to use for the connection. Valid options are:
``*mysql*`` : The classic MySQL extension. ``*mysqli*`` : The improved
MySQL extension. This extension became available with PHP 5.0.0 and is
the recommended way to connect to a server running MySQL 4.1.x or
newer.

.. _cfg_Servers_compress:

$cfg['Servers'][$i]['compress'] boolean
---------------------------------------

Whether to use a compressed protocol for the MySQL server connection
or not (experimental).

.. _controlhost:

.. _cfg_Servers_controlhost:

$cfg['Servers'][$i]['controlhost'] string
-----------------------------------------

Permits to use an alternate host to hold the configuration storage
data.

.. _controluser:

.. _cfg_Servers_controluser:

.. _cfg_Servers_controlpass:

$cfg['Servers'][$i]['controluser'] string $cfg['Servers'][$i]['controlpass'] string
-----------------------------------------------------------------------------------

This special account is used for 2 distinct purposes: to make possible
all relational features (see ) and, for a MySQL server running with
``--skip-show-database``, to enable a multi-user installation
(:abbr:`HTTP (HyperText Transfer Protocol)` or cookie authentication
mode). When using :abbr:`HTTP (HyperText Transfer Protocol)` or cookie
authentication modes (or 'config' authentication mode since phpMyAdmin
2.2.1), you need to supply the details of a MySQL account that has
``SELECT`` privilege on the *mysql.user (all columns except
"Password")*, *mysql.db (all columns)* and *mysql.tables\_priv (all
columns except "Grantor" and "Timestamp")* tables. This account is used
to check what databases the user will see at login. Please see the  on
"Using authentication modes" for more information. In phpMyAdmin
versions before 2.2.5, those were called "stduser/stdpass".

.. _cfg_Servers_auth_type:

$cfg['Servers'][$i]['auth\_type'] string ``[':abbr:`HTTP (HyperText Transfer Protocol)`'|'http'|'cookie'|'config'|'signon']``
-----------------------------------------------------------------------------------------------------------------------------

Whether config or cookie or :abbr:`HTTP (HyperText Transfer Protocol)`
or signon authentication should be used for this server.

* 'config' authentication (``$auth\_type = 'config'``) is the plain old
  way: username and password are stored in *config.inc.php*.
* 'cookie' authentication mode (``$auth\_type = 'cookie'``) as
  introduced in 2.2.3 allows you to log in as any valid MySQL user with
  the help of cookies. Username and password are stored in cookies
  during the session and password is deleted when it ends. This can also
  allow you to log in in arbitrary server if  enabled.
* ':abbr:`HTTP (HyperText Transfer Protocol)`' authentication (was
  called 'advanced' in previous versions and can be written also as
  'http') (``$auth\_type = ':abbr:`HTTP (HyperText Transfer
  Protocol)`'``) as introduced in 1.3.0 allows you to log in as any
  valid MySQL user via HTTP-Auth.
* 'signon' authentication mode (``$auth\_type = 'signon'``) as
  introduced in 2.10.0 allows you to log in from prepared PHP session
  data or using supplied PHP script. This is useful for implementing
  single signon from another application. Sample way how to seed session
  is in signon example: ``examples/signon.php``. There is also
  alternative example using OpenID - ``examples/openid.php`` and example
  for scripts based solution - ``examples/signon-script.php``. You need
  to configure  or  and  to use this authentication method.

Please see the  on "Using authentication modes" for more information.

.. _servers_auth_http_realm:

.. _cfg_Servers_auth_http_realm:

$cfg['Servers'][$i]['auth\_http\_realm'] string
-----------------------------------------------

When using auth\_type = ':abbr:`HTTP (HyperText Transfer Protocol)`',
this field allows to define a custom :abbr:`HTTP (HyperText Transfer
Protocol)` Basic Auth Realm which will be displayed to the user. If
not explicitly specified in your configuration, a string combined of
"phpMyAdmin " and either  or  will be used.

.. _servers_auth_swekey_config:

.. _cfg_Servers_auth_swekey_config:

$cfg['Servers'][$i]['auth\_swekey\_config'] string
--------------------------------------------------

The name of the file containing  ids and login names for hardware
authentication. Leave empty to deactivate this feature.

.. _servers_user:

.. _cfg_Servers_user:

.. _cfg_Servers_password:

$cfg['Servers'][$i]['user'] string $cfg['Servers'][$i]['password'] string
-------------------------------------------------------------------------

When using auth\_type = 'config', this is the user/password-pair which
phpMyAdmin will use to connect to the MySQL server. This user/password
pair is not needed when :abbr:`HTTP (HyperText Transfer Protocol)` or
cookie authentication is used and should be empty.

.. _servers_nopassword:

.. _cfg_Servers_nopassword:

$cfg['Servers'][$i]['nopassword'] boolean
-----------------------------------------

Allow attempt to log in without password when a login with password
fails. This can be used together with http authentication, when
authentication is done some other way and phpMyAdmin gets user name
from auth and uses empty password for connecting to MySQL. Password
login is still tried first, but as fallback, no password method is
tried.

.. _servers_only_db:

.. _cfg_Servers_only_db:

$cfg['Servers'][$i]['only\_db'] string or array
-----------------------------------------------

If set to a (an array of) database name(s), only this (these)
database(s) will be shown to the user. Since phpMyAdmin 2.2.1,
this/these database(s) name(s) may contain MySQL wildcards characters
("\_" and "%"): if you want to use literal instances of these
characters, escape them (I.E. use ``'my\\_db'`` and not ``'my\_db'``).
This setting is an efficient way to lower the server load since the
latter does not need to send MySQL requests to build the available
database list. But **it does not replace the privileges rules of the
MySQL database server**. If set, it just means only these databases
will be displayed but **not that all other databases can't be used.**
An example of using more that one database:
``$cfg['Servers'][$i]['only\_db'] = array('db1', 'db2');``  As of
phpMyAdmin 2.5.5 the order inside the array is used for sorting the
databases in the navigation panel, so that you can individually
arrange your databases. If you want to have certain databases at the
top, but don't care about the others, you do not need to specify all
other databases. Use: ``$cfg['Servers'][$i]['only\_db'] = array('db3',
'db4', '\*');`` instead to tell phpMyAdmin that it should display db3
and db4 on top, and the rest in alphabetic order.


.. _cfg_Servers_hide_db:

$cfg['Servers'][$i]['hide\_db'] string
--------------------------------------

Regular expression for hiding some databases from unprivileged users.
This only hides them from listing, but a user is still able to access
them (using, for example, the SQL query area). To limit access, use
the MySQL privilege system.  For example, to hide all databases
starting with the letter "a", use

.. code-block:: none

    $cfg['Servers'][$i]['hide_db'] = '^a';

and to hide both "db1" and "db2" use

.. code-block:: none

    $cfg['Servers'][$i]['hide_db'] = '^(db1|db2)$';

More information on regular expressions can be found in the `PCRE
pattern syntax
<http://php.net/manual/en/reference.pcre.pattern.syntax.php>`_ portion
of the PHP reference manual.

.. _cfg_Servers_verbose:

$cfg['Servers'][$i]['verbose'] string
-------------------------------------

Only useful when using phpMyAdmin with multiple server entries. If
set, this string will be displayed instead of the hostname in the
pull-down menu on the main page. This can be useful if you want to
show only certain databases on your system, for example. For HTTP
auth, all non-US-ASCII characters will be stripped.

.. _pmadb:

.. _cfg_Servers_pmadb:

$cfg['Servers'][$i]['pmadb'] string
-----------------------------------

The name of the database containing the phpMyAdmin configuration
storage.  See the  section in this document to see the benefits of
this feature, and for a quick way of creating this database and the
needed tables.  If you are the only user of this phpMyAdmin
installation, you can use your current database to store those special
tables; in this case, just put your current database name in
``$cfg['Servers'][$i]['pmadb']``. For a multi-user installation, set
this parameter to the name of your central database containing the
phpMyAdmin configuration storage.

.. _bookmark:

.. _cfg_Servers_bookmarktable:

$cfg['Servers'][$i]['bookmarktable'] string
-------------------------------------------

Since release 2.2.0 phpMyAdmin allows users to bookmark queries. This
can be useful for queries you often run. To allow the usage of this
functionality:

* set up  and the phpMyAdmin configuration storage
* enter the table name in ``$cfg['Servers'][$i]['bookmarktable']``



.. _relation:

.. _cfg_Servers_relation:

$cfg['Servers'][$i]['relation'] string
--------------------------------------

Since release 2.2.4 you can describe, in a special 'relation' table,
which column is a key in another table (a foreign key). phpMyAdmin
currently uses this to

* make clickable, when you browse the master table, the data values that
  point to the foreign table;
* display in an optional tool-tip the "display column" when browsing the
  master table, if you move the mouse to a column containing a foreign
  key (use also the 'table\_info' table); (see :ref:`faqdisplay`)
* in edit/insert mode, display a drop-down list of possible foreign keys
  (key value and "display column" are shown) (see :ref:`faq6_21`)
* display links on the table properties page, to check referential
  integrity (display missing foreign keys) for each described key;
* in query-by-example, create automatic joins (see :ref:`faq6_6`)
* enable you to get a :abbr:`PDF (Portable Document Format)` schema of
  your database (also uses the table\_coords table).

The keys can be numeric or character. To allow the usage of this
functionality:

* set up  and the phpMyAdmin configuration storage
* put the relation table name in ``$cfg['Servers'][$i]['relation']``
* now as normal user open phpMyAdmin and for each one of your tables
  where you want to use this feature, click "Structure/Relation view/"
  and choose foreign columns.

Please note that in the current version, ``master\_db`` must be the
same as ``foreign\_db``. Those columns have been put in future
development of the cross-db relations.

.. _table_info:

.. _cfg_Servers_table_info:

$cfg['Servers'][$i]['table\_info'] string
-----------------------------------------

Since release 2.3.0 you can describe, in a special 'table\_info'
table, which column is to be displayed as a tool-tip when moving the
cursor over the corresponding key. This configuration variable will
hold the name of this special table. To allow the usage of this
functionality:

* set up  and the phpMyAdmin configuration storage
* put the table name in ``$cfg['Servers'][$i]['table\_info']`` (e.g.
  'pma\_table\_info')
* then for each table where you want to use this feature, click
  "Structure/Relation view/Choose column to display" to choose the
  column.

Usage tip: .

.. _table_coords:

.. _cfg_Servers_table_coords:

.. _cfg_Servers_pdf_pages:

$cfg['Servers'][$i]['table\_coords'] string $cfg['Servers'][$i]['pdf\_pages'] string
------------------------------------------------------------------------------------

Since release 2.3.0 you can have phpMyAdmin create :abbr:`PDF
(Portable Document Format)` pages showing the relations between your
tables. To do this it needs two tables "pdf\_pages" (storing
information about the available :abbr:`PDF (Portable Document Format)`
pages) and "table\_coords" (storing coordinates where each table will
be placed on a :abbr:`PDF (Portable Document Format)` schema output).
You must be using the "relation" feature. To allow the usage of this
functionality:

* set up  and the phpMyAdmin configuration storage
* put the correct table names in
  ``$cfg['Servers'][$i]['table\_coords']`` and
  ``$cfg['Servers'][$i]['pdf\_pages']``

Usage tips: .

.. _col_com:

.. _cfg_Servers_column_info:

$cfg['Servers'][$i]['column\_info'] string
------------------------------------------

This part requires a content update!  Since release 2.3.0 you can
store comments to describe each column for each table. These will then
be shown on the "printview".  Starting with release 2.5.0, comments
are consequently used on the table property pages and table browse
view, showing up as tool-tips above the column name (properties page)
or embedded within the header of table in browse view. They can also
be shown in a table dump. Please see the relevant configuration
directives later on. Also new in release 2.5.0 is a MIME-
transformation system which is also based on the following table
structure. See  for further information. To use the MIME-
transformation system, your column\_info table has to have the three
new columns 'mimetype', 'transformation', 'transformation\_options'.
To allow the usage of this functionality:

* set up  and the phpMyAdmin configuration storage
* put the table name in ``$cfg['Servers'][$i]['column\_info']`` (e.g.
  'pma\_column\_info')
* to update your PRE-2.5.0 Column\_comments Table use this:  and
  remember that the Variable in *config.inc.php* has been renamed from
  ``$cfg['Servers'][$i]['column\_comments']`` to
  ``$cfg['Servers'][$i]['column\_info']``

  .. code-block:: none

       
       ALTER TABLE `pma_column_comments`
       ADD `mimetype` VARCHAR( 255 ) NOT NULL,
       ADD `transformation` VARCHAR( 255 ) NOT NULL,
       ADD `transformation_options` VARCHAR( 255 ) NOT NULL;





.. _history:

.. _cfg_Servers_history:

$cfg['Servers'][$i]['history'] string
-------------------------------------

Since release 2.5.0 you can store your :abbr:`SQL (structured query
language)` history, which means all queries you entered manually into
the phpMyAdmin interface. If you don't want to use a table-based
history, you can use the JavaScript-based history. Using that, all
your history items are deleted when closing the window. Using  you can
specify an amount of history items you want to have on hold. On every
login, this list gets cut to the maximum amount. The query history is
only available if JavaScript is enabled in your browser. To allow the
usage of this functionality:

* set up  and the phpMyAdmin configuration storage
* put the table name in ``$cfg['Servers'][$i]['history']`` (e.g.
  'pma\_history')



.. _recent:

.. _cfg_Servers_recent:

$cfg['Servers'][$i]['recent'] string
------------------------------------

Since release 3.5.0 you can show recently used tables in the
navigation panel. It helps you to jump across table directly, without
the need to select the database, and then select the table. Using  you
can configure the maximum number of recent tables shown. When you
select a table from the list, it will jump to the page specified in .
Without configuring the storage, you can still access the recently
used tables, but it will disappear after you logout. To allow the
usage of this functionality persistently:

* set up  and the phpMyAdmin configuration storage
* put the table name in ``$cfg['Servers'][$i]['recent']`` (e.g.
  'pma\_recent')



.. _table_uiprefs:

.. _cfg_Servers_table_uiprefs:

$cfg['Servers'][$i]['table\_uiprefs'] string
--------------------------------------------

Since release 3.5.0 phpMyAdmin can be configured to remember several
things (sorted column  , column order, and column visibility from a
database table) for browsing tables. Without configuring the storage,
these features still can be used, but the values will disappear after
you logout. To allow the usage of these functionality persistently:

* set up  and the phpMyAdmin configuration storage
* put the table name in ``$cfg['Servers'][$i]['table\_uiprefs']`` (e.g.
  'pma\_table\_uiprefs')



.. _tracking:

.. _cfg_Servers_tracking:

$cfg['Servers'][$i]['tracking'] string
--------------------------------------

Since release 3.3.x a tracking mechanism is available. It helps you to
track every :abbr:`SQL (structured query language)` command which is
executed by phpMyAdmin. The mechanism supports logging of data
manipulation and data definition statements. After enabling it you can
create versions of tables.  The creation of a version has two effects:

* phpMyAdmin saves a snapshot of the table, including structure and
  indexes.
* phpMyAdmin logs all commands which change the structure and/or data of
  the table and links these commands with the version number.

Of course you can view the tracked changes. On the "Tracking" page a
complete report is available for every version. For the report you can
use filters, for example you can get a list of statements within a
date range. When you want to filter usernames you can enter \* for all
names or you enter a list of names separated by ','. In addition you
can export the (filtered) report to a file or to a temporary database.
To allow the usage of this functionality:

* set up  and the phpMyAdmin configuration storage
* put the table name in ``$cfg['Servers'][$i]['tracking']`` (e.g.
  'pma\_tracking')



.. _tracking2:

.. _cfg_Servers_tracking_version_auto_create:

$cfg['Servers'][$i]['tracking\_version\_auto\_create'] boolean
--------------------------------------------------------------

Whether the tracking mechanism creates versions for tables and views
automatically. Default value is false.  If this is set to true and you
create a table or view with

* CREATE TABLE ...
* CREATE VIEW ...

and no version exists for it, the mechanism will create a version for
you automatically.

.. _tracking3:

.. _cfg_Servers_tracking_default_statements:

$cfg['Servers'][$i]['tracking\_default\_statements'] string
-----------------------------------------------------------

Defines the list of statements the auto-creation uses for new
versions. Default value is

.. code-block:: none

    CREATE TABLE,ALTER TABLE,DROP TABLE,RENAME TABLE,
    CREATE INDEX,DROP INDEX,
    INSERT,UPDATE,DELETE,TRUNCATE,REPLACE,
    CREATE VIEW,ALTER VIEW,DROP VIEW,
    CREATE DATABASE,ALTER DATABASE,DROP DATABASE



.. _tracking4:

.. _cfg_Servers_tracking_add_drop_view:

$cfg['Servers'][$i]['tracking\_add\_drop\_view'] boolean
--------------------------------------------------------

Whether a DROP VIEW IF EXISTS statement will be added as first line to
the log when creating a view. Default value is true.

.. _tracking5:

.. _cfg_Servers_tracking_add_drop_table:

$cfg['Servers'][$i]['tracking\_add\_drop\_table'] boolean
---------------------------------------------------------

Whether a DROP TABLE IF EXISTS statement will be added as first line
to the log when creating a table. Default value is true.

.. _tracking6:

.. _cfg_Servers_tracking_add_drop_database:

$cfg['Servers'][$i]['tracking\_add\_drop\_database'] boolean
------------------------------------------------------------

Whether a DROP DATABASE IF EXISTS statement will be added as first
line to the log when creating a database. Default value is true.

.. _userconfig:

.. _cfg_Servers_userconfig:

$cfg['Servers'][$i]['userconfig'] string
----------------------------------------

Since release 3.4.x phpMyAdmin allows users to set most preferences by
themselves and store them in the database.  If you don't allow for
storing preferences in , users can still personalize phpMyAdmin, but
settings will be saved in browser's local storage, or, it is is
unavailable, until the end of session.  To allow the usage of this
functionality:

* set up  and the phpMyAdmin configuration storage
* put the table name in ``$cfg['Servers'][$i]['userconfig']``



.. _designer_coords:

.. _cfg_Servers_designer_coords:

$cfg['Servers'][$i]['designer\_coords'] string
----------------------------------------------

Since release 2.10.0 a Designer interface is available; it permits to
visually manage the relations.  To allow the usage of this
functionality:

* set up  and the phpMyAdmin configuration storage
* put the table name in ``$cfg['Servers'][$i]['designer\_coords']``
  (e.g. 'pma\_designer\_coords')




.. _cfg_Servers_MaxTableUiprefs:

$cfg['Servers'][$i]['MaxTableUiprefs'] integer
----------------------------------------------

Maximum number of rows saved in  table. When tables are dropped or
renamed, table\_uiprefs may contain invalid data (referring to tables
which no longer exist). We only keep this number of newest rows in
table\_uiprefs and automatically delete older rows.


.. _cfg_Servers_AllowRoot:

$cfg['Servers'][$i]['AllowRoot'] boolean
----------------------------------------

Whether to allow root access. This is just a shortcut for the
AllowDeny rules below.


.. _cfg_Servers_AllowNoPassword:

$cfg['Servers'][$i]['AllowNoPassword'] boolean
----------------------------------------------

Whether to allow logins without a password. The default value of
``false`` for this parameter prevents unintended access to a MySQL
server with was left with an empty password for root or on which an
anonymous (blank) user is defined.

.. _servers_allowdeny_order:

.. _cfg_Servers_AllowDeny_order:

$cfg['Servers'][$i]['AllowDeny']['order'] string
------------------------------------------------

If your rule order is empty, then :abbr:`IP (Internet Protocol)`
authorization is disabled. If your rule order is set to
``'deny,allow'`` then the system applies all deny rules followed by
allow rules. Access is allowed by default. Any client which does not
match a Deny command or does match an Allow command will be allowed
access to the server.  If your rule order is set to ``'allow,deny'``
then the system applies all allow rules followed by deny rules. Access
is denied by default. Any client which does not match an Allow
directive or does match a Deny directive will be denied access to the
server. If your rule order is set to 'explicit', authorization is
performed in a similar fashion to rule order 'deny,allow', with the
added restriction that your host/username combination **must** be
listed in the *allow* rules, and not listed in the *deny* rules. This
is the **most** secure means of using Allow/Deny rules, and was
available in Apache by specifying allow and deny rules without setting
any order. Please also see  for detecting IP address behind proxies.

.. _servers_allowdeny_rules:

.. _cfg_Servers_AllowDeny_rules:

$cfg['Servers'][$i]['AllowDeny']['rules'] array of strings
----------------------------------------------------------

The general format for the rules is as such:

.. code-block:: none

    
    <'allow' | 'deny'> <username> [from] <ipmask>

If you wish to match all users, it is possible to use a ``'%'`` as a
wildcard in the *username* field. There are a few shortcuts you can
use in the *ipmask* field as well (please note that those containing
SERVER\_ADDRESS might not be available on all webservers):

.. code-block:: none

    
    'all' -> 0.0.0.0/0
    'localhost' -> 127.0.0.1/8
    'localnetA' -> SERVER_ADDRESS/8
    'localnetB' -> SERVER_ADDRESS/16
    'localnetC' -> SERVER_ADDRESS/24

Having an empty rule list is equivalent to either using ``'allow %
from all'`` if your rule order is set to ``'deny,allow'`` or ``'deny %
from all'`` if your rule order is set to ``'allow,deny'`` or
``'explicit'``. For the :abbr:`IP (Internet Protocol)` matching
system, the following work: ``xxx.xxx.xxx.xxx`` (an exact :abbr:`IP
(Internet Protocol)` address) ``xxx.xxx.xxx.[yyy-zzz]`` (an :abbr:`IP
(Internet Protocol)` address range) ``xxx.xxx.xxx.xxx/nn`` (CIDR,
Classless Inter-Domain Routing type :abbr:`IP (Internet Protocol)`
addresses) But the following does not work: ``xxx.xxx.xxx.xx[yyy-
zzz]`` (partial :abbr:`IP (Internet Protocol)` address range) Also
IPv6 addresses are not supported.


.. _cfg_Servers_DisableIS:

$cfg['Servers'][$i]['DisableIS'] boolean
----------------------------------------

Disable using ``INFORMATION\_SCHEMA`` to retrieve information (use
``SHOW`` commands instead), because of speed issues when many
databases are present. Currently used in some parts of the code, more
to come.


.. _cfg_Servers_ShowDatabasesCommand:

$cfg['Servers'][$i]['ShowDatabasesCommand'] string
--------------------------------------------------

On a server with a huge number of databases, the default ``SHOW
DATABASES`` command used to fetch the name of available databases will
probably be too slow, so it can be replaced by faster commands (see
``libraries/config.default.php`` for examples).


.. _cfg_Servers_CountTables:

$cfg['Servers'][$i]['CountTables'] boolean
------------------------------------------

Whether to count the number of tables for each database when preparing
the list of databases for the navigation panel.


.. _cfg_Servers_SignonScript:

$cfg['Servers'][$i]['SignonScript'] string
------------------------------------------

Name of PHP script to be sourced and executed to obtain login
credentials. This is alternative approach to session based single
signon. The script needs to provide function
``get\_login\_credentials`` which returns list of username and
password, accepting single parameter of existing username (can be
empty). See ``examples/signon-script.php`` for an example.


.. _cfg_Servers_SignonSession:

$cfg['Servers'][$i]['SignonSession'] string
-------------------------------------------

Name of session which will be used for signon authentication method.
You should use something different than ``phpMyAdmin``, because this
is session which phpMyAdmin uses internally. Takes effect only if  is
not configured.


.. _cfg_Servers_SignonURL:

$cfg['Servers'][$i]['SignonURL'] string
---------------------------------------

:abbr:`URL (Uniform Resource Locator)` where user will be redirected
to log in for signon authentication method. Should be absolute
including protocol.


.. _cfg_Servers_LogoutURL:

$cfg['Servers'][$i]['LogoutURL'] string
---------------------------------------

:abbr:`URL (Uniform Resource Locator)` where user will be redirected
after logout (doesn't affect config authentication method). Should be
absolute including protocol.


.. _cfg_Servers_StatusCacheDatabases:

$cfg['Servers'][$i]['StatusCacheDatabases'] array of strings
------------------------------------------------------------

Enables caching of ``TABLE STATUS`` outputs for specific databases on
this server (in some cases ``TABLE STATUS`` can be very slow, so you
may want to cache it). APC is used (if the PHP extension is available,
if not, this setting is ignored silently). You have to provide . Takes
effect only if  is ``true``.


.. _cfg_Servers_StatusCacheLifetime:

$cfg['Servers'][$i]['StatusCacheLifetime'] integer
--------------------------------------------------

Lifetime in seconds of the ``TABLE STATUS`` cache if  is used.

.. _cfg_ServerDefault:

$cfg['ServerDefault'] integer
-----------------------------

If you have more than one server configured, you can set
``$cfg['ServerDefault']`` to any one of them to autoconnect to that
server when phpMyAdmin is started, or set it to 0 to be given a list
of servers without logging in. If you have only one server configured,
``$cfg['ServerDefault']`` MUST be set to that server.

.. _cfg_AjaxEnable:

$cfg['AjaxEnable'] boolean
--------------------------

Defines whether to refresh only parts of certain pages using Ajax
techniques. Applies only where a non-Ajax behavior is possible; for
example, the Designer feature is Ajax-only so this directive does not
apply to it.

.. _cfg_VersionCheck:

$cfg['VersionCheck'] boolean
----------------------------

Enables check for latest versions using javascript on main phpMyAdmin
page.

.. _cfg_MaxDbList:

$cfg['MaxDbList'] integer
-------------------------

The maximum number of database names to be displayed in the database
list.

.. _cfg_MaxNavigationItems:

$cfg['MaxNavigationItems'] integer
----------------------------------

The number of items that can be displayed on each page of the
navigation tree.

.. _cfg_MaxTableList:

$cfg['MaxTableList'] integer
----------------------------

The maximum number of table names to be displayed in the main panel's
list (except on the Export page). This limit is also enforced in the
navigation panel when in Light mode.

.. _cfg_ShowHint:

$cfg['ShowHint'] boolean
------------------------

Whether or not to show hints (for example, hints when hovering over
table headers).

.. _cfg_MaxCharactersInDisplayedSQL:

$cfg['MaxCharactersInDisplayedSQL'] integer
-------------------------------------------

The maximum number of characters when a :abbr:`SQL (structured query
language)` query is displayed. The default limit of 1000 should be
correct to avoid the display of tons of hexadecimal codes that
represent BLOBs, but some users have real :abbr:`SQL (structured query
language)` queries that are longer than 1000 characters. Also, if a
query's length exceeds this limit, this query is not saved in the
history.

.. _cfg_OBGzip:

$cfg['OBGzip'] string/boolean
-----------------------------

Defines whether to use GZip output buffering for increased speed in
:abbr:`HTTP (HyperText Transfer Protocol)` transfers. Set to
true/false for enabling/disabling. When set to 'auto' (string),
phpMyAdmin tries to enable output buffering and will automatically
disable it if your browser has some problems with buffering. IE6 with
a certain patch is known to cause data corruption when having enabled
buffering.

.. _cfg_PersistentConnections:

$cfg['PersistentConnections'] boolean
-------------------------------------

Whether `persistent connections <http://php.net/manual/en/features
.persistent-connections.php>`_ should be used or not. Works with
following extensions:

* mysql (`mysql\_pconnect <http://php.net/manual/en/function.mysql-
  pconnect.php>`_),
* mysqli (requires PHP 5.3.0 or newer, `more information
  <http://php.net/manual/en/mysqli.persistconns.php>`_).



.. _cfg_ForceSSL:

$cfg['ForceSSL'] boolean
------------------------

Whether to force using https while accessing phpMyAdmin.

.. _cfg_ExecTimeLimit:

$cfg['ExecTimeLimit'] integer [number of seconds]
-------------------------------------------------

Set the number of seconds a script is allowed to run. If seconds is
set to zero, no time limit is imposed. This setting is used while
importing/exporting dump files and in the Synchronize feature but has
no effect when PHP is running in safe mode.

.. _cfg_SessionSavePath:

$cfg['SessionSavePath'] string
------------------------------

Path for storing session data (`session\_save\_path PHP parameter
<http://php.net/session_save_path>`_).

.. _cfg_MemoryLimit:

$cfg['MemoryLimit'] string [number of bytes]
--------------------------------------------

Set the number of bytes a script is allowed to allocate. If set to
zero, no limit is imposed. This setting is used while
importing/exporting dump files and at some other places in phpMyAdmin
so you definitely don't want to put here a too low value. It has no
effect when PHP is running in safe mode. You can also use any string
as in php.ini, eg. '16M'. Ensure you don't omit the suffix (16 means
16 bytes!)

.. _cfg_SkipLockedTables:

$cfg['SkipLockedTables'] boolean
--------------------------------

Mark used tables and make it possible to show databases with locked
tables (since MySQL 3.23.30).

.. _cfg_ShowSQL:

$cfg['ShowSQL'] boolean
-----------------------

Defines whether :abbr:`SQL (structured query language)` queries
generated by phpMyAdmin should be displayed or not.

.. _cfg_RetainQueryBox:

$cfg['RetainQueryBox'] boolean
------------------------------

Defines whether the :abbr:`SQL (structured query language)` query box
should be kept displayed after its submission.

.. _cfg_CodemirrorEnable:

$cfg['CodemirrorEnable'] boolean
--------------------------------

Defines whether to use a Javascript code editor for SQL query boxes.
CodeMirror provides syntax highlighting and line numbers.  However,
middle-clicking for pasting the clipboard contents in some Linux
distributions (such as Ubuntu) is not supported by all browsers.

.. _cfg_AllowUserDropDatabase:

$cfg['AllowUserDropDatabase'] boolean
-------------------------------------

Defines whether normal users (non-administrator) are allowed to delete
their own database or not. If set as FALSE, the link "Drop Database"
will not be shown, and even a "DROP DATABASE mydatabase" will be
rejected. Quite practical for :abbr:`ISP (Internet service
provider)`'s with many customers. Please note that this limitation of
:abbr:`SQL (structured query language)` queries is not as strict as
when using MySQL privileges. This is due to nature of :abbr:`SQL
(structured query language)` queries which might be quite complicated.
So this choice should be viewed as help to avoid accidental dropping
rather than strict privilege limitation.

.. _cfg_Confirm:

$cfg['Confirm'] boolean
-----------------------

Whether a warning ("Are your really sure...") should be displayed when
you're about to lose data.

.. _cfg_LoginCookieRecall:

$cfg['LoginCookieRecall'] boolean
---------------------------------

Define whether the previous login should be recalled or not in cookie
authentication mode. This is automatically disabled if you do not have
configured .

.. _cfg_LoginCookieValidity:

$cfg['LoginCookieValidity'] integer [number of seconds]
-------------------------------------------------------

Define how long is login cookie valid. Please note that php
configuration option `session.gc\_maxlifetime
<http://php.net/manual/en/session.configuration.php#ini.session.gc-
maxlifetime>`_ might limit session validity and if session is lost,
login cookie is also invalidated. So it is a good idea to set
``session.gc\_maxlifetime`` not lower than the value of
$cfg['LoginCookieValidity'].

.. _cfg_LoginCookieStore:

$cfg['LoginCookieStore'] integer [number of seconds]
----------------------------------------------------

Define how long login cookie should be stored in browser. Default 0
means that it will be kept for existing session. This is recommended
for not trusted environments.

.. _cfg_LoginCookieDeleteAll:

$cfg['LoginCookieDeleteAll'] boolean
------------------------------------

If enabled (default), logout deletes cookies for all servers,
otherwise only for current one. Setting this to false makes it easy to
forget to log out from other server, when you are using more of them.

.. _cfg_UseDbSearch:

$cfg['UseDbSearch'] boolean
---------------------------

Define whether the "search string inside database" is enabled or not.

.. _cfg_IgnoreMultiSubmitErrors:

$cfg['IgnoreMultiSubmitErrors'] boolean
---------------------------------------

Define whether phpMyAdmin will continue executing a multi-query
statement if one of the queries fails. Default is to abort execution.

.. _AllowArbitraryServer:

.. _cfg_AllowArbitraryServer:

$cfg['AllowArbitraryServer'] boolean
------------------------------------

If enabled, allows you to log in to arbitrary servers using cookie
auth and permits to specify servers of your choice in the Synchronize
dialog.  **NOTE:** Please use this carefully, as this may allow users
access to MySQL servers behind the firewall where your :abbr:`HTTP
(HyperText Transfer Protocol)` server is placed.

.. _cfg_Error_Handler_display:

$cfg['Error\_Handler']['display'] boolean
-----------------------------------------

Whether to display errors from PHP or not.

.. _cfg_Error_Handler_gather:

$cfg['Error\_Handler']['gather'] boolean
----------------------------------------

Whether to gather errors from PHP or not.

.. _cfg_NavigationTreeEnableGrouping:

$cfg['NavigationTreeEnableGrouping'] boolean
--------------------------------------------

Defines whether to group the databases based on a common prefix prefix
in their name .

.. _cfg_NavigationTreeDbSeparator:

$cfg['NavigationTreeDbSeparator'] string or array
-------------------------------------------------

The string used to separate the parts of the database name when
showing them in a tree. Alternatively you can specify more strings in
an array and all of them will be used as a separator.

.. _cfg_NavigationTreeTableSeparator:

$cfg['NavigationTreeTableSeparator'] string or array
----------------------------------------------------

Defines a string to be used to nest table spaces. Defaults to '\_\_'.
This means if you have tables like 'first\_\_second\_\_third' this
will be shown as a three-level hierarchy like: first > second > third.
If set to FALSE or empty, the feature is disabled. NOTE: You should
not use this separator at the beginning or end of a table name or
multiple times after another without any other characters in between.

.. _cfg_NavigationTreeTableLevel:

$cfg['NavigationTreeTableLevel'] integer
----------------------------------------

Defines how many sublevels should be displayed when splitting up
tables by the above separator.

.. _cfg_NumRecentTables:

$cfg['NumRecentTables'] integer
-------------------------------

The maximum number of recently used tables shown in the navigation
panel. Set this to 0 (zero) to disable the listing of recent tables.

.. _cfg_ShowTooltip:

$cfg['ShowTooltip'] boolean
---------------------------

Defines whether to display item comments as tooltips in navigation
panel or not.

.. _cfg_NavigationDisplayLogo:

$cfg['NavigationDisplayLogo'] boolean
-------------------------------------

Defines whether or not to display the phpMyAdmin logo at the top of
the navigation panel. Defaults to ``TRUE``.

.. _cfg_NavigationLogoLink:

$cfg['NavigationLogoLink'] string
---------------------------------

Enter :abbr:`URL (Uniform Resource Locator)` where logo in the
navigation panel will point to. For use especially with self made
theme which changes this. The default value for this is ``main.php``.

.. _cfg_NavigationLogoLinkWindow:

$cfg['NavigationLogoLinkWindow'] string
---------------------------------------

Whether to open the linked page in the main window (``main``) or in a
new one (``new``). Note: use ``new`` if you are linking to
``phpmyadmin.net``.

.. _cfg_NavigationTreeDisplayItemFilterMinimum:

$cfg['NavigationTreeDisplayItemFilterMinimum'] integer
------------------------------------------------------

Defines the minimum number of items (tables, views, routines and
events) to display a JavaScript filter box above the list of items in
the navigation tree. Defaults to ``30``. To disable the filter
completely some high number can be used (e.g. 9999)

.. _cfg_NavigationTreeDisplayDatabaseFilterMinimum:

$cfg['NavigationTreeDisplayDatabaseFilterMinimum'] integer
----------------------------------------------------------

Defines the minimum number of databases to display a JavaScript filter
box above the list of databases in the navigation tree. Defaults to
``30``. To disable the filter completely some high number can be used
(e.g. 9999)

.. _cfg_NavigationDisplayServers:

$cfg['NavigationDisplayServers'] boolean
----------------------------------------

Defines whether or not to display a server choice at the top of the
navigation panel. Defaults to FALSE.

.. _cfg_DisplayServersList:

$cfg['DisplayServersList'] boolean
----------------------------------

Defines whether to display this server choice as links instead of in a
drop-down. Defaults to FALSE (drop-down).

.. _cfg_NavigationTreeDefaultTabTable:

$cfg['NavigationTreeDefaultTabTable'] string
--------------------------------------------

Defines the tab displayed by default when clicking the small icon next
to each table name in the navigation panel. Possible values:
"tbl\_structure.php", "tbl\_sql.php", "tbl\_select.php",
"tbl\_change.php" or "sql.php".

.. _cfg_HideStructureActions:

$cfg['HideStructureActions'] boolean
------------------------------------

Defines whether the table structure actions are hidden under a "More"
drop-down.

.. _cfg_ShowStats:

$cfg['ShowStats'] boolean
-------------------------

Defines whether or not to display space usage and statistics about
databases and tables. Note that statistics requires at least MySQL
3.23.3 and that, at this date, MySQL doesn't return such information
for Berkeley DB tables.


.. _cfg_ShowServerInfo:

$cfg['ShowServerInfo']boolean
-----------------------------

Defines whether to display detailed server information on main page.
You can additionally hide more information by using .


.. _cfg_ShowPhpInfo:

.. _cfg_ShowChgPassword:

.. _cfg_ShowCreateDb:

$cfg['ShowPhpInfo']boolean $cfg['ShowChgPassword']boolean $cfg['ShowCreateDb']boolean
-------------------------------------------------------------------------------------

Defines whether to display the "PHP information" and "Change password
" links and form for creating database or not at the starting main
(right) frame. This setting does not check MySQL commands entered
directly. Please note that to block the usage of phpinfo() in scripts,
you have to put this in your *php.ini*:

.. code-block:: none

    disable_functions = phpinfo()

Also note that enabling the "Change password " link has no effect with
"config" authentication mode: because of the hard coded password value
in the configuration file, end users can't be allowed to change their
passwords.

.. _cfg_ShowDbStructureCreation:

$cfg['ShowDbStructureCreation'] boolean
---------------------------------------

Defines whether the database structure page (tables list) has a
"Creation" column that displays when each table was created.

.. _cfg_ShowDbStructureLastUpdate:

$cfg['ShowDbStructureLastUpdate'] boolean
-----------------------------------------

Defines whether the database structure page (tables list) has a "Last
update" column that displays when each table was last updated.

.. _cfg_ShowDbStructureLastCheck:

$cfg['cfg\_ShowDbStructureLastCheck'] boolean
---------------------------------------------

Defines whether the database structure page (tables list) has a "Last
check" column that displays when each table was last checked.

.. _cfg_NavigationBarIconic:

$cfg['NavigationBarIconic'] string
----------------------------------

Defines whether navigation bar buttons and the right panel top menu
contain text or symbols only. A value of TRUE displays icons, FALSE
displays text and 'both' displays both icons and text.

.. _cfg_ShowAll:

$cfg['ShowAll'] boolean
-----------------------

Defines whether a user should be displayed a "Show all" button in
browse mode or not in all cases. By default it is shown only on small
tables (less than 5 ×  rows) to avoid performance issues while getting
too many rows.

.. _cfg_MaxRows:

$cfg['MaxRows'] integer
-----------------------

Number of rows displayed when browsing a result set and no LIMIT
clause is used. If the result set contains more rows, "Previous" and
"Next" links will be shown.

.. _cfg_Order:

$cfg['Order'] string [``DESC``|``ASC``|``SMART``]
-------------------------------------------------

Defines whether columns are displayed in ascending (``ASC``) order, in
descending (``DESC``) order or in a "smart" (``SMART``) order - I.E.
descending order for columns of type TIME, DATE, DATETIME and
TIMESTAMP, ascending order else- by default.

.. _cfg_DisplayBinaryAsHex:

$cfg['DisplayBinaryAsHex'] boolean
----------------------------------

Defines whether the "Show binary contents as HEX" browse option is
ticked by default.

.. _cfg_GridEditing:

$cfg['GridEditing'] string
--------------------------

Defines which action (``double-click`` or ``click``) triggers grid
editing. Can be deactived with the ``disabled`` value.

.. _cfg_SaveCellsAtOnce:

$cfg['SaveCellsAtOnce'] boolean
-------------------------------

Defines whether or not to save all edited cells at once for grid
editing.

.. _cfg_ProtectBinary:

$cfg['ProtectBinary'] boolean or string
---------------------------------------

Defines whether ``BLOB`` or ``BINARY`` columns are protected from
editing when browsing a table's content. Valid values are:

* ``FALSE`` to allow editing of all columns;
* ``'blob'`` to allow editing of all columns except ``BLOBS``;
* ``'noblob'`` to disallow editing of all columns except ``BLOBS`` (the
  opposite of ``'blob'``);
* ``'all'`` to disallow editing of all ``BINARY`` or ``BLOB`` columns.



.. _cfg_ShowFunctionFields:

$cfg['ShowFunctionFields'] boolean
----------------------------------

Defines whether or not MySQL functions fields should be initially
displayed in edit/insert mode. Since version 2.10, the user can toggle
this setting from the interface.

.. _cfg_ShowFieldTypesInDataEditView:

$cfg['ShowFieldTypesInDataEditView'] boolean
--------------------------------------------

Defines whether or not type fields should be initially displayed in
edit/insert mode. The user can toggle this setting from the interface.

.. _cfg_CharEditing:

$cfg['CharEditing'] string
--------------------------

Defines which type of editing controls should be used for CHAR and
VARCHAR columns. Possible values are:

* input - this allows to limit size of text to size of columns in MySQL,
  but has problems with newlines in columns
* textarea - no problems with newlines in columns, but also no length
  limitations

Default is old behavior so input.

.. _cfg_MinSizeForInputField:

$cfg['MinSizeForInputField'] integer
------------------------------------

Defines the minimum size for input fields generated for CHAR and
VARCHAR columns.

.. _cfg_MaxSizeForInputField:

$cfg['MaxSizeForInputField'] integer
------------------------------------

Defines the maximum size for input fields generated for CHAR and
VARCHAR columns.

.. _cfg_InsertRows:

$cfg['InsertRows'] integer
--------------------------

Defines the maximum number of concurrent entries for the Insert page.

.. _cfg_ForeignKeyMaxLimit:

$cfg['ForeignKeyMaxLimit'] integer
----------------------------------

If there are fewer items than this in the set of foreign keys, then a
drop-down box of foreign keys is presented, in the style described by
the  setting.

.. _cfg_ForeignKeyDropdownOrder:

$cfg['ForeignKeyDropdownOrder'] array
-------------------------------------

For the foreign key drop-down fields, there are several methods of
display, offering both the key and value data. The contents of the
array should be one or both of the following strings: *'content-id'*,
*'id-content'*.


.. _cfg_ZipDump:

.. _cfg_GZipDump:

.. _cfg_BZipDump:

$cfg['ZipDump']boolean $cfg['GZipDump']boolean $cfg['BZipDump']boolean
----------------------------------------------------------------------

Defines whether to allow the use of zip/GZip/BZip2 compression when
creating a dump file


.. _cfg_CompressOnFly:

$cfg['CompressOnFly']boolean
----------------------------

Defines whether to allow on the fly compression for GZip/BZip2
compressed exports. This doesn't affect smaller dumps and allows users
to create larger dumps that won't otherwise fit in memory due to php
memory limit. Produced files contain more GZip/BZip2 headers, but all
normal programs handle this correctly.

.. _cfg_PropertiesIconic:

$cfg['PropertiesIconic'] string
-------------------------------

If set to ``TRUE``, will display icons instead of text for db and
table properties links (like 'Browse', 'Select', 'Insert', ...). Can
be set to ``'both'`` if you want icons AND text. When set to
``FALSE``, will only show text.

.. _cfg_PropertiesNumColumns:

$cfg['PropertiesNumColumns'] integer
------------------------------------

How many columns will be utilized to display the tables on the
database property view? Default is 1 column. When setting this to a
value larger than 1, the type of the database will be omitted for more
display space.

.. _cfg_DefaultTabServer:

$cfg['DefaultTabServer'] string
-------------------------------

Defines the tab displayed by default on server view. Possible values:
"main.php" (recommended for multi-user setups),
"server\_databases.php", "server\_status.php",
"server\_variables.php", "server\_privileges.php" or
"server\_processlist.php".

.. _cfg_DefaultTabDatabase:

$cfg['DefaultTabDatabase'] string
---------------------------------

Defines the tab displayed by default on database view. Possible
values: "db\_structure.php", "db\_sql.php" or "db\_search.php".

.. _cfg_DefaultTabTable:

$cfg['DefaultTabTable'] string
------------------------------

Defines the tab displayed by default on table view. Possible values:
"tbl\_structure.php", "tbl\_sql.php", "tbl\_select.php",
"tbl\_change.php" or "sql.php".

.. _cfg_MySQLManualBase:

$cfg['MySQLManualBase'] string
------------------------------

If set to an :abbr:`URL (Uniform Resource Locator)` which points to
the MySQL documentation (type depends on ), appropriate help links are
generated. See `MySQL Documentation page <http://dev.mysql.com/doc/>`_
for more information about MySQL manuals and their types.

.. _cfg_MySQLManualType:

$cfg['MySQLManualType'] string
------------------------------

Type of MySQL documentation:

* viewable - "viewable online", current one used on MySQL website
* searchable - "Searchable, with user comments"
* chapters - "HTML, one page per chapter"
* big - "HTML, all on one page"
* none - do not show documentation links



.. _cfg_DefaultLang:

$cfg['DefaultLang'] string
--------------------------

Defines the default language to use, if not browser-defined or user-
defined. The corresponding language file needs to be in
locale/*code*/LC\_MESSAGES/phpmyadmin.mo.

.. _cfg_DefaultConnectionCollation:

$cfg['DefaultConnectionCollation'] string
-----------------------------------------

Defines the default connection collation to use, if not user-defined.
See the `MySQL documentation <http://dev.mysql.com/doc/mysql/en
/charset-charsets.html>`_ for list of possible values. This setting is
ignored when connected to Drizzle server.

.. _cfg_Lang:

$cfg['Lang'] string
-------------------

Force language to use. The corresponding language file needs to be in
locale/*code*/LC\_MESSAGES/phpmyadmin.mo.

.. _cfg_FilterLanguages:

$cfg['FilterLanguages'] string
------------------------------

Limit list of available languages to those matching the given regular
expression. For example if you want only Czech and English, you should
set filter to ``'^(cs|en)'``.

.. _cfg_RecodingEngine:

$cfg['RecodingEngine'] string
-----------------------------

You can select here which functions will be used for character set
conversion. Possible values are:

* auto - automatically use available one (first is tested iconv, then
  recode)
* iconv - use iconv or libiconv functions
* recode - use recode\_string function
* none - disable encoding conversion

Default is auto.

Enabled charset conversion activates a pull-down menu in the Export
and Import pages, to choose the character set when exporting a file.
The default value in this menu comes from
``$cfg['Export']['charset']`` and ``$cfg['Import']['charset']``.

.. _cfg_IconvExtraParams:

$cfg['IconvExtraParams'] string
-------------------------------

Specify some parameters for iconv used in charset conversion. See
`iconv documentation <http://www.gnu.org/software/libiconv/documentati
on/libiconv/iconv_open.3.html>`_ for details. By default
``//TRANSLIT`` is used, so that invalid characters will be
transliterated.

.. _cfg_AvailableCharsets:

$cfg['AvailableCharsets'] array
-------------------------------

Available character sets for MySQL conversion. You can add your own
(any of supported by recode/iconv) or remove these which you don't
use. Character sets will be shown in same order as here listed, so if
you frequently use some of these move them to the top.

.. _cfg_TrustedProxies:

$cfg['TrustedProxies'] array
----------------------------

Lists proxies and HTTP headers which are trusted for . This list is by
default empty, you need to fill in some trusted proxy servers if you
want to use rules for IP addresses behind proxy. The following example
specifies that phpMyAdmin should trust a HTTP\_X\_FORWARDED\_FOR (``X
-Forwarded-For``) header coming from the proxy 1.2.3.4:

.. code-block:: none

    
    $cfg['TrustedProxies'] =
    array('1.2.3.4' => 'HTTP_X_FORWARDED_FOR');

The $cfg['Servers'][$i]['AllowDeny']['rules'] directive uses the
client's IP address as usual.

.. _cfg_GD2Available:

$cfg['GD2Available'] string
---------------------------

Specifies whether GD >= 2 is available. If yes it can be used for MIME
transformations. Possible values are:

* auto - automatically detect
* yes - GD 2 functions can be used
* no - GD 2 function cannot be used

Default is auto.

.. _cfg_CheckConfigurationPermissions:

$cfg['CheckConfigurationPermissions'] boolean
---------------------------------------------

We normally check the permissions on the configuration file to ensure
it's not world writable. However, phpMyAdmin could be installed on a
NTFS filesystem mounted on a non-Windows server, in which case the
permissions seems wrong but in fact cannot be detected. In this case a
sysadmin would set this parameter to ``FALSE``. Default is ``TRUE``.

.. _cfg_LinkLengthLimit:

$cfg['LinkLengthLimit'] integer
-------------------------------

Limit for length of :abbr:`URL (Uniform Resource Locator)` in links.
When length would be above this limit, it is replaced by form with
button. This is required as some web servers (:abbr:`IIS (Internet
Information Services)`) have problems with long :abbr:`URL (Uniform
Resource Locator)`s. Default is ``1000``.

.. _cfg_DisableMultiTableMaintenance:

$cfg['DisableMultiTableMaintenance'] boolean
--------------------------------------------

In the database Structure page, it's possible to mark some tables then
choose an operation like optimizing for many tables. This can slow
down a server; therefore, setting this to ``true`` prevents this kind
of multiple maintenance operation. Default is ``false``.

.. _cfg_NaviWidth:

$cfg['NaviWidth'] integer
-------------------------

Navigation panel width in pixels. See
``themes/themename/layout.inc.php``.


.. _cfg_NaviBackground:

.. _cfg_MainBackground:

$cfg['NaviBackground'] string [CSS color for background] $cfg['MainBackground'] string [CSS color for background]
-----------------------------------------------------------------------------------------------------------------

The background styles used for both the frames. See
``themes/themename/layout.inc.php``.

.. _cfg_NaviPointerBackground:

.. _cfg_NaviPointerColor:

$cfg['NaviPointerBackground'] string [CSS color for background] $cfg['NaviPointerColor'] string [CSS color]
-----------------------------------------------------------------------------------------------------------

The style used for the pointer in the navi frame. See
``themes/themename/layout.inc.php``.

.. _cfg_NavigationTreePointerEnable:

$cfg['NavigationTreePointerEnable'] boolean
-------------------------------------------

A value of ``TRUE`` activates the navi pointer.

.. _cfg_Border:

$cfg['Border'] integer
----------------------

The size of a table's border. See ``themes/themename/layout.inc.php``.

.. _cfg_ThBackground:

.. _cfg_ThColor:

$cfg['ThBackground'] string [CSS color for background] $cfg['ThColor'] string [CSS color]
-----------------------------------------------------------------------------------------

The style used for table headers. See
``themes/themename/layout.inc.php``.

.. _cfg_BgcolorOne:

$cfg['BgOne'] string [CSS color]
--------------------------------

The color (HTML) #1 for table rows. See
``themes/themename/layout.inc.php``.

.. _cfg_BgcolorTwo:

$cfg['BgTwo'] string [CSS color]
--------------------------------

The color (HTML) #2 for table rows. See
``themes/themename/layout.inc.php``.


.. _cfg_BrowsePointerBackground:

.. _cfg_BrowsePointerColor:

.. _cfg_BrowseMarkerBackground:

.. _cfg_BrowseMarkerColor:

$cfg['BrowsePointerBackground']string [CSS color] $cfg['BrowsePointerColor']string [CSS color] $cfg['BrowseMarkerBackground']string [CSS color] $cfg['BrowseMarkerColor']string [CSS color]
-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

The colors (HTML) uses for the pointer and the marker in browse mode.
The former feature highlights the row over which your mouse is passing
and the latter lets you visually mark/unmark rows by clicking on the
corresponding checkbox. Highlighting / marking a column is done by
hovering over / clicking the column's header (outside of the text).
See ``themes/themename/layout.inc.php``.

.. _cfg_FontFamily:

$cfg['FontFamily'] string
-------------------------

You put here a valid CSS font family value, for example ``arial, sans-
serif``. See ``themes/themename/layout.inc.php``.

.. _cfg_FontFamilyFixed:

$cfg['FontFamilyFixed'] string
------------------------------

You put here a valid CSS font family value, for example ``monospace``.
This one is used in textarea. See ``themes/themename/layout.inc.php``.

.. _cfg_BrowsePointerEnable:

$cfg['BrowsePointerEnable'] boolean
-----------------------------------

Whether to activate the browse pointer or not.

.. _cfg_BrowseMarkerEnable:

$cfg['BrowseMarkerEnable'] boolean
----------------------------------

Whether to activate the browse marker or not.


.. _cfg_TextareaCols:

.. _cfg_TextareaRows:

.. _cfg_CharTextareaCols:

.. _cfg_CharTextareaRows:

$cfg['TextareaCols']integer $cfg['TextareaRows']integer $cfg['CharTextareaCols']integer $cfg['CharTextareaRows']integer
-----------------------------------------------------------------------------------------------------------------------

Number of columns and rows for the textareas. This value will be
emphasized (\*2) for :abbr:`SQL (structured query language)` query
textareas and (\*1.25) for :abbr:`SQL (structured query language)`
textareas inside the query window. The Char\* values are used for CHAR
and VARCHAR editing (if configured via ).


.. _cfg_LongtextDoubleTextarea:

$cfg['LongtextDoubleTextarea']boolean
-------------------------------------

Defines whether textarea for LONGTEXT columns should have double size.


.. _cfg_TextareaAutoSelect:

$cfg['TextareaAutoSelect']boolean
---------------------------------

Defines if the whole textarea of the query box will be selected on
click.

.. _cfg_LimitChars:

$cfg['LimitChars'] integer
--------------------------

Maximum number of characters shown in any non-numeric field on browse
view. Can be turned off by a toggle button on the browse page.


.. _cfg_RowActionLinks:

$cfg['RowActionLinks']string
----------------------------

Defines the place where table row links (Edit, Copy, Delete) would be
put when tables contents are displayed (you may have them displayed at
the left side, right side, both sides or nowhere). "left" and "right"
are parsed as "top" and "bottom" with vertical display mode.

.. _cfg_DefaultDisplay:

$cfg['DefaultDisplay'] string
-----------------------------

There are 3 display modes: horizontal, horizontalflipped and vertical.
Define which one is displayed by default. The first mode displays each
row on a horizontal line, the second rotates the headers by 90
degrees, so you can use descriptive headers even though columns only
contain small values and still print them out. The vertical mode sorts
each row on a vertical lineup.

.. _cfg_RememberSorting:

$cfg['RememberSorting'] boolean
-------------------------------

If enabled, remember the sorting of each table when browsing them.

.. _cfg_HeaderFlipType:

$cfg['HeaderFlipType'] string
-----------------------------

The HeaderFlipType can be set to 'auto', 'css' or 'fake'. When using
'css' the rotation of the header for horizontalflipped is done via
CSS. The CSS transformation currently works only in Internet
Explorer.If set to 'fake' PHP does the transformation for you, but of
course this does not look as good as CSS. The 'auto' option enables
CSS transformation when browser supports it and use PHP based one
otherwise.

.. _cfg_ShowBrowseComments:

.. _cfg_ShowPropertyComments:

$cfg['ShowBrowseComments'] boolean $cfg['ShowPropertyComments']boolean
----------------------------------------------------------------------

By setting the corresponding variable to ``TRUE`` you can enable the
display of column comments in Browse or Property display. In browse
mode, the comments are shown inside the header. In property mode,
comments are displayed using a CSS-formatted dashed-line below the
name of the column. The comment is shown as a tool-tip for that
column.

.. _cfg_SQLQuery_Edit:

$cfg['SQLQuery']['Edit'] boolean
--------------------------------

Whether to display an edit link to change a query in any SQL Query
box.

.. _cfg_SQLQuery_Explain:

$cfg['SQLQuery']['Explain'] boolean
-----------------------------------

Whether to display a link to explain a SELECT query in any SQL Query
box.

.. _cfg_SQLQuery_ShowAsPHP:

$cfg['SQLQuery']['ShowAsPHP'] boolean
-------------------------------------

Whether to display a link to wrap a query in PHP code in any SQL Query
box.

.. _cfg_SQLQuery_Validate:

$cfg['SQLQuery']['Validate'] boolean
------------------------------------

Whether to display a link to validate a query in any SQL Query box.
See also .

.. _cfg_SQLQuery_Refresh:

$cfg['SQLQuery']['Refresh'] boolean
-----------------------------------

Whether to display a link to refresh a query in any SQL Query box.

.. _cfg_UploadDir:

$cfg['UploadDir'] string
------------------------

The name of the directory where :abbr:`SQL (structured query
language)` files have been uploaded by other means than phpMyAdmin
(for example, ftp). Those files are available under a drop-down box
when you click the database or table name, then the Import tab.  If
you want different directory for each user, %u will be replaced with
username. Please note that the file names must have the suffix ".sql"
(or ".sql.bz2" or ".sql.gz" if support for compressed formats is
enabled). This feature is useful when your file is too big to be
uploaded via :abbr:`HTTP (HyperText Transfer Protocol)`, or when file
uploads are disabled in PHP. Please note that if PHP is running in
safe mode, this directory must be owned by the same user as the owner
of the phpMyAdmin scripts.  See also :ref:`faq1_16` for alternatives.

.. _cfg_SaveDir:

$cfg['SaveDir'] string
----------------------

The name of the directory where dumps can be saved. If you want
different directory for each user, %u will be replaced with username.
Please note that the directory must exist and has to be writable for
the user running webserver. Please note that if PHP is running in safe
mode, this directory must be owned by the same user as the owner of
the phpMyAdmin scripts.

.. _cfg_TempDir:

$cfg['TempDir'] string
----------------------

The name of the directory where temporary files can be stored.  This
is needed for importing ESRI Shapefiles, see :ref:`faq6_30` and to
work around limitations of ``open\_basedir`` for uploaded files, see
:ref:`faq1_11`.  If the directory where phpMyAdmin is installed is
subject to an ``open\_basedir`` restriction, you need to create a
temporary directory in some directory accessible by the web server.
However for security reasons, this directory should be outside the
tree published by webserver. If you cannot avoid having this directory
published by webserver, place at least an empty ``index.html`` file
there, so that directory listing is not possible.  This directory
should have as strict permissions as possible as the only user
required to access this directory is the one who runs the webserver.
If you have root privileges, simply make this user owner of this
directory and make it accessible only by it:

.. code-block:: none

    
    chown www-data:www-data tmp
    chmod 700 tmp

If you cannot change owner of the directory, you can achieve a similar
setup using :abbr:`ACL (Access Control List)`:

.. code-block:: none

    
    chmod 700 tmp
    setfacl -m "g:www-data:rwx" tmp
    setfacl -d -m "g:www-data:rwx" tmp

If neither of above works for you, you can still make the directory
``chmod 777``, but it might impose risk of other users on system
reading and writing data in this directory.

.. _cfg_Export:

$cfg['Export'] array
--------------------

In this array are defined default parameters for export, names of
items are similar to texts seen on export page, so you can easily
identify what they mean.

.. _cfg_Export_method:

$cfg['Export']['method'] string
-------------------------------

Defines how the export form is displayed when it loads. Valid values
are:

* ``quick`` to display the minimum number of options to configure
* ``custom`` to display every available option to configure
* ``custom-no-form`` same as ``custom`` but does not display the option
  of using quick export



.. _cfg_Import:

$cfg['Import'] array
--------------------

In this array are defined default parameters for import, names of
items are similar to texts seen on import page, so you can easily
identify what they mean.

.. _cfg_ShowDisplayDirection:

$cfg['ShowDisplayDirection'] boolean
------------------------------------

Defines whether or not type display direction option is shown when
browsing a table.

.. _cfg_RepeatCells:

$cfg['RepeatCells'] integer
---------------------------

Repeat the headers every X cells, or 0 to deactivate.

.. _cfg_EditInWindow:

.. _cfg_QueryWindowWidth:

.. _cfg_QueryWindowHeight:

.. _cfg_QueryHistoryDB:

.. _cfg_QueryWindowDefTab:

.. _cfg_QueryHistoryMax:

$cfg['EditInWindow'] boolean $cfg['QueryWindowWidth']integer $cfg['QueryWindowHeight']integer $cfg['QueryHistoryDB']boolean $cfg['QueryWindowDefTab']string $cfg['QueryHistoryMax']integer
------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

All those variables affect the query window feature. A ``:abbr:`SQL
(structured query language)``` link or icon is always displayed in the
navigation panel. If JavaScript is enabled in your browser, a click on
this opens a distinct query window, which is a direct interface to
enter :abbr:`SQL (structured query language)` queries. Otherwise, the
right panel changes to display a query box. The size of this query
window can be customized with ``$cfg['QueryWindowWidth']`` and
``$cfg['QueryWindowHeight']`` - both integers for the size in pixels.
Note that normally, those parameters will be modified in
``layout.inc.php`` for the theme you are using. If
``$cfg['EditInWindow']`` is set to true, a click on [Edit] from the
results page (in the "Showing Rows" section) opens the query window
and puts the current query inside it. If set to false, clicking on the
link puts the :abbr:`SQL (structured query language)` query in the
right panel's query box.  The usage of the JavaScript query window is
recommended if you have a JavaScript enabled browser. Basic functions
are used to exchange quite a few variables, so most 4th generation
browsers should be capable to use that feature. It currently is only
tested with Internet Explorer 6 and Mozilla 1.x.  If
``$cfg['QueryHistoryDB']`` is set to ``TRUE``, all your Queries are
logged to a table, which has to be created by you (see ). If set to
FALSE, all your queries will be appended to the form, but only as long
as your window is opened they remain saved.  When using the JavaScript
based query window, it will always get updated when you click on a new
table/db to browse and will focus if you click on "Edit :abbr:`SQL
(structured query language)`" after using a query. You can suppress
updating the query window by checking the box "Do not overwrite this
query from outside the window" below the query textarea. Then you can
browse tables/databases in the background without losing the contents
of the textarea, so this is especially useful when composing a query
with tables you first have to look in. The checkbox will get
automatically checked whenever you change the contents of the
textarea. Please uncheck the button whenever you definitely want the
query window to get updated even though you have made alterations.  If
``$cfg['QueryHistoryDB']`` is set to ``TRUE`` you can specify the
amount of saved history items using ``$cfg['QueryHistoryMax']``.  The
query window also has a custom tabbed look to group the features.
Using the variable ``$cfg['QueryWindowDefTab']`` you can specify the
default tab to be used when opening the query window. It can be set to
either 'sql', 'files', 'history' or 'full'.

.. _cfg_BrowseMIME:

$cfg['BrowseMIME'] boolean
--------------------------

Enable .

.. _cfg_MaxExactCount:

$cfg['MaxExactCount'] integer
-----------------------------

For InnoDB tables, determines for how large tables phpMyAdmin should
get the exact row count using ``SELECT COUNT``. If the approximate row
count as returned by ``SHOW TABLE STATUS`` is smaller than this value,
``SELECT COUNT`` will be used, otherwise the approximate count will be
used.

.. _cfg_MaxExactCountViews:

$cfg['MaxExactCountViews'] integer
----------------------------------

For VIEWs, since obtaining the exact count could have an impact on
performance, this value is the maximum to be displayed, using a
``SELECT COUNT ... LIMIT``. Setting this to 0 bypasses any row
counting.

.. _cfg_NaturalOrder:

$cfg['NaturalOrder'] boolean
----------------------------

Sorts database and table names according to natural order (for
example, t1, t2, t10). Currently implemented in the navigation panel
and in Database view, for the table list.

.. _cfg_InitialSlidersState:

$cfg['InitialSlidersState'] string
----------------------------------

If set to ``'closed'``, the visual sliders are initially in a closed
state. A value of ``'open'`` does the reverse. To completely disable
all visual sliders, use ``'disabled'``.

.. _cfg_UserprefsDisallow:

$cfg['UserprefsDisallow'] array
-------------------------------

Contains names of configuration options (keys in ``$cfg`` array) that
users can't set through user preferences. For possible values, refer
to ``libraries/config/user\_preferences.forms.php``.

.. _cfg_UserprefsDeveloperTab:

$cfg['UserprefsDeveloperTab'] boolean
-------------------------------------

Activates in the user preferences a tab containing options for
developers of phpMyAdmin.

.. _cfg_TitleTable:

$cfg['TitleTable'] string
-------------------------

.. _cfg_TitleDatabase:

$cfg['TitleDatabase'] string
----------------------------

.. _cfg_TitleServer:

$cfg['TitleServer'] string
--------------------------

.. _cfg_TitleDefault:

$cfg['TitleDefault'] string
---------------------------

Allows you to specify window's title bar. You can use .

.. _cfg_ThemePath:

$cfg['ThemePath'] string
------------------------

If theme manager is active, use this as the path of the subdirectory
containing all the themes.

.. _cfg_ThemeManager:

$cfg['ThemeManager'] boolean
----------------------------

Enables user-selectable themes. See :ref:`faqthemes`.

.. _cfg_ThemeDefault:

$cfg['ThemeDefault'] string
---------------------------

The default theme (a subdirectory under ``cfg['ThemePath']``).

.. _cfg_ThemePerServer:

$cfg['ThemePerServer'] boolean
------------------------------

Whether to allow different theme for each server.

.. _cfg_DefaultQueryTable:

.. _cfg_DefaultQueryDatabase:

$cfg['DefaultQueryTable'] string $cfg['DefaultQueryDatabase'] string
--------------------------------------------------------------------

Default queries that will be displayed in query boxes when user didn't
specify any. You can use standard .

.. _cfg_SQP_fmtType:

$cfg['SQP']['fmtType'] string [``html``|``none``]
-------------------------------------------------

The main use of the new :abbr:`SQL (structured query language)` Parser
is to pretty-print :abbr:`SQL (structured query language)` queries. By
default we use HTML to format the query, but you can disable this by
setting this variable to ``'none'``.

.. _cfg_SQP_fmtInd:

.. _cfg_SQP:

$cfg['SQP']['fmtInd'] float $cfg['SQP']['fmtIndUnit'] string [``em``|``px``|``pt``|``ex``]
------------------------------------------------------------------------------------------

For the pretty-printing of :abbr:`SQL (structured query language)`
queries, under some cases the part of a query inside a bracket is
indented. By changing ``$cfg['SQP']['fmtInd']`` you can change the
amount of this indent. Related in purpose is
``$cfg['SQP']['fmtIndUnit']`` which specifies the units of the indent
amount that you specified. This is used via stylesheets.

.. _cfg_SQP_fmtColor:

$cfg['SQP']['fmtColor'] array of string tuples
----------------------------------------------

This array is used to define the colours for each type of element of
the pretty-printed :abbr:`SQL (structured query language)` queries.
The tuple format is *class* => [*HTML colour code* | *empty string*]
If you specify an empty string for the color of a class, it is ignored
in creating the stylesheet. You should not alter the class names, only
the colour strings. **Class name key:**

* **comment** Applies to all comment sub-classes
* **comment\_mysql** Comments as ``"#...\n"``
* **comment\_ansi** Comments as ``"-- ...\n"``
* **comment\_c** Comments as ``"/\*...\*/"``
* **digit** Applies to all digit sub-classes
* **digit\_hex** Hexadecimal numbers
* **digit\_integer** Integer numbers
* **digit\_float** Floating point numbers
* **punct** Applies to all punctuation sub-classes
* **punct\_bracket\_open\_round** Opening brackets``"("``
* **punct\_bracket\_close\_round** Closing brackets ``")"``
* **punct\_listsep** List item Separator ``","``
* **punct\_qualifier** Table/Column Qualifier ``"."``
* **punct\_queryend** End of query marker ``";"``
* **alpha** Applies to all alphabetic classes
* **alpha\_columnType** Identifiers matching a column type
* **alpha\_columnAttrib** Identifiers matching a database/table/column
  attribute
* **alpha\_functionName** Identifiers matching a MySQL function name
* **alpha\_reservedWord** Identifiers matching any other reserved word
* **alpha\_variable** Identifiers matching a :abbr:`SQL (structured
  query language)` variable ``"@foo"``
* **alpha\_identifier** All other identifiers
* **quote** Applies to all quotation mark classes
* **quote\_double** Double quotes ``"``
* **quote\_single** Single quotes ``'``
* **quote\_backtick** Backtick quotes `````



.. _cfg_SQLValidator:

$cfg['SQLValidator'] boolean
----------------------------



.. _cfg_SQLValidator_use:

$cfg['SQLValidator']['use'] boolean
-----------------------------------

phpMyAdmin now supports use of the `Mimer :abbr:`SQL (structured query
language)` Validator
<http://developer.mimer.com/validator/index.htm>`_ service, as
originally published on `Slashdot
<http://developers.slashdot.org/article.pl?sid=02/02/19/1720246>`_.
For help in setting up your system to use the service, see the
:ref:`faqsqlvalidator`.

.. _cfg_SQLValidator_username:

.. _cfg_SQLValidator_password:

$cfg['SQLValidator']['username'] string $cfg['SQLValidator']['password'] string
-------------------------------------------------------------------------------

The SOAP service allows you to log in with ``anonymous`` and any
password, so we use those by default. Instead, if you have an account
with them, you can put your login details here, and it will be used in
place of the anonymous login.



.. _cfg_DBG:

$cfg['DBG']
-----------

**DEVELOPERS ONLY!**

.. _cfg_DBG_sql:

$cfg['DBG']['sql'] boolean
--------------------------

**DEVELOPERS ONLY!** Enable logging queries and execution times to be
displayed in the bottom of main page (right frame).

.. _cfg_DefaultFunctions:

$cfg['DefaultFunctions'] array
------------------------------

Functions selected by default when inserting/changing row, Functions
are defined for meta types as (FUNC\_NUMBER, FUNC\_DATE, FUNC\_CHAR,
FUNC\_SPATIAL, FUNC\_UUID) and for ``first\_timestamp``, which is used
for first timestamp column in table.

