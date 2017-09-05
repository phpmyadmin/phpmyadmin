Setting up 2-factor authentication
==================================
1. Login to your account normally.
2. Access setup2FA.php through settings tab.
3. Open `Google Authenticator`_ (or `Authy`_ or whatever you prefer). Scan the barcode with your app. It now starts generating TOTP on your app.
4. Enter the TOTP in the text field and click submit. Done!!. You now have successfully added 2-factor authentication to you PMA account.
5. When you log in next time, after you enter your credentials, you will be asked for `TOTP`_ .
6. Enter the TOTP generated. You will not be logged in unless you clear this step.

.. _Google Authenticator: https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2&hl=en
.. _Authy: https://play.google.com/store/apps/details?id=com.authy.authy&hl=en
.. _TOTP: https://en.wikipedia.org/wiki/Time-based_One-time_Password_Algorithm
