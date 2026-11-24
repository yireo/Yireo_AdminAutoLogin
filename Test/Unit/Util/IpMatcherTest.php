<?php declare(strict_types=1);

namespace Yireo\AdminAutoLogin\Test\Unit\Util;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yireo\AdminAutoLogin\Config\Config;
use Yireo\AdminAutoLogin\Util\IpMatcher;

class IpMatcherTest extends TestCase
{
    /**
     * @dataProvider provideMatches
     */
    #[DataProvider('provideMatches')]
    public function testMatches(string $ip, array $ranges, bool $expected): void
    {
        $this->assertSame($expected, (new IpMatcher())->matches($ip, $ranges));
    }

    public static function provideMatches(): array
    {
        $defaults = Config::DEFAULT_ALLOWED_IPS;

        return [
            'IPv4 loopback' => ['127.0.0.1', $defaults, true],
            'IPv6 loopback' => ['::1', $defaults, true],
            'private 10/8' => ['10.1.2.3', $defaults, true],
            'private 172.16/12 lower bound' => ['172.16.0.1', $defaults, true],
            'private 172.16/12 upper bound' => ['172.31.255.254', $defaults, true],
            'outside 172.16/12' => ['172.32.0.1', $defaults, false],
            'private 192.168/16' => ['192.168.1.10', $defaults, true],
            'IPv6 unique local' => ['fd12:3456::1', $defaults, true],
            'public IPv4' => ['8.8.8.8', $defaults, false],
            'public IPv6' => ['2001:db8::1', $defaults, false],
            'exact single IP' => ['203.0.113.5', ['203.0.113.5'], true],
            'other single IP' => ['203.0.113.6', ['203.0.113.5'], false],
            'odd prefix length match' => ['203.0.113.9', ['203.0.113.8/29'], true],
            'odd prefix length mismatch' => ['203.0.113.16', ['203.0.113.8/29'], false],
            'zero prefix matches all IPv4' => ['8.8.8.8', ['0.0.0.0/0'], true],
            'IPv4 does not match IPv6 range' => ['127.0.0.1', ['::1/128'], false],
            'empty IP' => ['', $defaults, false],
            'invalid IP' => ['not-an-ip', $defaults, false],
            'invalid range' => ['127.0.0.1', ['garbage'], false],
            'invalid prefix' => ['127.0.0.1', ['127.0.0.0/abc'], false],
            'too large prefix' => ['127.0.0.1', ['127.0.0.0/33'], false],
            'empty ranges' => ['127.0.0.1', [], false],
        ];
    }
}
