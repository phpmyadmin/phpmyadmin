.. _2fa:

Two-factor authentication
=========================

.. versionadded:: 4.8.0

Since phpMyAdmin 4.8.0 you can configure two-factor authentication to be
used when logging in. To use this, you first need to configure the
:ref:`linked-tables`. Once this is done, every user can opt-in for second
authentication factor in the :guilabel:`Settings`.

When running phpMyAdmin from the Git source repository, the dependencies must be installed
manually; the typical way of doing so is with the command:

.. code-block:: sh

    composer require pragmarx/google2fa bacon/bacon-qr-code

Or when using a hardware security key with FIDO U2F:

.. code-block:: sh

    composer require samyoul/u2f-php-server

Authentication Application (2FA)
--------------------------------

Using application for authentication is quite common approach based on HOTP and
`TOTP <https://en.wikipedia.org/wiki/Time-based_One-time_Password_Algorithm>`_.
It is based on transmitting private key from phpMyAdmin to the authentication
application and the application is then able to generate one time codes based
on this key.

There are dozens of applications available for mobile phones to implement these
standards, the most widely used include:

* `FreeOTP for iOS, Android and Pebble <https://freeotp.github.io/>`_
* `Authy for iOS, Android, Chrome, OS X <https://authy.com/>`_
* `Google Authenticator for iOS <https://itunes.apple.com/us/app/google-authenticator/id388497605>`_
* `Google Authenticator for Android <https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2>`_
* `LastPass Authenticator for iOS, Android, OS X, Windows <https://lastpass.com/auth/>`_

Hardware Security Key (FIDO U2F)
--------------------------------

Using hardware tokens is considered to be more secure than software based
solution. phpMyAdmin supports `FIDO U2F <https://en.wikipedia.org/wiki/Universal_2nd_Factor>`_
tokens.

There are several manufacturers of these tokens, for example:

* `youbico FIDO U2F Security Key <https://www.yubico.com/solutions/fido-u2f/>`_
* `HyperFIDO <https://www.hypersecu.com/products/hyperfido>`_
* `TREZOR Bitcoin wallet <https://shop.trezor.io?a=572b241135e1>`_ can `act as an U2F token <https://doc.satoshilabs.com/trezor-user/u2f.html>`_

.. _simple2fa:

Simple two-factor authentication
--------------------------------

This authentication is included for testing and demostration purposes only as
it really does not provide two-factor authentication, it just asks user to confirm login by
clicking on the button.

It should not be used in the production and is disabled unless
:config:option:`$cfg['DBG']['simple2fa']` is set.
