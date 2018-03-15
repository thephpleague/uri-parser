<?php
/**
 * League.Uri (http://uri.thephpleague.com)
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

/**
 * A class to parse a URI string according to RFC3986.
 *
 * @see     https://tools.ietf.org/html/rfc3986
 * @package League\Uri
 * @author  Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @since   0.1.0
 */
final class Parser
{
    /**
     * @internal
     */
    const URI_COMPONENTS = [
        'scheme' => null, 'user' => null, 'pass' => null, 'host' => null,
        'port' => null, 'path' => '', 'query' => null, 'fragment' => null,
    ];

    /**
     * Returns whether a scheme is valid.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.1
     *
     * @param string $scheme
     *
     * @return bool
     */
    public function isScheme(string $scheme): bool
    {
        static $pattern = '/^[a-z][a-z\+\.\-]*$/i';

        return '' === $scheme || preg_match($pattern, $scheme);
    }

    /**
     * Returns whether a hostname is valid.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.2.2
     *
     * @param string $host
     *
     * @return bool
     */
    public function isHost(string $host): bool
    {
        return '' === $host
            || $this->isIpHost($host)
            || $this->isRegisteredName($host);
    }

    /**
     * Validate a IPv6/IPvfuture host
     *
     * @see http://tools.ietf.org/html/rfc3986#section-3.2.2
     * @see http://tools.ietf.org/html/rfc6874#section-2
     * @see http://tools.ietf.org/html/rfc6874#section-4
     *
     * @param string $host
     *
     * @return bool
     */
    private function isIpHost(string $host): bool
    {
        if ('[' !== ($host[0] ?? '') || ']' !== substr($host, -1)) {
            return false;
        }

        $ip = substr($host, 1, -1);
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return true;
        }

        static $ip_future = '/^
            v(?<version>[A-F0-9])+\.
            (?:
                (?<unreserved>[a-z0-9_~\-\.])|
                (?<sub_delims>[!$&\'()*+,;=:])  # also include the : character
            )+
        $/ix';
        if (preg_match($ip_future, $ip, $matches) && !in_array($matches['version'], ['4', '6'], true)) {
            return true;
        }

        if (false === ($pos = strpos($ip, '%'))) {
            return false;
        }

        static $gen_delims = '/[:\/?#\[\]@ ]/'; // Also includes space.
        if (preg_match($gen_delims, rawurldecode(substr($ip, $pos)))) {
            return false;
        }

        $ip = substr($ip, 0, $pos);
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return false;
        }

        //Only the address block fe80::/10 can have a Zone ID attach to
        //let's detect the link local significant 10 bits
        static $address_block = "\xfe\x80";

        return substr(inet_pton($ip) & $address_block, 0, 2) === $address_block;
    }


