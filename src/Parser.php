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
 * a class to parse a URI string according to RFC3986
 *
 * @see     https://tools.ietf.org/html/rfc3986
 * @package League\Uri
 * @author  Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @since   0.1.0
 */
class Parser
{
    const INVALID_URI_CHARS = "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F\x7F";

    const SCHEME_VALID_STARTING_CHARS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    const SCHEME_VALID_CHARS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789+.-';

    const LABEL_VALID_STARTING_CHARS = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    const LOCAL_LINK_PREFIX = '1111111010';

    const URI_COMPONENTS = [
        'scheme' => null, 'user' => null, 'pass' => null, 'host' => null,
        'port' => null, 'path' => '', 'query' => null, 'fragment' => null,
    ];

    const SUB_DELIMITERS = '!$&\'()*+,;=';

    /**
     * Returns whether a Scheme is valid.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.1
     *
     * @param string $scheme
     *
     * @return bool
     */
    public function isScheme(string $scheme): bool
    {
        return '' === $scheme
            || (strlen($scheme) === strspn($scheme, self::SCHEME_VALID_CHARS)
                && false !== strpos(self::SCHEME_VALID_STARTING_CHARS, $scheme[0]));
    }

    /**
     * Returns whether a Host is valid.
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
            || filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
            || $this->isIpv6Host($host)
            || $this->isRegisteredName($host);
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
        return in_array($port, [null, ''], true)
            || filter_var($port, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);
    }

    /**
     * Parse an URI string into its components.
     *
     * @see Parser::parse
     *
     * @param string $uri
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
     * This method parses a URL and returns an associative array containing any
     * of the various components of the URL that are present.
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

        if (strlen($uri) !== strcspn($uri, self::INVALID_URI_CHARS)) {
            throw Exception::createFromInvalidCharacters($uri);
        }

        //if the first character is a known URI delimiter parsing can be simplified
        $first_char = $uri[0];

        //The URI is made of the fragment only
        if ('#' == $first_char) {
            $components = self::URI_COMPONENTS;
            $components['fragment'] = (string) substr($uri, 1);

            return $components;
        }

        //The URI is made of the query and fragment
        if ('?' == $first_char) {
            $components = self::URI_COMPONENTS;
            list($components['query'], $components['fragment']) = explode('#', substr($uri, 1), 2) + [null, null];

            return $components;
        }

        //The URI does not contain any scheme part
        if (0 === strpos($uri, '//')) {
            return $this->parseSchemeSpecificPart($uri);
        }

        //The URI is made of a path, query and fragment
        if ('/' == $first_char || false === strpos($uri, ':')) {
            return $this->parsePathQueryAndFragment($uri);
        }

        //Fallback parser
        return $this->fallbackParser($uri);
    }

    /**
     * Extract Components from an URI without a scheme part.
     *
     * The URI MUST start with the authority component
     * preceded by its delimiter the double slash ('//')
     *
     * ex: //user:pass@host:42/path?query#fragment
     *
     * The authority MUST adhere to the RFC3986 requirements.
     *
     * If the URI contains a path component it MUST be empty or absolute
     * according to RFC3986 path classification.
     *
     * This method returns an associative array containing all
     * the URI components.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.2
     * @see https://tools.ietf.org/html/rfc3986#section-3.3
     *
     * @param string $uri
     *
     * @throws Exception If the port is invalid
     *
     * @return array
     */
    protected function parseSchemeSpecificPart(string $uri): array
    {
        //We remove the authority delimiter
        $remaining_uri = (string) substr($uri, 2);
        $components = self::URI_COMPONENTS;

        //Parsing is done from the right upmost part to the left
        //1 - detect fragment, query and path part if any
        list($remaining_uri, $components['fragment']) = explode('#', $remaining_uri, 2) + [null, null];
        list($remaining_uri, $components['query']) = explode('?', $remaining_uri, 2) + [null, null];
        if (false !== ($pos = strpos($remaining_uri, '/'))) {
            list($remaining_uri, $components['path']) = explode('/', $remaining_uri, 2) + [null, null];
            $components['path'] = '/'.$components['path'];
        }

        //2 - The $remaining_uri represents the authority part
        //if the authority part is empty parsing is simplified
        if ('' === $remaining_uri) {
            $components['host'] = '';

            return $components;
        }

        //otherwise we split the authority into the user information and the hostname parts
        $parts = explode('@', $remaining_uri, 2);
        $hostname = array_pop($parts);
        $user_info = array_pop($parts);
        if (null !== $user_info) {
            list($components['user'], $components['pass']) = explode(':', $user_info, 2) + [null, null];
        }
        list($components['host'], $components['port']) = $this->parseHostname($hostname);

        return $components;
    }

