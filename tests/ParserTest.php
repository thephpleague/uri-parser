<?php

namespace LeagueTest\Uri;

use League\Uri\Exception;
use League\Uri\Parser;
use PHPUnit\Framework\TestCase;

/**
 * @group parser
 */
class ParserTest extends TestCase
{
    /**
     * @dataProvider validUriProvider
     * @param $uri
     * @param $expected
     */
    public function testParseSucced($uri, $expected)
    {
        $components = (new Parser())($uri);
        $this->assertSame($expected, $components);
    }

    public function validUriProvider()
    {
        return [
            'complete URI' => [
                'scheme://user:pass@host:81/path?query#fragment',
                [
                    'scheme' => 'scheme',
                    'user' => 'user',
                    'pass' => 'pass',
                    'host' => 'host',
                    'port' => 81,
                    'path' => '/path',
                    'query' => 'query',
                    'fragment' => 'fragment',
                ],
            ],
            'URI is not normalized' => [
                'ScheMe://user:pass@HoSt:81/path?query#fragment',
                [
                    'scheme' => 'ScheMe',
                    'user' => 'user',
                    'pass' => 'pass',
                    'host' => 'HoSt',
                    'port' => 81,
                    'path' => '/path',
                    'query' => 'query',
                    'fragment' => 'fragment',
                ],
            ],
            'URI without scheme' => [
                '//user:pass@HoSt:81/path?query#fragment',
                [
                    'scheme' => null,
                    'user' => 'user',
                    'pass' => 'pass',
                    'host' => 'HoSt',
                    'port' => 81,
                    'path' => '/path',
                    'query' => 'query',
                    'fragment' => 'fragment',
                ],
            ],
            'URI without empty authority only' => [
                '//',
                [
                    'scheme' => null,
                    'user' => null,
                    'pass' => null,
                    'host' => '',
                    'port' => null,
                    'path' => '',
                    'query' => null,
                    'fragment' => null,
                ],
            ],
            'URI without userinfo' => [
                'scheme://HoSt:81/path?query#fragment',
                [
                    'scheme' => 'scheme',
                    'user' => null,
                    'pass' => null,
                    'host' => 'HoSt',
                    'port' => 81,
                    'path' => '/path',
                    'query' => 'query',
                    'fragment' => 'fragment',
                ],
            ],
            'URI with empty userinfo' => [
                'scheme://@HoSt:81/path?query#fragment',
                [
                    'scheme' => 'scheme',
                    'user' => '',
                    'pass' => null,
                    'host' => 'HoSt',
                    'port' => 81,
                    'path' => '/path',
                    'query' => 'query',
                    'fragment' => 'fragment',
                ],
            ],
            'URI without port' => [
                'scheme://user:pass@host/path?query#fragment',
                [
                    'scheme' => 'scheme',
                    'user' => 'user',
                    'pass' => 'pass',
                    'host' => 'host',
                    'port' => null,
                    'path' => '/path',
                    'query' => 'query',
                    'fragment' => 'fragment',
                ],
            ],
            'URI with an empty port' => [
                'scheme://user:pass@host:/path?query#fragment',
                [
                    'scheme' => 'scheme',
                    'user' => 'user',
                    'pass' => 'pass',
                    'host' => 'host',
                    'port' => null,
                    'path' => '/path',
                    'query' => 'query',
                    'fragment' => 'fragment',
                ],
            ],
            'URI without user info and port' => [
                'scheme://host/path?query#fragment',
                [
                    'scheme' => 'scheme',
                    'user' => null,
                    'pass' => null,
                    'host' => 'host',
                    'port' => null,
                    'path' => '/path',
                    'query' => 'query',
                    'fragment' => 'fragment',
                ],
            ],
            'URI with host IP' => [
                'scheme://10.0.0.2/p?q#f',
                [
                    'scheme' => 'scheme',
                    'user' => null,
                    'pass' => null,
                    'host' => '10.0.0.2',
                    'port' => null,
                    'path' => '/p',
                    'query' => 'q',
                    'fragment' => 'f',
                ],
            ],
            'URI with scoped IP' => [
                'scheme://[fe80:1234::%251]/p?q#f',
                [
                    'scheme' => 'scheme',
                    'user' => null,
                    'pass' => null,
                    'host' => '[fe80:1234::%251]',
                    'port' => null,
                    'path' => '/p',
                    'query' => 'q',
                    'fragment' => 'f',
                ],
            ],
            'URI without authority' => [
                'scheme:path?query#fragment',
                [
                    'scheme' => 'scheme',
                    'user' => null,
                    'pass' => null,
                    'host' => null,
                    'port' => null,
                    'path' => 'path',
                    'query' => 'query',
                    'fragment' => 'fragment',
                ],
            ],
            'URI without authority and scheme' => [
                '/path',
                [
                    'scheme' => null,
                    'user' => null,
                    'pass' => null,
                    'host' => null,
                    'port' => null,
                    'path' => '/path',
                    'query' => null,
                    'fragment' => null,
                ],
            ],
            'URI with empty host' => [
                'scheme:///path?query#fragment',
                [
                    'scheme' => 'scheme',
                    'user' => null,
                    'pass' => null,
                    'host' => '',
                    'port' => null,
                    'path' => '/path',
                    'query' => 'query',
                    'fragment' => 'fragment',
                ],
            ],
            'URI with empty host and without scheme' => [
                '///path?query#fragment',
                [
                    'scheme' => null,
                    'user' => null,
                    'pass' => null,
                    'host' => '',
                    'port' => null,
                    'path' => '/path',
                    'query' => 'query',
                    'fragment' => 'fragment',
                ],
            ],
            'URI without path' => [
                'scheme://[FEDC:BA98:7654:3210:FEDC:BA98:7654:3210]?query#fragment',
                [
                    'scheme' => 'scheme',
                    'user' => null,
                    'pass' => null,
                    'host' => '[FEDC:BA98:7654:3210:FEDC:BA98:7654:3210]',
                    'port' => null,
                    'path' => '',
                    'query' => 'query',
                    'fragment' => 'fragment',
                ],
            ],
            'URI without path and scheme' => [
                '//[FEDC:BA98:7654:3210:FEDC:BA98:7654:3210]?query#fragment',
                [
                    'scheme' => null,
                    'user' => null,
                    'pass' => null,
                    'host' => '[FEDC:BA98:7654:3210:FEDC:BA98:7654:3210]',
                    'port' => null,
                    'path' => '',
                    'query' => 'query',
                    'fragment' => 'fragment',
                ],
            ],
            'URI without scheme with IPv6 host and port' => [
                '//[FEDC:BA98:7654:3210:FEDC:BA98:7654:3210]:42?query#fragment',
                [
                    'scheme' => null,
                    'user' => null,
                    'pass' => null,
                    'host' => '[FEDC:BA98:7654:3210:FEDC:BA98:7654:3210]',
                    'port' => 42,
                    'path' => '',
                    'query' => 'query',
                    'fragment' => 'fragment',
                ],
            ],
            'complete URI without scheme' => [
                '//user@[FEDC:BA98:7654:3210:FEDC:BA98:7654:3210]:42?q#f',
                [
                    'scheme' => null,
                    'user' => 'user',
                    'pass' => null,
                    'host' => '[FEDC:BA98:7654:3210:FEDC:BA98:7654:3210]',
                    'port' => 42,
                    'path' => '',
                    'query' => 'q',
                    'fragment' => 'f',
                ],
            ],
            'URI without authority and query' => [
                'scheme:path#fragment',
                [
                    'scheme' => 'scheme',
                    'user' => null,
                    'pass' => null,
                    'host' => null,
                    'port' => null,
                    'path' => 'path',
                    'query' => null,
                    'fragment' => 'fragment',
                ],
            ],
            'URI with empty query' => [
                'scheme:path?#fragment',
                [
                    'scheme' => 'scheme',
                    'user' => null,
                    'pass' => null,
                    'host' => null,
                    'port' => null,
                    'path' => 'path',
                    'query' => '',
                    'fragment' => 'fragment',
                ],
            ],
            'URI with query only' => [
                '?query',
                [
                    'scheme' => null,
                    'user' => null,
                    'pass' => null,
                    'host' => null,
                    'port' => null,
                    'path' => '',
                    'query' => 'query',
                    'fragment' => null,
                ],
            ],
            'URI without fragment' => [
                'tel:05000',
                [
                    'scheme' => 'tel',
                    'user' => null,
                    'pass' => null,
                    'host' => null,
                    'port' => null,
                    'path' => '05000',
                    'query' => null,
                    'fragment' => null,
                ],
            ],
            'URI with empty fragment' => [
                'scheme:path#',
                [
                    'scheme' => 'scheme',
                    'user' => null,
                    'pass' => null,
                    'host' => null,
                    'port' => null,
                    'path' => 'path',
                    'query' => null,
                    'fragment' => '',
                ],
            ],
            'URI with fragment only' => [
                '#fragment',
                [
                    'scheme' => null,
                    'user' => null,
                    'pass' => null,
                    'host' => null,
                    'port' => null,
                    'path' => '',
                    'query' => null,
                    'fragment' => 'fragment',
                ],
            ],
            'URI with empty fragment only' => [
                '#',
                [
                    'scheme' => null,
                    'user' => null,
                    'pass' => null,
                    'host' => null,
                    'port' => null,
                    'path' => '',
                    'query' => null,
                    'fragment' => '',
                ],
            ],
            'URI without authority 2' => [
                'path#fragment',
                [
                    'scheme' => null,
                    'user' => null,
                    'pass' => null,
                    'host' => null,
                    'port' => null,
                    'path' => 'path',
                    'query' => null,
                    'fragment' => 'fragment',
                ],
            ],
            'URI with empty query and fragment' => [
                '?#',
                [
                    'scheme' => null,
                    'user' => null,
                    'pass' => null,
                    'host' => null,
                    'port' => null,
                    'path' => '',
                    'query' => '',
                    'fragment' => '',
                ],
            ],
            'URI with absolute path' => [
                '/?#',
                [
                    'scheme' => null,
                    'user' => null,
                    'pass' => null,
                    'host' => null,
                    'port' => null,
                    'path' => '/',
                    'query' => '',
                    'fragment' => '',
                ],
            ],
            'URI with absolute authority' => [
                'https://thephpleague.com./p?#f',
                [
                    'scheme' => 'https',
                    'user' => null,
                    'pass' => null,
                    'host' => 'thephpleague.com.',
                    'port' => null,
                    'path' => '/p',
                    'query' => '',
                    'fragment' => 'f',
                ],
            ],
            'URI with absolute path only' => [
                '/',
                [
                    'scheme' => null,
                    'user' => null,
                    'pass' => null,
                    'host' => null,
                    'port' => null,
                    'path' => '/',
                    'query' => null,
                    'fragment' => null,
                ],
            ],
            'URI with empty query only' => [
                '?',
                [
                    'scheme' => null,
                    'user' => null,
                    'pass' => null,
                    'host' => null,
                    'port' => null,
                    'path' => '',
                    'query' => '',
                    'fragment' => null,
                ],
            ],
            'relative path' => [
                '../relative/path',
                [
                    'scheme' => null,
                    'user' => null,
                    'pass' => null,
                    'host' => null,
                    'port' => null,
                    'path' => '../relative/path',
                    'query' => null,
                    'fragment' => null,
                ],
            ],
            'complex authority' => [
                'http://a_.!~*\'(-)n0123Di%25%26:pass;:&=+$,word@www.zend.com',
                [
                    'scheme' => 'http',
                    'user' => 'a_.!~*\'(-)n0123Di%25%26',
                    'pass' => 'pass;:&=+$,word',
                    'host' => 'www.zend.com',
                    'port' => null,
                    'path' => '',
                    'query' => null,
                    'fragment' => null,
                ],
            ],
            'complex authority without scheme' => [
                '//a_.!~*\'(-)n0123Di%25%26:pass;:&=+$,word@www.zend.com',
                [
                    'scheme' => null,
                    'user' => 'a_.!~*\'(-)n0123Di%25%26',
                    'pass' => 'pass;:&=+$,word',
                    'host' => 'www.zend.com',
                    'port' => null,
                    'path' => '',
                    'query' => null,
                    'fragment' => null,
                ],
            ],
            'single word is a path' => [
                'http',
                [
                    'scheme' => null,
                    'user' => null,
                    'pass' => null,
                    'host' => null,
                    'port' => null,
                    'path' => 'http',
                    'query' => null,
                    'fragment' => null,
                ],
            ],
            'URI scheme with an empty authority' => [
                'http://',
                [
                    'scheme' => 'http',
                    'user' => null,
                    'pass' => null,
                    'host' => '',
                    'port' => null,
                    'path' => '',
                    'query' => null,
                    'fragment' => null,
                ],
            ],
            'single word is a path, no' => [
                'http:::/path',
                [
                    'scheme' => 'http',
                    'user' => null,
                    'pass' => null,
                    'host' => null,
                    'port' => null,
                    'path' => '::/path',
                    'query' => null,
                    'fragment' => null,
                ],
            ],
            'fragment with pseudo segment' => [
                'http://example.com#foo=1/bar=2',
                [
                    'scheme' => 'http',
                    'user' => null,
                    'pass' => null,
                    'host' => 'example.com',
                    'port' => null,
                    'path' => '',
                    'query' => null,
                    'fragment' => 'foo=1/bar=2',
                ],
            ],
            'empty string' => [
                '',
                [
                    'scheme' => null,
                    'user' => null,
                    'pass' => null,
                    'host' => null,
                    'port' => null,
                    'path' => '',
                    'query' => null,
                    'fragment' => null,
                ],
            ],
            'complex URI' => [
                'htà+d/s:totot',
                [
                    'scheme' => null,
                    'user' => null,
                    'pass' => null,
                    'host' => null,
                    'port' => null,
                    'path' => 'htà+d/s:totot',
                    'query' => null,
                    'fragment' => null,
                ],
            ],
            'scheme only URI' => [
                'http:',
                [
                    'scheme' => 'http',
                    'user' => null,
                    'pass' => null,
                    'host' => null,
                    'port' => null,
                    'path' => '',
                    'query' => null,
                    'fragment' => null,
                ],
            ],
            'RFC3986 LDAP example' => [
                'ldap://[2001:db8::7]/c=GB?objectClass?one',
                [
                    'scheme' => 'ldap',
                    'user' => null,
                    'pass' => null,
                    'host' => '[2001:db8::7]',
                    'port' => null,
                    'path' => '/c=GB',
                    'query' => 'objectClass?one',
                    'fragment' => null,
                ],
            ],
            'RFC3987 example' => [
                'http://bébé.bé./有词法别名.zh',
                [
                    'scheme' => 'http',
                    'user' => null,
                    'pass' => null,
                    'host' => 'bébé.bé.',
                    'port' => null,
                    'path' => '/有词法别名.zh',
                    'query' => null,
                    'fragment' => null,
                ],
            ],
            'colon detection respect RFC3986 (1)' => [
                'http://example.org/hello:12?foo=bar#test',
                [
                    'scheme' => 'http',
                    'user' => null,
                    'pass' => null,
                    'host' => 'example.org',
                    'port' => null,
                    'path' => '/hello:12',
                    'query' => 'foo=bar',
                    'fragment' => 'test',
                ],
            ],
            'colon detection respect RFC3986 (2)' => [
                '/path/to/colon:34',
                [
                    'scheme' => null,
                    'user' => null,
                    'pass' => null,
                    'host' => null,
                    'port' => null,
                    'path' => '/path/to/colon:34',
                    'query' => null,
                    'fragment' => null,
                ],
            ],
        ];
    }

