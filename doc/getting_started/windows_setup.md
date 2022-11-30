# Windows Setup Guide

Below are the steps for installing, running, using, stopping, and uninstalling phpMyAdmin on Windows operating systems.

## <b><u>Installing and Running</b></u>

### Installing Apache and phpMyAdmin

<ol>
      <li> Navigate to <a>https://www.apachefriends.org/download.html</a> to download Apache. Choose the latest release. </li>
      <li> Run the installation for Apache. </li>
      <li> Ensure that MySQL and phpMyAdmin are selected for installation.
      <p align="center">
      <kbd><kbd><img src ="./images/Apache_Selection.png" width = "700" style="border-radius:5%"></kbd></kbd>
      </p>
      <p align="center">
      <code>Ensure that MySQL and phpMyAdmin are checked during installation of Apache's XAMPP Control Panel</code>
      </p>
      </li>
      <li> Finish installation. </li>
</ol>

### Running Apache and phpMyAdmin

<ol>
      <li> Run the XAMPP application </li>
      <li> Click the "Start" button under "Actions" for both the Apache and MySQL modules
      <p align="center">
      <kbd><kbd><img src ="./images/Apache_Run.png" width = "700" style="border-radius:5%"></kbd></kbd>
      </p>
      <p align="center">
      <code>XAMPP Control Panel. Start the Apache and MySQL modules to run phpMyAdmin</code>
      </p>
      </li>
      <code>This will run phpMyAdmin locally at 127.0.0.1. You may change this by clicking Config > phpMyAdmin (config.inc.php) for the Apache module </code>
      <li> To start phpMyAdmin, open either Chrome, Firefox, Safari, or Edge, and navigate to <a>http://localhost/phpmyadmin/ </a>. </li>
      <ul>Also, you may click on the "Admin" button under "Actions" for the MySQL module to access phpMyAdmin.
        <p align="center"><kbd><kbd><img src ="./images/Apache_access_phpMyAdmin.png" width = "700" style="border-radius:5%"></kbd></kbd></p><p align="center"><code>Access phpMyAdmin by clicking the "Admin" action button under the MySQL Module</code></ul>
      <li> You have successfully opened phpMyAdmin. Your screen should look similar to below: <p align="center"><kbd><kbd><img src ="./images/phpMyAdmin_Running.png" width = "700" style="border-radius:5%"></kbd></kbd></p><p align="center"><code>A running instance of phpMyAdmin</code>
      <li>To stop using phpMyAdmin, first "Stop" the MySQL and Apache modules in the XAMPP Control Panel. Then, you may close XAMPP Control Panel.</li>
</ol>
