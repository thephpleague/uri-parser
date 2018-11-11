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

declare(strict_types=1);

namespace League\Uri;

use League\Uri\Parser\RFC3986;

/**
 * Parse an URI string into its components.
 *
 * This method parses a URL and returns an associative array containing any
 * of the various components of the URL that are present.
 *
 * @see https://tools.ietf.org/html/rfc3986
 * @see https://tools.ietf.org/html/rfc3986#section-2
 * @see RFC3986::parse()
 *
 * @param mixed $uri
 *
 * @return array
 */
function parse($uri): array
{
    return RFC3986::parse($uri);
}

/**
 * Generate an URI string representation from its parsed representation
 * returned by League\Uri\parse() or PHP's parse_url.
 *
 * If you supply your own array, you are responsible for providing
 * valid components without their URI delimiters.
 *
 * For security reasons the password (pass) component has been deprecated
 * as per RFC3986 and is never returned in the URI string
 *
 * @see https://tools.ietf.org/html/rfc3986#section-5.3
 * @see https://tools.ietf.org/html/rfc3986#section-7.5
 * @see RFC3986::build()
 *
 * @param array $components
 *
 * @return string
 */
function build(array $components): string
{
    return RFC3986::build($components);
}
