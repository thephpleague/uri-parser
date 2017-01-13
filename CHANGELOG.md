# Changelog

All Notable changes to `league-uri-parser` will be documented in this file

## 1.0.1 - 2017-01-13

### Added

- None

### Fixed

- PHP version constraint in composer.json

### Deprecated

- None

### Removed

- None

## 1.0.0 - 2017-01-04

### Added

- `League\Uri\Exception::createFromInvalidHostname`

### Fixed

- `League\Uri\Parser::isHost` method improved

### Deprecated

- None

### Removed

- Support for PHP5
- Benchmark test

## 0.3.0 - 2016-11-09

### Added

- `League\Uri\Exception` replaces `League\Uri\ParserException`
- `League\Uri\Parser::isHost` method
- `League\Uri\Exception::createFromInvalidScheme` replaces `ParserException::createFromInvalidState` usage
- `League\Uri\Exception::createFromInvalidPath` replaces `ParserException::createFromInvalidState` usage

### Fixed

- None

### Deprecated

- None

### Removed

- `League\Uri\ParserException` replaced by `League\Uri\Exception`
- `League\Uri\ParserException::createFromInvalidState`
- `League\Uri\HostValidation` trait

## 0.2.0 - 2016-11-02

### Added

- `League\Uri\ParserException` class which extends SPL `InvalidArgumentException`
- `League\Uri\HostValidation` trait to ease Host validation without the parser

### Fixed

- Improve Performance by removing all regular expressions [see issue #2](https://github.com/thephpleague/uri-parser/issues/2)

### Deprecated

- None

### Removed

- None

## 0.1.0 - 2016-10-17

### Added

- None

### Fixed

- Improve [RFC3986](http://tools.ietf.org/html/rfc3986) compliance

### Deprecated

- None

### Removed

- None