# Changelog

All Notable changes to `league-uri-parser` will be documented in this file

## Next

### Added

- `League\Uri\Exception` replaces `League\Uri\ParserException`
- `League\Uri\Parser::isValidHost` method
- `League\Uri\Exception::createFromInvalidScheme` replaces `ParserException::createFromInvalidState` usage
- `League\Uri\Exception::createFromInvalidPath` replaces `ParserException::createFromInvalidState` usage

### Fixed

- None

### Deprecated

- None

### Removed

- `League\Uri\ParserException` replaced by `Exception`
- `League\Uri\ParserException::createFromInvalidState`

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