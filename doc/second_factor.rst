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
* `Google Authenticator for iOS <https://itunes.apple.com/us/app/google-authenticator/id388497605>`_
* `Google Authenticator for Android <https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2>`_
* `LastPass Authenticator for iOS, Android, OS X, Windows <https://lastpass.com/auth/>`_

Hardware Security Key
---------------------

Using hardware tokens is considered to be more secure than software based
solution. phpMyAdmin supports `FIDO U2F <https://en.wikipedia.org/wiki/Universal_2nd_Factor>`_
tokens.

There are several manufacturers of these tokens, for example:

* `youbico FIDO U2F Security Key <https://www.yubico.com/products/yubikey-hardware/fido-u2f-security-key/>`_
* `HyperFIDO <https://www.hypersecu.com/products/hyperfido>`_
* `ePass FIDO USB <https://www.ftsafe.com/onlinestore/product?id=21>`_
* `TREZOR Bitcoin wallet <https://shop.trezor.io?a=572b241135e1>`_ can `act as an U2F token <http://doc.satoshilabs.com/trezor-user/u2f.html>`_

Simple Second Factor
--------------------

This authentication is included for testing and demostration purposes only as
it really does not provide second factor, it just asks user to confirm login by
clicking on the button.

It should not be used in the production and is disabled unless
:config:option:`$cfg['DBG']['simple2fa']` is set.