    /**
     * Returns whether the host is an IPv4 or a registered named
     *
     * @see http://tools.ietf.org/html/rfc3986#section-3.2.2
     *
     * @param string $host
     *
     * @throws MissingIdnSupport if the registered name contains non-ASCII characters
     *                           and IDN support or ICU requirement are not available or met.
     *
     * @return bool
     */
    private function isRegisteredName(string $host): bool
    {
        // Note that unreserved is purposely missing . as it is used to separate labels.
        static $reg_name = '/(?(DEFINE)
                (?<unreserved>[a-z0-9_~\-])
                (?<sub_delims>[!$&\'()*+,;=])
                (?<encoded>%[A-F0-9]{2})
                (?<reg_name>(?:(?&unreserved)|(?&sub_delims)|(?&encoded))*)
            )
            ^(?:(?&reg_name)\.)*(?&reg_name)\.?$/ix';
        if (preg_match($reg_name, $host)) {
            return true;
        }

        //to test IDN host non-ascii characters must be present in the host
        static $idn_pattern = '/[^\x20-\x7f]/';
        if (!preg_match($idn_pattern, $host)) {
            return false;
        }

        static $idn_support = null;
        $idn_support = $idn_support ?? function_exists('idn_to_ascii') && defined('INTL_IDNA_VARIANT_UTS46');
        if ($idn_support) {
            idn_to_ascii($host, IDNA_NONTRANSITIONAL_TO_ASCII, INTL_IDNA_VARIANT_UTS46, $arr);

            return 0 === $arr['errors'];
        }

        // @codeCoverageIgnoreStart
        // added because it is not possible in travis to disabled the ext/intl extension
        // see travis issue https://github.com/travis-ci/travis-ci/issues/4701
        throw new MissingIdnSupport(sprintf('the host `%s` could not be processed for IDN. Verify that ext/intl is installed for IDN support and that ICU is at least version 4.6.', $host));
        // @codeCoverageIgnoreEnd
    }

    /**
     * Returns whether a port is valid.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.2.2
     *
     * @param mixed $port
     *
     * @return bool
     */
    public function isPort($port): bool
    {
        if (null === $port || '' === $port) {
            return true;
        }

        return false !== filter_var($port, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
    }

    /**
     * Parse a URI string into its components.
     *
     * @see Parser::parse
     *
     * @param string $uri
     *
     * @throws Exception if the URI contains invalid characters
     *
     * @return array
     */
    public function __invoke(string $uri): array
    {
        return $this->parse($uri);
    }

    /**
     * Parse an URI string into its components.
     *
     * This method parses a URI and returns an associative array containing any
     * of the various components of the URI that are present.
     *
     * <code>
     * $components = (new Parser())->parse('http://foo@test.example.com:42?query#');
     * var_export($components);
     * //will display
     * array(
     *   'scheme' => 'http',           // the URI scheme component
     *   'user' => 'foo',              // the URI user component
     *   'pass' => null,               // the URI pass component
     *   'host' => 'test.example.com', // the URI host component
     *   'port' => 42,                 // the URI port component
     *   'path' => '',                 // the URI path component
     *   'query' => 'query',           // the URI query component
     *   'fragment' => '',             // the URI fragment component
     * );
     * </code>
     *
     * The returned array is similar to PHP's parse_url return value with the following
     * differences:
     *
     * <ul>
     * <li>All components are always present in the returned array</li>
     * <li>Empty and undefined component are treated differently. And empty component is
     *   set to the empty string while an undefined component is set to the `null` value.</li>
     * <li>The path component is never undefined</li>
     * <li>The method parses the URI following the RFC3986 rules but you are still
     *   required to validate the returned components against its related scheme specific rules.</li>
     * </ul>
     *
     * @see https://tools.ietf.org/html/rfc3986
     * @see https://tools.ietf.org/html/rfc3986#section-2
     *
     * @param string $uri
     *
     * @throws Exception if the URI contains invalid characters
     *
     * @return array
     */
    public function parse(string $uri): array
    {
        //simple URI which do not need any parsing
        static $simple_uri = [
            '' => [],
            '#' => ['fragment' => ''],
            '?' => ['query' => ''],
            '?#' => ['query' => '', 'fragment' => ''],
            '/' => ['path' => '/'],
            '//' => ['host' => ''],
        ];

        if (isset($simple_uri[$uri])) {
            return array_merge(self::URI_COMPONENTS, $simple_uri[$uri]);
        }

        static $pattern = '/[\x00-\x1f\x7f]/';
        if (preg_match($pattern, $uri)) {
            throw Exception::createFromInvalidCharacters($uri);
        }

        //if the first character is a known URI delimiter parsing can be simplified
        $first_char = $uri[0];

        //The URI is made of the fragment only
        if ('#' === $first_char) {
            $components = self::URI_COMPONENTS;
            $components['fragment'] = (string) substr($uri, 1);

            return $components;
        }

        //The URI is made of the query and fragment
        if ('?' === $first_char) {
            $components = self::URI_COMPONENTS;
            list($components['query'], $components['fragment']) = explode('#', substr($uri, 1), 2) + [1 => null];

            return $components;
        }

        //Fallback parser
        return $this->fallbackParser($uri);
    }

    /**
     * Parse the URI using the RFC3986 regular expression
     *
     * @see https://tools.ietf.org/html/rfc3986
     * @see https://tools.ietf.org/html/rfc3986#section-2
     *
     * @param string $uri
     *
     * @throws Exception if the URI contains an invalid scheme
     *
     * @return array
     */
    private function fallbackParser(string $uri): array
    {
        static $uri_pattern = ',^
            (?<scheme>(?<scontent>[^:/?\#]+):)?    # URI scheme component
            (?<authority>//(?<acontent>[^/?\#]*))? # URI authority part
            (?<path>[^?\#]*)                       # URI path component
            (?<query>\?(?<qcontent>[^\#]*))?       # URI query component
            (?<fragment>\#(?<fcontent>.*))?        # URI fragment component
        ,x';

        preg_match($uri_pattern, $uri, $parts);
        $parts += ['query' => '', 'fragment' => ''];

        if (':' === $parts['scheme'] || !$this->isScheme($parts['scontent'])) {
            throw Exception::createFromInvalidScheme($uri);
        }

        return array_merge(
            self::URI_COMPONENTS,
            $this->parseAuthority($parts),
            [
                'path' => $parts['path'],
                'scheme' => '' === $parts['scheme'] ? null : $parts['scontent'],
                'query' => '' === $parts['query'] ? null : $parts['qcontent'],
                'fragment' => '' === $parts['fragment'] ? null : $parts['fcontent'],
            ]
        );
    }

    /**
     * Parse the Authority part of tha URI
     *
     * @param array $uri_parts
     *
     * @return array
     */
    private function parseAuthority(array $uri_parts): array
    {
        if ('' === $uri_parts['authority']) {
            return [];
        }

        if ('' === $uri_parts['acontent']) {
            return ['host' => ''];
        }

        //otherwise we split the authority into the user information and the hostname parts
        $components = [];
        $auth_parts = explode('@', $uri_parts['acontent'], 2);
        $hostname = $auth_parts[1] ?? $auth_parts[0];
        $user_info = isset($auth_parts[1]) ? $auth_parts[0] : null;
        if (null !== $user_info) {
            list($components['user'], $components['pass']) = explode(':', $user_info, 2) + [1 => null];
        }
        list($components['host'], $components['port']) = $this->parseHostname($hostname);

        return $components;
    }

    /**
     * Parse and validate the URI hostname.
     *
     * @param string $hostname
     *
     * @throws Exception If the hostname is invalid
     *
     * @return array
     */
    private function parseHostname(string $hostname): array
    {
        if (false === strpos($hostname, '[')) {
            list($host, $port) = explode(':', $hostname, 2) + [1 => null];

            return [$this->filterHost($host), $this->filterPort($port)];
        }

        $delimiter_offset = strpos($hostname, ']') + 1;
        if (isset($hostname[$delimiter_offset]) && ':' !== $hostname[$delimiter_offset]) {
            throw Exception::createFromInvalidHostname($hostname);
        }

        return [
            $this->filterHost(substr($hostname, 0, $delimiter_offset)),
            $this->filterPort(substr($hostname, ++$delimiter_offset)),
        ];
    }

    /**
     * validate the host component
     *
     * @param string|null $host
     *
     * @throws Exception If the hostname is invalid
     *
     * @return string|null
     */
    private function filterHost($host)
    {
        if (null === $host || $this->isHost($host)) {
            return $host;
        }

        throw Exception::createFromInvalidHost($host);
    }

    /**
     * Validate a port number.
     *
     * An exception is raised for ports outside the established TCP and UDP port ranges.
     *
     * @param mixed $port the port number
     *
     * @throws Exception If the port number is invalid.
     *
     * @return null|int
     */
    private function filterPort($port)
    {
        static $pattern = '/^[0-9]+$/';

        if (false === $port || null === $port || '' === $port) {
            return null;
        }

        if (false !== ($res = filter_var($port, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]))) {
            return $res;
        }

        throw Exception::createFromInvalidPort($port);
    }
}
