<?php

/**
 * League.Uri (http://uri.thephpleague.com/parser).
 *
 * @author  Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @license https://github.com/thephpleague/uri-parser/blob/master/LICENSE (MIT License)
 * @version 1.4.1
 * @link    https://uri.thephpleague.com/parser/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
require __DIR__.'/../vendor/autoload.php';

$uri = 'https://uri.thephpleague.com/5.0';
for ($i = 0; $i < 100000; $i++) {
    League\Uri\parse($uri);
}
