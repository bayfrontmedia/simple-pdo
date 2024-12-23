# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

- `Added` for new features.
- `Changed` for changes in existing functionality.
- `Deprecated` for soon-to-be removed features.
- `Removed` for now removed features.
- `Fixed` for any bug fixes.
- `Security` in case of vulnerabilities

## [5.4.3] - 2024.12.23

### Added

- Tested up to PHP v8.4.

## [5.4.2] - 2024.11.28

### Added

- Added support for MySQL functions in query builder conditions.

## [5.4.1] - 2024.11.26

### Added

- Added support for `NULL` in values and conditions of `Db` class methods.

## [5.4.0] - 2024.11.08

### Added

- Added `startGroup` and `endGroup` methods.

## [5.3.0] - 2024.10.08

### Added

- Added `groupBy` method.

### Changed

- Updated documentation.

## [5.2.0] - 2024.10.08

### Added

- Added `aggregate` method and related constants.
- Added the following operators:
  - `OPERATOR_STARTS_WITH_INSENSITIVE`
  - `OPERATOR_DOES_NOT_START_WITH_INSENSITIVE`
  - `OPERATOR_ENDS_WITH_INSENSITIVE`
  - `OPERATOR_DOES_NOT_END_WITH_INSENSITIVE`
  - `OPERATOR_HAS_INSENSITIVE`
  - `OPERATOR_DOES_NOT_HAVE_INSENSITIVE`
  - `OPERATOR_NOT_NULL`

### Depreciated

- Depreciated `getTotalRows` method in favor of `aggregate`.

## [5.1.0] - 2024.10.05

### Added

- Added `orWhere` method.

## [5.0.0] - 2024.09.16

### Added

- Added `setQueryTime` method.

### Changed

- Updated method for calculating query durations to be more accurate.
- Updated `getQueryTime` and `getTotalQueries` methods to accept a specific database name.
- Updated `DbFactory::create` method to not require a specific `default` database.

### Removed

- Removed concept of "default" and "current" database in favor of simply using "current".
- Removed `getDefaultConnectionName` method.

## [4.0.1] - 2024.09.10

### Fixed

- Fixed bug in `DbFactory` using old namespace.

## [4.0.0] - 2024.09.10

### Added

- Added `DB_DEFAULT` constant.
- Added `getCurrentConnection` method.
- Added constants to be used with the query builder.
- Added support for multiple `INNER`, `LEFT` and `RIGHT` joins with the query builder.

### Changed

- Renamed `add` method to `addConnection`.
- Renamed `use` method to `useConnection`.
- Renamed `get` method to `getConnection`.
- Renamed `getDefault` method to `getDefaultConnectionName`.
- Renamed `getCurrent` method to `getCurrentConnectionName`.
- Renamed `getConnections` method to `getConnectionNames`.
- Renamed `isConnected` method to `connectionExists`.
- Changed namespace from `Bayfront\PDO` to `Bayfront\SimplePdo`
- Updated documentation.

### Removed

- Removed need for `php-string-helpers` dependency.

## [3.0.0] - 2023.05.10

### Added

- Added ability to select, search and order by `json` data type columns using the query builder.

### Removed

- Removed needless exceptions rethrown on `PDOException`.

## [2.2.0] - 2023.04.21

### Added

- Added ability to utilize some native MySQL functions in the `where` method of the query builder.

## [2.1.0] - 2023.04.21

### Added

- Added `getLastQuery` method for `Query` class.
- Added `getLastParameters` method.

## [2.0.0] - 2023.01.26

### Added

- Added support for PHP 8.

## [1.0.1] - 2022.05.05

### Changed

- Fixed bug in MySQL adapter when passing options.

## [1.0.0] - 2021.01.29

### Added

- Initial release.