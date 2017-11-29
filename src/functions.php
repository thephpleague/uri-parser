<?php
/**
 * League.Uri (http://uri.thephpleague.com)
 *
 * @package    League\Uri
 * @subpackage League\Uri
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @license    https://github.com/thephpleague/uri-parser/blob/master/LICENSE (MIT License)
 * @version    1.3.0
 * @link       https://github.com/thephpleague/uri-parser/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace League\Uri;

/**
 * Returns whether the URI host component is valid according to RFC3986.
 *
 * @see https://tools.ietf.org/html/rfc3986#section-3.2.2
 * @see Parser::isHost()
 *
 * @param string $host
 *
 * @return bool
 */
function is_host(string $host): bool
{
    static $parser;

    $parser = $parser ?? new Parser();

    return $parser->isHost($host);
}

/**
 * Returns whether the URI scheme component is valid according to RFC3986.
 *
 * @see https://tools.ietf.org/html/rfc3986#section-3.2.3
 * @see Parser::isPort()
 *
 * @param mixed $port
 *
 * @return bool
 */
function is_port($port): bool
{
    static $parser;

    $parser = $parser ?? new Parser();

    return $parser->isPort($port);
}

/**
 * Returns whether the URI scheme component is valid according to RFC3986.
 *
 * @see https://tools.ietf.org/html/rfc3986#section-3.1
 * @see Parser::isScheme()
 *
 * @param string $scheme
 *
 * @return bool
 */
function is_scheme(string $scheme): bool
{
    static $parser;

    $parser = $parser ?? new Parser();

    return $parser->isScheme($scheme);
}

/**
 * Parse an URI string into its components.
 *
 * This method parses a URL and returns an associative array containing any
 * of the various components of the URL that are present.
 *
 * @see https://tools.ietf.org/html/rfc3986
 * @see https://tools.ietf.org/html/rfc3986#section-2
 * @see Parser::__invoke()
 *
 * @param string $uri
 *
 * @throws Exception if the URI contains invalid characters
 *
 * @return array
 */
function parse(string $uri): array
{
    static $parser;

    $parser = $parser ?? new Parser();

    return $parser($uri);
}

/**
 * Generate the URI string representation from its hash representation
 * returned by Parser::__invoke() or PHP's parse_url
 *
 * If you supply your own hash you are responsible for providing
 * valid components without their URI delimiters.
 *
 * For security reason the pass component has been deprecated
 * as per RFC3986 and is never returned in the URI string
 *
 * @see https://tools.ietf.org/html/rfc3986#section-5.3
 * @see https://tools.ietf.org/html/rfc3986#section-7.5
 *
 * @param array $components
 *
 * @return string
 */
function build(array $components): string
{
    $uri = $components['path'] ?? '';
    if (isset($components['query'])) {
        $uri .= '?'.$components['query'];
    }

    if (isset($components['fragment'])) {
        $uri .= '#'.$components['fragment'];
    }

    if (isset($components['host'])) {
        $authority = $components['host'];
        if (isset($components['port'])) {
            $authority .= ':'.$components['port'];
        }

        if (isset($components['user'])) {
            $authority = $components['user'].'@'.$authority;
        }

        $uri = '//'.$authority.$uri;
    }

    if (isset($components['scheme'])) {
        return $components['scheme'].':'.$uri;
    }

    return  $uri;
}
