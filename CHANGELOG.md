# Changelog

All Notable changes to `league-uri-parser` will be documented in this file

## 1.4.1 - 2018-11-22

### Added

- None

### Fixed

- Improve Scheme parsing according to RFC3986 see [#19](https://github.com/thephpleague/uri-parser/issues/19)
- `Parser` throws an `UnexpectedValueException` if the Intl extension is misconfigured on the PHP platform.

### Deprecated

- None

### Removed

- None

## 1.4.0 - 2018-03-14

### Added

- `MissingIdnSupport` exception which is thrown when trying to parse a possible RFC3987 compliant host
when the `ext/intl` extension is missing or the ICU minimum version is not supported.
- IPvFuture support

### Fixed

- Improve Host parsing according to RFC3986
- Improve Parsing performance
- Using PHPstan
- The library only requires the `intl` extension if you need to parse RFC3987 compliant host.

### Deprecated

- `Parser::INVALID_URI_CHARS` internal constants no longer in use
- `Parser::CHEME_VALID_STARTING_CHARS` internal constants no longer in use
- `Parser::SCHEME_VALID_CHARS` internal constants no longer in use
- `Parser::LABEL_VALID_STARTING_CHARS` internal constants no longer in use
- `Parser::LOCAL_LINK_PREFIX` internal constants no longer in use
- `Parser::UB_DELIMITERS` internal constants no longer in use
- `Parser::isIpv6host` internal method no longer in use
- `Parser::isHostLabel` internal method no longer in use
- `Parser::toAscii` internal method no longer in use

### Removed

- None

## 1.3.0 - 2017-12-01

### Added

- Much requested `Parser::parse` method

### Fixed

- Improve Host parsing according to RFC3986 rules see [#107](https://github.com/thephpleague/uri/issues/107)

### Deprecated

- None

### Removed

- None

## 1.2.0 - 2017-10-20

### Added

- `League\Uri\Parser::isScheme`
- `League\Uri\is_scheme` function version of `League\Uri\Parser::isScheme`
- `League\Uri\Parser::isPort`
- `League\Uri\is_port` function version of `League\Uri\Parser::isPort`

### Fixed

- None

### Deprecated

- None

### Removed

- None

## 1.1.0 - 2017-09-25

### Added

- `League\Uri\build`   function de build and URI from the result from `League\Uri\Parser::__invoke` or `parse_url`
- `League\Uri\parse`   function version of `League\Uri\Parser::__invoke`
- `League\Uri\is_host` function version of `League\Uri\Parser::isHost`

### Fixed

- None

### Deprecated

- None

### Removed

- None

## 1.0.5 - 2017-04-19

### Added

- None

### Fixed

- [issue #5](https://github.com/thephpleague/uri-parser/issues/5) Improve `Parser::isHost` validation of registered name

### Deprecated

- None

### Removed

- None

## 1.0.4 - 2017-03-01

### Added

- None

### Fixed

- [issue #3](https://github.com/thephpleague/uri-parser/issues/3) the `-` (hyphen) character is a valid one for a scheme.

### Deprecated

- None

### Removed

- None

## 1.0.3 - 2017-02-06

### Added

- None

### Fixed

- idn_to_ascii uses `INTL_IDNA_VARIANT_UTS46` as `INTL_IDNA_VARIANT_2003` will be deprecated

### Deprecated

- None

### Removed

- None

## 1.0.2 - 2017-01-19

### Added

- None

### Fixed

- Notice when an invalid host starts with an empty label

### Deprecated

- None

### Removed

- None

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