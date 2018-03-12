League Uri Parser
=======

[![Build Status](https://img.shields.io/travis/thephpleague/uri-parser/master.svg?style=flat-square)](https://travis-ci.org/thephpleague/uri-parser)
[![Latest Version](https://img.shields.io/github/release/thephpleague/uri-parser.svg?style=flat-square)](https://github.com/thephpleague/uri-parser/releases)

This package contains a userland PHP uri parser compliant with [RFC 3986](http://tools.ietf.org/html/rfc3986).

System Requirements
-------

You need:

- **PHP >= 7.0** but the latest stable version of PHP is recommended


While the library no longer requires the `ext/intl` extension, it is strongly advise to install this extension if you are dealing with URIs containing non-ASCII host. Without the extension, the parser will throw an exception if such URI is parsed.


Installation
--------

```bash
$ composer require league/uri-parser
```

Documentation
---------

Full documentation can be found at [uri.thephpleague.com](http://uri.thephpleague.com).

Testing
-------

`League Uri Parser` has a :

- a [PHPUnit](https://phpunit.de) test suite
- a coding style compliance test suite using [PHP CS Fixer](http://cs.sensiolabs.org/).
- a code analysis compliance test suite using [PHPStan](https://github.com/phpstan/phpstan).

To run the tests, run the following command from the project folder.

``` bash
$ composer test
```

Contributing
-------

Contributions are welcome and will be fully credited. Please see [CONTRIBUTING](.github/CONTRIBUTING.md) and [CONDUCT](CONDUCT.md) for details.

Security
-------

If you discover any security related issues, please email nyamsprod@gmail.com instead of using the issue tracker.

Credits
-------

- [ignace nyamagana butera](https://github.com/nyamsprod)
- [All Contributors](https://github.com/thephpleague/uri-parser/contributors)

License
-------

The MIT License (MIT). Please see [License File](LICENSE) for more information.