    /**
     * Parse and validate the URI hostname
     *
     * @param string $hostname
     *
     * @return array
     */
    protected function parseHostname(string $hostname): array
    {
        if (false === strpos($hostname, '[')) {
            list($host, $port) = explode(':', $hostname, 2) + [null, null];

            return [$this->filterHost($host), $this->filterPort($port)];
        }

        $delimiter_offset = strpos($hostname, ']') + 1;
        if (isset($hostname[$delimiter_offset]) && ':' != $hostname[$delimiter_offset]) {
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
     * @throws Exception If the host component is invalid
     *
     * @return string|null
     */
    protected function filterHost($host)
    {
        if (null === $host || $this->isHost($host)) {
            return $host;
        }

        throw Exception::createFromInvalidHost($host);
    }

    /**
     * validate an Ipv6 Hostname
     *
     * @see http://tools.ietf.org/html/rfc6874#section-2
     * @see http://tools.ietf.org/html/rfc6874#section-4
     *
     * @param string $ipv6
     *
     * @return bool
     */
    protected function isIpv6Host(string $ipv6): bool
    {
        if ('][' !== substr($ipv6.$ipv6, strlen($ipv6) - 1, 2)) {
            return false;
        }

        $ipv6 = substr($ipv6, 1, -1);
        if (false === ($pos = strpos($ipv6, '%'))) {
            return (bool) filter_var($ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
        }

        $scope = rawurldecode(substr($ipv6, $pos));
        if (strlen($scope) !== strcspn($scope, '?#@[]')) {
            return false;
        }

        $ipv6 = substr($ipv6, 0, $pos);
        if (!filter_var($ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return false;
        }

        $reducer = function (string $carry, string $char): string {
            return $carry.str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        };

        $res = array_reduce(str_split(unpack('A16', inet_pton($ipv6))[1]), $reducer, '');

        return substr($res, 0, 10) === self::LOCAL_LINK_PREFIX;
    }

    /**
     * Returns whether the hostname is valid
     *
     * A valid registered name MUST:
     *
     * - contains at most 127 subdomains deep
     * - be limited to 255 octets in length
     *
     * @see https://en.wikipedia.org/wiki/Subdomain
     * @see https://tools.ietf.org/html/rfc1035#section-2.3.4
     * @see https://blogs.msdn.microsoft.com/oldnewthing/20120412-00/?p=7873/
     *
     * @param string $host
     *
     * @return bool
     */
    protected function isRegisteredName(string $host): bool
    {
        if ('.' === substr($host, -1, 1)) {
            $host = substr($host, 0, -1);
        }

        $labels = array_map([$this, 'toAscii'], explode('.', $host));

        return 127 > count($labels)
            && 253 > strlen(implode('.', $labels))
            && $labels === array_filter($labels, [$this, 'isHostLabel']);
    }

    /**
     * Convert a registered name label to its IDNA ASCII form.
     *
     * Conversion is done only if the label contains none valid label characters
     * if a '%' sub delimiter is detected the label MUST be rawurldecode prior to
     * making the conversion
     *
     * @param string $label
     *
     * @return string|false
     */
    protected function toAscii(string $label)
    {
        if (false !== strpos($label, '%')) {
            $label = rawurldecode($label);
        }

        if (strlen($label) === strspn($label, self::LABEL_VALID_STARTING_CHARS.'-')) {
            return $label;
        }

        return idn_to_ascii($label, IDNA_NONTRANSITIONAL_TO_ASCII, INTL_IDNA_VARIANT_UTS46);
    }

    /**
     * Returns whether the registered name label is valid
     *
     * A valid registered name label MUST:
     *
     * - not be empty
     * - contain 63 characters or less
     * - conform to the following ABNF
     *
     * reg-name = *( unreserved / pct-encoded / sub-delims )
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.2.2
     *
     * @param string $label
     *
     * @return bool
     */
    protected function isHostLabel($label): bool
    {
        return '' != $label
            && 63 >= strlen($label)
            && strlen($label) == strspn($label, self::LABEL_VALID_STARTING_CHARS.'-_~'.self::SUB_DELIMITERS);
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
    protected function filterPort($port)
    {
        if (in_array($port, [null, false, ''], true)) {
            return null;
        }

        if (!$this->isPort($port)) {
            throw Exception::createFromInvalidPort($port);
        }

        return filter_var($port, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);
    }


    /**
     * Extract Components from an URI without scheme or authority part.
     *
     * The URI contains a path component and MUST adhere to path requirements
     * from RFC3986. The path can be
     *
     * <code>
     * path   = path-abempty    ; begins with "/" or is empty
     *        / path-absolute   ; begins with "/" but not "//"
     *        / path-noscheme   ; begins with a non-colon segment
     *        / path-rootless   ; begins with a segment
     *        / path-empty      ; zero characters
     * </code>
     *
     * ex: path?q#f
     * ex: /path
     * ex: /pa:th#f
     *
     * This method returns an associative array containing all
     * the URI components.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.3
     *
     * @param string $uri
     *
     * @throws Exception If the path component is invalid
     *
     * @return array
     */
    protected function parsePathQueryAndFragment(string $uri): array
    {
        //No scheme is present so we ensure that the path respects RFC3986
        if (false !== ($pos = strpos($uri, ':')) && false === strpos(substr($uri, 0, $pos), '/')) {
            throw Exception::createFromInvalidPath($uri);
        }

        $components = self::URI_COMPONENTS;

        //Parsing is done from the right upmost part to the left
        //1 - detect the fragment part if any
        list($remaining_uri, $components['fragment']) = explode('#', $uri, 2) + [null, null];

        //2 - detect the query and the path part
        list($components['path'], $components['query']) = explode('?', $remaining_uri, 2) + [null, null];

        return $components;
    }

    /**
     * Extract components from an URI containing a colon.
     *
     * Depending on the colon ":" position and on the string
     * composition before the presence of the colon, the URI
     * will be considered to have an scheme or not.
     *
     * <ul>
     * <li>In case no valid scheme is found according to RFC3986 the URI will
     * be parsed as an URI without a scheme and an authority</li>
     * <li>In case an authority part is detected the URI specific part is parsed
     * as an URI without scheme</li>
     * </ul>
     *
     * ex: email:johndoe@thephpleague.com?subject=Hellow%20World!
     *
     * This method returns an associative array containing all
     * the URI components.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.1
     * @see Parser::parsePathQueryAndFragment
     * @see Parser::parseSchemeSpecificPart
     *
     * @param string $uri
     *
     * @throws Exception If the URI scheme component is empty
     *
     * @return array
     */
    protected function fallbackParser(string $uri): array
    {
        //1 - we split the URI on the first detected colon character
        $parts = explode(':', $uri, 2);
        $remaining_uri = array_pop($parts);
        $scheme = array_shift($parts);

        //1.1 - a scheme can not be empty (ie a URI can not start with a colon)
        if ('' === $scheme) {
            throw Exception::createFromInvalidScheme($uri);
        }

        //2 - depending on the scheme presence and validity we will differ the parsing

        //2.1 - If the scheme part is invalid the URI may be an URI with a path-noscheme
        //      let's differ the parsing to the Parser::parsePathQueryAndFragment method
        if (!$this->isScheme($scheme)) {
            return $this->parsePathQueryAndFragment($uri);
        }

        $components = self::URI_COMPONENTS;
        $components['scheme'] = $scheme;

        //2.2 - if no scheme specific part is detect parsing is finished
        if ('' == $remaining_uri) {
            return $components;
        }

        //2.3 - if the scheme specific part is a double forward slash
        if ('//' === $remaining_uri) {
            $components['host'] = '';

            return $components;
        }

        //2.4 - if the scheme specific part starts with double forward slash
        //      we differ the remaining parsing to the Parser::parseSchemeSpecificPart method
        if (0 === strpos($remaining_uri, '//')) {
            $components = $this->parseSchemeSpecificPart($remaining_uri);
            $components['scheme'] = $scheme;

            return $components;
        }

        //2.5 - Parsing is done from the right upmost part to the left from the scheme specific part
        //2.5.1 - detect the fragment part if any
        list($remaining_uri, $components['fragment']) = explode('#', $remaining_uri, 2) + [null, null];

        //2.5.2 - detect the part and query part if any
        list($components['path'], $components['query']) = explode('?', $remaining_uri, 2) + [null, null];

        return $components;
    }
}
