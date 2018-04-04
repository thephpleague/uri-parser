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

use TypeError;

/**
 * A class to parse a URI string according to RFC3986.
 *
 * @internal use the functions League\Uri\parse and League\Uri\is_host instead
 *
 * @see     https://tools.ietf.org/html/rfc3986
 * @package League\Uri
 * @author  Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @since   0.1.0
 */
final class Parser
{
    /**
     * @internal default URI component values
     */
    const URI_COMPONENTS = [
        'scheme' => null, 'user' => null, 'pass' => null, 'host' => null,
        'port' => null, 'path' => '', 'query' => null, 'fragment' => null,
    ];

    /**
     * @internal simple URI which do not need any parsing
     */
    const URI_SCHORTCUTS = [
        '' => [],
        '#' => ['fragment' => ''],
        '?' => ['query' => ''],
        '?#' => ['query' => '', 'fragment' => ''],
        '/' => ['path' => '/'],
        '//' => ['host' => ''],
    ];

    /**
     * @internal
     */
    const REGEXP_INVALID_URI_CHARS = '/[\x00-\x1f\x7f]/';

    /**
     * @internal RFC3986 regular expression URI splitter
     */
    const REGEXP_URI_PARTS = ',^
        (?<scheme>(?<scontent>[^:/?\#]+):)?    # URI scheme component
        (?<authority>//(?<acontent>[^/?\#]*))? # URI authority part
        (?<path>[^?\#]*)                       # URI path component
        (?<query>\?(?<qcontent>[^\#]*))?       # URI query component
        (?<fragment>\#(?<fcontent>.*))?        # URI fragment component
    ,x';

    /**
     * @internal
     */
    const REGEXP_URI_SCHEME = '/^[a-z][a-z\+\.\-]*$/i';

    /**
     * @internal
     */
    const REGEXP_IP_FUTURE = '/^
        v(?<version>[A-F0-9])+\.
        (?:
            (?<unreserved>[a-z0-9_~\-\.])|
            (?<sub_delims>[!$&\'()*+,;=:])  # also include the : character
        )+
    $/ix';

    /**
     * @internal
     */
    const REGEXP_REGISTERED_NAME = '/(?(DEFINE)
        (?<unreserved>[a-z0-9_~\-])   # . is missing as it is used to separate labels
        (?<sub_delims>[!$&\'()*+,;=])
        (?<encoded>%[A-F0-9]{2})
        (?<reg_name>(?:(?&unreserved)|(?&sub_delims)|(?&encoded))*)
    )
    ^(?:(?&reg_name)\.)*(?&reg_name)\.?$/ix';

    /**
     * @internal
     */
    const REGEXP_INVALID_HOST_CHARS = '/
        [:\/?#\[\]@ ]  # gen-delims characters as well as the space character
    /ix';

    /**
     * @internal
     */
    const REGEXP_IDN_PATTERN = '/[^\x20-\x7f]/';

    /**
     * @internal
     *
     * Only the address block fe80::/10 can have a Zone ID attach to
     * let's detect the link local significant 10 bits
     */
    const ZONE_ID_ADDRESS_BLOCK = "\xfe\x80";

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
     * @param mixed $uri
     *
     * @throws Exception if the URI contains invalid characters
     *
     * @return array
     */
    public function parse($uri): array
    {
        if (!\is_scalar($uri) && !\method_exists($uri, '__toString')) {
            throw new TypeError(\sprintf('The uri must be a scalar or a stringable object `%s` given', \gettype($uri)));
        }

        $uri = (string) $uri;

        if (isset(self::URI_SCHORTCUTS[$uri])) {
            return \array_merge(self::URI_COMPONENTS, self::URI_SCHORTCUTS[$uri]);
        }

        if (\preg_match(self::REGEXP_INVALID_URI_CHARS, $uri)) {
            throw new Exception(\sprintf('The uri `%s` contains invalid characters', $uri));
        }

        //if the first character is a known URI delimiter parsing can be simplified
        $first_char = $uri[0];

        //The URI is made of the fragment only
        if ('#' === $first_char) {
            $components = self::URI_COMPONENTS;
            list(, $components['fragment']) = \explode('#', $uri, 2);

            return $components;
        }

        //The URI is made of the query and fragment
        if ('?' === $first_char) {
            $components = self::URI_COMPONENTS;
            list(, $partial) = \explode('?', $uri, 2);
            list($components['query'], $components['fragment']) = \explode('#', $partial, 2) + [1 => null];

            return $components;
        }

        return $this->split($uri);
    }

    /**
     * Split an URI using the RFC3986 rules and return its components.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3
     * @see https://tools.ietf.org/html/rfc3986#appendix-B
     *
     * @param string $uri
     *
     * @throws Exception if the URI contains an invalid scheme
     * @throws Exception if the URI contains an invalid path
     *
     * @return array
     */
    private function split(string $uri): array
    {
        \preg_match(self::REGEXP_URI_PARTS, $uri, $parts);
        $parts += ['query' => '', 'fragment' => ''];

        if (
            ':' === $parts['scheme']
            || ('' !== $parts['scontent'] && !\preg_match(self::REGEXP_URI_SCHEME, $parts['scontent']))
        ) {
            throw new Exception(\sprintf('The uri `%s` contains an invalid scheme', $uri));
        }

        if (
            '' === $parts['scheme'].$parts['authority']
            && false !== ($pos = \strpos($parts['path'], ':'))
            && false === \strpos(\substr($parts['path'], 0, $pos), '/')
        ) {
            throw new Exception(\sprintf('The uri `%s` contains an invalid path', $uri));
        }

        return \array_merge(
            self::URI_COMPONENTS,
            '' === $parts['authority'] ? [] : $this->parseAuthority($parts['acontent']),
            [
                'path' => $parts['path'],
                'scheme' => '' === $parts['scheme'] ? null : $parts['scontent'],
                'query' => '' === $parts['query'] ? null : $parts['qcontent'],
                'fragment' => '' === $parts['fragment'] ? null : $parts['fcontent'],
            ]
        );
    }

    /**
     * Parse the URI authority part.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.2
     *
     * @param string $authority
     *
     * @throws Exception If the host is invalid
     *
     * @return array
     */
    private function parseAuthority(string $authority): array
    {
        if ('' === $authority) {
            return ['host' => ''];
        }

        $parts = \explode('@', $authority, 2);
        $hostport = $parts[1] ?? $parts[0];
        $user_info = isset($parts[1]) ? $parts[0] : null;
        $components = [];
        if (null !== $user_info) {
            list($components['user'], $components['pass']) = \explode(':', $user_info, 2) + [1 => null];
        }

        list($host, $port) = $this->parseHostPort($hostport);
        $components['port'] = $this->filterPort($port);
        if ($this->isHost($host)) {
            $components['host'] = $host;

            return $components;
        }

        throw new Exception(\sprintf('The host `%s` is invalid', $host));
    }

    /**
     * Parse the URI host and port.
     *
     * The hostport contains the host and optionally the port.
     *
     * @param string $hostport
     *
     * @throws Exception If the URI part is invalid
     *
     * @return array
     */
    private function parseHostPort(string $hostport): array
    {
        if (false === ($pos = \strpos($hostport, '['))) {
            return \explode(':', $hostport, 2) + [1 => ''];
        }

        if (0 !== $pos || false === ($delimiter_offset = \strpos($hostport, ']'))) {
            throw new Exception(\sprintf('The URI part `%s` is invalid', $hostport));
        }

        ++$delimiter_offset;
        $host = \substr($hostport, 0, $delimiter_offset);
        $port = \substr($hostport, $delimiter_offset);
        if ('' === $port) {
            return [$host, $port];
        }

        if (':' === $port[0]) {
            return [$host, \substr($port, 1)];
        }

        throw new Exception(\sprintf('The URI part `%s` is invalid', $hostport));
    }

    /**
     * Validate a port number.
     *
     * An exception is raised for ports outside the established TCP and UDP port ranges.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.2.3
     *
     * @param string $port the port number
     *
     * @throws Exception If the port number is invalid.
     *
     * @return null|int
     */
    private function filterPort(string $port)
    {
        if ('' === $port) {
            return null;
        }

        if (false !== ($res = \filter_var($port, \FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]))) {
            return $res;
        }

        throw new Exception(\sprintf('The submitted port `%s` is invalid', $port));
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
        return $this->isIpHost($host) || $this->isRegisteredName($host);
    }

    /**
     * Validate a IPv6/IPvfuture host.
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
        if ('[' !== ($host[0] ?? '') || ']' !== \substr($host, -1)) {
            return false;
        }

        $ip = \substr($host, 1, -1);
        if (\filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV6)) {
            return true;
        }

        if (\preg_match(self::REGEXP_IP_FUTURE, $ip, $matches) && !\in_array($matches['version'], ['4', '6'], true)) {
            return true;
        }

        if (false === ($pos = \strpos($ip, '%'))) {
            return false;
        }

        if (\preg_match(self::REGEXP_INVALID_HOST_CHARS, \rawurldecode(\substr($ip, $pos)))) {
            return false;
        }

        $ip = \substr($ip, 0, $pos);
        if (!\filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV6)) {
            return false;
        }

        return \substr(\inet_pton($ip) & self::ZONE_ID_ADDRESS_BLOCK, 0, 2) === self::ZONE_ID_ADDRESS_BLOCK;
    }

    /**
     * Returns whether the host is an IPv4 or a registered named.
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
        if (\preg_match(self::REGEXP_REGISTERED_NAME, $host)) {
            return true;
        }

        //to test IDN host non-ascii characters must be present in the host
        if (!\preg_match(self::REGEXP_IDN_PATTERN, $host)) {
            return false;
        }

        static $idn_support = null;
        $idn_support = $idn_support ?? \function_exists('\idn_to_ascii') && \defined('\INTL_IDNA_VARIANT_UTS46');
        if ($idn_support) {
            \idn_to_ascii($host, \IDNA_NONTRANSITIONAL_TO_ASCII, \INTL_IDNA_VARIANT_UTS46, $arr);

            return 0 === $arr['errors'];
        }

        // @codeCoverageIgnoreStart
        // added because it is not possible in travis to disabled the ext/intl extension
        // see travis issue https://github.com/travis-ci/travis-ci/issues/4701
        throw new MissingIdnSupport(\sprintf('the host `%s` could not be processed for IDN. Verify that ext/intl is installed for IDN support and that ICU is at least version 4.6.', $host));
        // @codeCoverageIgnoreEnd
    }
}
