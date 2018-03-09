.. _bookmarks:

Bookmarks
=========

.. note::

    You need to have configured the :ref:`linked-tables` for using bookmarks
    feature.

Storing bookmarks
-----------------

Any query you have executed can be stored as a bookmark on the page
where the results are displayed. You will find a button labeled
:guilabel:`Bookmark this query` just at the end of the page. As soon as you have
stored a bookmark, it is related to the database you run the query on.
You can now access a bookmark dropdown on each page, the query box
appears on for that database.

Variables inside bookmarks
--------------------------

You can also have, inside the query, placeholders for variables.
This is done by inserting into the query SQL comments between ``/*`` and
``*/``. Inside the comments, the special strings ``[VARIABLE{variable-number}]`` is used.
Be aware that the whole query minus the SQL comments must be
valid by itself, otherwise you won't be able to store it as a bookmark.
Note also that the text 'VARIABLE' is case-sensitive.

When you execute the bookmark, everything typed into the *Variables*
input boxes on the query box page will replace the strings ``/*[VARIABLE{variable-number}]*/`` in
your stored query.

Also remember, that everything else inside the ``/*[VARIABLE{variable-number}]*/`` string for
your query will remain the way it is, but will be stripped of the ``/**/``
chars. So you can use:

.. code-block:: mysql

    /*, [VARIABLE1] AS myname */

which will be expanded to

.. code-block:: mysql

    , VARIABLE1 as myname

in your query, where VARIABLE1 is the string you entered in the Variable 1 input box.

A more complex example. Say you have stored
this query:

.. code-block:: mysql

    SELECT Name, Address FROM addresses WHERE 1 /* AND Name LIKE '%[VARIABLE1]%' */

Say, you now enter "phpMyAdmin" as the variable for the stored query, the full
query will be:

.. code-block:: mysql

    SELECT Name, Address FROM addresses WHERE 1 AND Name LIKE '%phpMyAdmin%'

**NOTE THE ABSENCE OF SPACES** inside the ``/**/`` construct. Any spaces
inserted there will be later also inserted as spaces in your query and may lead
to unexpected results especially when using the variable expansion inside of a
"LIKE ''" expression.

Browsing table using bookmark
-----------------------------

When bookmark is named same as table, it will be used as query when browsing
this table.

.. seealso::

    :ref:`faqbookmark`,
    :ref:`faq6_22`
