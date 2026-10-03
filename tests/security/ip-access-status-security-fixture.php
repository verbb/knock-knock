<?php

declare(strict_types=1);

namespace craft\web {
    class Request
    {
    }
}

namespace yii\validators {
    class IpValidator
    {
    }
}

namespace verbb\knockknock {
    class KnockKnock
    {
        public static mixed $plugin = null;
    }
}

namespace {
    use verbb\knockknock\helpers\IpHelper;

    class Craft
    {
        public static object $app;
    }

    function assertSame(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException($message . '\nExpected: ' . var_export($expected, true) . '\nActual: ' . var_export($actual, true));
        }
    }

    require dirname(__DIR__, 2) . '/src/helpers/IpHelper.php';

    assertSame(IpHelper::ACCESS_ALLOWED, IpHelper::getAccessStatus('203.0.113.10', ['203.0.113.10'], []), 'An allow rule must produce the allowed status.');
    assertSame(IpHelper::ACCESS_DENIED, IpHelper::getAccessStatus('203.0.113.10', [], ['203.0.113.10']), 'A deny rule must produce the denied status.');
    assertSame(IpHelper::ACCESS_ALLOWED, IpHelper::getAccessStatus('203.0.113.10', ['203.0.113.10'], ['203.0.113.10']), 'An allow rule must take precedence over a deny rule.');
    assertSame(IpHelper::ACCESS_NEUTRAL, IpHelper::getAccessStatus('203.0.113.10', ['198.51.100.0/24'], ['192.0.2.0/24']), 'An unmatched IP must produce the neutral status.');
    assertSame(IpHelper::ACCESS_DENIED, IpHelper::getAccessStatus('203.0.113.10', [], ['203.0.113.0/24']), 'Deny CIDR rules must use the existing matcher.');
    assertSame(true, IpHelper::ipInCidrList('203.0.113.10', ['0.0.0.0/0']), 'An IPv4 zero-prefix CIDR must match every IPv4 address.');
    assertSame(true, IpHelper::ipInCidrList('2001:db8::1', ['::/0']), 'An IPv6 zero-prefix CIDR must match every IPv6 address.');
    assertSame(false, IpHelper::ipInCidrList('203.0.113.10', ['::/0']), 'An IPv6 zero-prefix CIDR must not match an IPv4 address.');
    assertSame(false, IpHelper::ipInCidrList('2001:db8::1', ['0.0.0.0/0']), 'An IPv4 zero-prefix CIDR must not match an IPv6 address.');
    assertSame(true, IpHelper::ipInCidrList('203.0.113.10', ['203.0.113.10']), 'An exact IPv4 rule must continue to match itself.');
    assertSame(false, IpHelper::ipInCidrList('203.0.113.11', ['203.0.113.10']), 'An exact IPv4 rule must not match another address.');
    assertSame(true, IpHelper::ipInCidrList('203.0.113.10', ['203.0.113.10/32']), 'A full-width IPv4 CIDR must continue to match exactly.');
    assertSame(true, IpHelper::ipInCidrList('2001:db8::1', ['2001:db8::1/128']), 'A full-width IPv6 CIDR must continue to match exactly.');
    assertSame(true, IpHelper::ipInCidrList('203.0.113.10', ['203.0.113.10/']), 'An empty mask suffix must retain its existing exact-match behavior.');
    assertSame(false, IpHelper::ipInCidrList('198.51.100.23', ['203.0.113.7/0/24']), 'A malformed IPv4 rule must not use an ignored suffix to become universal.');
    assertSame(false, IpHelper::ipInCidrList('2001:db8::2', ['2001:db8::1/0/64']), 'A malformed IPv6 rule must not use an ignored suffix to become universal.');
    assertSame(IpHelper::ACCESS_ALLOWED, IpHelper::getAccessStatus('203.0.113.10', ['0.0.0.0/0'], ['0.0.0.0/0']), 'A universal allow rule must continue to take precedence over a universal deny rule.');
    assertSame(IpHelper::ACCESS_DENIED, IpHelper::getAccessStatus('203.0.113.10', [], ['0.0.0.0/0']), 'A universal deny rule must deny an ordinary address.');

    echo "Knock Knock IP access status security fixture passed.\n";
}
