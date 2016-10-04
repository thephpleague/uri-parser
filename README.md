League Uri Parser
=======

This package contains a userland PHP uri parser compliant with [RFC 3986](http://tools.ietf.org/html/rfc3986).

System Requirements
-------

You need:

- **PHP >= 5.6.0** but the latest stable version of PHP is recommended
- the `mbstring` extension
- the `intl` extension

Installation
--------

```bash
$ composer require league/uri-parser
```

Usage
---------

```php
<?php

use League\Uri\Parser;

$url = 'scheme://[FEDC:BA98:7654:3210:FEDC:BA98:7654:3210]?query#fragment';
$parser = new Parser();
$components = $parser($url);
var_export($components);

/**
 * Displays
 * array (
 *  'scheme' => 'scheme',
 *  'user' => NULL,
 *  'pass' => NULL,
 *  'host' => '[FEDC:BA98:7654:3210:FEDC:BA98:7654:3210]',
 *  'port' => NULL,
 *  'path' => '',
 *  'query' => 'query',
 *  'fragment' => 'fragment',
 * )
 */
```

Testing
-------

`URI Parser` has a [PHPUnit](https://phpunit.de) test suite and a coding style compliance test suite using [PHP CS Fixer](http://cs.sensiolabs.org/). To run the tests, run the following command from the project folder.

```bash
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