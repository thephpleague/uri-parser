# Changelog

All Notable changes to `league-uri-parser` will be documented in this file

## Next

### Added

- `ParserException::createFromInvalidScheme` replaces `ParserException::createFromInvalidState` usage
- `ParserException::createFromInvalidPath` replaces `ParserException::createFromInvalidState` usage

### Fixed

- None

### Deprecated

- None

### Removed

- `ParserException::createFromInvalidState`

## 0.2.0 - 2016-11-02

### Added

- `ParserException` class which extends SPL `InvalidArgumentException`
- `HostValidation` trait to ease Host validation without the parser

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