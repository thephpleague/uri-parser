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

use InvalidArgumentException;

/**
 * a Trait to validate a Hostname
 *
 * @see     https://tools.ietf.org/html/rfc3986
 * @package League\Uri
 * @author  Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @since   0.2.0
 */
class ParserException extends InvalidArgumentException
{
    /**
     * Returns a new Instance from an error in URI characters
     *
     * @param string $uri
     *
     * @return self
     */
    public static function createFromInvalidCharacters($uri)
    {
        return new self(sprintf('The submitted uri `%s` contains invalid characters', $uri));
    }

    /**
     * Returns a new Instance from an error in URI characters
     *
     * @param string $uri
     *
     * @return self
     */
    public static function createFromInvalidScheme($uri)
    {
        return new self(sprintf('The submitted uri `%s` contains an invalid scheme', $uri));
    }

    /**
     * Returns a new Instance from an error in Host validation
     *
     * @param string $host
     *
     * @return self
     */
    public static function createFromInvalidHost($host)
    {
        return new self(sprintf('The submitted host `%s` is invalid', $host));
    }

    /**
     * Returns a new Instance from an error in port validation
     *
     * @param string $port
     *
     * @return self
     */
    public static function createFromInvalidPort($port)
    {
        return new self(sprintf('The submitted port `%s` is invalid', $port));
    }

    /**
     * Returns a new Instance from an error in Uri path component
     *
     * @param string $uri
     *
     * @return self
     */
    public static function createFromInvalidPath($uri)
    {
        return new self(sprintf('The submitted uri `%s` contains an invalid path', $uri));
    }
}
