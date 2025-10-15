# Changes in phpMyAdmin 5.2

All notable changes of the phpMyAdmin 5.2 release series are documented in this file following the [Keep a Changelog](https://keepachangelog.com/) format.

## [5.2.2] - YYYY-MM-DD

### Fixed

* [#17522](https://github.com/phpmyadmin/phpmyadmin/issues/17522): Fix case where the routes cache file is invalid

### Security

* Upgrade slim/psr7 to 1.4.1 for CVE-2023-30536 - GHSA-q2qj-628g-vhfw

## [5.2.1] - 2023-02-07

### Added

* [#17519](https://github.com/phpmyadmin/phpmyadmin/issues/17519): Fix Export pages not working in certain conditions
* [#17496](https://github.com/phpmyadmin/phpmyadmin/issues/17496): Fix error in table operation page when partitions are broken

### Changed

* [#17519](https://github.com/phpmyadmin/phpmyadmin/issues/17519): Fix Export pages not working in certain conditions
* [#17496](https://github.com/phpmyadmin/phpmyadmin/issues/17496): Fix error in table operation page when partitions are broken

### Deprecated

* [#17519](https://github.com/phpmyadmin/phpmyadmin/issues/17519): Fix Export pages not working in certain conditions
* [#17496](https://github.com/phpmyadmin/phpmyadmin/issues/17496): Fix error in table operation page when partitions are broken

### Removed

* [#17519](https://github.com/phpmyadmin/phpmyadmin/issues/17519): Fix Export pages not working in certain conditions
* [#17496](https://github.com/phpmyadmin/phpmyadmin/issues/17496): Fix error in table operation page when partitions are broken

### Fixed

* [#17519](https://github.com/phpmyadmin/phpmyadmin/issues/17519): Fix Export pages not working in certain conditions
* [#17496](https://github.com/phpmyadmin/phpmyadmin/issues/17496): Fix error in table operation page when partitions are broken
* [#16418](https://github.com/phpmyadmin/phpmyadmin/issues/16418): Fix FAQ 1.44 about manually removing vendor folders

### Security

* Fix an XSS attack through the drag-and-drop upload feature (PMASA-2023-01)

[5.2.2]: https://github.com/phpmyadmin/phpmyadmin/compare/RELEASE_5_2_1...QA_5_2
[5.2.1]: https://github.com/phpmyadmin/phpmyadmin/compare/RELEASE_5_2_0...RELEASE_5_2_1
