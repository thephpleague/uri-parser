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
 * a Trait to validate a Hostname
 *
 * @see     https://tools.ietf.org/html/rfc3986
 * @package League\Uri
 * @author  Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @since   0.2.0
 */
trait HostValidation
{
    protected static $starting_label_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    protected static $local_link_prefix = '1111111010';

    /**
     * validate the host component
     *
     * @param string $host
     *
     * @throws ParserException If the host component is invalid
     *
     * @return string
     */
    protected function filterHost($host)
    {
        if ($this->isValidHost($host)) {
            return $host;
        }

        throw ParserException::createFromInvalidHost($host);
    }

    /**
     * Tell whether a Host is valid
     *
     * @param  string $host
     * @return bool
     */
    public function isValidHost($host)
    {
        return '' == $host
            || filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
            || $this->isValidHostnameIpv6($host)
            || $this->isValidHostname($host);
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
    protected function isValidHostnameIpv6($ipv6)
    {
        if (false === strpos($ipv6, '[')) {
            return false;
        }

        $ipv6 = substr($ipv6, 1, -1);
        if (false === ($pos = strpos($ipv6, '%'))) {
            return (bool) filter_var($ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
        }

        $scope = rawurldecode(substr($ipv6, $pos));
        if (strlen($scope) !== strcspn($scope, '?#@[]'.self::INVALID_URI_CHARS)) {
            return false;
        }

        $ipv6 = substr($ipv6, 0, $pos);
        if (!filter_var($ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return false;
        }

        $reducer = function ($carry, $char) {
            return $carry.str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        };

        $res = array_reduce(str_split(unpack('A16', inet_pton($ipv6))[1]), $reducer, '');

        return substr($res, 0, 10) === self::$local_link_prefix;
    }

    /**
     * Returns whether the hostname is valid
     *
     * @param string $host
     *
     * @return bool
     */
    protected function isValidHostname($host)
    {
        if ('.' === mb_substr($host, -1, 1, 'UTF-8')) {
            $host = mb_substr($host, 0, -1, 'UTF-8');
        }

        $labels = array_map('idn_to_ascii', explode('.', $host));

        return 127 > count($labels) && $labels === array_filter($labels, [$this, 'isValidHostLabel']);
    }

    /**
     * Returns whether the host label is valid
     *
     * @param string $label
     *
     * @return bool
     */
    protected function isValidHostLabel($label)
    {
        $pos = strlen($label);
        $delimiters = $label[0].$label[$pos - 1];

        return 2 === strspn($delimiters, static::$starting_label_chars)
            && $pos === strspn($label, static::$starting_label_chars.'-');
    }
}
