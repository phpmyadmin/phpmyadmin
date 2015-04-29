Import and export
=================

In addition to the standard Import and Export tab, you can also import an SQL file directly by dragging and dropping
it from your local file manager to the phpMyAdmin interface in your web browser.

Open Document Spreadsheet
-------------------------

When importing an ODS speadsheet, the spreadsheet must be named in a specific way in order to make the
import as simple as possible.

Table name
~~~~~~~~~~

During import, phpMyAdmin uses the sheet name as the table name; you should rename the
sheet in your spreadsheet program in order to match your existing table name (or the table you wish to create,
though this is less of a concern since you could quickly rename the new table from the Operations tab).

Column names
~~~~~~~~~~~~

You should also make the first row of your spreadsheet a header with the names of the columns (this can be
accomplished by inserting a new row at the top of your spreadsheet). When on the Import screen, select the
checkbox for "The first line of the file contains the table column names;" this way your newly imported
data will go to the proper columns.

