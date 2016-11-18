<?php
/**
 * League.Uri (http://uri.thephpleague.com)
 *
 * @package    League\Uri
 * @subpackage League\Uri
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @copyright  2016 Ignace Nyamagana Butera
 * @license    https://github.com/thephpleague/uri-parser/blob/master/LICENSE (MIT License)
 * @version    0.2.0
 * @link       https://github.com/thephpleague/uri-parser/
 */
namespace League\Uri;

/**
 * a class to parse a URI string according to RFC3986
 *
 * @see     https://tools.ietf.org/html/rfc3986
 * @package League\Uri
 * @author  Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @since   0.1.0
 */
final class Parser
{
    use HostValidation;

    const INVALID_URI_CHARS = "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F\x7F";

    const SCHEME_VALID_STARTING_CHARS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    const SCHEME_VALID_CHARS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789+.';

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
     * Parse an URI string into its components.
     *
     * This method parses a URL and returns an associative array containing any
     * of the various components of the URL that are present.
     *
     * <code>
     * $components = (new Parser())->__invoke('http://foo@test.example.com:42?query#');
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
     * @throws ParserException if the URI contains invalid characters
     *
     * @return array
     */
    public function __invoke($uri)
    {
        if ('' === $uri) {
            return self::$components;
        }

        $final = self::$components;
        if ('/' === $uri) {
            $final['path'] = '/';

            return $final;
        }

        if ('//' === $uri) {
            $final['host'] = '';

            return $final;
        }

        if (strlen($uri) !== strcspn($uri, self::INVALID_URI_CHARS)) {
            throw ParserException::createFromInvalidCharacters($uri);
        }

        //if the first characters is a known URI delimiter parsing
        //can be simplified
        $first_char = $uri[0];

        //The URI is made of the fragment only
        if ('#' === $first_char) {
            $final['fragment'] = (string) substr($uri, 1);

            return $final;
        }

        //The URI is made of the query and fragment
        if ('?' === $first_char) {
            $parts = explode('#', substr($uri, 1), 2);
            $final['query'] = array_shift($parts);
            $final['fragment'] = array_shift($parts);

            return $final;
        }

        //The URI does not contain any scheme part
        if (0 === strpos($uri, '//')) {
            return $this->parseUriWithoutScheme(substr($uri, 2));
        }

        //The URI is made of a path, query and fragment
        if ('/' === $first_char || false === strpos($uri, ':')) {
            return $this->parseUriWithoutSchemeAndAuthority($uri);
        }

        //Fallback parser
        return $this->parseUriWithColonCharacter($uri);
    }

