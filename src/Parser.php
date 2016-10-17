<?php
/**
 * League.Uri (http://uri.thephpleague.com)
 *
 * @package    League\Uri
 * @subpackage League\Uri
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @copyright  2016 Ignace Nyamagana Butera
 * @license    https://github.com/thephpleague/uri-parser/blob/master/LICENSE (MIT License)
 * @version    1.0.0
 * @link       https://github.com/thephpleague/uri-parser/
 */
namespace League\Uri;

use InvalidArgumentException;

/**
 * a class to parse a URI string according to RFC3986
 *
 * @see     https://tools.ietf.org/html/rfc3986
 * @package League\Uri
 * @author  Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @since   1.0.0
 */
final class Parser
{
    use HostValidation;

    const INVALID_URI_CHARS = "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F\x7F";

    const REGEXP_URI = ',^
        (?<scheme>(?<scontent>[a-zA-Z]([-a-zA-Z0-9+.]+)):)? # URI scheme component
        (?<authority>//(?<acontent>                         # URI authority part
            (?<userinfo>                                    # URI userinfo component
                (?<user>[^:@]*)?                            # URI user component
                (\:(?<pass>[^@]*))?                         # URI pass component
            @)?
            (?<host>(\[.*\]|[^:/?\#])*)                     # URI host component
            (\:(?<port>[^/?\#]*))?                          # URI port component
        ))?
        (?<path>[^?\#]*)                                    # URI path component
        (?<query>\?(?<qcontent>[^\#]*))?                    # URI query component
        (?<fragment>\#(?<fcontent>.*))?                     # URI fragment component
    ,x';

    const LOCAL_LINK_PREFIX = '1111111010';

    /**
     * Default URI components
     *
     * @var array
     */
    private static $components = [
        'scheme' => null, 'user' => null, 'pass' => null, 'host' => null,
        'port' => null, 'path' => '', 'query' => null, 'fragment' => null,
    ];

    /**
     * Split an URI string into its component
     *
     * @param string $uri
     *
     * @return array
     */
    public function __invoke($uri)
    {
        $parts = $this->simpleExtract($uri);
        if (!empty($parts)) {
            return $parts;
        }

        return $this->complexExtract($uri);
    }

    /**
     * URI easy parsing with non complex URI
     *
     * Simplify parsing simple URI
     *
     * @param string $uri the URI string to parse
     *
     * @throws InvalidArgumentException If the URI contains invalid characters
     *
     * @return array
     */
    private function simpleExtract($uri)
    {
        if ('' === $uri) {
            return self::$components;
        }

        if (strlen($uri) !== strcspn($uri, self::INVALID_URI_CHARS)) {
            throw new InvalidArgumentException(sprintf('Invalid Uri `%s` contains invalid characters', $uri));
        }

        $first_char = $uri[0];
        if ('#' === $first_char) {
            return array_merge(self::$components, ['fragment' => substr($uri, 1)]);
        }

        if ('?' === $first_char) {
            $components = explode('#', substr($uri, 1), 2);
            return array_merge(self::$components, [
                'query' => array_shift($components),
                'fragment' =>  array_shift($components),
            ]);
        }

        return [];
    }

    /**
     * URI complex parsing using Regular expression
     *
     * @param string $uri the URI string to parse
     *
     * @throws InvalidArgumentException If the URI is not valid
     *
     * @return array
     */
    private function complexExtract($uri)
    {
        preg_match(self::REGEXP_URI, $uri, $parts);
        $parts += ['scheme' => '', 'authority' => '', 'path' => '', 'query' => '', 'fragment' => ''];
        if ($this->isValidUriParts($parts)) {
            return $this->formatParts($parts);
        }

        throw new InvalidArgumentException(sprintf('Invalid Uri `%s` contains invalid URI parts', $uri));
    }

    /**
     * Validate the URI againts RFC3986 rules
     *
     * @param array $parts URI parts
     *
     * @return bool
     */
    private function isValidUriParts(array $parts)
    {
        if ('' != $parts['authority']) {
            return '' === $parts['path'] || strpos($parts['path'], '/') === 0;
        }

        if ('' != $parts['scheme'] || false === ($pos = strpos($parts['path'], ':'))) {
            return true;
        }

        return false !== strpos(substr($parts['path'], 0, $pos), '/');
    }

    /**
     * Normalize URI parts to return an array similar to parse_url
     *
     * @param array $parts URI parts
     *
     * @return array
     */
    private function formatParts(array $parts)
    {
        $components = self::$components;
        $components['scheme'] = ('' !== $parts['scheme']) ? $parts['scontent'] : null;
        $components['query']  = ('' !== $parts['query']) ? $parts['qcontent'] : null;
        $components['fragment'] = ('' !== $parts['fragment']) ? $parts['fcontent'] : null;
        $components['path'] = $parts['path'];

        if ('' === $parts['authority']) {
            return $components;
        }

        if ('' === $parts['acontent']) {
            $components['host'] = '';
            return $components;
        }

        $components['port'] = $this->filterPort($parts['port']);
        $components['host'] = $this->filterHost($parts['host']);
        if ('' === $parts['userinfo']) {
            return $components;
        }

        $components['user'] = $parts['user'];
        if ('' !== $parts['user']) {
            $components['pass'] = $parts['pass'];
        }

        return $components;
    }

    /**
     * Validate a Port number
     *
     * @param mixed $port the port number
     *
     * @throws InvalidArgumentException If the port number is invalid
     *
     * @return null|int
     */
    private function filterPort($port)
    {
        if ('' === $port) {
            return null;
        }

        $port = filter_var($port, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);
        if ($port) {
            return $port;
        }

        throw new InvalidArgumentException('The submitted port is invalid');
    }
}
