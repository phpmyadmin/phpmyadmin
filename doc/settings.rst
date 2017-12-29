Configuring phpMyAdmin
----------------------

To Configure phpMyAdmin the step by step details are provided in
:ref:`config`.

In order to configure connections to databases and other system settings the global
settings needs to be configured in :file:`config.inc.php` details of which will be
found in :ref:`config`

Adding to this there are user settings which can be persistently stored in
:ref:`linked-tables`, possibly automatically configured through
:ref:`zeroconf`.  If the :ref:`linked-tables` are not configured, the settings
are temporarily stored in the session data, and are valid only till you are logged In

You can also save the user configuration for further use, either download them
as a file or to the browser's local storage. You can find both these options in
the :guilabel:`Settings` tab. The settings stored in browser local storage will
be automatically provided for loading upon your login to phpMyAdmin.