    /**
     * Extract Components from an URI without a scheme part.
     *
     * The URI MUST start with the authority component not
     * preceded by its delimiter the double slash ('//')
     *
     * ex: user:pass@host:42/path?query#fragment
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
     * @see Parser::__invoke
     *
     * @param string $uri
     *
     * @throws ParserException If the port is invalid
     *
     * @return array
     */
    private function parseUriWithoutScheme($uri)
    {
        //Parsing is done from the right upmost part to the left
        //1 - detect the fragment part if any
        $final = self::$components;
        $parts = explode('#', $uri, 2);
        $uri = array_shift($parts);
        $final['fragment'] = array_shift($parts);

        //2 - detect the query part if any
        $parts = explode('?', $uri, 2);
        $uri = array_shift($parts);
        $final['query'] = array_shift($parts);

        //3 - detect the path part if any
        if (false !== ($pos = strpos($uri, '/'))) {
            $final['path'] = substr($uri, $pos);
            $uri = substr($uri, 0, $pos);
        }

        //4 - detect and parse the authority
        //if the uri is empty then the host is
        //set to an empty string
        if ('' === $uri) {
            $final['host'] = '';

            return $final;
        }

        //4.1 - split the authority into the userInfo part
        // and the hostname
        $authority = explode('@', $uri, 2);
        $hostname = array_pop($authority);
        $user_info = array_pop($authority);

        //4.2 - parse the user info part if present
        if (null !== $user_info) {
            $parts = explode(':', $user_info, 2);
            $final['user'] = array_shift($parts);
            $final['pass'] = array_shift($parts);
        }

        //4.3 - parse the hostname
        //4.3.1 Parsing the hostname if the host is not an IPv6 or IPfuture
        if (false === ($pos = strpos($hostname, ']'))) {
            $parts = explode(':', $hostname, 2);
            $host = array_shift($parts);
            $final['host'] = $this->filterHost($host);

            $port = array_shift($parts);
            $final['port'] = $this->filterPort($port);

            return $final;
        }

        //4.3.2 - Parsing the hostname if the host is an IPv6 or IPfuture
        $port_delimiter_index = $pos + 1;
        $final['host'] = $this->filterHost(substr($hostname, 0, $port_delimiter_index));
        if (!isset($hostname[$port_delimiter_index])) {
            return $final;
        }

        if (':' !== $hostname[$port_delimiter_index]) {
            throw ParserException::createFromInvalidPort(substr($hostname, $port_delimiter_index));
        }

        $final['port'] = $this->filterPort(substr($hostname, $port_delimiter_index + 1));

        return $final;
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
     * @see Parser::__invoke
     *
     * @param string $uri
     *
     * @throws ParserException If the path component is invalid
     *
     * @return array
     */
    private function parseUriWithoutSchemeAndAuthority($uri)
    {
        //No scheme is present so we ensure that if presence of a path-noscheme
        //RFC3986 is respected
        if (false !== ($pos = strpos($uri, ':')) && false === strpos(substr($uri, 0, $pos), '/')) {
            throw ParserException::createFromInvalidPath($uri);
        }

        //Parsing is done from the right upmost part to the left
        //1 - detect the fragment part if any
        $final = self::$components;
        $parts = explode('#', $uri, 2);
        $uri = array_shift($parts);
        $final['fragment'] = array_shift($parts);

        //2 - detect the query and the path part
        $parts = explode('?', $uri, 2);
        $final['path'] = array_shift($parts);
        $final['query'] = array_shift($parts);

        return $final;
    }

    /**
     * Validate a port number.
     *
     * An exception is raised for ports outside the established TCP and UDP port ranges.
     *
     * @param mixed $port the port number
     *
     * @throws ParserException If the port number is invalid.
     *
     * @return null|int
     */
    private function filterPort($port)
    {
        if ('' == $port) {
            return null;
        }

        $formatted_port = filter_var($port, FILTER_VALIDATE_INT, ['options' => [
            'min_range' => 1,
            'max_range' => 65535,
        ]]);

        if ($formatted_port) {
            return $formatted_port;
        }

        throw ParserException::createFromInvalidPort($port);
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
     * @see Parser::parseUriWithoutSchemeAndAuthority
     * @see Parser::parseUriWithoutScheme
     * @see Parser::__invoke
     *
     * @param string $uri
     *
     * @throws ParserException If the URI scheme component is empty
     *
     * @return array
     */
    private function parseUriWithColonCharacter($uri)
    {
        //1 - we split the URI on the first detected colon character
        $parts = explode(':', $uri, 2);
        $scheme_specific_part = array_pop($parts);
        $scheme = array_shift($parts);

        //1.1 - a scheme can not be empty (ie a URI can not start with a colon)
        if ('' === $scheme) {
            throw ParserException::createFromInvalidScheme($uri);
        }

        //2 - depending on the scheme presence and validity we will differ the
        //    parsing

        //2.1 - If the scheme part is invalid the URI may be an URI with a
        //      path-noscheme let's differ the parsing to the
        //      Parser::parseUriWithoutSchemeAndAuthority method
        if (strlen($scheme) !== strspn($scheme, self::SCHEME_VALID_CHARS)
            || false === strpos(self::SCHEME_VALID_STARTING_CHARS, $scheme[0])) {
            return $this->parseUriWithoutSchemeAndAuthority($uri);
        }

        $final = self::$components;
        $final['scheme'] = $scheme;

        //2.2 - if no scheme specific part is detect parsing is finished
        if (in_array($scheme_specific_part, [null, ''], true)) {
            return $final;
        }

        //2.3 - if the scheme specific part is a double forward slash
        if ('//' === $scheme_specific_part) {
            $final['host'] = '';

            return $final;
        }

        //2.4 - if the scheme specific part starts with double forward slash
        //      we differ the remaining parsing to the
        //      Parser::parseUriWithoutScheme method
        if (0 === strpos($scheme_specific_part, '//')) {
            $final = $this->parseUriWithoutScheme(substr($scheme_specific_part, 2));
            $final['scheme'] = $scheme;

            return $final;
        }

        //2.5 - Parsing is done from the right upmost part to the left from
        //      the scheme specific part

        //2.5.1 - detect the fragment part if any
        $parts = explode('#', $scheme_specific_part, 2);
        $scheme_specific_part = array_shift($parts);
        $final['fragment'] = array_shift($parts);

        //2.5.2 - detect the part and query part if any
        $parts = explode('?', $scheme_specific_part, 2);
        $final['path'] = array_shift($parts);
        $final['query'] = array_shift($parts);

        return $final;
    }
}