    /**
     * @dataProvider invalidUriProvider
     * @param string $uri
     */
    public function testParseFailed($uri)
    {
        $this->expectException(Exception::class);
        (new Parser())($uri);
    }

    public function invalidUriProvider()
    {
        return [
            'invalid scheme (1)' => ['0scheme://host/path?query#fragment'],
            'invalid scheme (2)' => ['://host:80/p?q#f'],
            'invalid port (1)' => ['//host:port/path?query#fragment'],
            'invalid port (2)' => ['//host:892358/path?query#fragment'],
            'invalid ipv6 host (1)' => ['scheme://[127.0.0.1]/path?query#fragment'],
            'invalid ipv6 host (2)' => ['scheme://]::1[/path?query#fragment'],
            'invalid ipv6 host (3)' => ['scheme://[::1|/path?query#fragment'],
            'invalid ipv6 host (4)' => ['scheme://|::1]/path?query#fragment'],
            'invalid ipv6 host (5)' => ['scheme://[::1]./path?query#fragment'],
            'invalid ipv6 host (6)' => ['scheme://[[::1]]:80/path?query#fragment'],
            'invalid ipv6 scoped (1)' => ['scheme://[::1%25%23]/path?query#fragment'],
            'invalid ipv6 scoped (2)' => ['scheme://[fe80::1234::%251]/path?query#fragment'],
            'invalid host too long' => ['scheme://'.implode('.', array_fill(0, 128, 'a'))],
            'invalid host with starting empty label' => ['scheme://.example.com'],
            'invalid char on URI' => ["scheme://host/path/\r\n/toto"],
            'invalid path only URI' => ['2620:0:1cfe:face:b00c::3'],
            'invalid path PHP bug #72811' => ['[::1]:80'],
        ];
    }

    /**
     * @dataProvider validHostProvider
     */
    public function testHost($host, $expected)
    {
        $this->assertSame($expected, (new Parser())->isHost($host));
    }

    public function validHostProvider()
    {
        return [
            'RFC3986 registered name' => ['bebe.be', true],
            'RFC3987 registered name' => ['bébé.bé', true],
            'IPv4 host' => ['127.0.0.1', true],
            'IPv4 like host' => ['9.2.3', true],
            'IPv6 host' => ['[::]', true],
            'invalid IPv6 host (1)' => ['::1', false],
            'invalid IPv6 host (1)' => ['[fe80::1234::%251]', false],
            'invalid IPv6 host (2)' => ['[127.0.0.1]', false],
            'invalid IPv6 host (3)' => [']::1[', false],
            'invalid IPv6 host (4)' => ['[::1|', false],
            'invalid IPv6 host (5)' => ['|::1]', false],
            'invalid IPv6 host (6)' => ['[[::1]]', false],
            'invalid IPv6 host (7)' => ['[::1%25%23]', false],
            'empty host' => ['', true],
        ];
    }
}
