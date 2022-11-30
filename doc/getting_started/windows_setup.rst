:orphan:

===================
Windows Setup Guide
===================

Below are the steps for installing, running, using, stopping, and uninstalling phpMyAdmin on Windows operating systems.


Installing Apache and phpMyAdmin
++++++++++++++++++++++++++++++++

#. Navigate to `ApacheFriends.org <https://www.apachefriends.org/download.html>`_ to download Apache. Choose the latest release.
#. Run the installation for Apache.
#. Ensure that MySQL and phpMyAdmin are selected for installation.

    .. image:: images/Apache_Selection.png
        :width: 700
        :align: center
        :alt: Apache installation contents

#. Finish installation.

Running Apache and phpMyAdmin
+++++++++++++++++++++++++++++

#. Run the XAMPP application

#. Click the "Start" button under "Actions" for both the Apache and MySQL modules

    .. image:: images/Apache_Run.png
        :width: 700
        :align: center
        :alt: Apache Control Panel layout

    This will run phpMyAdmin locally at 127.0.0.1. You may change this by clicking Config > phpMyAdmin (config.inc.php) for the Apache module

#. To start phpMyAdmin, open either Chrome, Firefox, Safari, or Edge, and navigate to `localhost <http://localhost/phpmyadmin/>`_.

    * Alternatively, you may click on the "Admin" button under "Actions" for the MySQL module to access phpMyAdmin.

    .. image:: images/Apache_access_phpMyAdmin.png
        :width: 700
        :align: center
        :alt: You can also click the "Admin" button under "MySQL" to access phpMyAdmin

#. You have successfully opened phpMyAdmin. Your screen should look similar to below:

    .. image:: images/phpMyAdmin_Running.png
        :width: 700
        :align: center
        :alt: A running instance of phpMyAdmin

#. To stop using phpMyAdmin, first "Stop" the MySQL and Apache modules in the XAMPP Control Panel. Then, you may close XAMPP Control Panel.
