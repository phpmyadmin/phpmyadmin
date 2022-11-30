:orphan:

================================================================
Creating, Exporting, and Importing your First Database and Table
================================================================

Below are steps to create, export, and import your first simple database and table. With Apache and MySQL modules both started, navigate to `localhost <http://localhost/phpmyadmin/>`_ via Chrome, Firefox, Safari, or Edge


Creating a Simple Database and Table in phpMyAdmin
+++++++++++++++++++++++++++++++++++++++++++++++++++

#. In the crossbar at the top of the window, click "Databases. You may also click "New" in the pullout menu on the left side of the screen.
    * If you do not see "Databases," expand the size of the window, click "More", or click on the three vertically-stacked horizontal lines to access more commands.

    .. image:: images/phpMyAdmin_create_database.png
        :width: 700
        :align: center
        :alt: Screen displayed when creating a database in phpMyAdmin

#. Specify a name for your database. In this example, we will use test_guide. Leave the character set to default at utf8mb4_general_ci.
    * This is most common as most characters are specified by unicode value.

#. Click the "Create" button to create the database.

#. Now, we must create a table for this database. For this example, we will create a table with the name "classes" and the number of columns set to 4:

    .. image:: images/phpMyAdmin_create_table.png
        :width: 700
        :align: center
        :alt: Screen displayed when creating a table in phpMyAdmin

#. You are then taken to the table's details and attributes. We can set the values and options for each column of the table. Notice that we may scroll horizontally to see more options.

#. In the first row, we will set the name to "class_id", type as "INT", length/values as "6", and we will check the A_I (auto_increment) box.

#. In the second row, we will set the name to "class_name", type as "VARCHAR", length/values as "45".

#. In the third row, we will set the name to "class_description", type as "TEXT".

#. In the fourth row, we will set the name to "class_grade", type as "CHAR", length/values as "1", default as NULL, and we will ensure that the Null box is checked.

#. Lastly, in the "Table comments" box, we will type "My first table!"

#. Your screen should look similar to below:

    .. image:: images/phpMyAdmin_specify_table.png
        :width: 700
        :align: center
        :alt: Screen displaying options that can be specified when creating a table in phpMyAdmin

#. Click on the Preview SQL button to see a popup containing the syntax that will be used to create the table. It should match as follows:
    * CREATE TABLE `test_guide`.`classes` (`class_id` INT(6) NOT NULL AUTO_INCREMENT , `class_name` VARCHAR(45) NOT NULL , `class_description` TEXT NOT NULL , `class_grade` CHAR(1) NULL DEFAULT NULL , PRIMARY KEY (`class_id`)) ENGINE = InnoDB COMMENT = 'My first table!';

#. Save the table.

#. After saving the table, you will notice that you are now in the "Structure" tab of phpMyAdmin. Here, you may modify your tables, browse data within the tables, and more. You may always verify your location in phpMyAdmin at the very top of the screen:

    .. image:: images/phpMyAdmin_saved_table.png
        :width: 700
        :align: center
        :alt: The Structures tab shows the structure of the saved table

#. Let's return to the database. At the very top of the screen, click on "Database: test_guide":

    .. image:: images/phpMyAdmin_back_to_databases.png
        :width: 700
        :align: center
        :alt: You can click on the name of the database at the top of the screen to return to the databases screen

#. We can view the tables we have created in Designer mode. Click "Designer" on the crossbar at the top of the screen:
    * Remember to expand your screen, click on the three vertically-stacked horizontal lines, or click "More" if you do not see it.

    .. image:: images/phpMyAdmin_get_to_designer.png
        :width: 700
        :align: center
        :alt: Steps you can take to reach the Designer screen in phpMyAdmin

#. In designer mode, you may visualize the setup of the tables that make up your database. You may export schemas, create pages, add other tables from other databases, and more via the menu on the lefthand side of the canvas:

    .. image:: images/phpMyAdmin_designer.png
        :width: 700
        :align: center
        :alt: Example of the designer screen in phpMyAdmin


#. Success! We have created our first database and table. We know how to:

* Find where we are in phpMyAdmin (very top of the screen)
* Create a database (click "New" on left side of screen, or navigate to "Databases" in top menu crossbar)
* Create a table (while a database is selected, click "Structure" and specify table name and number of columns on the bottom of the screen. You may also click "New" on the left pullout menu underneath our selected database)
* Modify a table (while a database is selected, click "Structure" and then click on the subsection "Structure" of the table of choice)



Exporting a Database in phpMyAdmin
++++++++++++++++++++++++++++++++++

Exporting databases is simple and widely customizable.


#. In the top menu crossbar, click Export.
    Similarly, you may first click on the database you'd like to export, and then click on Export.

#. Under "Export method", click "Custom." You will see several customizable options for export.

#. In the next field, for now, leave the format as SQL.

#. Ensure that only our "test_guide" database is selected under "Databases".

#. Under "Output", rename the file as you see fit. For this example, we will rename it as the name of the database, "test_guide". Ensure that "Use this for future exports" is NOT selected, else all future databases will be renamed test_guide unless otherwise specified.

#. Feel free to examine the rest of the options. For now, we will scroll down and export the database. Click Export.

#. The database will have been exported as a SQL file on the Desktop. Go ahead and open up the test_guide.SQL file in a text editor to see what has been exported!

    .. image:: images/phpMyAdmin_export.png
        :width: 800
        :align: center
        :alt: A list of options are presented when exporting tables and databases in phpMyAdmin


Importing a Database in phpMyAdmin
++++++++++++++++++++++++++++++++++

Let's begin by dropping the test_guide database. Don't worry, we've already saved a copy to our desktop. Then, we'll reimport the database back into phpMyAdmin.


#. Select the test_guide database in the left menu pullout. Ensure that the database was selected by checking our current location at the very top of the screen:

    .. image:: images/phpMyAdmin_drop_test_guide.png
        :width: 700
        :align: center
        :alt: You may view your current location in phpMyAdmin towards the top of the window

#. In the top menu crossbar, click "Operations."
    This area provides options for modifying the database.
#. Toward the middle of the screen under "Remove Database", locate and click on "Drop the database (DROP)" in red letters. Confirm that you would like to drop the database:

    .. image:: images/phpMyAdmin_drop_test_guide_confirm.png
        :width: 700
        :align: center
        :alt: A popup asks for confirmation before dropping a database

#. In the top menu crossbar, click "Import."
#. Browse for and select the test_guide.SQL file that was created on your desktop.
#. At the bottom of the screen, click "Import."
#. You will see that the screen has populated with queries to import the SQL file. We have imported our test_guide.SQL database:

    .. image:: images/phpMyAdmin_successful_import.png
        :width: 700
        :align: center
        :alt: Results are detailed after importing a table. This screen can also show failure messages
