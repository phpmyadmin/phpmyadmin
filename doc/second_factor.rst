.. _2fa:

Second authentication factor
============================

.. versionadded:: 4.8.0

Since phpMyAdmin 4.8.0 you can configure second authentication factor to be
used when logging into it. To use this, you first need to configure
:ref:`linked-tables`. Once this is done, every user can opt-in for second
authentication factor in the :guilabel:`Settings`.

Authentication Application
--------------------------

Using application for authentication is quite common approach based on HOTP and
TOTP. It is based on transmitting private key from phpMyAdmin to the
authentication application and the application is then able to generate one
time codes based on this key.

There are dozens of applications available for mobile phones to implement these
standards, the most widely used include:

* `FreeOTP for iOS, Android and Pebble <https://freeotp.github.io/>`_
* `Authy for iOS, Android, Chrome, OS X <https://www.authy.com/>`_
* `Google Authenticator for iOS <https://itunes.apple.com/us/app/google-authenticator/id388497605?mt=8>`_
* `Google Authenticator for Android <https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2>`_
* `Google Authenticator (port) on Windows Store <https://www.microsoft.com/en-us/store/p/google-authenticator/9wzdncrdnkrf>`_
* `Microsoft Authenticator for Windows Phone <https://www.microsoft.com/en-us/store/apps/authenticator/9wzdncrfj3rj>`_
* `LastPass Authenticator for iOS, Android, OS X, Windows <https://lastpass.com/auth/>`_
* `1Password for iOS, Android, OS X, Windows <https://1password.com>`_

Simple Second Factor
--------------------

This authentication is included for testing and demostration purposes only as
it really does not provide second factor, it just asks user to confirm login by
clicking on the button.

It should not be used in the production and is disabled unless
:config:option:`$cfg['DBG']['simple2fa']` is set.
