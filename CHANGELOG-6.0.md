# Changes in phpMyAdmin 6.0

All notable changes of the phpMyAdmin 6.0 release series are documented in this file following the [Keep a Changelog](https://keepachangelog.com/) format.

## [6.0.0] - YYYY-MM-DD

### Added

* [#17029](https://github.com/phpmyadmin/phpmyadmin/pull/17029): Enable Twig's `strict_variables` option in development environment
* [#17361](https://github.com/phpmyadmin/phpmyadmin/pull/17361): Add webpack bundler
* [#16500](https://github.com/phpmyadmin/phpmyadmin/pull/16500): Add paging to Routines page
* [#17753](https://github.com/phpmyadmin/phpmyadmin/pull/17753): Adding `inputmode` numeric to integer fields
* [#17765](https://github.com/phpmyadmin/phpmyadmin/pull/17765): Make search input field types match columns types (integer and float)
* [#17809](https://github.com/phpmyadmin/phpmyadmin/pull/17809): Adds support for SHA2 function
* [#18098](https://github.com/phpmyadmin/phpmyadmin/pull/18098): Added reCaptcha example for Cloudflare Turnstile
* [#18223](https://github.com/phpmyadmin/phpmyadmin/pull/18223): Add a configuration option to (dis)allow shared bookmarks: `$cfg['AllowSharedBookmarks'] = true;`
* [#18225](https://github.com/phpmyadmin/phpmyadmin/pull/18225): Make database and web server info separately configurable on `$cfg['ShowServerInfo']`
* [#18257](https://github.com/phpmyadmin/phpmyadmin/pull/18257): Add support for theme color modes
* [#17009](https://github.com/phpmyadmin/phpmyadmin/issues/17009): Implement the security header `Permissions-Policy`
* [#18349](https://github.com/phpmyadmin/phpmyadmin/pull/18349): Allow rollbacking SET queries
* [#18589](https://github.com/phpmyadmin/phpmyadmin/pull/18589): Add support for PSR-15 middleware
* [#18649](https://github.com/phpmyadmin/phpmyadmin/pull/18649): Add color mode toggle at the top navigation bar
* [#18668](https://github.com/phpmyadmin/phpmyadmin/pull/18668): Add number formatting to Total on Browse
* [#18747](https://github.com/phpmyadmin/phpmyadmin/pull/18747): Add `composer.lock` to version control
* [#18799](https://github.com/phpmyadmin/phpmyadmin/pull/18799): Add an user account menu button at top right
* [#18812](https://github.com/phpmyadmin/phpmyadmin/pull/18812): Add role based auth for MySQL 8.x and compatibles
* [#18916](https://github.com/phpmyadmin/phpmyadmin/pull/18916): Add format button to routine, event and trigger
* [#18537](https://github.com/phpmyadmin/phpmyadmin/pull/18537): Add copy to clipboard for export as text
* [#19287](https://github.com/phpmyadmin/phpmyadmin/pull/19287): Add optional support for binary data in database search
* [#19308](https://github.com/phpmyadmin/phpmyadmin/pull/19308): Added an option in the SQL box to "Format as a single line"
* [#19352](https://github.com/phpmyadmin/phpmyadmin/pull/19352): Added support for `BETWEEN` and `NOT BETWEEN` on the query generator
* [#19525](https://github.com/phpmyadmin/phpmyadmin/pull/19525): Add support for `INSERT IGNORE` into for non table imports
* [#19555](https://github.com/phpmyadmin/phpmyadmin/pull/19555): Add button for Copy Query in Query Generator
* [#19691](https://github.com/phpmyadmin/phpmyadmin/pull/19691): Show hostname on main page
* [#19751](https://github.com/phpmyadmin/phpmyadmin/pull/19751): Add Go button at the top of the insert row table
* [#19812](https://github.com/phpmyadmin/phpmyadmin/pull/19812): Add show create table shortcuts when viewing tables

### Changed

* [#17116](https://github.com/phpmyadmin/phpmyadmin/pull/17116): Replace Change Password dialog to modal
* [#17125](https://github.com/phpmyadmin/phpmyadmin/pull/17125): Replace Add Index dialog with modal
* [#17127](https://github.com/phpmyadmin/phpmyadmin/pull/17127): Replace Page settings dialog with modal
* [#17278](https://github.com/phpmyadmin/phpmyadmin/pull/17278): Improve NULL div design
* [#17280](https://github.com/phpmyadmin/phpmyadmin/pull/17280): Improve theme card heights
* [#17399](https://github.com/phpmyadmin/phpmyadmin/pull/17399): Handle connection errors with Exception instead of warnings
* [#17515](https://github.com/phpmyadmin/phpmyadmin/pull/17515): Bump js-cookie version from 2.2.1 to 3.0.1
* [#17561](https://github.com/phpmyadmin/phpmyadmin/pull/17561): Improve Change Password modal UI
* [#17676](https://github.com/phpmyadmin/phpmyadmin/pull/17676): Extract `url.php` entry point into a route
* [#17677](https://github.com/phpmyadmin/phpmyadmin/pull/17677): Extract `js/messages.php` entry point into `/messages` route
* [#17801](https://github.com/phpmyadmin/phpmyadmin/pull/17801): Export page of Settings is not responsive
* [#17842](https://github.com/phpmyadmin/phpmyadmin/issues/17842): Change js.cookie.js to js.cookie.min.js
* [#17632](https://github.com/phpmyadmin/phpmyadmin/issues/17632): Improve tab keypress to text fields on the login form
* [#18124](https://github.com/phpmyadmin/phpmyadmin/pull/18124): Invert svg gis viewer mouse wheel zoom direction
* [#18136](https://github.com/phpmyadmin/phpmyadmin/pull/18136): Force a full page reload for top menu links
* [#18145](https://github.com/phpmyadmin/phpmyadmin/pull/18145): Move public files to the public directory
* [#18300](https://github.com/phpmyadmin/phpmyadmin/pull/18300): Convert JavaScript files to TypeScript
* [#18310](https://github.com/phpmyadmin/phpmyadmin/pull/18310): Minimize form label column width in Routine, Event and Trigger modals
* [#18311](https://github.com/phpmyadmin/phpmyadmin/pull/18311): Only allow resizing textarea vertically
* [#18365](https://github.com/phpmyadmin/phpmyadmin/pull/18365): Change 'reCaptcha' text to 'Captcha'
* [#18439](https://github.com/phpmyadmin/phpmyadmin/pull/18439): Redesign the SQL message card
* [#18576](https://github.com/phpmyadmin/phpmyadmin/pull/18576): Wrap the Application::run() code with an HTTP handler
* [#18652](https://github.com/phpmyadmin/phpmyadmin/pull/18652): Disable AJAX for navigation sidebar links
* [#18658](https://github.com/phpmyadmin/phpmyadmin/pull/18658): Update OpenLayers
* [#18703](https://github.com/phpmyadmin/phpmyadmin/pull/18703): Limit label width in create-view table
* [#18512](https://github.com/phpmyadmin/phpmyadmin/issues/18512): New directory structure
* [#18994](https://github.com/phpmyadmin/phpmyadmin/pull/18994): Refactor name generation of file imports
* [#19110](https://github.com/phpmyadmin/phpmyadmin/pull/19110): Refactor recent/favorite tables buttons
* [#19182](https://github.com/phpmyadmin/phpmyadmin/pull/19182): Refactor GIS editor modal
* [#19214](https://github.com/phpmyadmin/phpmyadmin/pull/19214): Replace jqPlot library with Chart.js library
* [#19294](https://github.com/phpmyadmin/phpmyadmin/pull/19294): Simplify IN, NOT IN criteria text in Query Generator
* [#19327](https://github.com/phpmyadmin/phpmyadmin/pull/19327): Prevent IN / NOT IN duplicates in Query Generator
* [835fc27](https://github.com/phpmyadmin/phpmyadmin/commit/835fc2715d6f7330fa5ed680e8315b98eaa96cec): Redesign the User Groups form page
* [624126e](https://github.com/phpmyadmin/phpmyadmin/commit/624126edc8ea629ebbb222496bb4dad6cac9c6dd): Redesign the database and table privileges pages
* [#19398](https://github.com/phpmyadmin/phpmyadmin/issues/19398): Use `application/json` instead of `text/plain` for JSON export
* [#19496](https://github.com/phpmyadmin/phpmyadmin/pull/19496): Update CODE_OF_CONDUCT.md
* [#19513](https://github.com/phpmyadmin/phpmyadmin/pull/19513): Hide unwanted input depending on chosen Op type in Query Generator
* [#19536](https://github.com/phpmyadmin/phpmyadmin/pull/19536): Disable Search input on unary operators
* [#19542](https://github.com/phpmyadmin/phpmyadmin/pull/19542): Made Search operators `= ''`/`!= ''` more obvious
* [#19651](https://github.com/phpmyadmin/phpmyadmin/pull/19651): Stay the on same page when deleting a foreign key
* [#19712](https://github.com/phpmyadmin/phpmyadmin/pull/19712): Import OpenLayers and Bootstrap directly using Webpack
* [#19794](https://github.com/phpmyadmin/phpmyadmin/pull/19794): Use `jquery-ui-timepicker-addon.min.js` instead of `jquery-ui-timepicker-addon.js`
* [#19795](https://github.com/phpmyadmin/phpmyadmin/pull/19795): Use `additional-methods.min.js` instead of `additional-methods.js`
* [#19564](https://github.com/phpmyadmin/phpmyadmin/pull/19564): Update the table comment field to a textarea
* [#19947](https://github.com/phpmyadmin/phpmyadmin/pull/19947): Export ODS column headers by default

### Removed

* [#17351](https://github.com/phpmyadmin/phpmyadmin/pull/17351): Drop `stripslashes`
* [#17516](https://github.com/phpmyadmin/phpmyadmin/pull/17516): Remove `jquery-debounce-throttle` JavaScript dependency
* [#17752](https://github.com/phpmyadmin/phpmyadmin/pull/17752): Remove Sodium polyfill and require the PHP sodium extension
* [#18430](https://github.com/phpmyadmin/phpmyadmin/pull/18430): Remove Query-By-Example feature
* [#18547](https://github.com/phpmyadmin/phpmyadmin/pull/18547): Remove support for the Recode extension
* [#18587](https://github.com/phpmyadmin/phpmyadmin/pull/18587): Remove the `public/show_config_errors.php` file
* [#19128](https://github.com/phpmyadmin/phpmyadmin/pull/19128): Remove the `phpmyadmin/twig-i18n-extension` package
* [#19243](https://github.com/phpmyadmin/phpmyadmin/pull/19243): Bump minimum PHP version to 8.2.0
* [#19408](https://github.com/phpmyadmin/phpmyadmin/pull/19408): Removed the popup that warned about having an auto-saved query
* [#19409](https://github.com/phpmyadmin/phpmyadmin/pull/19409): Drop Protocol version
* [#19550](https://github.com/phpmyadmin/phpmyadmin/pull/19550): Replace jQuery UI's tooltip with Bootstrap's Tooltip
* [#19852](https://github.com/phpmyadmin/phpmyadmin/pull/19852): Bump Node version to 20
* [#19854](https://github.com/phpmyadmin/phpmyadmin/pull/19854): Drop support for BaconQrCode v2

[6.0.0]: https://github.com/phpmyadmin/phpmyadmin/compare/QA_5_2...master
