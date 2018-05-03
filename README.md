League Uri Parser
=======

[![Build Status](https://img.shields.io/travis/thephpleague/uri-parser/master.svg?style=flat-square)](https://travis-ci.org/thephpleague/uri-parser)
[![Latest Version](https://img.shields.io/github/release/thephpleague/uri-parser.svg?style=flat-square)](https://github.com/thephpleague/uri-parser/releases)

This package contains a userland PHP uri parser and builder compliant with:

- [RFC 3986](http://tools.ietf.org/html/rfc3986).
- [RFC 3987](http://tools.ietf.org/html/rfc3987).
- [RFC 6874](https://tools.ietf.org/html/rfc6874).

As well as helper functions to help process URI.

```php
<?php

use League\Uri;

var_export(Uri\parse('http://www.example.com/'));
//returns the following array
//array(
//  'scheme' => 'http',
//  'user' => null,
//  'pass' => null,
//  'host' => 'www.example.com',
//  'port' => null,
//  'path' => '/',
//  'query' => null,
//  'fragment' => null,
//);

```

System Requirements
-------

You need:

- **PHP >= 7.0** but the latest stable version of PHP is recommended

While the library no longer requires the `ext/intl` extension, it is strongly advise to install this extension if you are dealing with URIs containing non-ASCII host. Without the extension, the parser will throw a `MissingIdnSupport` exception when trying to parse such URI.


Installation
--------

```bash
$ composer require league/uri-parser
```

Documentation
---------

### URI Parser

The `League\Uri\parse` function is a drop-in replacement to PHP's `parse_url` function, with the following differences:

#### The parser is RFC3986/RFC3987 compliant

```php
<?php

use League\Uri;

var_export(Uri\parse('http://foo.com?@bar.com/'));
//returns the following array
//array(
//  'scheme' => 'http',
//  'user' => null,
//  'pass' => null,
//  'host' => 'foo.com',
//  'port' => null,
//  'path' => '',
//  'query' => '@bar.com/',
//  'fragment' => null,
//);

var_export(parse_url('http://foo.com?@bar.com/'));
//returns the following array
//array(
//  'scheme' => 'http',
//  'host' => 'bar.com',
//  'user' => 'foo.com?',
//  'path' => '/',
//);
// Depending on the PHP version
```

#### The Parser returns all URI components.

```php
<?php

use League\Uri;

var_export(Uri\parse('http://www.example.com/'));
//returns the following array
//array(
//  'scheme' => 'http',
//  'user' => null,
//  'pass' => null,
//  'host' => 'www.example.com',
//  'port' => null,
//  'path' => '/',
//  'query' => null,
//  'fragment' => null,
//);

var_export(parse_url('http://www.example.com/'));
//returns the following array
//array(
//  'scheme' => 'http',
//  'host' => 'www.example.com',
//  'path' => '/',
//);
```

#### No extra parameters needed

```php
<?php

use League\Uri;

$uri = 'http://www.example.com/';

Uri\parse($uri)['query']; //returns null
parse_url($uri, PHP_URL_QUERY); //returns null
```

#### Empty component and undefined component are not treated the same

A distinction is made between an unspecified component, which will be set to `null` and an empty component which will be equal to the empty string.

```php
<?php

use League\Uri;

$uri = 'http://www.example.com?';

Uri\parse($uri)['query'];       //returns ''
parse_url($uri, PHP_URL_QUERY); //returns null
```

#### The path component is never equal to `null`

Since a URI is made of at least a path component, this component is never equal to `null`

```php
<?php

use League\Uri;

$uri = 'http://www.example.com?';
Uri\parse($uri)['path'];         //returns ''
parse_url($uri, PHP_URL_PATH); //returns null
```

#### The parser throws exception instead of returning `false`.

```php
<?php

use League\Uri;

$uri = '//example.com:toto';
Uri\parse($uri);
//throw a League\Uri\Parser\Exception

parse_url($uri); //returns false
```

#### The parser is not a validator

Just like `parse_url`, the `League\Uri\Parser` only parses and extracts from the URI string its components.

<p class="message-info">You still need to validate them against its scheme specific rules.</p>

```php
<?php

use League\Uri;

$uri = 'http:www.example.com';
var_export(Uri\parse($uri));
//returns the following array
//array(
//  'scheme' => 'http',
//  'user' => null,
//  'pass' => null,
//  'host' => null,
//  'port' => null,
//  'path' => 'www.example.com',
//  'query' => null,
//  'fragment' => null,
//);
```

### URI Builder

~~~php
<?php

use League\Uri;

function build(array $components): string
~~~

You can rebuild a URI from its hash representation returned by the `League\Uri\parse` function or PHP's `parse_url` function using the `League\Uri\build` function.  

If you supply your own hash you are responsible for providing valid encoded components without their URI delimiters.

~~~php
<?php

use League\Uri;

$base_uri = 'http://hello:world@foo.com?@bar.com/';
$components = Uri\parse($base_uri);
//returns the following array
//array(
//  'scheme' => 'http',
//  'user' => 'hello',
//  'pass' => 'world',
//  'host' => 'foo.com',
//  'port' => null,
//  'path' => '',
//  'query' => '@bar.com/',
//  'fragment' => null,
//);

$uri = Uri\build($components);

echo $uri; //displays http://hello@foo.com?@bar.com/
~~~

**The `League\Uri\build` function never output the `pass` component as suggested by [RFC3986](https://tools.ietf.org/html/rfc3986#section-7.5).**

### URI Components Validation

#### Scheme validation

If you have a scheme **string** you can validate it using the `League\Uri\is_scheme` function. The scheme is considered to be valid if it is:

- an empty string;
- a string which follow [RFC3986 rules](https://tools.ietf.org/html/rfc3986#section-3.1);

```php
<?php

use League\Uri;

Uri\is_scheme('example.com'); //returns false
Uri\is_scheme('ssh+svn'); //returns true
Uri\is_scheme('data');  //returns true
Uri\is_scheme('data:'); //returns false
```

#### Host validation

If you have a host **string** you can validate it using the `League\Uri\is_host` function. The host is considered to be valid if it is:

- an empty string;
- a IP Host;
- a registered name;

As described in [RFC3986](https://tools.ietf.org/html/rfc3986#section-3.2.2) and RFC3987.

```php
<?php

use League\Uri;

Uri\is_host('example.com'); //returns true
Uri\is_host('/path/to/yes'); //returns false
Uri\is_host('[:]'); //returns true
Uri\is_host('[127.0.0.1]'); //returns false
```

#### Port validation

If you have a port, you can validate it using the `League\Uri\is_port` function. The port is considered to be valid if it is:

- a numeric value which follow [RFC3986 rules](https://tools.ietf.org/html/rfc3986#section-3.2.3);

```php
<?php

use League\Uri;

Uri\is_port('example.com'); //returns false
Uri\is_port(888);           //returns true
Uri\is_port('23');    //returns true
Uri\is_port('data:'); //returns false
```

Testing
-------

The library has a :

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