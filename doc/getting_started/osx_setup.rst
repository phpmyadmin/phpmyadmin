:orphan:

====================
OS X Setup Guide
====================

Below are the steps for installing, running, using, stopping, and uninstalling phpMyAdmin on Mac OS X operating systems.

======================
Installing and Running
======================

Installing Apache and phpMyAdmin
++++++++++++++++++++++++++++++++

#. Navigate to `ApacheFriends.org <https://www.apachefriends.org/download.html>`_ to download Apache. Choose the latest release.

#. Run the installation for Apache.

    * Sometimes, Mac OS X prevents installation of installers. To circumvent, hold the "control" button on your keyboard, click the installer once, and then click "Open"
  
#. Complete the installation.

    .. image:: images/OSX_Apache_Selection.png
        :width: 700
        :align: center
        :alt: Apache installation contents

Running Apache and phpMyAdmin
+++++++++++++++++++++++++++++

#. Launch the XAMPP application. The XAMPP Control Panel will open, and you may notice that your default browser opens to `localhost <http://localhost/dashboard/>`__. If the web browser did not open, then click the "Go to Application" button in the XAMPP application.

    .. image:: images/OSX_Apache_Run.png
        :width: 700
        :align: center
        :alt: Apache Control Panel layout

#. From the XAMPP Control Panel, click the "Manage Servers" tab and "Start" the MySQL Database Server. A green light with the text, "Running" will indicate that the server is running properly.

    * You may also configure the MySQL server here by clicking the "Configure" button.

    .. image:: images/OSX_Apache_Manage_Servers.png
        :width: 700
        :align: center
        :alt: XAMPP Manage Servers screen
    .. image:: images/OSX_Apache_Manage_Servers_Running.png
        :width: 700
        :align: center
        :alt: XAMPP Manage Servers screen with MySQL server running

#. To start phpMyAdmin, you may click on phpMyAdmin from the splash page that XAMPP had previously opened. 

    * Alternatively, you may open either Chrome, Firefox, Safari, or Edge, and navigate to `localhost <http://localhost/phpmyadmin/>`__.

    .. image:: images/OSX_Apache_access_phpMyAdmin.png
        :width: 700
        :align: center
        :alt: Click on phpMyAdmin from the XAMPP splash screen to open phpMyAdmin

#. You have successfully opened phpMyAdmin. Your screen should look similar to below:

    .. image:: images/OSX_phpMyAdmin_running.png
        :width: 700
        :align: center
        :alt: The phpMyAdmin software running in the browser screen

#. To stop using phpMyAdmin, first "Stop" the MySQL and Apache Web Server modules in the XAMPP Control Panel. Then, you may close XAMPP Control Panel.