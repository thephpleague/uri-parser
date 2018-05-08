<?php

/**
 * League.Uri (http://uri.thephpleague.com).
 *
 * @package    League\Uri
 * @subpackage League\Uri\Parser
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @license    https://github.com/thephpleague/uri-parser/blob/master/LICENSE (MIT License)
 * @version    2.0.0
 * @link       https://github.com/thephpleague/uri-parser/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
require __DIR__.'/../src/Parser/UriParser.php';
require __DIR__.'/../src/functions.php';

$uri = 'https://uri.thephpleague.com/5.0';
for ($i = 0; $i < 100000; $i++) {
    League\Uri\parse($uri);
}
