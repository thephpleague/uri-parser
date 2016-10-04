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
    const REGEXP_INVALID_URI_CHAR = ',[\x0-\x1F|\x7F],';
    const REGEXP_INVALID_USER = ',[/?#@:],';
    const REGEXP_INVALID_PASS = ',[/?#@],';
    const REGEXP_INVALID_IP_SCOPED = ',[^\x20-\x7F]|[?#@\[\]],';
    const REGEXP_URI = ',^
        ((?<scheme>[^:/?\#]+):)?    # URI scheme component
        (?<authority>//([^/?\#]*))? # URI authority part
        (?<path>[^?\#]*)            # URI path component
        (?<query>\?([^\#]*))?       # URI query component
        (?<fragment>\#(.*))?        # URI fragment component
    ,x';
    const REGEXP_SCHEME = ',^([a-z]([-a-z0-9+.]+)?)?$,i';
    const REGEXP_AUTHORITY = ',^(?<userinfo>(?<ucontent>.*?)@)?(?<hostname>.*?)?$,';
    const REGEXP_REVERSE_HOSTNAME = ',^((?<port>[^(\[\])]*):)?(?<host>.*)?$,';
    const REGEXP_HOST_IPV6 = ',^\[(?P<ipv6>.*?)\]$,';
    const REGEXP_HOST_LABEL = '/^[0-9a-z]([0-9a-z-]{0,61}[0-9a-z])?$/i';
    const PORT_MINIMUM = 1;
    const PORT_MAXIMUM = 65535;
    const LOCAL_LINK_PREFIX = '1111111010';

    /**
     * Parse a string as an URI according to the regexp form rfc3986
     *
     * @param string $uri
     *
     * @return array
     */
    public function __invoke($uri)
    {
        $parts = $this->extractUriParts($uri);
        if (!$this->isValidUri($parts)) {
            throw new InvalidArgumentException(sprintf('Invalid Uri `%s`', $uri));
        }

        $auth = $this->parseAuthority($parts['authority']);

        return [
            'scheme' => '' === $parts['scheme'] ? null : $parts['scheme'],
            'user' => $auth['user'],
            'pass' => $auth['pass'],
            'host' => $auth['host'],
            'port' => $auth['port'],
            'path' => $parts['path'],
            'query' => '' === $parts['query'] ? null : mb_substr($parts['query'], 1, null, 'UTF-8'),
            'fragment' => '' === $parts['fragment'] ? null : mb_substr($parts['fragment'], 1, null, 'UTF-8'),
        ];
    }

    /**
     * Extract URI parts
     *
     * @see http://tools.ietf.org/html/rfc3986#appendix-B
     *
     * @param string $uri The URI to split
     *
     * @return string[]
     */
    private function extractUriParts($uri)
    {
        if (preg_match(self::REGEXP_INVALID_URI_CHAR, $uri)) {
            throw new InvalidArgumentException(sprintf('Invalid Uri `%s` contains invalid characters', $uri));
        }

        preg_match(self::REGEXP_URI, $uri, $parts);
        $parts += ['query' => '', 'fragment' => ''];

        if (preg_match(self::REGEXP_SCHEME, $parts['scheme'])) {
            return $parts;
        }

        $parts['path'] = $parts['scheme'].':'.$parts['authority'].$parts['path'];
        $parts['scheme'] = '';
        $parts['authority'] = '';

        return $parts;
    }

    private function isValidUri(array $components)
    {
        if ('' != $components['authority']) {
            return '' === $components['path'] || strpos($components['path'], '/') === 0;
        }

        if ('' != $components['scheme'] || false === ($pos = strpos($components['path'], ':'))) {
            return true;
        }

        return false !== strpos(substr($components['path'], 0, $pos), '/');
    }

    /**
     * Parse a URI authority part into its components
     *
     * @param string $authority
     *
     * @return array
     */
    private function parseAuthority($authority)
    {
        if ('' === $authority) {
            return ['user' => null, 'pass' => null, 'host' => null, 'port' => null];
        }

        $content = mb_substr($authority, 2, null, 'UTF-8');
        if ('' === $content) {
            return ['user' => null, 'pass' => null, 'host' => '', 'port' => null];
        }

        $res = [];
        preg_match(self::REGEXP_AUTHORITY, $content, $auth);
        if ('' !== $auth['userinfo']) {
            $userinfo = explode(':', $auth['ucontent'], 2);
            $res = ['user' => array_shift($userinfo), 'pass' => array_shift($userinfo)];
        }

        return $this->parseHostname($auth['hostname']) + $res + ['user' => null, 'pass' => null];
    }

    /**
     * Parse the hostname into its components Host and Port
     *
     * No validation is done on the port or host component found
     *
     * @param string $hostname
     *
     * @return array
     */
    private function parseHostname($hostname)
    {
        $components = ['host' => null, 'port' => null];
        $hostname = strrev($hostname);
        if (preg_match(self::REGEXP_REVERSE_HOSTNAME, $hostname, $res)) {
            $components['port'] = strrev($res['port']);
            $components['host'] = strrev($res['host']);
        }
        $components['port'] = $this->filterPort($components['port']);
        $components['host'] = $this->filterHost($components['host']);

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
        if (in_array($port, [null, ''])) {
            return null;
        }

        $port = filter_var($port, FILTER_VALIDATE_INT, ['options' => [
            'min_range' => self::PORT_MINIMUM,
            'max_range' => self::PORT_MAXIMUM,
        ]]);

        if ($port) {
            return $port;
        }

        throw new InvalidArgumentException('The submitted port is invalid');
    }

    /**
     * validate the host component
     *
     * @param string $host
     *
     * @throws InvalidArgumentException If the host component is invalid
     *
     * @return string
     */
    private function filterHost($host)
    {
        if ($this->isValidHostname($host) || $this->isValidIpv6Host($host)) {
            return $host;
        }

        throw new InvalidArgumentException('The submitted host is invalid');
    }

    /**
     * validate and filter a Ipv6 Hostname
     *
     * @see http://tools.ietf.org/html/rfc6874#section-2
     * @see http://tools.ietf.org/html/rfc6874#section-4
     *
     * @param string $str
     *
     * @return string|false
     */
    private function isValidIpv6Host($str)
    {
        if (!preg_match(self::REGEXP_HOST_IPV6, $str, $matches)) {
            return false;
        }

        if (false === ($pos = strpos($matches['ipv6'], '%'))) {
            return (bool) filter_var($matches['ipv6'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
        }

        if (preg_match(self::REGEXP_INVALID_IP_SCOPED, rawurldecode(substr($matches['ipv6'], $pos)))) {
            return false;
        }

        $ipv6 = substr($matches['ipv6'], 0, $pos);
        if (!filter_var($ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return false;
        }

        $convert = function ($carry, $char) {
            return $carry.str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        };
        $res = array_reduce(str_split(unpack('A16', inet_pton($ipv6))[1]), $convert, '');

        return substr($res, 0, 10) === self::LOCAL_LINK_PREFIX;
    }

    /**
     * Validate a string only host
     *
     * @param string $str
     *
     * @return array
     */
    private function isValidHostname($host)
    {
        if ('.' === mb_substr($host, -1, 1, 'UTF-8')) {
            $host = mb_substr($host, 0, -1, 'UTF-8');
        }

        $labels = array_map(function ($value) {
            return idn_to_ascii($value);
        }, explode('.', $host));

        $verifs = array_filter($labels, function ($value) {
            return '' !== trim($value);
        });

        return $verifs === $labels
            && 127 > count($labels)
            && empty(preg_grep(self::REGEXP_HOST_LABEL, $labels, PREG_GREP_INVERT));
    }
}
